<?php
/*
 * This file is part of Okatea.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Okatea\Admin\Controller\Config;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Okatea\Admin\Controller;
use Tao\Core\HttpClient;
use Tao\Misc\Utilities;
use Tao\Modules\Collection as ModulesCollection;
use Tao\Themes\Collection as ThemesCollection;

class Modules extends Controller
{
	protected $aAllModules;

	protected $aInstalledModules;

	protected $aUninstalledModules;

	protected $aUpdatablesModules;

	public function page()
	{
		if (!$this->okt->checkPerm('modules')) {
			return $this->serve401();
		}

		$this->init();

		# Affichage changelog
		if (($showChangelog = $this->showChangelog()) !== false) {
			return $showChangelog;
		}

		# Enable a module
		if (($enableModule = $this->enableModule()) !== false) {
			return $enableModule;
		}

		# Disable a module
		if (($disableModule = $this->disableModule()) !== false) {
			return $disableModule;
		}

		# Install a module
		if (($installModule = $this->installModule()) !== false) {
			return $installModule;
		}

		# Update a module
		if (($updateModule = $this->updateModule()) !== false) {
			return $updateModule;
		}

		# Uninstall a module
		if (($uninstallModule = $this->uninstallModule()) !== false) {
			return $uninstallModule;
		}

		# Re-install a module
		if (($reinstallModule = $this->reinstallModule()) !== false) {
			return $reinstallModule;
		}

		# Install test set of a module
		if (($installTestSet = $this->installTestSet()) !== false) {
			return $installTestSet;
		}

		# Install default data of a module
		if (($installDefaultData = $this->installDefaultData()) !== false) {
			return $installDefaultData;
		}

		# Remove content of a module
		if (($removeModuleContent = $this->removeModuleContent()) !== false) {
			return $removeModuleContent;
		}

		# Remove a module
		if (($removeModule = $this->removeModule()) !== false) {
			return $removeModule;
		}

		# Replace templates files of a module by its default ones
		if (($replaceTemplatesFiles = $this->replaceTemplatesFiles()) !== false) {
			return $replaceTemplatesFiles;
		}

		# Replace assets files of a module by its default ones
		if (($replaceAssetsFiles = $this->replaceAssetsFiles()) !== false) {
			return $replaceAssetsFiles;
		}

		# Package and send a module
		if (($packageAndSendModule = $this->packageAndSendModule()) !== false) {
			return $packageAndSendModule;
		}

		# Compare module files
		if (($compareFiles = $this->compareFiles()) !== false) {
			return $compareFiles;
		}

		# Add a module to the system
		if (($moduleUpload = $this->moduleUpload()) !== false) {
			return $moduleUpload;
		}

		return $this->render('Config/Modules', array(
			'aAllModules' => $this->aAllModules,
			'aInstalledModules' => $this->aInstalledModules,
			'aUninstalledModules' => $this->aUninstalledModules,
			'aUpdatablesModules' => $this->aUpdatablesModules
		));
	}

	protected function init()
	{
		# Modules locales
		$this->okt->l10n->loadFile($this->okt->options->locales_dir.'/'.$this->okt->user->language.'/admin.modules');

		# Récupération de la liste des modules dans le système de fichiers (tous les modules)
		$this->aAllModules = $this->okt->modules->getModulesFromFileSystem();

		# Load all modules admin locales files
		foreach ($this->aAllModules as $id=>$infos) {
			$this->okt->l10n->loadFile($infos['root'].'/locales/'.$this->okt->user->language.'/main');
		}

		# Récupération de la liste des modules dans la base de données (les modules installés)
		$this->aInstalledModules = $this->okt->modules->getInstalledModules();

		# Calcul de la liste des modules non-installés
		$this->aUninstalledModules = array_diff_key($this->aAllModules,$this->aInstalledModules);

		foreach ($this->aUninstalledModules as $sModuleId=>$aModuleInfos) {
			$this->aUninstalledModules[$sModuleId]['name_l10n'] = __($aModuleInfos['name']);
		}

		# Liste des dépôts de modules
		$aModulesRepositories = array();
		if ($this->okt->config->modules_repositories_enabled)
		{
			$aRepositories = $this->okt->config->modules_repositories;
			$aModulesRepositories = $this->okt->modules->getRepositoriesInfos($aRepositories);
		}

		# Liste des éventuelles mise à jours disponibles sur les dépots
		$this->aUpdatablesModules = array();
		foreach ($aModulesRepositories as $repo_name=>$modules)
		{
			foreach ($modules as $module)
			{
				$aModulesRepositories[$repo_name][$module['id']]['name_l10n'] = $module['name'];

				if (isset($this->aAllModules[$module['id']]) && $this->aAllModules[$module['id']]['updatable'] && version_compare($this->aAllModules[$module['id']]['version'],$module['version'], '<'))
				{
					$this->aUpdatablesModules[$module['id']] = array(
						'id' => $module['id'],
						'name' => $module['name'],
						'version' => $module['version'],
						'info' => $module['info'],
						'repository' => $repo_name
					);
				}
			}
		}

		# Tri par ordre alphabétique des listes de modules
		ModulesCollection::sortModules($this->aInstalledModules);
		ModulesCollection::sortModules($this->aUninstalledModules);

		foreach ($aModulesRepositories as $repo_name=>$modules) {
			ModulesCollection::sortModules($aModulesRepositories[$repo_name]);
		}
	}

	protected function showChangelog()
	{
		$sModuleId = $this->request->query->get('show_changelog');
		$sChangelogFile = $this->okt->modules->path.'/'.$sModuleId.'/CHANGELOG';

		if (!$sModuleId || !file_exists($sChangelogFile)) {
			return false;
		}

		$sChangelogContent = '<pre class="changelog">'.file_get_contents($sChangelogFile).'</pre>';

		return $this->response->setContent($sChangelogContent);
	}

	protected function enableModule()
	{
		$sModuleId = $this->request->query->get('enable');

		if (!$sModuleId || !array_key_exists($sModuleId, $this->aInstalledModules)) {
			return false;
		}

		$this->okt->modules->enableModule($sModuleId);

		# vidange du cache global
		Utilities::deleteOktCacheFiles();

		# log admin
		$this->okt->logAdmin->warning(array(
			'code' => 30,
			'message' => $sModuleId
		));

		return $this->redirect($this->generateUrl('config_modules'));
	}

	protected function disableModule()
	{
		$sModuleId = $this->request->query->get('disable');

		if (!$sModuleId || !array_key_exists($sModuleId, $this->aInstalledModules)) {
			return false;
		}

		$this->okt->modules->disableModule($sModuleId);

		# vidange du cache global
		Utilities::deleteOktCacheFiles();

		# log admin
		$this->okt->logAdmin->warning(array(
			'code' => 31,
			'message' => $sModuleId
		));

		return $this->redirect($this->generateUrl('config_modules'));
	}

	protected function installModule()
	{
		$sModuleId = $this->request->query->get('install');

		if (!$sModuleId || !array_key_exists($sModuleId, $this->aUninstalledModules)) {
			return false;
		}

		@ini_set('memory_limit',-1);
		set_time_limit(0);

		$sInstallClassName = $this->okt->modules->getInstallClass($sModuleId);
		$oInstallModule = new $sInstallClassName($this->okt, $this->okt->options->get('modules_dir'), $sModuleId);
		$oInstallModule->doInstall();

		# activation du module
		$oInstallModule->checklist->addItem(
			'add_module_to_db',
			$this->okt->modules->enableModule($sModuleId),
			'Enable module',
			'Cannot enable module'
		);

		# vidange du cache global
		Utilities::deleteOktCacheFiles();

		if ($oInstallModule->checklist->checkAll()) {
			$this->okt->page->success->set(__('c_a_modules_correctly_installed'));
		}
		else {
			$this->okt->error->set(__('c_a_modules_not_installed'));
		}

		# log admin
		$this->okt->logAdmin->warning(array(
			'code' => 20,
			'message' => $sModuleId
		));

		return $this->render('Config/Modules/Install', array(
			'oInstallModule' => $oInstallModule
		));
	}

	protected function updateModule()
	{
		$sModuleId = $this->request->query->get('update');

		if (!$sModuleId || !array_key_exists($sModuleId, $this->aInstalledModules)) {
			return false;
		}

		# D'abord on active le module
		if (!$this->okt->modules->moduleExists($sModuleId))
		{
			$this->okt->modules->enableModule($sModuleId);

			$this->okt->modules->generateCacheList();

			return $this->redirect($this->generateUrl('config_modules').'?reinstall='.$sModuleId);
		}

		# Ensuite on met à jour
		$sInstallClassName = $this->okt->modules->getInstallClass($sModuleId);
		$oInstallModule = new $sInstallClassName($this->okt, $this->okt->options->get('modules_dir'), $sModuleId);
		$oInstallModule->doUpdate();

		# Confirmations
		if ($oInstallModule->checklist->checkAll()) {
			$this->okt->page->success->set(__('c_a_modules_correctly_updated'));
		}
		else {
			$this->okt->error->set(__('c_a_modules_not_updated'));
		}

		# vidange du cache global
		Utilities::deleteOktCacheFiles();

		# log admin
		$this->okt->logAdmin->critical(array(
			'code' => 21,
			'message' => $sModuleId
		));

		$sNextUrl = $this->generateUrl('config_modules');

		if (file_exists($oInstallModule->root().'/install/tpl/') || file_exists($oInstallModule->root().'/install/assets/')) {
			$sNextUrl .= '?compare='.$oInstallModule->id();
		}

		return $this->render('Config/Modules/Update', array(
			'oInstallModule' => $oInstallModule,
			'sNextUrl' => $sNextUrl
		));
	}

	protected function uninstallModule()
	{
		$sModuleId = $this->request->query->get('uninstall');

		if (!$sModuleId || !array_key_exists($sModuleId, $this->aInstalledModules)) {
			return false;
		}

		@ini_set('memory_limit',-1);
		set_time_limit(0);

		$sInstallClassName = $this->okt->modules->getInstallClass($sModuleId);
		$oInstallModule = new $sInstallClassName($this->okt, $this->okt->options->get('modules_dir'), $sModuleId);
		$oInstallModule->doUninstall();

		# vidange du cache global
		Utilities::deleteOktCacheFiles();

		if ($oInstallModule->checklist->checkAll()) {
			$this->okt->page->success->set(__('c_a_modules_correctly_uninstalled'));
		}
		else {
			$this->okt->error->set(__('c_a_modules_not_uninstalled'));
		}

		# log admin
		$this->okt->logAdmin->critical(array(
			'code' => 22,
			'message' => $sModuleId
		));

		return $this->render('Config/Modules/Uninstall', array(
			'oInstallModule' => $oInstallModule
		));
	}

	protected function reinstallModule()
	{
		$sModuleId = $this->request->query->get('reinstall');

		if (!$sModuleId || !array_key_exists($sModuleId, $this->aInstalledModules)) {
			return false;
		}

		@ini_set('memory_limit',-1);
		set_time_limit(0);

		# il faut d'abord désactiver le module
		if ($this->aInstalledModules[$sModuleId]['status'])
		{
			$this->okt->modules->disableModule($sModuleId);

			# cache de la liste de module
			$this->okt->modules->generateCacheList();

			return $this->redirect($this->generateUrl('config_modules').'?reinstall='.$sModuleId);
		}

		$sInstallClassName = $this->okt->modules->getInstallClass($sModuleId);
		$oInstallModule = new $sInstallClassName($this->okt, $this->okt->options->get('modules_dir'), $sModuleId);

		# désinstallation
		$oInstallModule->doUninstall();

		# installation
		$oInstallModule->doInstall();

		# activation du module
		$oInstallModule->checklist->addItem(
			'add_module_to_db',
			$this->okt->modules->enableModule($sModuleId),
			'Enable module',
			'Cannot enable module'
		);

		# vidange du cache global
		Utilities::deleteOktCacheFiles();

		if ($oInstallModule->checklist->checkAll()) {
			$this->okt->page->success->set(__('c_a_modules_correctly_reinstalled'));
		}
		else {
			$this->okt->error->set(__('c_a_modules_not_correctly_reinstalled.'));
		}

		# log admin
		$this->okt->logAdmin->critical(array(
			'code' => 23,
			'message' => $sModuleId
		));

		return $this->render('Config/Modules/Reinstall', array(
			'oInstallModule' => $oInstallModule
		));
	}

	protected function installTestSet()
	{
		$sModuleId = $this->request->query->get('testset');

		if (!$sModuleId || !array_key_exists($sModuleId, $this->aInstalledModules)) {
			return false;
		}

		@ini_set('memory_limit',-1);
		set_time_limit(0);

		$sInstallClassName = $this->okt->modules->getInstallClass($sModuleId);
		$oInstallModule = new $sInstallClassName($this->okt, $this->okt->options->get('modules_dir'), $sModuleId);

		# d'abord on vident le module
		$oInstallModule->doEmpty();

		# ensuite on installent les données par défaut
		$oInstallModule->doInstallDefaultData();

		# et ensuite on installent le jeu de test
		$oInstallModule->doInstallTestSet();

		if ($oInstallModule->checklist->checkAll()) {
			$this->okt->page->success->set(__('c_a_modules_test_set_correctly_installed'));
		}
		else {
			$this->okt->error->set(__('c_a_modules_test_set_not_correctly_installed'));
		}

		# log admin
		$this->okt->logAdmin->critical(array(
			'message' => 'install test set '.$sModuleId
		));

		return $this->render('Config/Modules/InstallTestSet', array(
			'oInstallModule' => $oInstallModule
		));
	}

	protected function installDefaultData()
	{
		$sModuleId = $this->request->query->get('defaultdata');

		if (!$sModuleId || !array_key_exists($sModuleId, $this->aInstalledModules)) {
			return false;
		}

		@ini_set('memory_limit',-1);
		set_time_limit(0);

		$sInstallClassName = $this->okt->modules->getInstallClass($sModuleId);
		$oInstallModule = new $sInstallClassName($this->okt, $this->okt->options->get('modules_dir'), $sModuleId);

		# on installent les données par défaut
		$oInstallModule->doInstallDefaultData();

		if ($oInstallModule->checklist->checkAll()) {
			$this->okt->page->success->set(__('c_a_modules_test_set_correctly_installed'));
		}
		else {
			$this->okt->error->set(__('c_a_modules_test_set_not_correctly_installed'));
		}

		# log admin
		$this->okt->logAdmin->warning(array(
			'message' => 'install default data '.$sModuleId
		));

		return $this->render('Config/Modules/InstallDefaultData', array(
			'oInstallModule' => $oInstallModule
		));
	}

	protected function removeModuleContent()
	{
		$sModuleId = $this->request->query->get('empty');

		if (!$sModuleId || !array_key_exists($sModuleId, $this->aInstalledModules)) {
			return false;
		}

		@ini_set('memory_limit',-1);
		set_time_limit(0);

		$sInstallClassName = $this->okt->modules->getInstallClass($sModuleId);
		$oInstallModule = new $sInstallClassName($this->okt, $this->okt->options->get('modules_dir'), $sModuleId);
		$oInstallModule->doEmpty();

		if ($oInstallModule->checklist->checkAll()) {
			$this->okt->page->success->set(__('c_a_modules_correctly_emptied'));
		}
		else {
			$this->okt->error->set(__('c_a_modules_not_correctly_emptied'));
		}

		# log admin
		$this->okt->logAdmin->critical(array(
			'message' => 'remove content of module '.$sModuleId
		));

		return $this->render('Config/Modules/RemoveContent', array(
			'oInstallModule' => $oInstallModule
		));
	}

	protected function removeModule()
	{
		$sModuleId = $this->request->query->get('delete');

		if (!$sModuleId || !array_key_exists($sModuleId, $this->aUninstalledModules)) {
			return false;
		}

		if (\files::deltree($this->okt->modules->path.'/'.$sModuleId))
		{
			$this->okt->page->flash->success(__('c_a_modules_successfully_deleted'));

			# log admin
			$this->okt->logAdmin->warning(array(
				'code' => 42,
				'message' => $sModuleId
			));

			return $this->redirect($this->generateUrl('config_modules'));
		}
		else {
			$this->okt->error->set(__('c_a_modules_not_deleted.'));
		}
	}

	protected function replaceTemplatesFiles()
	{
		$sModuleId = $this->request->query->get('templates');

		if (!$sModuleId || !array_key_exists($sModuleId, $this->aInstalledModules)) {
			return false;
		}

		$sInstallClassName = $this->okt->modules->getInstallClass($sModuleId);
		$oInstallModule = new $sInstallClassName($this->okt, $this->okt->options->get('modules_dir'), $sModuleId);
		$oInstallModule->forceReplaceTpl();

		# cache de la liste de module
		$this->okt->modules->generateCacheList();

		$this->okt->page->flash->success(__('c_a_modules_templates_files_replaced'));

		return $this->redirect($this->generateUrl('config_modules'));
	}

	protected function replaceAssetsFiles()
	{
		$sModuleId = $this->request->query->get('common');

		if (!$sModuleId || !array_key_exists($sModuleId, $this->aInstalledModules)) {
			return false;
		}

		$sInstallClassName = $this->okt->modules->getInstallClass($sModuleId);
		$oInstallModule = new $sInstallClassName($this->okt, $this->okt->options->get('modules_dir'), $sModuleId);

		$oInstallModule->forceReplaceAssets();

		# cache de la liste de module
		$this->okt->modules->generateCacheList();

		$this->okt->page->flash->success(__('c_a_modules_common_files_replaced'));

		return $this->redirect($this->generateUrl('config_modules'));
	}

	protected function packageAndSendModule()
	{
		$sModuleId = $this->request->query->get('download');

		if (!$sModuleId || !array_key_exists($sModuleId, $this->aAllModules)) {
			return false;
		}

		$sModulePath = $this->okt->options->get('modules_dir').'/'.$sModuleId;

		if (!is_readable($sModulePath) ) {
			return false;
		}

		$sFilename = 'module-'.$sModuleId.'-'.date('Y-m-d-H-i').'.zip';

		ob_start();

		$fp = fopen('php://output', 'wb');

		$zip = new \fileZip($fp);
		$zip->addDirectory($sModulePath, '', true);

		$zip->write();

		$this->response->headers->set('Content-Disposition',
				$this->response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $sFilename));

		$this->response->setContent(ob_get_clean());

		return $this->response;
	}

	protected function compareFiles()
	{
		$sModuleId = $this->request->query->get('compare');

		if (!$sModuleId || !array_key_exists($sModuleId, $this->aInstalledModules)) {
			return false;
		}

		$sInstallClassName = $this->okt->modules->getInstallClass($sModuleId);
		$oInstallModule = new $sInstallClassName($this->okt, $this->okt->options->get('modules_dir'), $sModuleId);
		$oInstallModule->compareFiles();

		return $this->render('Config/Modules/Compare', array(
			'oInstallModule' => $oInstallModule
		));
	}

	protected function moduleUpload()
	{
		$upload_pkg = $this->request->request->get('upload_pkg');
		$pkg_file = $this->request->files->get('pkg_file');

		$fetch_pkg = $this->request->request->get('fetch_pkg');
		$pkg_url = $this->request->request->get('pkg_url');

		$repository = $this->request->query->get('repository');
		$module = $this->request->query->get('module');

		# Plugin upload
		if (($upload_pkg && $pkg_file) || ($fetch_pkg && $pkg_url) ||
			($repository && $module && $this->okt->config->modules_repositories_enabled))
		{
			try
			{
				if ($upload_pkg)
				{
					if (array_key_exists($pkg_file->getClientOriginalName(), $this->aUninstalledModules)) {
						throw new Exception(__('c_a_modules_module_already_exists_not_installed_install_before_update'));
					}

					$pkg_file->move($this->okt->options->get('modules_dir'));
				}
				else
				{
					if ($repository && $module)
					{
						$repository = urldecode($repository);
						$module = urldecode($module);
						$url = urldecode($aModulesRepositories[$repository][$module]['href']);
					}
					else {
						$url = urldecode($pkg_url);
					}

					$dest = $this->okt->options->get('modules_dir').'/'.basename($url);

					if (array_key_exists(basename($url), $aUninstalledModules)) {
						throw new Exception(__('c_a_modules_module_already_exists_not_installed_install_before_update'));
					}

					try
					{
						$client = new HttpClient();

						$request = $client->get($url, array(), array(
							'save_to' => $dest
						));
					}
					catch( Exception $e) {
						throw new Exception(__('An error occurred while downloading the file.'));
					}

					unset($client);
				}

				$ret_code = $this->okt->modules->installPackage($dest, $this->okt->modules);

				if ($ret_code == 2) {
					$this->okt->page->flash->success(__('c_a_modules_module_successfully_upgraded'));
				}
				else {
					$this->okt->page->flash->success(__('c_a_modules_module_successfully_added'));
				}

				return $this->redirect($this->generateUrl('config_modules'));
			}
			catch (Exception $e)
			{
				$this->okt->error->set($e->getMessage());
				return false;
			}
		}

		return false;
	}
}