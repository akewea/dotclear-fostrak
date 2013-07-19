<?php
//@@licence@@

class fostrakPage extends dcPage
{
	
	const SELECTED_TAB = '<div class="multi-part" id="%s" title="%s">%s</div>';
	const LINK_TAB = '<a href="%s" class="multi-part">%s</a>';
		
	public static function open($title='', $head='')
	{
		echo
		'<html>'."\n".
		"<head>\n".
		'   <title>'.$title.'</title>'."\n".
		$head.
		'	<style type="text/css">@import "index.php?pf=fostrak/admin.css";</style>'.
		"</head>\n".
		'<body>'."\n";
	}
	
	public static function close()
	{		
		global $core;
		echo
		'<div id="fostrak-footer"><p>'.
		'<abbr title="Fostrak v'.$core->getVersion('fostrak').'">Fostrak'.
		'<img src="index.php?pf=fostrak/img/icon-24.png" alt="Fostrak" class="fostrak-logo" /></abbr></p></div>'."\n".
		
		'</body></html>';
	}
	
	public static function getHomeTabs($tab='media-list')
	{
		$tabs = array();
		
		$tabs['media-list'] = 	array( 'name' => __('Media Stream'), 'url' => 'plugin.php?p=fostrak&amp;tab=media-list');
		$tabs['config'] = 		array( 'name' => __('Configuration'), 'url' => 'plugin.php?p=fostrak&amp;tab=config');
		
		return self::getTabs($tabs, $tab);
	}
	
	private static function getTabs($tabs, $current)
	{
		$res = '';
		foreach($tabs as $id => $tab){
			if($current == $id)
			{
				$res .= sprintf(self::SELECTED_TAB, $id, $tab['name'], '%s');
			}else{
				$res .= sprintf(self::LINK_TAB, $tab['url'], $tab['name']);
			}
		}
		
		return $res;
	}
	
	public static function helpBlock($name='')
	{		
		if(empty($name)){
			return parent::helpBlock('fostrak');
		}else{
			return parent::helpBlock('fostrak-'.$name);
		}
	}
	
}

?>