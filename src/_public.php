<?php
//@@licence@@

if (!defined('DC_RC_PATH')) { return; }

//require dirname(__FILE__).'/inc/lib.twittak.utils.php';
require dirname(__FILE__).'/inc/lib.fostrak.templates.php';

// Pour utilisation avec la balise "tpl:Widget"
//require dirname(__FILE__).'/_widgets.php';

if($core->blog->settings->fostrak->fostrak_enabled){
	$core->url->register('FostrakFeed','fostrak-feed','^'.$core->blog->settings->fostrak->fostrak_base_url.'/feed/(.*)$',array('fostrakPublic','feed'));
	$core->url->register('Fostrak','fostrak','^'.$core->blog->settings->fostrak->fostrak_base_url.'/(.*)$',array('fostrakPublic','photo'));
}

// Enregistrement des nouvelles balises de template
//$core->tpl->addValue('TwitterBaseURL',array('twittakTemplates','TwitterBaseURL'));
//$core->tpl->addBlock('Tweets',array('twittakTemplates','Tweets'));

$core->tpl->addValue('FostrakMediaRootURL',			array('fostrakTemplates','MediaRootURL'));
$core->tpl->addValue('FostrakMediaURL',			array('fostrakTemplates','MediaURL'));
$core->tpl->addValue('FostrakMediaContentURL',	array('fostrakTemplates','MediaContentURL'));
$core->tpl->addValue('FostrakMediaDescription',	array('fostrakTemplates','MediaDescription'));
$core->tpl->addValue('FostrakMediaTitle',		array('fostrakTemplates','MediaTitle'));
$core->tpl->addValue('FostrakMediaDate',		array('fostrakTemplates','MediaDate'));
$core->tpl->addValue('FostrakMediaAuthor',		array('fostrakTemplates','MediaAuthor'));
$core->tpl->addValue('FostrakMediaFeedID',		array('fostrakTemplates','MediaFeedID'));
$core->tpl->addValue('FostrakMediaSize',		array('fostrakTemplates','MediaSize'));
$core->tpl->addValue('FostrakMediaMimeType',	array('fostrakTemplates','MediaMimeType'));
$core->tpl->addValue('FostrakMediaLang',		array('fostrakTemplates','MediaLang'));
$core->tpl->addValue('FostrakMediaID',			array('fostrakTemplates','MediaID'));
$core->tpl->addValue('FostrakMediaTime',		array('fostrakTemplates','MediaTime'));
$core->tpl->addValue('FostrakMediaAuthorLink',	array('fostrakTemplates','MediaAuthorLink'));
$core->tpl->addValue('FostrakMediaFeedURL',		array('fostrakTemplates','MediaFeedURL'));

$core->tpl->addBlock('FostrakMedias',		array('fostrakTemplates','Medias'));
$core->tpl->addBlock('FostrakMediasHeader',	array('fostrakTemplates','MediasHeader'));
$core->tpl->addBlock('FostrakMediasFooter',	array('fostrakTemplates','MediasFooter'));
$core->tpl->addBlock('FostrakNext',			array('fostrakTemplates','Next'));
$core->tpl->addBlock('FostrakPrevious',		array('fostrakTemplates','Previous'));

global $fostrak;


/**
 * Classe pour la gestion des éléments publiques du plugin (url, widget).
 *
 * @author akewea
 *
 */
class fostrakPublic extends dcUrlHandlers
{

	// ##################### PAGE ##########################

	public static function photo($args)
	{
		global $_ctx;
		global $core;
		global $fostrak;

		$fostrak = new fostrak($core);
		//echo "[".$args."]";

		$rs = $fostrak->getStreamMedias(array('relname' => $args));

		if($rs->isEmpty()){
			return self::p404();
		}

		$_ctx->fostrak = $rs;
		
		$_ctx->posts = $core->blog->getPosts(array(
				'post_id' => $rs->post_id,
				'post_type' => 'fostrak'
		));
		
		$_ctx->comment_preview = new ArrayObject();
		$_ctx->comment_preview['content'] = '';
		$_ctx->comment_preview['rawcontent'] = '';
		$_ctx->comment_preview['name'] = '';
		$_ctx->comment_preview['mail'] = '';
		$_ctx->comment_preview['site'] = '';
		$_ctx->comment_preview['preview'] = false;
		$_ctx->comment_preview['remember'] = false;
			
		
		$post_id = $_ctx->posts->post_id;
		$post_password = $_ctx->posts->post_password;
		
		$post_comment =
		isset($_POST['c_name']) && isset($_POST['c_mail']) &&
				isset($_POST['c_site']) && isset($_POST['c_content']) &&
						$_ctx->posts->commentsActive();
		
		# Posting a comment
		if ($post_comment) {
			# Spam trap
			if (!empty($_POST['f_mail'])) {
				http::head(412,'Precondition Failed');
				header('Content-Type: text/plain');
				echo "So Long, and Thanks For All the Fish";
				# Exits immediately the application to preserve the server.
				exit;
			}
					
			$name = $_POST['c_name'];
			$mail = $_POST['c_mail'];
			$site = $_POST['c_site'];
			$content = $_POST['c_content'];
			$preview = !empty($_POST['preview']);
				
			if ($content != '')
			{
				if ($core->blog->settings->system->wiki_comments) {
					$core->initWikiComment();
				} else {
					$core->initWikiSimpleComment();
				}
				$content = $core->wikiTransform($content);
				$content = $core->HTMLfilter($content);
			}
				
			$_ctx->comment_preview['content'] = $content;
			$_ctx->comment_preview['rawcontent'] = $_POST['c_content'];
			$_ctx->comment_preview['name'] = $name;
			$_ctx->comment_preview['mail'] = $mail;
			$_ctx->comment_preview['site'] = $site;
								
			if ($preview)
			{
				# --BEHAVIOR-- publicBeforeCommentPreview
				$core->callBehavior('publicBeforeCommentPreview',$_ctx->comment_preview);
	
				$_ctx->comment_preview['preview'] = true;
			}
			else
			{
				# Post the comment
				$cur = $core->con->openCursor($core->prefix.'comment');
				$cur->comment_author = $name;
				$cur->comment_site = html::clean($site);
				$cur->comment_email = html::clean($mail);
				$cur->comment_content = $content;
				$cur->post_id = $_ctx->posts->post_id;
				$cur->comment_status = $core->blog->settings->system->comments_pub ? 1 : -1;
				$cur->comment_ip = http::realIP();

				$redir = $_ctx->posts->getURL();
				$redir = $_ctx->fostrak->getURL();
				$redir .= $core->blog->settings->system->url_scan == 'query_string' ? '&' : '?';

				try
				{
					if (!text::isEmail($cur->comment_email)) {
						throw new Exception(__('You must provide a valid email address.'));
					}
								
					# --BEHAVIOR-- publicBeforeCommentCreate
					$core->callBehavior('publicBeforeCommentCreate',$cur);
					if ($cur->post_id) {
						$comment_id = $core->blog->addComment($cur);
	
						# --BEHAVIOR-- publicAfterCommentCreate
						$core->callBehavior('publicAfterCommentCreate',$cur,$comment_id);
					}
								
					if ($cur->comment_status == 1) {
						$redir_arg = 'pub=1';
					} else {
						$redir_arg = 'pub=0';
					}
				
					header('Location: '.$redir.$redir_arg);
				}
				catch (Exception $e)
				{
					$_ctx->form_error = $e->getMessage();
					$_ctx->form_error;
				}
			}
		}
		
		$core->tpl->setPath($core->tpl->getPath(), dirname(__FILE__).'/default-templates');
		self::serveDocument('photo.html');
	}

	// ##################### FEED ##########################

	public static function feed($args)
	{
		$type = null;

		$mime = 'application/xml';

		$_ctx =& $GLOBALS['_ctx'];
		$core =& $GLOBALS['core'];
		
		global $fostrak;
		$fostrak = new fostrak($core);

		if (preg_match('!^([a-z]{2}(-[a-z]{2})?)/(.*)$!',$args,$m)) {
			$params = new ArrayObject(array('lang' => $m[1]));
				
			$args = $m[3];
				
			$core->callBehavior('publicFeedBeforeGetLangs',$params,$args);
				
			$_ctx->langs = $core->blog->getLangs($params);
				
			if ($_ctx->langs->isEmpty()) {
				# The specified language does not exist.
				self::p404();
				return;
			} else {
				$_ctx->cur_lang = $m[1];
			}
		}

		if (preg_match('#^rss2/xslt$#',$args,$m))
		{
			# RSS XSLT stylesheet
			self::serveDocument('rss2.xsl','text/xml');
			return;
		}
		elseif (preg_match('#^(atom|rss2)$#',$args,$m))
		{
			# All posts or comments feed
			$type = $m[1];
		}
		else
		{
			# The specified Feed URL is malformed.
			self::p404();
			return;
		}

		$tpl = 'fostrak-'.$type;
		$_ctx->nb_entry_per_page = $core->blog->settings->system->nb_post_per_feed;
		$_ctx->short_feed_items = $core->blog->settings->system->short_feed_items;
		$tpl .= '.xml';

		if ($type == 'atom') {
			$mime = 'application/atom+xml';
		}

		$_ctx->feed_subtitle = ' - '.__('Photo Stream');

		header('X-Robots-Tag: '.context::robotsPolicy($core->blog->settings->system->robots_policy,''));
		
		$core->tpl->setPath($core->tpl->getPath(), dirname(__FILE__).'/default-templates');
		self::serveDocument($tpl,$mime);
	}

	// ##################### WIDGET ##########################

	//	/**
	//	 * Traitement du widget TwittAk.
	//	 *
	//	 * @param $w les paramètres du widget.
	//	 */
	//	public static function twittakWidget($w) {
	//
	//		global $core;
	//		global $_ctx;
	//
	//		//Affichage page d'accueil seulement
	//		if ($w->homeonly && $core->url->type != 'default') {
	//			return;
	//		}
	//
	//		$account = (isset($w->account) && strlen($w->account) > 0) ? $w->account : $core->blog->settings->twittak->twittak_account;
	//
	//		if ($account == ''){
	//			return;
	//		}
	//
	//		if (isset($w->twitterlink)) {
	//			$p .= "\$params['post_type'] = '".addslashes($attr['post_type'])."';\n";
	//		}
	//
	//		$_ctx->tweets = twittakUtils::getTweets($account, $w->limit);
	//
	//		$_ctx->twittakAccount = $account;
	//		$_ctx->twittakWidgetTitle = $w->title;
	//		$_ctx->twittakShowTwitterLink = $w->twitterlink;
	//		$_ctx->twittakShowTweetsPageLink = $w->tweetslink && $core->blog->settings->twittak->twittak_enabled;
	//
	//		$core->tpl->setPath($core->tpl->getPath(), dirname(__FILE__).'/default-templates');
	//		self::serveDocument('tweets-widget.html');
	//	}

}
?>