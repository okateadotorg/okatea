<?php
/*
 * This file is part of Okatea.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Okatea\Modules\News;

use ArrayObject;
use Okatea\Admin\Menu as AdminMenu;
use Okatea\Admin\Page;
use Okatea\Tao\Html\Escaper;
use Okatea\Tao\Html\Modifiers;
use Okatea\Tao\Images\ImageUpload;
use Okatea\Tao\L10n\Date;
use Okatea\Tao\Misc\Utilities;
use Okatea\Tao\Misc\FileUpload;
use Okatea\Tao\Extensions\Modules\Module as BaseModule;
use Okatea\Tao\Themes\SimpleReplacements;
use Okatea\Tao\Triggers\Triggers;
use Okatea\Tao\Users\Groups;
use RuntimeException;

class Module extends BaseModule
{
	public $config;

	public $categories;

	public $filters;

	protected $t_news;

	protected $t_news_locales;

	protected $t_categories;

	protected $t_categories_locales;

	protected $t_permissions;

	protected $t_users;

	protected function prepend()
	{
		# permissions
		$this->okt['permissions']->addPermGroup('news', __('m_news_perm_group'));
		$this->okt['permissions']->addPerm('news_usage', __('m_news_perm_global'), 'news');
		$this->okt['permissions']->addPerm('news_show_all', __('m_news_perm_show_all'), 'news');
		$this->okt['permissions']->addPerm('news_publish', __('m_news_perm_publish'), 'news');
		$this->okt['permissions']->addPerm('news_delete', __('m_news_perm_delete'), 'news');
		$this->okt['permissions']->addPerm('news_contentadmin', __('m_news_perm_contentadmin'), 'news');
		$this->okt['permissions']->addPerm('news_categories', __('m_news_perm_categories'), 'news');
		$this->okt['permissions']->addPerm('news_display', __('m_news_perm_display'), 'news');
		$this->okt['permissions']->addPerm('news_config', __('m_news_perm_config'), 'news');

		# tables
		$this->t_news = $this->db->prefix . 'mod_news';
		$this->t_news_locales = $this->db->prefix . 'mod_news_locales';
		$this->t_categories = $this->db->prefix . 'mod_news_categories';
		$this->t_categories_locales = $this->db->prefix . 'mod_news_categories_locales';
		$this->t_permissions = $this->db->prefix . 'mod_news_permissions';
		$this->t_users = $this->db->prefix . 'core_users';

		# déclencheurs
		$this->triggers = new Triggers();

		# config
		$this->config = $this->okt->newConfig('conf_news');

		# rubriques
		if ($this->config->categories['enable'])
		{
			$this->categories = new Categories($this->okt, $this->t_news, $this->t_news_locales, $this->t_categories, $this->t_categories_locales, 'id', 'parent_id', 'ord', 'category_id', 'language', array(
				'active',
				'ord'
			), array(
				'title',
				'title_tag',
				'title_seo',
				'slug',
				'content',
				'meta_description',
				'meta_keywords'
			));
		}
	}

	protected function prepend_admin()
	{
		# on ajoutent un item au menu admin
		if ($this->okt->page->display_menu)
		{
			$this->okt->page->newsSubMenu = new AdminMenu(null, Page::$formatHtmlSubMenu);

			$this->okt->page->mainMenu->add($this->getName(), $this->okt['adminRouter']->generate('News_index'), $this->okt['request']->attributes->get('_route') === 'News_index', 15, ($this->okt['visitor']->checkPerm('news_usage') || $this->okt['visitor']->checkPerm('news_contentadmin')), null, $this->okt->page->newsSubMenu, $this->okt['public_url'] . '/modules/' . $this->id() . '/module_icon.png');
			$this->okt->page->newsSubMenu->add(__('c_a_menu_management'), $this->okt['adminRouter']->generate('News_index'), in_array($this->okt['request']->attributes->get('_route'), array(
				'News_index',
				'News_post'
			)), 10);
			$this->okt->page->newsSubMenu->add(__('m_news_menu_add_post'), $this->okt['adminRouter']->generate('News_post_add'), $this->okt['request']->attributes->get('_route') === 'News_post_add', 20);
			$this->okt->page->newsSubMenu->add(__('m_news_menu_categories'), $this->okt['adminRouter']->generate('News_categories'), in_array($this->okt['request']->attributes->get('_route'), array(
				'News_categories',
				'News_category',
				'News_category_add'
			)), 30, ($this->config->categories['enable'] && $this->okt['visitor']->checkPerm('news_categories')));
			$this->okt->page->newsSubMenu->add(__('c_a_menu_display'), $this->okt['adminRouter']->generate('News_display'), $this->okt['request']->attributes->get('_route') === 'News_display', 40, $this->okt['visitor']->checkPerm('news_display'));
			$this->okt->page->newsSubMenu->add(__('c_a_menu_configuration'), $this->okt['adminRouter']->generate('News_config'), $this->okt['request']->attributes->get('_route') === 'News_config', 50, $this->okt['visitor']->checkPerm('news_config'));
		}

		$this->okt['triggers']->registerTrigger('adminConfigSiteInit', array(
			$this,
			'adminConfigSiteInit'
		));
	}

	protected function prepend_public()
	{
		# Publication des articles différés
		$this->publishScheduledPosts();

		# Handle website home page
		$this->okt['triggers']->registerTrigger('handleWebsiteHomePage', array(
			$this,
			'handleWebsiteHomePage'
		));

		# Ajout d'éléments à la barre admin
		$this->okt['triggers']->registerTrigger('websiteAdminBarItems', array(
			$this,
			'websiteAdminBarItems'
		));
	}

	public function handleWebsiteHomePage($item, $details)
	{
		if ($item == 'newsList')
		{
			$this->okt->controllerInstance = new Controller($this->okt);
			$this->okt->response = $this->okt->controllerInstance->newsListForHomePage($details);
		}
	}

	public function adminConfigSiteInit($aPageData)
	{
		$aPageData['home_page_items'][__('m_news_config_homepage_newsList')] = 'newsList';

		foreach ($this->okt['languages']->getList() as $aLanguage)
		{
			$this->okt->page->js->addReady('
				$("#p_home_page_item_' . $aLanguage['code'] . '").change(function(){

					var selected = $("#p_home_page_item_' . $aLanguage['code'] . ' option:selected").val();
					var details = $("#p_home_page_details_' . $aLanguage['code'] . '");

					if (selected == "newsList") {
						details.find("option").remove();
					}
				});
			');
		}
	}

	/**
	 * Ajout d'éléments à la barre admin côté publique.
	 *
	 * @param arrayObject $aPrimaryAdminBar
	 * @param arrayObject $aSecondaryAdminBar
	 * @param arrayObject $aBasesUrl
	 * @return void
	 */
	public function websiteAdminBarItems($aPrimaryAdminBar, $aSecondaryAdminBar, $aBasesUrl)
	{
		# lien ajouter un article
		if ($this->okt['visitor']->checkPerm('news_usage') || $this->okt['visitor']->checkPerm('news_contentadmin'))
		{
			$aPrimaryAdminBar[200]['items'][200] = array(
				'href' => $this->okt['adminRouter']->generateFromWebsite('News_post_add'),
				'title' => __('m_news_ab_post_title'),
				'intitle' => __('m_news_ab_post')
			);
		}

		# modification de l'article en cours
		if (isset($this->okt->page->module) && $this->okt->page->module == 'news' && isset($this->okt->page->action) && $this->okt->page->action == 'item')
		{
			if (isset($this->okt->controller->rsPost) && $this->okt->controller->rsPost->isEditable())
			{
				$aPrimaryAdminBar[300] = array(
					'href' => $this->okt['adminRouter']->generateFromWebsite('News_post', array(
						'post_id' => $this->okt->controller->rsPost->id
					)),
					'intitle' => __('m_news_ab_edit_post')
				);
			}
		}
	}

	/**
	 * Indique si on as accès à la partie publique en fonction de la configuration.
	 *
	 * @return boolean
	 */
	public function isPublicAccessible()
	{
		# si on est superadmin on as droit à tout
		if ($this->okt['visitor']->is_superadmin)
		{
			return true;
		}

		# si on a le groupe id 0 (zero) alors tous le monde a droit
		# sinon il faut etre dans le bon groupe
		if (in_array(0, $this->config->perms) || in_array($this->okt['visitor']->group_id, $this->config->perms))
		{
			return true;
		}

		# toutes éventualités testées, on as pas le droit
		return false;
	}

	/**
	 * Initialisation des filtres
	 *
	 * @param string $part
	 *        	'public' ou 'admin'
	 */
	public function filtersStart($part = 'public')
	{
		if ($this->filters === null || !($this->filters instanceof Filters))
		{
			$this->filters = new Filters($this->okt, $part);
		}
	}

	/* Gestion des articles d'actualité
	----------------------------------------------------------*/

	/**
	 * Retourne une liste d'articles sous forme de recordset selon des paramètres donnés.
	 *
	 * @param array $aParams
	 *        	Paramètres de requete
	 * @param boolean $bCountOnly
	 *        	Ne renvoi qu'un nombre d'articles
	 * @return object Recordset/integer
	 */
	public function getPostsRecordset($aParams = [], $bCountOnly = false)
	{
		$sReqPlus = '';

		if (!empty($aParams['id']))
		{
			$sReqPlus .= ' AND p.id=' . (integer) $aParams['id'] . ' ';
		}

		if (!empty($aParams['user_id']))
		{
			$sReqPlus .= ' AND p.user_id=' . (integer) $aParams['user_id'] . ' ';
		}

		if (!empty($aParams['category_id']))
		{
			$sReqPlus .= ' AND p.category_id=' . (integer) $aParams['category_id'] . ' ';
		}

		if (!empty($aParams['selected']))
		{
			$sReqPlus .= ' AND p.selected=' . (integer) $aParams['selected'] . ' ';
		}

		if (!empty($aParams['slug']))
		{
			$sReqPlus .= ' AND pl.slug=\'' . $this->db->escapeStr($aParams['slug']) . '\' ';
		}

		if (!empty($aParams['created_after']))
		{
			$sReqPlus .= ' AND created_at>=\'' . $this->db->escapeStr($aParams['created_after']) . '\' ';
		}

		if (!empty($aParams['created_before']))
		{
			$sReqPlus .= ' AND created_at<=\'' . $this->db->escapeStr($aParams['created_before']) . '\' ';
		}

		if (!empty($aParams['pending']))
		{
			$sReqPlus .= 'AND p.active=2 ';
		}
		elseif (!empty($aParams['scheduled']))
		{
			$sReqPlus .= 'AND p.active=3 ';
		}
		elseif (isset($aParams['active']))
		{
			if ($aParams['active'] == 0)
			{
				$sReqPlus .= 'AND p.active=0 ';
			}
			elseif ($aParams['active'] == 1)
			{
				$sReqPlus .= 'AND p.active=1 ';
			}
			elseif ($aParams['active'] == 2)
			{
				$sReqPlus .= 'AND p.active=2 ';
			}
			elseif ($aParams['active'] == 3)
			{
				$sReqPlus .= 'AND p.active=3 ';
			}
		}

		if (!empty($aParams['search']))
		{
			$aWords = Modifiers::splitWords($aParams['search']);

			if (!empty($aWords))
			{
				foreach ($aWords as $i => $w)
				{
					$aWords[$i] = 'pl.words LIKE \'%' . $this->db->escapeStr($w) . '%\' ';
				}
				$sReqPlus .= ' AND ' . implode(' AND ', $aWords) . ' ';
			}
		}

		if ($bCountOnly)
		{
			$sQuery = 'SELECT COUNT(p.id) AS num_posts ' . $this->getSqlFrom($aParams) . 'WHERE 1 ' . $sReqPlus;
		}
		else
		{
			$sQuery = 'SELECT ' . $this->getSelectFields($aParams) . ' ' . $this->getSqlFrom($aParams) . 'WHERE 1 ' . $sReqPlus;

			$sDirection = 'DESC';
			if (!empty($aParams['order_direction']) && strtoupper($aParams['order_direction']) == 'ASC')
			{
				$sDirection = 'ASC';
			}

			if (!empty($aParams['order']))
			{
				$sQuery .= 'ORDER BY p.selected DESC, ' . $aParams['order'] . ' ' . $sDirection . ' ';
			}
			else
			{
				$sQuery .= 'ORDER BY p.selected DESC, p.created_at ' . $sDirection . ' ';
			}

			if (!empty($aParams['limit']))
			{
				$sQuery .= 'LIMIT ' . $aParams['limit'] . ' ';
			}
		}

		if (($rsPosts = $this->db->select($sQuery, 'Okatea\Modules\News\Recordset')) === false)
		{
			if ($bCountOnly)
			{
				return 0;
			}
			else
			{
				$rsPosts = new Recordset([]);
				$rsPosts->setCore($this->okt);
				return $rsPosts;
			}
		}

		if ($bCountOnly)
		{
			return (integer) $rsPosts->num_posts;
		}
		else
		{
			$rsPosts->setCore($this->okt);
			return $rsPosts;
		}
	}

	/**
	 * Retourne la chaine des champs pour le SELECT.
	 *
	 * @return string
	 */
	protected function getSelectFields($aParams)
	{
		$aFields = array(
			'p.id',
			'p.user_id',
			'p.category_id',
			'p.active',
			'p.selected',
			'p.created_at',
			'p.updated_at',
			'p.images',
			'p.files',
			'p.tpl',
			'pl.language',
			'pl.title',
			'pl.subtitle',
			'pl.title_tag',
			'pl.title_seo',
			'pl.slug',
			'pl.content',
			'pl.meta_description',
			'pl.meta_keywords',
			'pl.words',
			'u.username',
			'u.lastname',
			'u.firstname',
			'u.displayname',
			'rl.title AS category_title',
			'rl.slug AS category_slug',
			'r.items_tpl AS category_items_tpl'
		);

		$oFields = new ArrayObject($aFields);

		# -- TRIGGER MODULE NEWS : getPostsSelectFields
		$this->triggers->callTrigger('getPostsSelectFields', $oFields);

		return implode(', ', (array) $oFields);
	}

	/**
	 * Retourne la chaine FROM en fonction de paramètres.
	 *
	 * @param array $aParams
	 * @return string
	 */
	protected function getSqlFrom($aParams)
	{
		if (empty($aParams['language']))
		{
			$aFrom = array(
				'FROM ' . $this->t_news . ' AS p ',
				'LEFT OUTER JOIN ' . $this->t_users . ' AS u ON u.id=p.user_id ',
				'LEFT OUTER JOIN ' . $this->t_news_locales . ' AS pl ON p.id=pl.post_id ',
				'LEFT OUTER JOIN ' . $this->t_categories . ' AS r ON r.id=p.category_id ',
				'LEFT OUTER JOIN ' . $this->t_categories_locales . ' AS rl ON r.id=rl.category_id '
			);
		}
		else
		{
			$aFrom = array(
				'FROM ' . $this->t_news . ' AS p ',
				'LEFT OUTER JOIN ' . $this->t_users . ' AS u ON u.id=p.user_id ',
				'INNER JOIN ' . $this->t_news_locales . ' AS pl ON p.id=pl.post_id ' . 'AND pl.language=\'' . $this->db->escapeStr($aParams['language']) . '\' ',
				'LEFT OUTER JOIN ' . $this->t_categories . ' AS r ON r.id=p.category_id ',
				'LEFT OUTER JOIN ' . $this->t_categories_locales . ' AS rl ON r.id=rl.category_id ' . 'AND rl.language=\'' . $this->db->escapeStr($aParams['language']) . '\' '
			);
		}

		$oFrom = new ArrayObject($aFrom);

		# -- TRIGGER MODULE NEWS : getPostsSqlFrom
		$this->triggers->callTrigger('getPostsSqlFrom', $oFrom);

		return implode(' ', (array) $oFrom);
	}

	/**
	 * Retourne une liste d'articles sous forme de recordset selon des paramètres donnés
	 * et les prépares en vue d'un affichage.
	 *
	 * @param array $aParams
	 *        	Paramètres de requete
	 * @param integer $iTruncatChar
	 *        	(null) Nombre de caractère avant troncature du contenu
	 * @return object Recordset
	 */
	public function getPosts($aParams = [], $iTruncatChar = null)
	{
		$rs = $this->getPostsRecordset($aParams);

		$this->preparePosts($rs, $iTruncatChar);

		return $rs;
	}

	/**
	 * Retourne un compte du nombre d'articles selon des paramètres donnés.
	 *
	 * @param array $aParams
	 *        	Paramètres de requete
	 * @return integer
	 */
	public function getPostsCount($aParams = [])
	{
		return $this->getPostsRecordset($aParams, true);
	}

	/**
	 * Retourne un article donné sous forme de recordset.
	 *
	 * @param integer $mPostId
	 *        	Identifiant numérique ou slug de l'article.
	 * @param integer $iActive
	 * @return object recordset
	 */
	public function getPost($mPostId, $iActive = null)
	{
		$aParams = array(
			'language' => $this->okt['visitor']->language
		);

		if (!is_null($iActive))
		{
			$aParams['active'] = $iActive;
		}

		if (Utilities::isInt($mPostId))
		{
			$aParams['id'] = $mPostId;
		}
		else
		{
			$aParams['slug'] = $mPostId;
		}

		$rs = $this->getPostsRecordset($aParams);

		$this->preparePost($rs);

		return $rs;
	}

	/**
	 * Indique si un article donné existe.
	 *
	 * @param
	 *        	$iPostId
	 * @return boolean
	 */
	public function postExists($iPostId)
	{
		if (empty($iPostId) || $this->getPostsRecordset(array(
			'id' => $iPostId
		))->isEmpty())
		{
			return false;
		}

		return true;
	}

	/**
	 * Retourne les localisations d'un article donné.
	 *
	 * @param integer $iPostId
	 * @return recordset
	 */
	public function getPostL10n($iPostId)
	{
		$query = 'SELECT * FROM ' . $this->t_news_locales . ' ' . 'WHERE post_id=' . (integer) $iPostId;

		if (($rsPostLocales = $this->db->select($query)) === false)
		{
			$rsPostLocales = new Recordset([]);
			return $rsPostLocales;
		}

		return $rsPostLocales;
	}

	/**
	 * Formatage des données d'un Recordset en vue d'un affichage d'une liste.
	 *
	 * @param Recordset $rsPosts
	 * @param integer $iTruncatChar
	 *        	(null)
	 * @return void
	 */
	public function preparePosts(Recordset $rsPosts, $iTruncatChar = null)
	{
		# on utilise une troncature personnalisée à cette préparation
		if (!is_null($iTruncatChar))
		{
			$iNumCharBeforeTruncate = (integer) $iTruncatChar;
		}
		# on utilise la troncature de la configuration
		elseif ($this->config->public_truncat_char > 0)
		{
			$iNumCharBeforeTruncate = $this->config->public_truncat_char;
		}
		# on n'utilisent pas de troncature
		else
		{
			$iNumCharBeforeTruncate = 0;
		}

		$iCountLine = 0;
		while ($rsPosts->fetch())
		{
			# odd/even
			$rsPosts->odd_even = ($iCountLine % 2 == 0 ? 'even' : 'odd');
			$iCountLine ++;

			# formatages génériques
			$this->commonPreparation($rsPosts);

			# troncature
			if ($iNumCharBeforeTruncate > 0)
			{
				$rsPosts->content = strip_tags($rsPosts->content);
				$rsPosts->content = Modifiers::truncate($rsPosts->content, $iNumCharBeforeTruncate);
			}
		}
	}

	/**
	 * Formatage des données d'un Recordset en vue d'un affichage d'un article.
	 *
	 * @param Recordset $rsPost
	 * @return void
	 */
	public function preparePost(Recordset $rsPost)
	{
		# formatages génériques
		$this->commonPreparation($rsPost);
	}

	/**
	 * Formatages des données d'un Recordset communs aux listes et aux éléments.
	 *
	 * @param Recordset $rsPost
	 * @return void
	 */
	protected function commonPreparation(Recordset $rsPost)
	{
		# url post
		$rsPost->url = $rsPost->getPostUrl();

		# url rubrique
		if ($this->config->categories['enable'])
			$rsPost->category_url = $rsPost->getCategoryUrl();

			# author
		$rsPost->author = $rsPost->getPostAuthor();

		# récupération des images
		$rsPost->images = $rsPost->getImagesInfo();

		# récupération des fichiers
		$rsPost->files = $rsPost->getFilesInfo();

		# contenu
		if (!$this->config->enable_rte)
		{
			$rsPost->content = Modifiers::nlToP($rsPost->content);
		}

		# perform content replacements
		SimpleReplacements::setStartString('');
		SimpleReplacements::setEndString('');

		$aReplacements = array_merge($this->okt->getCommonContentReplacementsVariables(), $this->okt->getImagesReplacementsVariables($rsPost->images));

		$rsPost->content = SimpleReplacements::parse($rsPost->content, $aReplacements);
	}

	/**
	 * Créer une instance de cursor pour un article et la retourne.
	 *
	 * @param array $aPostData
	 * @return object cursor
	 */
	public function openPostCursor($aPostData = null)
	{
		$oCursor = $this->db->openCursor($this->t_news);

		if (!empty($aPostData))
		{
			foreach ($aPostData as $k => $v)
			{
				$oCursor->{$k} = $v;
			}
		}

		return $oCursor;
	}

	/**
	 * Ajout/modification des textes internationnalisés de l'article.
	 *
	 * @param integer $iPostId
	 * @param array $aPostLocalesData
	 */
	protected function setPostL10n($iPostId, $aPostLocalesData)
	{
		foreach ($this->okt['languages']->getList() as $aLanguage)
		{
			$oCursor = $this->db->openCursor($this->t_news_locales);

			$oCursor->post_id = $iPostId;

			$oCursor->language = $aLanguage['code'];

			foreach ($aPostLocalesData[$aLanguage['code']] as $k => $v)
			{
				$oCursor->{$k} = $v;
			}

			$oCursor->content = $this->okt->HTMLfilter($oCursor->content);

			$oCursor->words = implode(' ', array_unique(Modifiers::splitWords($oCursor->title . ' ' . $oCursor->subtitle . ' ' . $oCursor->content)));

			$oCursor->meta_description = strip_tags($oCursor->meta_description);

			$oCursor->meta_keywords = strip_tags($oCursor->meta_keywords);

			if (!$oCursor->insertUpdate())
			{
				throw new RuntimeException('Unable to insert/update post locales into database');
			}

			$this->setPostSlug($iPostId, $aLanguage['code']);
		}
	}

	/**
	 * Création du slug d'un article donné dans une langue donnée.
	 *
	 * @param integer $iPostId
	 * @param string $sLanguage
	 * @return boolean
	 */
	protected function setPostSlug($iPostId, $sLanguage)
	{
		$rsPost = $this->getPostsRecordset(array(
			'id' => $iPostId,
			'language' => $sLanguage
		));

		if ($rsPost->isEmpty())
		{
			$this->error->set(sprintf(__('m_news_post_%s_not_exists'), $iPostId));
			return false;
		}

		if (empty($rsPost->slug))
		{
			$sUrl = $rsPost->title;
		}
		else
		{
			$sUrl = $rsPost->slug;
		}

		$sUrl = Modifiers::strToSlug($sUrl, false);

		# Let's check if URL is taken…
		$rsTakenSlugs = $this->db->select('SELECT slug FROM ' . $this->t_news_locales . ' ' . 'WHERE slug=\'' . $this->db->escapeStr($sUrl) . '\' ' . 'AND post_id <> ' . (integer) $iPostId . ' ' . 'AND language=\'' . $this->db->escapeStr($sLanguage) . '\' ' . 'ORDER BY slug DESC');

		if (!$rsTakenSlugs->isEmpty())
		{
			$rsCurrentSlugs = $this->db->select('SELECT slug FROM ' . $this->t_news_locales . ' ' . 'WHERE slug LIKE \'' . $this->db->escapeStr($sUrl) . '%\' ' . 'AND post_id <> ' . (integer) $iPostId . ' ' . 'AND language=\'' . $this->db->escapeStr($sLanguage) . '\' ' . 'ORDER BY slug DESC ');

			$a = [];
			while ($rsCurrentSlugs->fetch())
			{
				$a[] = $rsCurrentSlugs->slug;
			}

			$sUrl = Utilities::getIncrementedString($a, $sUrl, '-');
		}

		$sQuery = 'UPDATE ' . $this->t_news_locales . ' SET ' . 'slug=\'' . $this->db->escapeStr($sUrl) . '\' ' . 'WHERE post_id=' . (integer) $iPostId . ' ' . 'AND language=\'' . $this->db->escapeStr($sLanguage) . '\' ';

		if (!$this->db->execute($sQuery))
		{
			return false;
		}

		return true;
	}

	/**
	 * Ajout d'un article.
	 *
	 * @param cursor $oCursor
	 * @param array $aPostLocalesData
	 * @param array $aPostPermsData
	 * @return integer
	 */
	public function addPost($oCursor, array $aPostLocalesData, array $aPostPermsData = [])
	{
		# insertion dans la DB
		$this->preparePostCursor($oCursor);

		$oCursor->user_id = $this->okt['visitor']->id;

		if (!$oCursor->insert())
		{
			throw new RuntimeException('Unable to insert post into database');
		}

		# récupération de l'ID
		$iNewId = $this->db->getLastID();

		# ajout des textes internationnalisés
		$this->setPostL10n($iNewId, $aPostLocalesData);

		# ajout des images
		if ($this->addImages($iNewId) === false)
		{
			throw new RuntimeException('Unable to insert images post');
		}

		# ajout des fichiers
		if ($this->addFiles($iNewId) === false)
		{
			throw new RuntimeException('Unable to insert files post');
		}

		# ajout permissions
		if (!$this->setPostPermissions($iNewId, $aPostPermsData))
		{
			throw new RuntimeException('Unable to set post permissions');
		}

		return $iNewId;
	}

	/**
	 * Mise à jour d'un article.
	 *
	 * @param cursor $oCursor
	 * @param array $aPostLocalesData
	 * @param array $aPostPermsData
	 * @return boolean
	 */
	public function updPost($oCursor, array $aPostLocalesData, array $aPostPermsData = [])
	{
		$rsPost = $this->getPostsRecordset(array(
			'id' => $oCursor->id
		));

		if ($rsPost->isEmpty())
		{
			$this->error->set(sprintf(__('m_news_post_%s_not_exists'), $oCursor->id));
			return false;
		}

		if (!$rsPost->isEditable())
		{
			$this->error->set(__('m_news_post_not_editable'));
			return false;
		}

		# modification dans la DB
		$this->preparePostCursor($oCursor, $rsPost);

		if (!$oCursor->update('WHERE id=' . (integer) $oCursor->id . ' '))
		{
			throw new RuntimeException('Unable to update post into database');
		}

		# modification des images
		if ($this->updImages($oCursor->id) === false)
		{
			throw new RuntimeException('Unable to update images post');
		}

		# modification des fichiers
		if ($this->updFiles($oCursor->id) === false)
		{
			throw new RuntimeException('Unable to update files post');
		}

		# modification permissions
		if (!$this->setPostPermissions($oCursor->id, $aPostPermsData))
		{
			throw new RuntimeException('Unable to set post permissions');
		}

		# modification des textes internationnalisés
		$this->setPostL10n($oCursor->id, $aPostLocalesData);

		return true;
	}

	/**
	 * Réalise les opérations communes sur le cursor pour l'insertion et la modification.
	 *
	 * @param cursor $oCursor
	 */
	protected function preparePostCursor($oCursor, $rsPost = null)
	{
		$sDate = Date::now('UTC')->toMysqlString();

		if (empty($oCursor->created_at))
		{
			$oCursor->created_at = $sDate;
		}
		else
		{
			$oCursor->created_at = Date::parse($oCursor->created_at)->toMysqlString();
		}

		$oCursor->updated_at = $sDate;

		if (Date::parse($oCursor->created_at)->isFuture())
		{
			$oCursor->active = 3;
		}
		elseif ($oCursor->active == 3)
		{
			if (null === $rsPost)
			{
				$oCursor->active = 1;
			}
		}
	}

	/**
	 * Vérifie les données envoyées par formulaire.
	 *
	 * @param array $aPostData
	 *        	Le tableau de données de l'article.
	 * @param array $aPostLocalesData
	 *        	Le tableau de données des textes internationnalisés de l'article.
	 * @param array $aPostPermsData
	 *        	Le tableau de données des permissions de l'article.
	 * @return boolean
	 */
	public function checkPostData($aPostData, $aPostLocalesData, $aPostPermsData)
	{
		$bHasAtLeastOneTitle = false;
		foreach ($this->okt['languages']->getList() as $aLanguage)
		{
			if (empty($aPostLocalesData[$aLanguage['code']]['title']))
			{
				continue;
			}
			else
			{
				$bHasAtLeastOneTitle = true;
				break;
			}
		}

		if (!$bHasAtLeastOneTitle)
		{
			if ($this->okt['languages']->hasUniqueLanguage())
			{
				$this->error->set(__('m_news_post_must_enter_title'));
			}
			else
			{
				$this->error->set(__('m_news_post_must_enter_at_least_one_title'));
			}
		}

		if ($this->config->enable_group_perms && empty($aPostPermsData))
		{
			$this->error->set(__('m_news_post_must_set_perms'));
		}

		# -- TRIGGER MODULE NEWS : checkPostData
		$this->triggers->callTrigger('checkPostData', $aPostData, $aPostLocalesData, $aPostPermsData);

		return $this->error->isEmpty();
	}

	/**
	 * Switch le statut de visibilité d'un article donné.
	 *
	 * @param integer $iPostId
	 * @return boolean
	 */
	public function switchPostStatus($iPostId)
	{
		$rsPost = $this->getPostsRecordset(array(
			'id' => $iPostId
		));

		if ($rsPost->isEmpty())
		{
			$this->error->set(sprintf(__('m_news_post_%s_not_exists'), $iPostId));
			return false;
		}

		if (!$rsPost->isEditable())
		{
			$this->error->set(__('m_news_post_not_editable'));
			return false;
		}

		if ($rsPost->active == 2)
		{
			$this->error->set(__('m_news_post_not_yet_validated'));
			return false;
		}

		$sQuery = 'UPDATE ' . $this->t_news . ' SET ' . 'updated_at=NOW(), ' . 'active = 1-active ' . 'WHERE id=' . (integer) $iPostId;

		if (!$this->db->execute($sQuery))
		{
			throw new RuntimeException('Unable to update post in database.');
		}

		return true;
	}

	/**
	 * Masquage d'un article.
	 *
	 * @param integer $iPostId
	 * @throws Exception
	 * @return boolean
	 */
	public function hidePost($iPostId)
	{
		$rsPost = $this->getPostsRecordset(array(
			'id' => $iPostId
		));

		if ($rsPost->isEmpty())
		{
			$this->error->set(sprintf(__('m_news_post_%s_not_exists'), $iPostId));
			return false;
		}

		if ($rsPost->active != 1)
		{
			return false;
		}

		if (!$rsPost->isEditable())
		{
			$this->error->set(__('m_news_post_not_editable'));
			return false;
		}

		$this->setPostStatus($iPostId, 0);

		return true;
	}

	/**
	 * Modification du statut d'un article à "visible".
	 *
	 * @param integer $iPostId
	 * @throws Exception
	 * @return boolean
	 */
	public function showPost($iPostId)
	{
		$rsPost = $this->getPostsRecordset(array(
			'id' => $iPostId
		));

		if ($rsPost->isEmpty())
		{
			$this->error->set(sprintf(__('m_news_post_%s_not_exists'), $iPostId));
			return false;
		}

		if ($rsPost->active != 0)
		{
			return false;
		}

		if (!$rsPost->isEditable())
		{
			$this->error->set(__('m_news_post_not_editable'));
			return false;
		}

		$this->setPostStatus($iPostId, 1);

		return true;
	}

	/**
	 * Publication d'un article en attente de publication.
	 *
	 * @param integer $iPostId
	 * @throws Exception
	 * @return boolean
	 */
	public function publishPost($iPostId)
	{
		$rsPost = $this->getPostsRecordset(array(
			'id' => $iPostId
		));

		if ($rsPost->isEmpty())
		{
			$this->error->set(sprintf(__('m_news_post_%s_not_exists'), $iPostId));
			return false;
		}

		if ($rsPost->active != 2)
		{
			return false;
		}

		if (!$rsPost->isEditable())
		{
			$this->error->set(__('m_news_post_not_editable'));
			return false;
		}

		if (!$rsPost->isPublishable())
		{
			$this->error->set(__('m_news_post_not_publishable'));
			return false;
		}

		$this->setPostStatus($iPostId, 1);

		return true;
	}

	/**
	 * Publication des articles programmés.
	 *
	 * @return boolean
	 */
	public function publishScheduledPosts()
	{
		$rsPosts = $this->getPostsRecordset(array(
			'scheduled' => true
		));

		if ($rsPosts->isEmpty())
		{
			return null;
		}

		$iNow = time();

		while ($rsPosts->fetch())
		{
			if ($iNow > strtotime($rsPosts->created_at))
			{
				$this->setPostStatus($rsPosts->id, 1);
			}
		}

		return true;
	}

	/**
	 * Définit le statut de visibilité d'un article donné.
	 *
	 * @param integer $iPostId
	 * @param integer $iStatus
	 * @return boolean
	 */
	protected function setPostStatus($iPostId, $iStatus)
	{
		$sQuery = 'UPDATE ' . $this->t_news . ' SET ' . 'updated_at=NOW(), ' . 'active = ' . (integer) $iStatus . ' ' . 'WHERE id=' . (integer) $iPostId;

		if (!$this->db->execute($sQuery))
		{
			throw new RuntimeException('Unable to update post in database.');
		}

		return true;
	}

	/**
	 * Switch la selection d'un article donné.
	 *
	 * @param integer $iPostId
	 * @return boolean
	 */
	public function switchPostSelected($iPostId)
	{
		$rsPost = $this->getPostsRecordset(array(
			'id' => $iPostId
		));

		if ($rsPost->isEmpty())
		{
			$this->error->set(sprintf(__('m_news_post_%s_not_exists'), $iPostId));
			return false;
		}

		if (!$rsPost->isEditable())
		{
			$this->error->set(__('m_news_post_not_editable'));
			return false;
		}

		$sQuery = 'UPDATE ' . $this->t_news . ' SET ' . 'updated_at=NOW(), ' . 'selected = 1-selected ' . 'WHERE id=' . (integer) $iPostId;

		if (!$this->db->execute($sQuery))
		{
			throw new RuntimeException('Unable to update post in database.');
		}

		return true;
	}

	/**
	 * Définit la selection d'un article donné.
	 *
	 * @param integer $iPostId
	 * @param boolean $bSelected
	 * @return boolean
	 */
	public function setPostSelected($iPostId, $bSelected)
	{
		$rsPost = $this->getPostsRecordset(array(
			'id' => $iPostId
		));

		if ($rsPost->isEmpty())
		{
			$this->error->set(sprintf(__('m_news_post_%s_not_exists'), $iPostId));
			return false;
		}

		if (!$rsPost->isEditable())
		{
			$this->error->set(__('m_news_post_not_editable'));
			return false;
		}

		$sQuery = 'UPDATE ' . $this->t_news . ' SET ' . 'updated_at=NOW(), ' . 'selected = ' . ($bSelected ? '1' : '0') . ' ' . 'WHERE id=' . (integer) $iPostId;

		if (!$this->db->execute($sQuery))
		{
			throw new RuntimeException('Unable to update post in database.');
		}

		return true;
	}

	/**
	 * Suppression d'un article.
	 *
	 * @param integer $iPostId
	 * @return boolean
	 */
	public function deletePost($iPostId)
	{
		$rsPost = $this->getPostsRecordset(array(
			'id' => $iPostId
		));

		if ($rsPost->isEmpty())
		{
			$this->error->set(sprintf(__('m_news_post_%s_not_exists'), $iPostId));
			return false;
		}

		if (!$rsPost->isDeletable())
		{
			$this->error->set(__('m_news_post_not_deletable'));
			return false;
		}

		if ($this->deleteImages($iPostId) === false)
		{
			throw new RuntimeException('Unable to delete images post.');
		}

		if ($this->deleteFiles($iPostId) === false)
		{
			throw new RuntimeException('Unable to delete files post.');
		}

		$sQuery = 'DELETE FROM ' . $this->t_news . ' ' . 'WHERE id=' . (integer) $iPostId;

		if (!$this->db->execute($sQuery))
		{
			throw new RuntimeException('Unable to remove post from database.');
		}

		$this->db->optimize($this->t_news);

		$sQuery = 'DELETE FROM ' . $this->t_news_locales . ' ' . 'WHERE post_id=' . (integer) $iPostId;

		if (!$this->db->execute($sQuery))
		{
			throw new RuntimeException('Unable to remove post locales from database.');
		}

		$this->db->optimize($this->t_news_locales);

		$this->deletePostPermissions($iPostId);

		return true;
	}

	/* Gestion des permissions des articles
	----------------------------------------------------------*/

	/**
	 * Retourne la liste des groupes pour les permissions.
	 *
	 * @param
	 *        	$bWithAdmin
	 * @param
	 *        	$bWithAll
	 * @return array
	 */
	public function getUsersGroupsForPerms($bWithAdmin = false, $bWithAll = false)
	{
		$aParams = array(
			'language' => $this->okt['visitor']->language,
			'group_id_not' => array(
				Groups::GUEST,
				Groups::SUPERADMIN
			)
		);

		if (!$this->okt['visitor']->is_admin && !$bWithAdmin)
		{
			$aParams['group_id_not'][] = Groups::ADMIN;
		}

		$rsGroups = $this->okt['groups']->getGroups($aParams);

		$aGroups = [];

		if ($bWithAll)
		{
			$aGroups[] = __('c_c_All');
		}

		while ($rsGroups->fetch())
		{
			$aGroups[$rsGroups->group_id] = Escaper::html($rsGroups->title);
		}

		return $aGroups;
	}

	/**
	 * Retourne les permissions d'un article donné sous forme de tableau.
	 *
	 * @param integer $iPostId
	 * @return array
	 */
	public function getPostPermissions($iPostId)
	{
		if (!$this->config->enable_group_perms)
		{
			return [];
		}

		$sQuery = 'SELECT post_id, group_id ' . 'FROM ' . $this->t_permissions . ' ' . 'WHERE post_id=' . (integer) $iPostId . ' ';

		if (($rs = $this->db->select($sQuery)) === false)
		{
			return [];
		}

		$aPerms = [];
		while ($rs->fetch())
		{
			$aPerms[] = $rs->group_id;
		}

		return $aPerms;
	}

	/**
	 * Met à jour les permissions d'un article donné.
	 *
	 * @param integer $iPostId
	 * @param array $aGroupsIds
	 * @return boolean
	 */
	protected function setPostPermissions($iPostId, $aGroupsIds)
	{
		if (!$this->config->enable_group_perms || empty($aGroupsIds))
		{
			return $this->setDefaultPostPermissions($iPostId);
		}

		if (!$this->postExists($iPostId))
		{
			$this->error->set(sprintf(__('m_news_post_%s_not_exists'), $iPostId));
			return false;
		}

		# si l'utilisateur qui définit les permissions n'est pas un admin
		# alors on force la permission à ce groupe admin
		if (!$this->okt['visitor']->is_admin)
		{
			$aGroupsIds[] = Groups::ADMIN;
		}

		# qu'une seule ligne par groupe pleaz
		$aGroupsIds = array_unique((array) $aGroupsIds);

		# liste des groupes existants réellement dans la base de données
		# (sauf invités et superadmin)
		$rsGroups = $this->okt['groups']->getGroups(array(
			'language' => $this->okt['visitor']->language,
			'group_id_not' => array(
				Groups::GUEST,
				Groups::SUPERADMIN
			)
		));

		$aGroups = [];
		while ($rsGroups->fetch())
		{
			$aGroups[] = $rsGroups->group_id;
		}
		unset($rsGroups);

		# suppression de toutes les permissions éventuellement existantes
		$this->deletePostPermissions($iPostId);

		# mise en base de données
		$return = true;
		foreach ($aGroupsIds as $iGroupId)
		{
			if ($iGroupId == 0 || in_array($iGroupId, $aGroups))
			{
				$return = $return && $this->setPostPermission($iPostId, $iGroupId);
			}
		}

		return $return;
	}

	/**
	 * Met les permissions par défaut d'un article donné.
	 *
	 * @param integer $iPostId
	 * @return boolean
	 */
	protected function setDefaultPostPermissions($iPostId)
	{
		if (!$this->postExists($iPostId))
		{
			$this->error->set(sprintf(__('m_news_post_%s_not_exists'), $iPostId));
			return false;
		}

		# suppression de toutes les permissions éventuellement existantes
		$this->deletePostPermissions($iPostId);

		# mise en base de données de la permission "tous" (0)
		return $this->setPostPermission($iPostId, 0);
	}

	/**
	 * Insertion d'une permission donnée pour un article donné.
	 *
	 * @param
	 *        	$iPostId
	 * @param
	 *        	$iGroupId
	 * @return boolean
	 */
	protected function setPostPermission($iPostId, $iGroupId)
	{
		$sQuery = 'INSERT INTO ' . $this->t_permissions . ' ' . '(post_id, group_id) ' . 'VALUES (' . (integer) $iPostId . ', ' . (integer) $iGroupId . ' ' . ') ';

		if (!$this->db->execute($sQuery))
		{
			throw new RuntimeException('Unable to insert post permissions into database');
		}

		return true;
	}

	/**
	 * Supprime les permissions d'un article donné.
	 *
	 * @param integer $iPostId
	 * @return boolean
	 */
	public function deletePostPermissions($iPostId)
	{
		$sQuery = 'DELETE FROM ' . $this->t_permissions . ' ' . 'WHERE post_id=' . (integer) $iPostId;

		if (!$this->db->execute($sQuery))
		{
			throw new RuntimeException('Unable to delete post permissions from database');
		}

		$this->db->optimize($this->t_permissions);

		return true;
	}

	/* Gestion des images des articles
	----------------------------------------------------------*/

	/**
	 * Retourne une instance de la classe oktImageUpload
	 *
	 * @return object oktImageUpload
	 */
	public function getImageUpload()
	{
		$o = new ImageUpload($this->okt, $this->config->images);
		$o->setConfig(array(
			'upload_dir' => $this->upload_dir . '/img',
			'upload_url' => $this->upload_url . '/img'
		));

		return $o;
	}

	/**
	 * Ajout d'image(s) à un article donné
	 *
	 * @param
	 *        	$iPostId
	 * @return boolean
	 */
	public function addImages($iPostId)
	{
		if (!$this->config->images['enable'])
		{
			return null;
		}

		$aImages = $this->getImageUpload()->addImages($iPostId);

		if (!$this->error->isEmpty())
		{
			return false;
		}

		return $this->updImagesInDb($iPostId, $aImages);
	}

	/**
	 * Modification d'image(s) d'un article donné
	 *
	 * @param
	 *        	$iPostId
	 * @return boolean
	 */
	public function updImages($iPostId)
	{
		if (!$this->config->images['enable'])
		{
			return null;
		}

		$aCurrentImages = $this->getImagesFromDb($iPostId);

		if (!$this->error->isEmpty())
		{
			return false;
		}

		$aImages = $this->getImageUpload()->updImages($iPostId, $aCurrentImages);

		if (!$this->error->isEmpty())
		{
			return false;
		}

		return $this->updImagesInDb($iPostId, $aImages);
	}

	/**
	 * Suppression d'une image donnée d'un article donné
	 *
	 * @param
	 *        	$iPostId
	 * @param
	 *        	$img_id
	 * @return boolean
	 */
	public function deleteImage($iPostId, $img_id)
	{
		$aCurrentImages = $this->getImagesFromDb($iPostId);

		if (!$this->error->isEmpty())
		{
			return false;
		}

		$aNewImages = $this->getImageUpload()->deleteImage($iPostId, $aCurrentImages, $img_id);

		if (!$this->error->isEmpty())
		{
			return false;
		}

		return $this->updImagesInDb($iPostId, $aNewImages);
	}

	/**
	 * Suppression des images d'un article donné
	 *
	 * @param
	 *        	$iPostId
	 * @return boolean
	 */
	public function deleteImages($iPostId)
	{
		$aCurrentImages = $this->getImagesFromDb($iPostId);

		if (!$this->error->isEmpty())
		{
			return false;
		}

		$this->getImageUpload()->deleteAllImages($iPostId, $aCurrentImages);

		return $this->updImagesInDb($iPostId);
	}

	/**
	 * Régénération de toutes les miniatures des images
	 *
	 * @return void
	 */
	public function regenMinImages()
	{
		@ini_set('memory_limit', - 1);
		set_time_limit(0);

		$rsPosts = $this->getPostsRecordset();

		while ($rsPosts->fetch())
		{
			$aImages = $rsPosts->getImagesInfo();
			$aImagesList = [];

			foreach ($aImages as $key => $image)
			{
				$this->getImageUpload()->buildThumbnails($rsPosts->id, $image['img_name']);

				$aImagesList[$key] = array_merge($aImages[$key], $this->getImageUpload()->buildImageInfos($rsPosts->id, $image['img_name']));
			}

			$this->updImagesInDb($rsPosts->id, $aImagesList);
		}

		return true;
	}

	/**
	 * Récupère la liste des images d'un article donné
	 *
	 * @param
	 *        	$iPostId
	 * @return array
	 */
	public function getImagesFromDb($iPostId)
	{
		if (!$this->postExists($iPostId))
		{
			$this->error->set(__('m_news_post_%s_not_exists'), $iPostId);
			return false;
		}

		$rsPost = $this->getPostsRecordset(array(
			'id' => $iPostId
		));

		$aImages = $rsPost->images ? unserialize($rsPost->images) : [];

		return $aImages;
	}

	/**
	 * Met à jours la liste des images d'un article donné.
	 *
	 * @param integer $iPostId
	 * @param array $aImages
	 * @return boolean
	 */
	public function updImagesInDb($iPostId, $aImages = [])
	{
		if (!$this->postExists($iPostId))
		{
			$this->error->set(__('m_news_post_%s_not_exists'), $iPostId);
			return false;
		}

		$aImages = !empty($aImages) ? serialize($aImages) : NULL;

		$sQuery = 'UPDATE ' . $this->t_news . ' SET ' . 'images=' . (!is_null($aImages) ? '\'' . $this->db->escapeStr($aImages) . '\'' : 'NULL') . ' ' . 'WHERE id=' . (integer) $iPostId;

		if (!$this->db->execute($sQuery))
		{
			return false;
		}

		return true;
	}

	/* Gestion des fichiers des articles
	----------------------------------------------------------*/

	/**
	 * Retourne une instance de la classe fileUpload
	 *
	 * @return object
	 */
	protected function getFileUpload()
	{
		return new FileUpload($this->okt, $this->config->files, $this->upload_dir . '/files', $this->upload_url . '/files');
	}

	/**
	 * Ajout de fichier(s) à un article donné
	 *
	 * @param
	 *        	$iPostId
	 * @return boolean
	 */
	public function addFiles($iPostId)
	{
		if (!$this->config->files['enable'])
		{
			return null;
		}

		$aFiles = $this->getFileUpload()->addFiles($iPostId);

		if (!$this->error->isEmpty())
		{
			return false;
		}

		return $this->updPostFiles($iPostId, $aFiles);
	}

	/**
	 * Modification de fichier(s) d'un article donné
	 *
	 * @param
	 *        	$iPostId
	 * @return boolean
	 */
	public function updFiles($iPostId)
	{
		if (!$this->config->files['enable'])
		{
			return null;
		}

		$aCurrentFiles = $this->getPostFiles($iPostId);

		if (!$this->error->isEmpty())
		{
			return false;
		}

		$aFiles = $this->getFileUpload()->updFiles($iPostId, $aCurrentFiles);

		if (!$this->error->isEmpty())
		{
			return false;
		}

		return $this->updPostFiles($iPostId, $aFiles);
	}

	/**
	 * Suppression d'un fichier donné d'un article donné
	 *
	 * @param
	 *        	$iPostId
	 * @param
	 *        	$file_id
	 * @return boolean
	 */
	public function deleteFile($iPostId, $file_id)
	{
		$aCurrentFiles = $this->getPostFiles($iPostId);

		if (!$this->error->isEmpty())
		{
			return false;
		}

		$aNewFiles = $this->getFileUpload()->deleteFile($iPostId, $aCurrentFiles, $file_id);

		if (!$this->error->isEmpty())
		{
			return false;
		}

		return $this->updPostFiles($iPostId, $aNewFiles);
	}

	/**
	 * Suppression des fichiers d'un article donné
	 *
	 * @param
	 *        	$iPostId
	 * @return boolean
	 */
	public function deleteFiles($iPostId)
	{
		$aCurrentFiles = $this->getPostFiles($iPostId);

		if (!$this->error->isEmpty())
		{
			return false;
		}

		$this->getFileUpload()->deleteAllFiles($aCurrentFiles);

		return $this->updPostFiles($iPostId);
	}

	/**
	 * Récupère la liste des fichiers d'un article donné
	 *
	 * @param
	 *        	$iPostId
	 * @return array
	 */
	public function getPostFiles($iPostId)
	{
		if (!$this->postExists($iPostId))
		{
			$this->error->set(__('m_news_post_%s_not_exists'), $iPostId);
			return false;
		}

		$rsPost = $this->getPostsRecordset(array(
			'id' => $iPostId
		));

		$aFiles = $rsPost->files ? unserialize($rsPost->files) : [];

		return $aFiles;
	}

	/**
	 * Met à jours la liste des fichiers d'un article donné
	 *
	 * @param integer $iPostId
	 * @param array $aFiles
	 * @return boolean
	 */
	public function updPostFiles($iPostId, $aFiles = [])
	{
		if (!$this->postExists($iPostId))
		{
			$this->error->set(__('m_news_post_%s_not_exists'), $iPostId);
			return false;
		}

		$aFiles = !empty($aFiles) ? serialize($aFiles) : NULL;

		$sQuery = 'UPDATE ' . $this->t_news . ' SET ' . 'files=' . (!is_null($aFiles) ? '\'' . $this->db->escapeStr($aFiles) . '\'' : 'NULL') . ' ' . 'WHERE id=' . (integer) $iPostId;

		if (!$this->db->execute($sQuery))
		{
			return false;
		}

		return true;
	}

	/* Utilitaires
	----------------------------------------------------------*/

	/**
	 * Retourne le chemin du template de la liste des actualités.
	 *
	 * @return string
	 */
	public function getListTplPath()
	{
		return 'News/list/' . $this->config->templates['list']['default'] . '/template';
	}

	/**
	 * Retourne le chemin du template du flux des actualités.
	 *
	 * @return string
	 */
	public function getFeedTplPath()
	{
		return 'News/feed/' . $this->config->templates['feed']['default'] . '/template';
	}

	/**
	 * Retourne le chemin du template de l'encart des actualités.
	 *
	 * @return string
	 */
	public function getInsertTplPath()
	{
		return 'News/insert/' . $this->config->templates['insert']['default'] . '/template';
	}

	/**
	 * Retourne le chemin du template de la liste des actualités d'une rubrique.
	 *
	 * @return string
	 */
	public function getCategoryTplPath($sCategoryTemplate = null)
	{
		$sTemplate = $this->config->templates['list']['default'];

		if (!empty($sCategoryTemplate) && in_array($sCategoryTemplate, $this->config->templates['list']['usables']))
		{
			$sTemplate = $sCategoryTemplate;
		}

		return 'News/list/' . $sTemplate . '/template';
	}

	/**
	 * Retourne le chemin du template d'une actualité.
	 *
	 * @return string
	 */
	public function getItemTplPath($sPostTemplate = null, $sCatPostTemplate = null)
	{
		$sTemplate = $this->config->templates['item']['default'];

		if (!empty($sPostTemplate) && in_array($sPostTemplate, $this->config->templates['item']['usables']))
		{
			$sTemplate = $sPostTemplate;
		}
		elseif (!empty($sCatPostTemplate) && in_array($sCatPostTemplate, $this->config->templates['item']['usables']))
		{
			$sTemplate = $sCatPostTemplate;
		}

		return 'News/item/' . $sTemplate . '/template';
	}

	/**
	 * Reconstruction des index de recherche de tous les articles.
	 */
	public function indexAllPosts()
	{
		$rsPosts = $this->db->select('SELECT post_id, language, title, subtitle, content FROM ' . $this->t_news_locales);

		while ($rsPosts->fetch())
		{
			$words = $rsPosts->title . ' ' . $rsPosts->subtitle . ' ' . $rsPosts->content . ' ';

			$words = implode(' ', Modifiers::splitWords($words));

			$query = 'UPDATE ' . $this->t_news . ' SET ' . 'words=\'' . $this->db->escapeStr($words) . '\' ' . 'WHERE post_id=' . (integer) $rsPosts->id . ' ' . 'AND language=\'' . $this->db->escapeStr($rsPosts->language) . '\' ';

			$this->db->execute($query);
		}

		return true;
	}
}
