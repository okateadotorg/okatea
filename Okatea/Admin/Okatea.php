<?php
/*
 * This file is part of Okatea.
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
namespace Okatea\Admin;

use Okatea\Admin\Menu as AdminMenu;
use Okatea\Admin\Page;
use Okatea\Tao\Application;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

class Okatea extends Application
{
	/**
	 * L'utilitaire de contenu de page.
	 *
	 * @var Okatea\Tao\Html\Page
	 */
	public $page;

	/**
	 * Run application.
	 */
	public function run()
	{
		# Load main locales files
		$this['l10n']->loadFile($this['locales_path'] . '/%s/main');
		$this['l10n']->loadFile($this['locales_path'] . '/%s/users');

		# Define templates directories
		$this['tpl_directories'] = [
			__DIR__ . '/Templates/%name%.php',
			$this['modules_path'] . '/%name%.php'
		];

		$this->loadPageHelpers();

		$this->matchRequest();

		if ($this->checkUser() === true)
		{
			$this->defineAdminPerms();

			$this->buildAdminMenu();

			$this['themes']->load('admin');

			$this['modules']->load('admin');

			$this->callController();
		}

		$this->sendResponse();
	}

	/**
	 * Init content page helpers.
	 *
	 * @return \Okatea\Website\Page
	 */
	protected function loadPageHelpers()
	{
		$this->page = new Page($this);
	}

	protected function checkUser()
	{
		# Validation du CSRF token si un formulaire est envoyé
		if (!$this['debug'] && count($this['request']->request) > 0 && (!$this['request']->request->has($this['csrf_token_name']) || !$this['session']->isValidToken($this['request']->request->get($this['csrf_token_name']))))
		{
			$this['flashMessages']->error(__('c_c_auth_bad_csrf_token'));

			$this['visitor']->logout();

			$this['logAdmin']->critical([
				'user_id' 	=> $this['visitor']->infos['id'],
				'username' 	=> $this['visitor']->infos['username'],
				'message' 	=> 'Security CSRF blocking',
				'code' 		=> 0
			]);

			$this->response = new RedirectResponse($this['adminRouter']->generate('login'));

			return false;
		}

		# Vérification de l'utilisateur en cours sur les parties de l'administration où l'utilisateur doit être identifié
		if ($this['request']->attributes->get('_route') !== 'login' && $this['request']->attributes->get('_route') !== 'forget_password')
		{
			# on stocke l'URL de la page dans un cookie
			$this['visitor']->setAuthFromCookie($this['request']->getUri());

			# si c'est un invité, il n'a rien à faire ici
			if ($this['visitor']->is_guest)
			{
				$this['flashMessages']->warning(__('c_c_auth_not_logged_in'));

				$this->response = new RedirectResponse($this['adminRouter']->generate('login'));

				return false;
			}

			# il faut au minimum la permission d'utilisation de l'interface d'administration
			elseif (!$this['visitor']->checkPerm('usage'))
			{
				$this['flashMessages']->error(__('c_c_auth_restricted_access'));

				$this['visitor']->logout();

				$this->response = new RedirectResponse($this['adminRouter']->generate('login'));

				return false;
			}

			# enfin, si on est en maintenance, il faut être superadmin
			elseif ($this['config']->maintenance['admin'] && !$this['visitor']->is_superadmin)
			{
				$this['flashMessages']->error(__('c_c_auth_maintenance_admin'));

				$this['visitor']->logout();

				$this->response = new RedirectResponse($this['adminRouter']->generate('login'));

				return false;
			}
		}

		return true;
	}

	protected function buildAdminMenu()
	{
		if (!$this->page->display_menu) {
			return null;
		}

		if ($this['config']->admin_menu_position != 'top')
		{
			Page::$formatHtmlMainMenu = [
				'block' => '<div%2$s>%1$s</div>',
				'item' => '<h2%3$s><a href="%2$s">%1$s</a></h2>%4$s',
				'active' => '<h2%3$s><a href="%2$s">%1$s</a></h2>%4$s',
				'separator' => '',
				'emptyBlock' => '<div%s>&nbsp;</div>'
			];

			Page::$formatHtmlSubMenu = [
				'block' => '<div%2$s><ul class="sub-menu">%1$s</ul></div>',
				'item' => '<li%3$s class=""><span class="ui-icon ui-icon-arrow-1-e"></span><a href="%2$s">%1$s</a>%4$s</li>',
				'active' => '<li%3$s class=""><span class="ui-icon ui-icon-arrowthick-1-e"></span><a href="%2$s"><strong>%1$s</strong></a>%4$s</li>',
				'separator' => '',
				'emptyBlock' => '<div%s>&nbsp;</div>'
			];
		}
		else
		{
			Page::$formatHtmlMainMenu = [
				'block' => '<ul%2$s>%1$s</ul>',
				'item' => '<li%3$s><a href="%2$s">%1$s</a>%4$s</li>',
				'active' => '<li%3$s><a href="%2$s"><strong>%1$s</strong></a>%4$s</li>',
				'separator' => '',
				'emptyBlock' => '<div%s>&nbsp;</div>'
			];
			Page::$formatHtmlSubMenu = [
				'block' => '<ul%2$s>%1$s</ul>',
				'item' => '<li%3$s><a href="%2$s">%1$s</a>%4$s</li>',
				'active' => '<li%3$s><a href="%2$s"><strong>%1$s</strong></a>%4$s</li>',
				'separator' => '',
				'emptyBlock' => '<div%s>&nbsp;</div>'
			];
		}

		# Menu principal
		$this->page->mainMenu = new AdminMenu('mainMenu-' . $this['config']->admin_menu_position, Page::$formatHtmlMainMenu);

		# Accueil
		$this->page->mainMenu->add(
			/* titre*/ 		__('c_a_menu_home'),
			/* URL */ 		$this['adminRouter']->generate('home'),
			/* actif ? */	$this['request']->attributes->get('_route') === 'home',
			/* position */	1,
			/* visible ? */	true,
			/* ID */ 		null,
			/* Sub */		($this->page->homeSubMenu = new AdminMenu(null, Page::$formatHtmlSubMenu)),
			/* Icon */		$this['public_url'] . '/img/admin/start-here.png');
		$this->page->homeSubMenu->add(__('c_a_menu_roundabout'), $this['adminRouter']->generate('home'), $this['request']->attributes->get('_route') === 'home', 10, true);

		# Users
		$this->page->mainMenu->add(__('c_a_menu_users'), $this['adminRouter']->generate('Users_index'), $this['request']->attributes->get('_route') === 'Users_index', 9000000, ($this['visitor']->checkPerm('users')), null, ($this->page->usersSubMenu = new AdminMenu(null, Page::$formatHtmlSubMenu)), $this['public_url'] . '/img/admin/users.png');
		$this->page->usersSubMenu->add(__('c_a_menu_management'), $this['adminRouter']->generate('Users_index'), in_array($this['request']->attributes->get('_route'), [
			'Users_index',
			'Users_add',
			'Users_edit'
		]), 10, $this['visitor']->checkPerm('users'));
		$this->page->usersSubMenu->add(__('c_a_menu_users_groups'), $this['adminRouter']->generate('Users_groups'), in_array($this['request']->attributes->get('_route'), [
			'Users_groups',
			'Users_groups_add',
			'Users_groups_edit'
		]), 20, $this['visitor']->checkPerm('users_groups'));
		/*
			$this->page->usersSubMenu->add(
				__('m_users_Custom_fields'),
				'module.php?m=users&amp;action=fields',
				$this->bCurrentlyInUse && ($this->page->action === 'fields' || $this->page->action === 'field'),
				30,
				$this['config']->enable_custom_fields && $this['visitor']->checkPerm('users_custom_fields')
			);
			$this->page->usersSubMenu->add(
				__('m_users_Export'),
				'module.php?m=users&amp;action=export',
				$this->bCurrentlyInUse && ($this->page->action === 'export'),
				40,
				$this['visitor']->checkPerm('users_export')
			);
			*/
		$this->page->usersSubMenu->add(__('c_a_menu_display'), $this['adminRouter']->generate('Users_display'), $this['request']->attributes->get('_route') === 'Users_display', 90, $this['visitor']->checkPerm('users_display'));
		$this->page->usersSubMenu->add(__('c_a_menu_configuration'), $this['adminRouter']->generate('Users_config'), $this['request']->attributes->get('_route') === 'Users_config', 100, $this['visitor']->checkPerm('users_config'));

		# Configuration
		$this->page->mainMenu->add(__('c_a_menu_configuration'), $this['adminRouter']->generate('config_general'), $this['request']->attributes->get('_route') === 'config_general', 10000000, $this['visitor']->checkPerm('configsite'), null, ($this->page->configSubMenu = new AdminMenu(null, Page::$formatHtmlSubMenu)), $this['public_url'] . '/img/admin/network-server.png');
		$this->page->configSubMenu->add(__('c_a_menu_general'), $this['adminRouter']->generate('config_general'), $this['request']->attributes->get('_route') === 'config_general', 10, $this['visitor']->checkPerm('configsite'));
		$this->page->configSubMenu->add(__('c_a_menu_display'), $this['adminRouter']->generate('config_display'), $this['request']->attributes->get('_route') === 'config_display', 20, $this['visitor']->checkPerm('display'));
		$this->page->configSubMenu->add(__('c_a_menu_localization'), $this['adminRouter']->generate('config_l10n'), in_array($this['request']->attributes->get('_route'), [
			'config_l10n',
			'config_l10n_add_language',
			'config_l10n_edit_language'
		]), 60, $this['visitor']->checkPerm('languages'));
		$this->page->configSubMenu->add(__('c_a_menu_modules'), $this['adminRouter']->generate('config_modules'), $this['request']->attributes->get('_route') === 'config_modules', 70, $this['visitor']->checkPerm('modules'));
		$this->page->configSubMenu->add(__('c_a_menu_themes'), $this['adminRouter']->generate('config_themes'), in_array($this['request']->attributes->get('_route'), [
			'config_themes',
			'config_theme',
			'config_theme_add'
		]), 80, $this['visitor']->checkPerm('themes'));
		$this->page->configSubMenu->add(__('c_a_menu_navigation'), $this['adminRouter']->generate('config_navigation'), $this['request']->attributes->get('_route') === 'config_navigation', 90, $this['visitor']->checkPerm('navigation'));
		$this->page->configSubMenu->add(__('c_a_menu_permissions'), $this['adminRouter']->generate('config_permissions'), $this['request']->attributes->get('_route') === 'config_permissions', 100, $this['visitor']->checkPerm('permissions'));
		$this->page->configSubMenu->add(__('c_a_menu_tools'), $this['adminRouter']->generate('config_tools'), $this['request']->attributes->get('_route') === 'config_tools', 110, $this['visitor']->checkPerm('tools'));
		$this->page->configSubMenu->add(__('c_a_menu_infos'), $this['adminRouter']->generate('config_infos'), $this['request']->attributes->get('_route') === 'config_infos', 120, $this['visitor']->checkPerm('infos'));
		$this->page->configSubMenu->add(__('c_a_menu_update'), $this['adminRouter']->generate('config_update'), $this['request']->attributes->get('_route') === 'config_update', 130, $this['config']->updates['enabled'] && $this['visitor']->checkPerm('is_superadmin'));
		$this->page->configSubMenu->add(__('c_a_menu_log_admin'), $this['adminRouter']->generate('config_logadmin'), $this['request']->attributes->get('_route') === 'config_logadmin', 140, $this['visitor']->checkPerm('is_superadmin'));
		$this->page->configSubMenu->add(__('c_a_menu_router'), $this['adminRouter']->generate('config_router'), $this['request']->attributes->get('_route') === 'config_router', 150, $this['visitor']->checkPerm('is_superadmin'));
		$this->page->configSubMenu->add(__('c_a_menu_advanced'), $this['adminRouter']->generate('config_advanced'), $this['request']->attributes->get('_route') === 'config_advanced', 160, $this['visitor']->checkPerm('is_superadmin'));
	}

	protected function defineAdminPerms()
	{
		$this['permissions']->addPerm('usage', __('c_a_def_perm_usage'));

		$this['permissions']->addPermGroup('users', __('c_a_def_perm_users_group'));
		$this['permissions']->addPerm('users', __('c_a_def_perm_users_global'), 'users');
		$this['permissions']->addPerm('users_edit', __('c_a_def_perm_users_edit'), 'users');
		$this['permissions']->addPerm('users_delete', __('c_a_def_perm_users_delete'), 'users');
		$this['permissions']->addPerm('change_password', __('c_a_def_perm_users_change_password'), 'users');
		$this['permissions']->addPerm('users_groups', __('c_a_def_perm_users_groups'), 'users');
		$this['permissions']->addPerm('users_custom_fields', __('c_a_def_perm_users_custom_fields'), 'users');
		$this['permissions']->addPerm('users_export', __('c_a_def_perm_users_export'), 'users');
		$this['permissions']->addPerm('users_display', __('c_a_def_perm_users_display'), 'users');
		$this['permissions']->addPerm('users_config', __('c_a_def_perm_users_config'), 'users');

		$this['permissions']->addPermGroup('configuration', __('c_a_def_perm_config'));
		$this['permissions']->addPerm('configsite', __('c_a_config_router_route_controller'), 'configuration');
		$this['permissions']->addPerm('display', __('c_a_def_perm_config_display'), 'configuration');
		$this['permissions']->addPerm('languages', __('c_a_def_perm_config_local'), 'configuration');
		$this['permissions']->addPerm('modules', __('c_a_def_perm_config_modules'), 'configuration');
		$this['permissions']->addPerm('themes', __('c_a_def_perm_config_themes'), 'configuration');
		$this['permissions']->addPerm('themes_editor', __('c_a_def_perm_config_themes_editor'), 'configuration');
		$this['permissions']->addPerm('navigation', __('c_a_def_perm_config_navigation'), 'configuration');
		$this['permissions']->addPerm('permissions', __('c_a_def_perm_config_perms'), 'configuration');
		$this['permissions']->addPerm('tools', __('c_a_def_perm_config_tools'), 'configuration');
		$this['permissions']->addPerm('infos', __('c_a_def_perm_config_infos'), 'configuration');
	}

	/**
	 * Résolution de la route à utiliser
	 */
	protected function matchRequest()
	{
		# -- CORE TRIGGER : adminBeforeMatchRequest
		$this['triggers']->callTrigger('adminBeforeMatchRequest');

		try
		{
			$matchRequest = $this['adminRouter']->matchRequest($this['request']);

			$attributes = [];
			$attributes['requestParameters'] = array_diff($matchRequest, [
				'controller' 	=> $matchRequest['controller'],
				'_route' 		=> $matchRequest['_route']
			]);

			$this['request']->attributes->add(array_merge($matchRequest, $attributes));
		}
		catch (ResourceNotFoundException $e)
		{
			$this->page->serve404();
		}
		catch (\Exception $e)
		{
			$this->response->headers->set('Content-Type', 'text/plain');
			$this->response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
			$this->response->setContent($e->getMessage());
		}
	}

	protected function callController()
	{
		# -- CORE TRIGGER : adminBeforeCallController
		$this['triggers']->callTrigger('adminBeforeCallController');

		# Special case : user lang switch
		if (null !== $sLanguage = $this['request']->query->get('lang'))
		{
			$this['visitor']->setUserLang($sLanguage);

			$this->response = new RedirectResponse($this['adminRouter']->generate($this['request']->attributes->get('_route'), $this['request']->attributes->get('requestParameters')));
		}
		# else, call the controller
		else
		{
			$this->response = $this['adminRouter']->callController();
		}

		if (null === $this->response || false === $this->response)
		{
			$this->response = new Response();
			$this->response->headers->set('Content-Type', 'text/plain');
			$this->response->setStatusCode(Response::HTTP_NOT_IMPLEMENTED);
			$this->response->setContent('Unable to load controller.');
		}
	}

	protected function sendResponse()
	{
		# -- CORE TRIGGER : adminBeforePrepareResponse
		$this['triggers']->callTrigger('adminBeforePrepareResponse');

		$this->response->prepare($this['request']);

		# -- CORE TRIGGER : adminBeforeSendResponse
		$this['triggers']->callTrigger('adminBeforeSendResponse');

		$this->response->send();
	}
}
