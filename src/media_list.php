<?php
//@@licence@@

if (!defined('DC_CONTEXT_ADMIN')) { return; }

require_once dirname(__FILE__).'/inc/lib.fostrak.pager.php';

/* Prepare filters
-------------------------------------------------------- */
//$status_combo = litraakUtils::getMilestoneStatusCombo($litraak);
//$sortby_combo = litraakUtils::getMilestoneSortByCombo();
//$order_combo = litraakUtils::getOrderCombo();

/* Get milestones
-------------------------------------------------------- */
//$status = isset($_GET['milestone_status']) ?	$_GET['milestone_status'] : '';
//$sortby = !empty($_GET['milestone_sortby']) ?	$_GET['milestone_sortby'] : 'milestone_dt';
//$order = !empty($_GET['milestone_order']) ?		$_GET['milestone_order'] : 'desc';

$show_filters = false;

$page = !empty($_GET['page']) ? (integer) $_GET['page'] : 1;
$nb_per_page =  30;

if (!empty($_GET['media_nb']) && (integer) $_GET['media_nb'] > 0) {
	if ($nb_per_page != $_GET['media_nb']) {
		$show_filters = true;
	}
	$nb_per_page = (integer) $_GET['media_nb'];
}

$params = array();
$params['limit'] = array((($page-1)*$nb_per_page),$nb_per_page);
//$params['no_content'] = true;
//$params['post_id'] = $project_id;

# - Status filter
//if ($status !== '' && in_array($status,$status_combo)) {
//	$params['milestone_status'] = $status;
//	$show_filters = true;
//}

# - Sortby and order filter
//if ($sortby !== '' && in_array($sortby,$sortby_combo)) {
//	if ($order !== '' && in_array($order,$order_combo)) {
//		$params['order'] = $sortby.' '.$order;
//	}
//	
//	if ($sortby != 'milestone_dt' || $order != 'desc') {
//		$show_filters = true;
//	}
//}

# Get medias
try {
	$medias = $fostrak->getStreamMedias($params);
	$counter = $fostrak->getStreamMedias($params,true);
	$media_list = new fostratAdminMediaList($core,$medias,$counter->f(0));
} catch (Exception $e) {
	$core->error->add($e->getMessage());
}

/* DISPLAY
------------------------------------------------------------------------------*/
$starting_script = fostrakPage::jsPageTabs('media-list');
	
if (!$show_filters) {
	$starting_script .= fostrakPage::jsLoad('js/filter-controls.js');
}

fostrakPage::open(__('Fostrak'), 
	$starting_script.
	fostrakPage::jsLoad('index.php?pf=fostrak/js/_milestones.js'));

$tabs = fostrakPage::getHomeTabs('media-list');
$blocks = explode('%s', $tabs, 2);
echo $blocks[0];

if (!$core->error->flag())
{
//	if (!$show_filters) {
//		echo '<p><a id="filter-control" class="form-control" href="#">'.
//		__('Filters').'</a></p>';
//	}
	
//	echo
//	'<form action="plugin.php" method="get" id="filters-form">'.
//	'<fieldset><legend>'.__('Filters').'</legend>'.
//	'<div class="two-cols">'.
//	
//	'<div class="col">'.
//	'<label>'.__('Status:').
//	form::combo('milestone_status',$status_combo,$status).'</label> '.
//	'</div>'.
//	
//	'<div class="col">'.
//	'<p><label for="sortby">'.__('Order by:').'</label>'.
//	form::combo('milestone_sortby',$sortby_combo,$sortby).' '.
//	form::combo('milestone_order',$order_combo,$order).'</p>'.
//	'<p><label class="classic">'.	form::field('milestone_nb',3,3,$nb_per_page).' '.
//	__('Milestones per page').'</label> '.
//	'<input type="submit" value="'.__('filter').'" /></p>'.
//	'</div>'.
//	
//	'</div>'.
//	'<br class="clear" />'. //Opera sucks
//	'</fieldset>'.
//	form::hidden(array('p'),'litraak').
//	form::hidden(array('projectid'),$project_id).
//	form::hidden(array('tab'),'milestones').
//	'</form>';
	
	# Show posts
	$media_list->display($page,$nb_per_page,'');
}

fostrakPage::helpBlock();
echo $blocks[1];
fostrakPage::close();

?>