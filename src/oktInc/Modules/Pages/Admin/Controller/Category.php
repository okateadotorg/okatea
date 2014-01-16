<?php
/*
 * This file is part of Okatea.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Okatea\Module\Pages\Admin\Controller;

use Okatea\Admin\Controller;
use Tao\Forms\Statics\SelectOption;
use Tao\Misc\Utilities;
use Tao\Themes\TemplatesSet;

class Category extends Controller
{
	public function add()
	{
		$this->init();

		# post sended
		if ($this->populateDataFromPost())
		{
			try
			{
				# -- TRIGGER MODULE PAGES : beforeCategoryCreate
				$this->okt->Pages->triggers->callTrigger('beforeCategoryCreate', $this->aCategoryData['cursor'], $this->aCategoryData['cat'], $this->aCategoryData['locales']);

				$this->aCategoryData['cat']['id'] = $this->okt->Pages->categories->addCategory($this->aCategoryData['cursor'], $this->aCategoryData['locales']);

				# -- TRIGGER MODULE PAGES : afterCategoryCreate
				$this->okt->Pages->triggers->callTrigger('afterCategoryCreate', $this->aCategoryData['cursor'], $this->aCategoryData['cat'], $this->aCategoryData['locales']);

				# log admin
				$this->okt->logAdmin->info(array(
					'code' => 40,
					'component' => 'pages',
					'message' => 'category #'.$this->aCategoryData['cat']['id']
				));

				$this->okt->page->flash->success(__('m_pages_cat_added'));

				return $this->redirect($this->generateUrl('Pages_category', array('category_id' => $this->aCategoryData['cat']['id'])));
			}
			catch (Exception $e) {
				$this->okt->error->set($e->getMessage());
			}
		}

		$this->display();
	}

	public function edit()
	{
		$this->init();

		$this->aCategoryData['cat']['id'] = $this->request->attributes->getInt('category_id');

		$rsCategory = $this->okt->Pages->categories->getCategory($this->aCategoryData['cat']['id']);

		if (null === $this->aCategoryData['cat']['id'] || $rsCategory->isEmpty())
		{
			$this->page->flash->error(sprintf(__('m_pages_post_%s_not_exists'), $this->aPostData['post']['id']));

			return $this->serve404();
		}

		$this->aCategoryData['cat']['active'] = $rsCategory->active;
		$this->aCategoryData['cat']['parent_id'] = $rsCategory->parent_id;
		$this->aCategoryData['cat']['tpl'] = $rsCategory->tpl;
		$this->aCategoryData['cat']['items_tpl'] = $rsCategory->items_tpl;

		$rsCategoryI18n = $this->okt->Pages->categories->getCategoryI18n($this->aCategoryData['cat']['id']);

		foreach ($this->okt->languages->list as $aLanguage)
		{
			while ($rsCategoryI18n->fetch())
			{
				if ($rsCategoryI18n->language == $aLanguage['code'])
				{
					$this->aCategoryData['locales'][$aLanguage['code']]['title'] = $rsCategoryI18n->title;
					$this->aCategoryData['locales'][$aLanguage['code']]['content'] = $rsCategoryI18n->content;

					if ($this->okt->Pages->config->enable_metas)
					{
						$this->aCategoryData['locales'][$aLanguage['code']]['title_seo'] = $rsCategoryI18n->title_seo;
						$this->aCategoryData['locales'][$aLanguage['code']]['title_tag'] = $rsCategoryI18n->title_tag;
						$this->aCategoryData['locales'][$aLanguage['code']]['meta_description'] = $rsCategoryI18n->meta_description;
						$this->aCategoryData['locales'][$aLanguage['code']]['meta_keywords'] = $rsCategoryI18n->meta_keywords;
						$this->aCategoryData['locales'][$aLanguage['code']]['slug'] = $rsCategoryI18n->slug;
					}
				}
			}
		}

		# rubriques voisines
		$this->aCategoryData['extra']['rsSiblings'] = $this->okt->Pages->categories->getChildren($rsCategory->parent_id, false, $this->okt->user->language);

		$this->aCategoryData['extra']['iNumPosts'] = $rsCategory->num_posts;

		# AJAX : changement de l'ordre des rubriques voisines
		if ($this->request->query->has('ajax_update_order'))
		{
			$order = $this->request->query->get('ord', array());

			if (!empty($order))
			{
				try
				{
					foreach ($order as $ord=>$id)
					{
						$ord = ((integer) $ord)+1;
						$this->okt->Pages->categories->setCategoryOrder($id, $ord);
					}

					$this->okt->Pages->categories->rebuild();
				}
				catch (Exception $e) {
					die($e->getMessage());
				}
			}

			exit();
		}

		# POST : changement de l'ordre des rubriques voisines
		if ($this->request->request->has('order_categories'))
		{
			$order = $this->request->request->get('p_order', array());

			asort($order);
			$order = array_keys($order);

			if (!empty($order))
			{
				try
				{
					foreach ($order as $ord=>$id)
					{
						$ord = ((integer) $ord)+1;
						$this->okt->Pages->categories->setCategoryOrder($id, $ord);
					}

					$this->okt->Pages->categories->rebuild();

					return $this->redirect($this->generateUrl('Pages_category', array('category_id' => $this->aCategoryData['cat']['id'])));
				}
				catch (Exception $e) {
					$this->okt->error->set($e->getMessage());
				}
			}
		}

		# switch status
		if ($this->request->query->has('switch_status'))
		{
			try
			{
				$this->okt->Pages->categories->switchCategoryStatus($this->aCategoryData['cat']['id']);

				# log admin
				$this->okt->logAdmin->info(array(
					'code' => 32,
					'component' => 'pages',
					'message' => 'category #'.$this->aCategoryData['cat']['id']
				));

				return $this->redirect($this->generateUrl('Pages_category', array('category_id' => $this->aCategoryData['cat']['id'])));
			}
			catch (Exception $e) {
				$this->okt->error->set($e->getMessage());
			}
		}

		# post sended
		if ($this->populateDataFromPost())
		{
			try
			{
				# -- TRIGGER MODULE PAGES : beforeCategoryUpdate
				$this->okt->Pages->triggers->callTrigger('beforeCategoryUpdate', $this->aCategoryData['cursor'], $this->aCategoryData['cat'], $this->aCategoryData['locales']);

				$this->okt->Pages->categories->updCategory($this->aCategoryData['cursor'], $this->aCategoryData['locales']);

				# -- TRIGGER MODULE PAGES : afterCategoryUpdate
				$this->okt->Pages->triggers->callTrigger('afterCategoryUpdate', $this->aCategoryData['cursor'], $this->aCategoryData['cat'], $this->aCategoryData['locales']);

				# log admin
				$this->okt->logAdmin->info(array(
					'code' => 41,
					'component' => 'pages',
					'message' => 'category #'.$this->aCategoryData['cat']['id']
				));

				$this->okt->page->flash->success(__('m_pages_cat_updated'));

				return $this->redirect($this->generateUrl('Pages_category', array('category_id' => $this->aCategoryData['cat']['id'])));
			}
			catch (Exception $e) {
				$this->okt->error->set($e->getMessage());
			}
		}

		$this->display();
	}


	protected function init()
	{
		# Chargement des locales
		$this->okt->l10n->loadFile(__DIR__.'/../../Locales/'.$this->okt->user->language.'/admin.categories');

		# Récupération de la liste complète des rubriques
		$this->rsCategories = $this->okt->Pages->categories->getCategories(array(
			'active' => 2,
			'with_count' => true,
			'language' => $this->okt->user->language
		));

		$this->aCategoryData['cat'] = new \ArrayObject();

		$this->aCategoryData['cat']['id'] = null;
		$this->aCategoryData['cat']['active'] = 1;
		$this->aCategoryData['cat']['parent_id'] = 0;
		$this->aCategoryData['cat']['tpl'] = '';
		$this->aCategoryData['cat']['items_tpl'] = '';

		foreach ($this->okt->languages->list as $aLanguage)
		{
			$this->aCategoryData['locales'][$aLanguage['code']] = array();

			$this->aCategoryData['locales'][$aLanguage['code']]['title'] = '';
			$this->aCategoryData['locales'][$aLanguage['code']]['content'] = '';

			if ($this->okt->Pages->config->enable_metas)
			{
				$this->aCategoryData['locales'][$aLanguage['code']]['title_seo'] = '';
				$this->aCategoryData['locales'][$aLanguage['code']]['title_tag'] = '';
				$this->aCategoryData['locales'][$aLanguage['code']]['meta_description'] = '';
				$this->aCategoryData['locales'][$aLanguage['code']]['meta_keywords'] = '';
				$this->aCategoryData['locales'][$aLanguage['code']]['slug'] = '';
			}
		}

		$this->aCategoryData['extra']['rsSiblings'] = null;

		$this->aCategoryData['extra']['iCategoryNumPosts'] = null;
	}

	protected function populateDataFromPost()
	{
		if (!$this->request->request->has('sended')) {
			return false;
		}

		$this->aCategoryData['cat']['active'] = $this->request->request->getInt('p_active');
		$this->aCategoryData['cat']['parent_id'] = $this->request->request->getInt('p_parent_id');
		$this->aCategoryData['cat']['tpl'] = $this->request->request->get('p_tpl');
		$this->aCategoryData['cat']['items_tpl'] = $this->request->request->get('p_items_tpl');

		foreach ($this->okt->languages->list as $aLanguage)
		{
			$this->aCategoryData['locales'][$aLanguage['code']]['title'] = $this->request->request->get('p_title['.$aLanguage['code'].']', null, true);

			if ($this->okt->Pages->config->categories['descriptions']) {
				$this->aCategoryData['locales'][$aLanguage['code']]['content'] = $this->request->request->get('p_content['.$aLanguage['code'].']', null, true);
			}

			if ($this->okt->Pages->config->enable_metas)
			{
				$this->aCategoryData['locales'][$aLanguage['code']]['title_seo'] = $this->request->request->get('p_title_seo['.$aLanguage['code'].']', null, true);
				$this->aCategoryData['locales'][$aLanguage['code']]['title_tag'] = $this->request->request->get('p_title_tag['.$aLanguage['code'].']', null, true);
				$this->aCategoryData['locales'][$aLanguage['code']]['meta_description'] = $this->request->request->get('p_meta_description['.$aLanguage['code'].']', null, true);
				$this->aCategoryData['locales'][$aLanguage['code']]['meta_keywords'] = $this->request->request->get('p_meta_keywords['.$aLanguage['code'].']', null, true);
				$this->aCategoryData['locales'][$aLanguage['code']]['slug'] = $this->request->request->get('p_slug['.$aLanguage['code'].']', null, true);
			}
		}

		# vérification des données avant modification dans la BDD
		if ($this->okt->Pages->categories->checkPostData($this->aCategoryData['cat'], $this->aCategoryData['locales']))
		{
			$this->aCategoryData['cursor'] = $this->okt->Pages->categories->openCategoryCursor($this->aCategoryData['cat']);

			return true;
		}

		return false;
	}

	protected function display()
	{
		# Liste des templates utilisables
		$oTemplatesList = new TemplatesSet($this->okt, $this->okt->Pages->config->templates['list'], 'pages/list', 'list');
		$aTplChoices = array_merge(
			array('&nbsp;' => null),
			$oTemplatesList->getUsablesTemplatesForSelect($this->okt->Pages->config->templates['list']['usables'])
		);

		$oItemsTemplatesList = new TemplatesSet($this->okt, $this->okt->Pages->config->templates['item'], 'pages/item', 'item');
		$aItemsTplChoices = array_merge(
			array('&nbsp;' => null),
			$oItemsTemplatesList->getUsablesTemplatesForSelect($this->okt->Pages->config->templates['item']['usables'])
		);

		# Calcul de la liste des parents possibles
		$aAllowedParents = array(__('m_pages_cat_first_level')=>0);

		$aChildrens = array();
		if ($this->aCategoryData['cat']['id'])
		{
			$rsDescendants = $this->okt->Pages->categories->getDescendants($this->aCategoryData['cat']['id'], true);

			while ($rsDescendants->fetch()) {
				$aChildrens[] = $rsDescendants->id;
			}
		}

		while ($this->rsCategories->fetch())
		{
			if (!in_array($this->rsCategories->id, $aChildrens))
			{
				$aAllowedParents[] = new SelectOption(
					str_repeat('&nbsp;&nbsp;&nbsp;', $this->rsCategories->level-1).'&bull; '.Utilities::escapeHTML($this->rsCategories->title),
					$this->rsCategories->id
				);
			}
		}

		return $this->render('Pages/Admin/Templates/Category', array(
			'aCategoryData' 	=> $this->aCategoryData,
			'aTplChoices' 		=> $aTplChoices,
			'aItemsTplChoices' 	=> $aItemsTplChoices,
			'aAllowedParents' 	=> $aAllowedParents
		));
	}
}