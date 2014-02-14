<?php
/*
 * This file is part of Okatea.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Okatea\Admin\Controller\Users;

use Okatea\Admin\Controller;
use Okatea\Tao\Users\Authentification;
use Okatea\Tao\Users\Groups as UsersGroups;

class Groups extends Controller
{
	public function index()
	{
		$this->init();

		if ($this->okt->request->query->has('delete_id'))
		{
			$iGroupIdToDelete = $this->okt->request->query->get('delete_id');

			if ($this->okt->getGroups()->deleteGroup($iGroupIdToDelete))
			{
				$this->okt->page->flash->success(__('c_a_users_group_deleted'));

				return $this->redirect($this->generateUrl('Users_groups'));
			}
		}

		$aParams = array();

		if (!$this->okt->user->is_superadmin) {
			$aparams['group_id_not'][] = UsersGroups::SUPERADMIN;
		}

		if (!$this->okt->user->is_admin) {
			$aparams['group_id_not'][] = UsersGroups::ADMIN;
		}

		$rsGroups = $this->okt->getGroups()->getGroups($aParams);

		return $this->render('Users/Groups/Index', array(
			'rsGroups'       => $rsGroups
		));
	}

	public function add()
	{
		$this->init();

		$title = '';

		if ($this->okt->request->request->has('form_sent'))
		{
			$title = $this->okt->request->request->get('title');

			$aPerms = array();
			if ($this->okt->request->request->has('perms')) {
				$aPerms = array_keys($this->okt->request->request->get('perms'));
			}

			if (empty($title)) {
				$this->okt->error->set(__('c_a_users_must_enter_group_title'));
			}

			if ($this->okt->error->isEmpty())
			{
				if (($iGroupId = $this->okt->getGroups()->addGroup($title)) !== false
					&& $this->okt->getGroups()->updGroupPerms($iGroupId, $aPerms))
				{
					$this->okt->page->flash->success(__('c_a_users_group_added'));

					return $this->redirect($this->generateUrl('Users_groups_edit', array('group_id' => $iGroupId)));
				}
			}
		}

		return $this->render('Users/Groups/Add', array(
			'title'          => $title,
			'aPermissions'   => $this->okt->getPermsForDisplay()
		));
	}

	public function edit()
	{
		$this->init();

		$iGroupId = $this->request->attributes->getInt('group_id');

		if (empty($iGroupId)) {
			return $this->serve404();
		}

		$rsGroup = $this->okt->getGroups()->getGroup($iGroupId);

		$title = $rsGroup->title;

		if ($this->okt->request->request->has('form_sent'))
		{
			$title = $this->okt->request->request->get('title');

			$aPerms = array();
			if ($this->okt->request->request->has('perms')) {
				$aPerms = array_keys($this->okt->request->request->get('perms'));
			}

			if (empty($title)) {
				$this->okt->error->set(__('c_a_users_must_enter_group_title'));
			}

			if ($this->okt->error->isEmpty())
			{
				if ($this->okt->getGroups()->updGroup($iGroupId, $title) && $this->okt->getGroups()->updGroupPerms($iGroupId, $aPerms))
				{
					$this->okt->page->flash->success(__('c_a_users_group_edited'));

					return $this->redirect($this->generateUrl('Users_groups_edit', array('group_id' => $iGroupId)));
				}
			}
		}

		return $this->render('Users/Groups/Edit', array(
			'iGroupId'       => $iGroupId,
			'title'          => $title,
			'aPerms'         => $rsGroup->perms ? json_decode($rsGroup->perms) : array(),
			'aPermissions'   => $this->okt->getPermsForDisplay()
		));
	}

	protected function init()
	{
		if (!$this->okt->checkPerm('users_groups')) {
			return $this->serve401();
		}

		$this->okt->l10n->loadFile($this->okt->options->get('locales_dir').'/'.$this->okt->user->language.'/admin/users');
	}
}