<?php
//@@licence@@

if (!defined('DC_CONTEXT_ADMIN')) { return; }

require_once dirname(__FILE__).'/inc/lib.fostrak.page.php';

// TODO Redirection vers la bonne page en fonction des paramètres
$fostrak_enabled = $core->blog->settings->fostrak->fostrak_enabled;

if(isset($_REQUEST['tab']) && $_REQUEST['tab'] == 'about'){
	include dirname(__FILE__).'/about.php';
}else if(isset($_REQUEST['tab']) && $_REQUEST['tab'] == 'config'){
	include dirname(__FILE__).'/config.php';
}else if($fostrak_enabled){
	include dirname(__FILE__).'/media_list.php';
}else {
	include dirname(__FILE__).'/config.php';
}

?>