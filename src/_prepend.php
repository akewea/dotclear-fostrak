<?php
//@@licence@@

if (!defined('DC_RC_PATH')) { return; }

# Chargement des classes
$GLOBALS['__autoload']['fostrak'] = dirname(__FILE__).'/inc/class.fostrak.php';
$GLOBALS['__autoload']['fostrakExtMedia'] = dirname(__FILE__).'/inc/lib.fostrak.extensions.php';

# Nouveau type de post
$core->setPostType('fostrak', 'plugin.php?p=fostrak&projectid=%s', $core->blog->settings->fostrak->fostrak_base_url.'/(.*)$');

//# Nouveau type de post
//$core->setPostType('litraak', 'plugin.php?p=litraak&projectid=%s', $core->blog->settings->litraak->litraak_basename_url.'/%s/');
//
//# Services
//$core->rest->addFunction('getTicketById',array('litraakRestMethods','getTicketById'));
//$core->rest->addFunction('getMilestoneById',array('litraakRestMethods','getMilestoneById'));

?>