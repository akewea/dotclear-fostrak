<?php
//@@licence@@

if (!defined('DC_CONTEXT_ADMIN')) { return; }
 
$m_version = $core->plugins->moduleInfo('fostrak','version');

$i_version = $core->getVersion('fostrak');

if (version_compare($i_version,$m_version,'>=')) {
	return;
}
 
# Création des setting (s'ils existent, ils ne seront pas écrasés)
$settings = new dcSettings($core,null);
$settings->addNameSpace('fostrak');
$settings->fostrak->put('fostrak_base_url','photos','string','Fostrak base URL',false,true);
$settings->fostrak->put('fostrak_enabled',false,'boolean','Enable Fostrak',false,true);

$core->setVersion('fostrak',$m_version);
?>