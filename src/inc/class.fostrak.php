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
			$strReq = 'SELECT count(S.media_id) ';
		}
		else
		{
			$strReq =
			'SELECT S.media_id, S.media_dt, '.
			'media_title, media_file, media_path, media_private, M.user_id, '.
			'media_creadt, media_upddt, media_meta, '.
			'U.user_name, U.user_firstname, U.user_displayname, U.user_url ';
		}

		$strReq .=
		'FROM '.$this->prefix.'fostrak_stream S '.
		'INNER JOIN '.$this->prefix.'media M ON M.media_id = S.media_id '.
		'INNER JOIN '.$this->prefix.'user U ON U.user_id = M.user_id ';

		if (!empty($params['from'])) {
			$strReq .= $params['from'].' ';
		}

		$strReq .=
		"WHERE M.media_path = '".$this->core->blog->settings->system->public_path."' ";

		if (!$this->core->auth->check('contentadmin',$this->core->blog->id)) {
			$strReq .= 'AND media_private <> 1 ';
		}

		if (!empty($params['media_id'])) {
			$strReq .= 'AND S.media_id = '.(integer) $params['media_id'].' ';
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
				$strReq .= 'ORDER BY S.media_dt DESC ';
			}
		}

		if (!$count_only && !empty($params['limit'])) {
			$strReq .= $this->con->limit($params['limit']);
		}

		$rs = $this->con->select($strReq);

		$f_res = array();
		while ($rs->fetch())
		{
			$tmp = $this->fileRecord($rs);
			$tmp->media_dtdb = $rs->media_dt;
			$tmp->user_url = $rs->user_url;
			if($tmp != null){
				$f_res[] = new ArrayObject($tmp);
			}
		}
		
		//print_r($f_res[0]);
		$f_res = staticRecord::newFromArray($f_res);
		$f_res->core = $this->core;
		$f_res->extend('fostrakExtMedia');
		
		//print_r($f_res);

		return $f_res;
	}

	public function addOrUpdStreamMedia($id,$cur)
	{
		$id = (integer) $id;

		if (empty($id)) {
			throw new Exception(__('No such media ID'));
		}

		$offset = dt::getTimeOffset($this->core->blog->settings->system->blog_timezone);
		$cur->media_dt = date('Y-m-d H:i:s',time() + $offset);

		$rs = $this->getStreamMedias(array('media_id' => $id));

		if ($rs->isEmpty()) {
			$cur->media_id = (integer) $id;
			$this->getStreamMediaCursor($cur);
			$cur->insert();
		}else{
			$this->getStreamMediaCursor($cur);
			$cur->update('WHERE media_id = '.$id.' ');
		}
	}

	public function delStreamMedia($id)
	{
		/*if (!$this->core->auth->check('delete,contentadmin',$this->id)) {
			throw new Exception(__('You are not allowed to delete comments'));
			}*/

		$id = (integer) $id;

		if (empty($id)) {
			throw new Exception(__('No such stream media ID'));
		}

		$strReq = 'DELETE FROM '.$this->prefix.'fostrak_stream '.
				'WHERE media_id = '.$id.' ';

		$this->con->execute($strReq);
	}

	private function getStreamMediaCursor($cur)
	{
		if ($cur->media_dt !== null && $cur->media_dt == '') {
			throw new Exception(__('Empty publishing date'));
		}
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
		$params['order'] = 'S.media_dt '.$order.', M.media_id '.$order;
		$params['sql'] =
		'AND ( '.
		"	(S.media_dt = '".$this->con->escape($dt)."' AND M.media_id ".$sign." ".$media_id.") ".
		"	OR S.media_dt ".$sign." '".$this->con->escape($dt)."' ".
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