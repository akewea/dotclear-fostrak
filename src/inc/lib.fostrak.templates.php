<?php
//@@licence@@

class fostrakTemplates
{

	public static function MediaRootURL($attr)
	{
		$f = $GLOBALS['core']->tpl->getFilters($attr);
		
		$res = '<?php ';
		$res .= 'global $fostrak;'."\n";
		$res .= 'if(!$fostrak) $fostrak = new fostrak($core);'."\n";
		$res .= ' echo '.sprintf($f,'$fostrak->getPublicURL()').'; ?>';
		return $res;
	}

	public static function MediaURL($attr)
	{
		$f = $GLOBALS['core']->tpl->getFilters($attr);
		return '<?php echo '.sprintf($f,'$_ctx->fostrak->getURL()').'; ?>';
	}

	public static function MediaContentURL($attr)
	{
		$size = !empty($attr['size']) ? $attr['size'] : '';
		$f = $GLOBALS['core']->tpl->getFilters($attr);
		return '<?php echo '.sprintf($f,'$_ctx->fostrak->getContentURL("'.$size.'")').'; ?>';
	}

	public static function MediaTitle($attr)
	{
		$f = $GLOBALS['core']->tpl->getFilters($attr);
		return '<?php echo '.sprintf($f,'$_ctx->fostrak->media_title').'; ?>';
	}

	public static function MediaDate($attr)
	{
		$format = '';
		if (!empty($attr['format'])) {
			$format = addslashes($attr['format']);
		}

		$iso8601 = !empty($attr['iso8601']);
		$rfc822 = !empty($attr['rfc822']);

		$f = $GLOBALS['core']->tpl->getFilters($attr);

		if ($rfc822) {
			return '<?php echo '.sprintf($f,"\$_ctx->fostrak->getRFC822Date()").'; ?>';
		} elseif ($iso8601) {
			return '<?php echo '.sprintf($f,"\$_ctx->fostrak->getISO8601Date()").'; ?>';
		} else {
			return '<?php echo '.sprintf($f,"\$_ctx->fostrak->getDate('".$format."')").'; ?>';
		}
	}

	public static function MediaTime($attr)
	{
		$format = '';
		if (!empty($attr['format'])) {
			$format = addslashes($attr['format']);
		}

		$f = $GLOBALS['core']->tpl->getFilters($attr);
		return '<?php echo '.sprintf($f,"\$_ctx->fostrak->getTime('".$format."')").'; ?>';
	}

	public static function MediaAuthor($attr)
	{
		$f = $GLOBALS['core']->tpl->getFilters($attr);
		return '<?php echo '.sprintf($f,'$_ctx->fostrak->getAuthorCN()').'; ?>';
	}

	public static function MediaAuthorLink($attr)
	{
		$f = $GLOBALS['core']->tpl->getFilters($attr);
		return '<?php echo '.sprintf($f,'$_ctx->fostrak->getAuthorLink()').'; ?>';
	}

	public static function MediaFeedID($attr)
	{
		$f = $GLOBALS['core']->tpl->getFilters($attr);
		return '<?php echo '.sprintf($f,'$_ctx->fostrak->getFeedID()').'; ?>';
	}

	public static function MediaFeedURL($attr)
	{
		$type = !empty($attr['type']) ? $attr['type'] : 'atom';

		if (!preg_match('#^(rss2|atom)$#',$type)) {
			$type = 'atom';
		}

		$f = $GLOBALS['core']->tpl->getFilters($attr);
		return '<?php echo '.sprintf($f,'$core->blog->url.$core->blog->settings->fostrak->fostrak_base_url."/feed/'.$type.'"').'; ?>';
	}

	public static function MediaMimeType($attr)
	{
		$f = $GLOBALS['core']->tpl->getFilters($attr);
		return '<?php echo '.sprintf($f,'$_ctx->fostrak->type').'; ?>';
	}

	public static function MediaSize($attr)
	{
		$f = $GLOBALS['core']->tpl->getFilters($attr);
		if (!empty($attr['full'])) {
			return '<?php echo '.sprintf($f,'$_ctx->fostrak->size').'; ?>';
		}
		return '<?php echo '.sprintf($f,'files::size($_ctx->fostrak->size)').'; ?>';
	}

	public static function MediaLang($attr)
	{
		$f = $GLOBALS['core']->tpl->getFilters($attr);
		return
		'<?php echo '.sprintf($f,'$core->blog->settings->system->lang').'; ?>';
	}

	public static function MediaID($attr)
	{
		$f = $GLOBALS['core']->tpl->getFilters($attr);
		return '<?php echo '.sprintf($f,'$_ctx->fostrak->media_id').'; ?>';
	}

	public static function Medias($attr,$content)
	{
		$lastn = -1;
		if (isset($attr['lastn'])) {
			$lastn = abs((integer) $attr['lastn'])+0;
		}

		$p = 'if (!isset($_page_number)) { $_page_number = 1; }'."\n";

		if ($lastn != 0) {
			if ($lastn > 0) {
				$p .= "\$params['limit'] = ".$lastn.";\n";
			} else {
				$p .= "\$params['limit'] = \$_ctx->nb_entry_per_page;\n";
			}

			if (!isset($attr['ignore_pagination']) || $attr['ignore_pagination'] == "0") {
				$p .= "\$params['limit'] = array(((\$_page_number-1)*\$params['limit']),\$params['limit']);\n";
			} else {
				$p .= "\$params['limit'] = array(0, \$params['limit']);\n";
			}
		}

		if (isset($attr['author'])) {
			$p .= "\$params['user_id'] = '".addslashes($attr['author'])."';\n";
		}

		if (isset($attr['age'])) {
			$age = $this->getAge($attr);
			$p .= !empty($age) ? "@\$params['sql'] .= ' AND S.media_dt > \'".$age."\'';\n" : '';
		}

		$res = "<?php\n";
		$res .= $p;
		$res .= '$_ctx->fostrak_params = $params;'."\n";
		$res .= 'global $fostrak;'."\n";
		$res .= 'if(!$fostrak) $fostrak = new fostrak($core);'."\n";
		$res .= '$_ctx->fostrak = $fostrak->getStreamMedias($params); unset($params);'."\n";
		$res .= "?>\n";

		$res .=
		'<?php while ($_ctx->fostrak->fetch()) : ?>'.$content.'<?php endwhile; '.
		'$_ctx->fostrak = null; $_ctx->fostrak_params = null; ?>';

		return $res;
	}
	
	public static function MediasHeader($attr,$content)
	{
		return
		"<?php if (\$_ctx->fostrak->isStart()) : ?>".
		$content.
		"<?php endif; ?>";
	}
	
	public static function MediasFooter($attr,$content)
	{
		return
		"<?php if (\$_ctx->fostrak->isEnd()) : ?>".
		$content.
		"<?php endif; ?>";
	}

	public static function Next($attr,$content)
	{
		return self::NextOrPrevious($attr,$content, 1);
	}

	public static function Previous($attr,$content)
	{
		return self::NextOrPrevious($attr,$content, 0);
	}

	private static function NextOrPrevious($attr,$content,$dir)
	{
		return
		'<?php $next_media = $fostrak->getNextMedia($_ctx->fostrak,'.$dir.'); ?>'."\n".
		'<?php if ($next_media !== null) : ?>'.
			'<?php $old_media = $_ctx->fostrak; ?>'.
			'<?php $_ctx->fostrak = $next_media; unset($next_media);'."\n".
			'while ($_ctx->fostrak->fetch()) : ?>'.
		$content.
			'<?php endwhile; $_ctx->fostrak = $old_media; unset($old_media); ?>'.
		"<?php endif; ?>\n";
	}

}

?>