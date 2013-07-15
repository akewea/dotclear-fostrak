<?php
//@@licence@@

class fostrakExtMedia
{

	public static function getURL($rs)
	{
		return $rs->core->blog->url.$rs->core->blog->settings->fostrak->fostrak_base_url.'/'.$rs->relname;
	}

	public static function getContentURL($rs, $size)
	{
		if(!empty($rs->media_thumb[$size])){
			return $rs->media_thumb[$size];
		}
		return $rs->file_url;
	}

	public static function getDate($rs,$format)
	{
		if ($format) {
			return dt::str($format,$rs->media_dt);
		} else {
			return dt::str($rs->core->blog->settings->system->date_format,$rs->media_dt);
		}
	}

	public static function getTS($rs)
	{
		return $rs->media_dt;
	}

	public static function getISO8601Date($rs)
	{
		return dt::iso8601($rs->getTS());
	}

	public static function getRFC822Date($rs)
	{
		return dt::rfc822($rs->getTS());
	}

	public static function getTime($rs,$format)
	{
		if (!$format) {
			$format = $rs->core->blog->settings->system->time_format;
		}

		return dt::str($format,$rs->media_dt);
	}

	public static function getAuthorCN($rs)
	{
		return $rs->media_user;
	}

	public static function getAuthorLink($rs)
	{
		$res = '%1$s';
		$url = $rs->user_url;
		if ($url) {
			$res = '<a href="%2$s">%1$s</a>';
		}

		return sprintf($res,html::escapeHTML($rs->getAuthorCN()),html::escapeHTML($url));
	}

	public static function getFeedID($rs)
	{
		return 'urn:md5:'.md5($rs->core->blog->uid.$rs->media_id);

		$url = parse_url($rs->core->blog->url);
		$date_part = date('Y-m-d',$rs->media_dt);

		return 'tag:'.$url['host'].','.$date_part.':'.$rs->media_id;
	}
}

?>