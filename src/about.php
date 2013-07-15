<?php
//@@licence@@

if (!defined('DC_CONTEXT_ADMIN')) { return; }

/* DISPLAY
------------------------------------------------------------------------------*/
$starting_script = fostrakPage::jsPageTabs('about');
	
if (!$show_filters) {
	$starting_script .= fostrakPage::jsLoad('js/filter-controls.js');
}

fostrakPage::open(__('Fostrak'), 
	$starting_script);

$tabs = fostrakPage::getHomeTabs('about');
$blocks = explode('%s', $tabs, 2);
echo $blocks[0];

// TODO About

echo $blocks[1];
fostrakPage::close();

?>