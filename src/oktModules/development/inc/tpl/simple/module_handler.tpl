<?php
##header##


use Tao\Modules\Module;
use Tao\Routing\Route;


class module_##module_id## extends Module
{
	public $config;

	protected function prepend()
	{
		# chargement des principales locales
		//l10n::set(__DIR__.'/locales/'.$this->okt->user->language.'/main');

		# autoload
		$this->okt->autoloader->addClassMap(array(
			'##module_camel_case_id##Controller' => __DIR__.'/inc/class.##module_id##.controller.php'
		));

		# config
		$this->config = $this->okt->newConfig('conf_##module_id##');

		$this->config->url = $this->okt->page->getBaseUrl().$this->config->public_url[$this->okt->user->language];

		# définition des routes
		$this->okt->router->addRoute('##module_camel_case_id##Page', new Route(
			'^('.html::escapeHTML(implode('|',$this->config->public_url)).')$',
			'##module_camel_case_id##Controller', '##module_camel_case_id##Page'
		));
	}

	protected function prepend_admin()
	{
		# on détermine si on est actuellement sur ce module
		$this->onThisModule();

		# chargement des locales admin
		l10n::set(__DIR__.'/locales/'.$this->okt->user->language.'/admin');

		# on ajoutent un item au menu admin
		if (!defined('OKT_DISABLE_MENU'))
		{
			$this->okt->page->configSubMenu->add(
				$this->getName(),
				'module.php?m=##module_id##&amp;action=config',
				ON_##module_upper_id##_MODULE && ($this->okt->page->action === 'config'),
				22,
				$this->okt->checkPerm('is_superadmin'),
				null
			);
		}
	}

	protected function prepend_public()
	{
	}


}
