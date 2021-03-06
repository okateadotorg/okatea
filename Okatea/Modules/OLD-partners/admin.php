<?php
/**
 * @ingroup okt_module_partners
 * @brief La page d'administration du module partners
 *
 */

# Accès direct interdit
if (!defined('ON_MODULE'))
	die();
	
	# Perms ?
if (!$okt['visitor']->checkPerm('partners'))
{
	http::redirect(OKT_ADMIN_LOGIN_PAGE);
}

# suppression d'un partenaire
if ($okt->page->action === 'delete' && !empty($_GET['partner_id']) && $okt['visitor']->checkPerm('partners_remove'))
{
	if ($okt->partners->deletePartner($_GET['partner_id']))
	{
		$okt['flashMessages']->success(__('m_partners_deleted'));
		
		http::redirect('module.php?m=partners&action=index');
	}
	else
	{
		$okt->page->action = 'index';
	}
}

# title tag
$okt->page->addTitleTag($okt->partners->getTitle());

# fil d'ariane
$okt->page->addAriane($okt->partners->getName(), 'module.php?m=partners');

# button set
$okt->page->setButtonset('partnersBtSt', array(
	'id' => 'partners-buttonset',
	'type' => '', #  buttonset-single | buttonset-multi | ''
	'buttons' => array(
		array(
			'permission' => ($okt->page->action !== 'add') && $okt['visitor']->checkPerm('partners_add'),
			'title' => __('m_partners_add_partner'),
			'url' => 'module.php?m=partners&amp;action=add',
			'ui-icon' => 'plusthick',
			'active' => ($okt->page->action === 'add')
		)
	)
));

# inclusion du fichier requis en fonction de l'action demandée
if ($okt->page->action === 'add' && $okt['visitor']->checkPerm('partners_add'))
{
	require __DIR__ . '/admin/partner.php';
}
elseif ($okt->page->action === 'edit')
{
	require __DIR__ . '/admin/partner.php';
}
elseif ($okt->page->action === 'categories' && $okt->partners->config->enable_categories && $okt['visitor']->checkPerm('partners_add'))
{
	require __DIR__ . '/admin/categories.php';
}
elseif ($okt->page->action === 'display' && $okt['visitor']->checkPerm('partners'))
{
	require __DIR__ . '/admin/display.php';
}
elseif ($okt->page->action === 'config' && $okt['visitor']->checkPerm('partners_config'))
{
	require __DIR__ . '/admin/config.php';
}
else
{
	require __DIR__ . '/admin/index.php';
}
