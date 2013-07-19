<?php
//@@ licence@@

class fostrak extends dcMedia
{

	protected $core;
	public $con;
	public $prefix;
	public $media;

	public function __construct($core)
	{
		parent::__construct($core);
		$this->con =& $core->con;
		$this->prefix = $core->prefix;
		$this->core =& $core;
		$this->media = $core->media;
		if(!$this->media) {
			$this->media = new dcMedia($core);
		}
	}

	public function getPublicURL($escaped=true){
		$url = $this->core->blog->url.$this->core->blog->settings->fostrak->fostrak_base_url.'/';
		return $escaped ? html::escapeURL($url) : $url;
	}

	// ### STREAM ##########################################################

	public function getStreamMedias($params=array(),$count_only=false)
	{
		
		if ($count_only)
		{
			$strReq = 'SELECT count(M.media_id) ';
		}
		else
		{
			$strReq =
			'SELECT M.media_id, P.post_dt, P.post_excerpt, P.post_id, P.nb_comment, M.media_dt, '.
			'media_title, media_file, media_path, media_private, M.user_id, '.
			'media_creadt, media_upddt, media_meta, '.
			'U.user_name, U.user_firstname, U.user_displayname, U.user_url ';
		}

		$strReq .=
		'FROM '.$this->prefix.'post P '.
		'INNER JOIN '.$this->prefix.'post_media PM ON PM.post_id = P.post_id and link_type = \'fostrak\' '.
		'INNER JOIN '.$this->prefix.'media M ON M.media_id = PM.media_id '.
		'INNER JOIN '.$this->prefix.'user U ON U.user_id = M.user_id ';

		if (!empty($params['from'])) {
			$strReq .= $params['from'].' ';
		}
		
		$strReq .=
		"WHERE M.media_path = '".$this->core->blog->settings->system->public_path."' and P.post_type = 'fostrak'";

		if (!$this->core->auth->check('contentadmin',$this->core->blog->id)) {
			$strReq .= 'AND media_private <> 1 ';
		}

		if (!empty($params['media_id'])) {
			$strReq .= 'AND M.media_id = '.(integer) $params['media_id'].' ';
		}

		if (!empty($params['user_id'])) {
			$strReq .= 'AND M.user_id = '.(integer) $params['user_id'].' ';
		}

		if (!empty($params['relname'])) {
			$strReq .= "AND M.media_file = '".$params['relname']."' ";
		}

		if (!empty($params['sql'])) {
			$strReq .= $params['sql'].' ';
		}

		if (!$count_only)
		{
			if (!empty($params['order'])) {
				$strReq .= 'ORDER BY '.$this->con->escape($params['order']).' ';
			} else {
				$strReq .= 'ORDER BY P.post_dt DESC ';
			}
		}

		if (!$count_only && !empty($params['limit'])) {
			$strReq .= $this->con->limit($params['limit']);
		}

		$rs = $this->con->select($strReq);
		
		if($count_only){
			return $rs;
		}

		$f_res = array();
		while ($rs->fetch())
		{
			$tmp = $this->fileRecord($rs);
			$tmp->media_dtdb = $rs->post_dt;
			$tmp->user_url = $rs->user_url;
			$tmp->user_id = $rs->user_id;
			$tmp->post_excerpt = $rs->post_excerpt;
			$tmp->post_id = $rs->post_id;
			$tmp->nb_comment = $rs->nb_comment;
			if($tmp != null){
				$f_res[] = new ArrayObject($tmp);
			}
		}

		$f_res = staticRecord::newFromArray($f_res);
		$f_res->core = $this->core;
		$f_res->extend('fostrakExtMedia');

		return $f_res;
	}
	
	public function addOrUpdPost($media_id,$cur)
	{
		$id = (integer) $media_id;
	
		if (empty($id)) {
			throw new Exception(__('No such media ID'));
		}
	
		$offset = dt::getTimeOffset($this->core->blog->settings->system->blog_timezone);
		$cur->post_dt = date('Y-m-d H:i:s',time() + $offset);
	
		$rs = $this->getPost($media_id);
	
		if ($rs && !$rs->isEmpty()) {
			$post_id = $rs->post_id;
			
			$cur->post_title =  $rs->post_title;
			$cur->post_content =  $rs->post_content;
				
			$this->core->blog->updPost($post_id, $cur);
		}else{		
			$media = $this->media->getFile($media_id);
			
			$cur->post_title = $media->media_title;
			$cur->post_content =  $media->media_title;
			$cur->post_url = $media->relname;
			$cur->post_type = 'fostrak';
			$cur->post_status = 1;
			$cur->post_open_comment = $this->core->blog->settings->system->allow_comments;
			$cur->user_id = $this->core->auth->userID();
			
			$post_id = $this->core->blog->addPost($cur);			
			$this->core->media->postmedia->addPostMedia($post_id,$id,'fostrak');
		}
	}
	
	public function getPost($media_id){
		
		$rs_postmedia = $this->core->media->postmedia->getPostMedia(array(
				'media_id' => $media_id,
				'link_type' => 'fostrak',
				'from' => ' INNER JOIN '.$this->prefix."post P ON P.post_id = PM.post_id and P.post_type = 'fostrak' "
		));
	
		if(!$rs_postmedia->isEmpty()){
			$rs = $this->core->blog->getPosts(array(
					'post_id' => $rs_postmedia->post_id,
					'post_type' => 'fostrak'
			));
	
			if(!$rs->isEmpty()){
				return $rs;
			}
		}
	
		return null;
	}

	public function delStreamMedia($media_id)
	{
		/*if (!$this->core->auth->check('delete,contentadmin',$this->id)) {
			throw new Exception(__('You are not allowed to delete comments'));
			}*/

		$media_id = (integer) $media_id;

		if (empty($media_id)) {
			throw new Exception(__('No such stream media ID'));
		}
		
		$post = $this->getPost($media_id);

		$this->core->blog->delPost($post->post_id);
	}

	public function getNextMedia($media,$dir)
	{
		$dt = $media->media_dtdb;
		$media_id = (integer) $media->media_id;
		
		if($dir > 0) {
			$sign = '>';
			$order = 'ASC';
		}
		else {
			$sign = '<';
			$order = 'DESC';
		}

		$params['limit'] = 1;
		$params['order'] = 'P.post_dt '.$order.', M.media_id '.$order;
		$params['sql'] =
		'AND ( '.
		"	(P.post_dt = '".$this->con->escape($dt)."' AND M.media_id ".$sign." ".$media_id.") ".
		"	OR P.post_dt ".$sign." '".$this->con->escape($dt)."' ".
		') ';

		$rs = $this->getStreamMedias($params);

		if ($rs->isEmpty()) {
			return null;
		}

		return $rs;
	}

	public function imageURL($img,$size)
	{
		$root = $this->core->blog->public_path;

		# Get base name and extension
		$info = path::info($img);
		$base = $info['base'];

		if (preg_match('/^\.(.+)_(sq|t|s|m)$/',$base,$m)) {
			$base = $m[1];
		}

		$res = false;
		if ($size != 'o' && file_exists($root.'/'.$info['dirname'].'/.'.$base.'_'.$size.'.jpg'))
		{
			$res = '.'.$base.'_'.$size.'.jpg';
		}
		else
		{
			$f = $root.'/'.$info['dirname'].'/'.$base;
			if (file_exists($f.'.'.$info['extension'])) {
				$res = $base.'.'.$info['extension'];
			} elseif (file_exists($f.'.jpg')) {
				$res = $base.'.jpg';
			} elseif (file_exists($f.'.png')) {
				$res = $base.'.png';
			} elseif (file_exists($f.'.gif')) {
				$res = $base.'.gif';
			}
		}

		if ($res) {
			return $res;
		}
		return false;
	}
}

?>