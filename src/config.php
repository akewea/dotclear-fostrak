<?php
//@@licence@@

if (!defined('DC_CONTEXT_ADMIN')) { return; }

if(!empty($_POST['upd']) || !empty($_POST['add'])){
	try {
		$core->blog->settings->addNameSpace('fostrak');
		$core->blog->settings->fostrak->put('fostrak_enabled',!empty($_POST['fostrak_enabled']),'boolean');
		$core->blog->settings->fostrak->put('fostrak_base_url',$_POST['fostrak_base_url']);
		
		return http::redirect('plugin.php?p=fostrak&tab=config');
	} catch (Exception $e) {
		$core->error->add($e->getMessage());
		$err = true;
	}
}

/* DISPLAY
------------------------------------------------------------------------------*/
$starting_script = fostrakPage::jsPageTabs('config');
	
if (!$show_filters) {
	$starting_script .= fostrakPage::jsLoad('js/filter-controls.js');
}

fostrakPage::open(__('Fostrak'), 
	$starting_script);

$tabs = fostrakPage::getHomeTabs('config');
$blocks = explode('%s', $tabs, 2);
echo $blocks[0];

// TODO About

echo
	'<form action="plugin.php?p=fostrak&amp;tab=config" method="post" id="config-form">'.
	
	'<fieldset><legend>Enable</legend>'.
	'<div class="two-cols">'.
	'<div class="col">'.

	'<p><label class="classic">'.
	form::checkbox('fostrak_enabled','1',$core->blog->settings->fostrak->fostrak_enabled).
	__('Enable Fostrak photo stream page').'</label></p>'.

	'</div>'.
	'<div class="col">'.
	
	'<p><label class="classic">'.__('Fostrak base URL').' '.
	form::field('fostrak_base_url', 10, 256, $core->blog->settings->fostrak->fostrak_base_url).
	'</label></p>'.

	'</div>'.
	'</div>'.

	'</fieldset>'.
	
	'<p>'.$core->formNonce().
	'<input type="submit" name="upd" value="'.__('save').'" /> '.
	'</p>'.
	'</form>';

fostrakPage::helpBlock('config');
echo $blocks[1];
fostrakPage::close();

?>