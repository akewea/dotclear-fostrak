<?php
//@@licence@@

if (!defined('DC_RC_PATH')) { return; }

// require dirname(__FILE__).'/_widgets.php';

if($core->blog->settings->fostrak->fostrak_enabled){
	// Enregistrement des behaviors pour l'admin.
	$core->addBehavior('adminMediaItemForm',	array('fostrakAdminBehaviors', 'adminMediaItemForm'));
	$core->addBehavior('adminDashboardFavs',	array('fostrakAdminBehaviors', 'dashboardFavs'));
}

$_menu['Plugins']->addItem(
	# nom du lien (en anglais)
	__('Fostrak'),
	# URL de base de la page d'administration
	'plugin.php?p=fostrak',
	# URL de l'image utilisée comme icône
	'index.php?pf=fostrak/img/icon-16.png',
	# expression régulière de l'URL de la page d'administration
	preg_match('/plugin.php\?p=fostrak(&.*)?$/',
		$_SERVER['REQUEST_URI']),
	# persmissions nécessaires pour afficher le lien
	$core->auth->check('usage,contentadmin',$core->blog->id));
	
//print_r($_menu);
	
global $fostrak;
$fostrak = new fostrak($core);

// TODO Ca serait bien de pouvoir mettre tout ca dans un Behavior...
$popup = (integer) !empty($_GET['popup']);

if(!$popup && (!empty($_POST['fostrak_publish']) || !empty($_POST['fostrak_remove']))){

	$page_url = 'media_item.php';
	$id = !empty($_REQUEST['id']) ? (integer) $_REQUEST['id'] : '';

	if(!empty($_POST['fostrak_publish']) && !empty($id)){
		try {
				
			$cur = $core->con->openCursor($core->prefix.'fostrak_stream');
				
			$offset = dt::getTimeOffset($core->blog->settings->system->blog_timezone);
			$cur->media_dt = date('Y-m-d H:i:s',time() + $offset);
				
			$fostrak->addOrUpdStreamMedia($id, $cur);
				
			return http::redirect($page_url.'?id='.$id.'#fostrak');
		} catch (Exception $e) {
			$core->error->add($e->getMessage());
			$err = true;
		}
	}

	if(!empty($_POST['fostrak_remove']) && !empty($id)){
		try {
			$fostrak->delStreamMedia($id);
			return http::redirect($page_url.'?id='.$id.'#fostrak');
		} catch (Exception $e) {
			$core->error->add($e->getMessage());
			$err = true;
		}
	}
}

/**
 * Classe pour la gestion de Behaviors de l'administation.
 *
 * @author akewea
 *
 */
class fostrakAdminBehaviors
{

	public static function dashboardFavs($core,$favs)
    {
        $favs['fostrak'] = new ArrayObject(array(
            'fostrak',
            __('Fostrak'),
            'plugin.php?p=fostrak',
            'index.php?pf=fostrak/img/icon-16.png',
            'index.php?pf=fostrak/img/icon-64.png',
            'usage,contentadmin',
            null,
            null));
    }
    
	/**
	 * Formulaire de publication dans le Flux depuis l'écran de détail d'un média.
	 * 
	 * @param $file
	 */
	public static function adminMediaItemForm($file)
	{
		global $core;
		global $fostrak;

		$popup = (integer) !empty($_GET['popup']);
		if($popup){
			return;
		}

		$page_url = 'media_item.php';
		$id = $file->media_id;

		$rs = $fostrak->getStreamMedias(array('media_id' => $id));

		echo
		'<form class="clear" action="'.html::escapeURL($page_url).'" method="post" enctype="multipart/form-data">'.
		'<fieldset id="fostrak"><legend>'.__('Fostrak Photo stream').'</legend>';

		if(!$rs->isEmpty()){
			echo
			'<p><img src="images/check-on.png" /> '.
			__('Media published on').' '.dt::dt2str(__('%Y-%m-%d %H:%M'),$rs->media_dtdb).'</p>'.
			'<p><input type="submit" name="fostrak_publish" value="'.__('Republish').'" />'.
			'<input type="submit" name="fostrak_remove" value="'.__('Remove').'" />';
		}else{
			echo
			'<p><input type="submit" name="fostrak_publish" value="'.__('Publish').'" />';
		}

		echo
		form::hidden(array('id'),$id).
		$core->formNonce().'</p>'.
		'</fieldset></form>';
	}
	
}
?>