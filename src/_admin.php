<?php
//@@licence@@

if (!defined('DC_RC_PATH')) { return; }

// require dirname(__FILE__).'/_widgets.php';

if($core->blog->settings->fostrak->fostrak_enabled){
	
	global $fostrak;
	$fostrak = new fostrak($core);
	
	// Enregistrement des behaviors pour l'admin.
	$core->addBehavior('adminDashboardFavs',			array('fostrakAdminBehaviors', 'dashboardFavs'));
	
	if(fostrakAdminBehaviors::isMediaTabEnabled()){
		$core->addBehavior('adminMediaItemFooter',			array('fostrakAdminBehaviors', 'adminMediaItemFooter'));
		$core->addBehavior('adminMediaItemMessage',			array('fostrakAdminBehaviors', 'adminMediaItemMessage'));
		$core->addBehavior('adminMediaItemBeforeDisplay',	array('fostrakAdminBehaviors', 'adminMediaItemBeforeDisplay'));
	}else{
		$core->addBehavior('adminMediaItemForm',			array('fostrakAdminBehaviors', 'adminMediaItemForm'));

		$popup = (integer) !empty($_GET['popup']);
		if(!$popup && (!empty($_POST['fostrak_publish']) || !empty($_POST['fostrak_remove']))){
			$id = !empty($_REQUEST['id']) ? (integer) $_REQUEST['id'] : '';
			fostrakAdminBehaviors::adminMediaItemActions($id);
		}
	}
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
			return; // On affiche pas la zone en popup.
		}
		
		if(!$file->media_image){
			return; // On affiche la zone que pour les images.
		}

		$page_url = 'media_item.php';
		$id = $file->media_id;
		
		$content = isset($_POST['fostrak_post_content']) ? $_POST['fostrak_post_content'] : '';
		$ispublished = false;
		$date_published = null;
		
		$post = $fostrak->getPost($id);		
		if($post){
			$content = $post->post_excerpt;
			$ispublished = true;
			$date_published = $post->post_dt;
		}

		// Formulaire
		echo
		'<form class="clear" action="'.html::escapeURL($page_url).'" method="post" enctype="multipart/form-data">'.
		'<fieldset id="fostrak"><legend>'.__('Fostrak Photo stream').'</legend>';
		
		// Messages
		self::adminMediaItemMessage($file);
		
		// Champs de saisie
		echo 
		'<p class="area"><label class="required" '.
		'for="fostrak_post_content">'.__('Description:').'</label> '.
		form::textarea('fostrak_post_content',50,2,html::escapeHTML($content)).
		'</p>';

		// Boutons
		if($ispublished){
			echo
			'<p><img src="images/check-on.png" /> '.
			__('Media published on').' '.dt::dt2str(__('%Y-%m-%d %H:%M'),$date_published).
			' | '.$post->nb_comment.' commentaire(s)'.
			' | <a href="'.$fostrak->getPublicUrl().$file->relname.'" target="blank">'.__('View on site').'</a></p>'.
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
	
	public static function adminMediaItemFooter($file) {
		
		global $core;
		global $fostrak;
		
		$popup = (integer) !empty($_GET['popup']);
		if($popup){
			return; // On affiche pas la zone en popup.
		}
		
		if(!$file->media_image){
			return; // On affiche la zone que pour les images.
		}
		
		$page_url = 'media_item.php';
		$id = $file->media_id;
		
		$content = isset($_POST['fostrak_post_content']) ? $_POST['fostrak_post_content'] : '';
		$ispublished = false;
		$date_published = null;
		
		$post = $fostrak->getPost($id);
		if($post){
			$content = $post->post_excerpt;
			$ispublished = true;
			$date_published = $post->post_dt;
		}
		
		echo
		'<div class="multi-part" title="'.__('Fostrak Photo stream').'" id="fostrak-media-details-tab">';
		
		// Formulaire
		echo
		'<form class="clear" action="'.html::escapeURL($page_url).'" method="post" enctype="multipart/form-data">';
		
		// Champs de saisie
		echo
		'<p class="area"><label class="required" '.
		'for="fostrak_post_content">'.__('Description:').'</label> '.
		form::textarea('fostrak_post_content',50,2,html::escapeHTML($content)).
		'</p>';
		
		// Boutons
		if($ispublished){
			echo
			'<p><img src="images/check-on.png" /> '.
			__('Media published on').' '.dt::dt2str(__('%Y-%m-%d %H:%M'),$date_published).
			' | '.$post->nb_comment.' commentaire(s)'.
			' | <a href="'.$fostrak->getPublicUrl().$file->relname.'" target="blank">'.__('View on site').'</a></p>'.
			'<p><input type="submit" name="fostrak_publish" value="'.__('Republish').'" />'.
			'<input type="submit" name="fostrak_remove" value="'.__('Remove').'" />';
		}else{
			echo
			'<p><input type="submit" name="fostrak_publish" value="'.__('Publish').'" />';
		}
		
		echo
		form::hidden(array('id'),$id).
		$core->formNonce().'</p>'.
		'</form>';
		
		echo '</div>';
	}
	
	public static function adminMediaItemMessage($file) {
		// Messages
		if (!empty($_GET['fostrakpublishok'])) {
			dcPage::message(__('Photo has been successfully published on Fostrak.'));
		}
		
		if (!empty($_GET['fostrakremoveok'])) {
			dcPage::message(__('Photo has been successfully unpublished from Fostrak.'));
		}
	}
	
	public static function adminMediaItemBeforeDisplay($file) {
		return self::adminMediaItemActions($file->media_id);
	}
	
	public static function adminMediaItemActions($id) {
		global $core, $fostrak;
		$popup = (integer) !empty($_GET['popup']);
	
		if(!$popup && (!empty($_POST['fostrak_publish']) || !empty($_POST['fostrak_remove']))){
	
			$core->media = new dcMedia($core);
			$page_url = 'media_item.php';
	
			if(!empty($_POST['fostrak_publish']) && !empty($id)){
				try {
	
					$cur = $core->con->openCursor($core->prefix.'post');
					$cur->post_excerpt = $_POST['fostrak_post_content'];
	
					$fostrak->addOrUpdPost($id, $cur);
	
					return http::redirect($page_url.'?id='.$id.
							(self::isMediaTabEnabled() ? '&tab=fostrak-media-details-tab' : '').
							'&fostrakpublishok=1#fostrak');
				} catch (Exception $e) {
					$core->error->add($e->getMessage());
					$err = true;
				}
			}
	
			if(!empty($_POST['fostrak_remove']) && !empty($id)){
				try {
					$fostrak->delStreamMedia($id);
					return http::redirect($page_url.'?id='.$id.
							(self::isMediaTabEnabled() ? '&tab=fostrak-media-details-tab' : '').
							'&fostrakremoveok=1#fostrak');
				} catch (Exception $e) {
					$core->error->add($e->getMessage());
					$err = true;
				}
			}
		}
	}
	
	public static function isMediaTabEnabled() {
		return true; // TODO tester en fonction de la version
	}
}
?>