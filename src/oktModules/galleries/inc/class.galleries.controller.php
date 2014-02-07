<?php
/**
 * @ingroup okt_module_galleries
 * @brief Controller public.
 *
 */

class galleriesController extends oktController
{
	/**
	 * Affichage de la liste des galeries.
	 *
	 */
	public function galleriesList()
	{
		# module actuel
		$this->okt->page->module = 'galleries';
		$this->okt->page->action = 'list';

		# Récupération de la liste des galeries à la racine
		$rsGalleriesList = $this->okt->galleries->tree->getGalleries(array(
			'active' => 1,
			'parent_id' => 0,
			'language' => $this->okt->user->language
		));
			# formatage des données avant affichage
			$this->okt->galleries->tree->prepareGalleries($rsGalleriesList);

		# meta description
		if (!empty($this->okt->galleries->config->meta_description[$this->okt->user->language])) {
			$this->okt->page->meta_description = $this->okt->galleries->config->meta_description[$this->okt->user->language] ;
		}
		else {
			$this->okt->page->meta_description = util::getSiteMetaDesc();
		}

		# meta keywords
		if (!empty($this->okt->galleries->config->meta_keywords[$this->okt->user->language])) {
			$this->okt->page->meta_keywords = $this->okt->galleries->config->meta_keywords[$this->okt->user->language] ;
		}
		else {
			$this->okt->page->meta_keywords = util::getSiteMetaKeywords();
		}
		
		# fil d'ariane
		if (!$this->isDefaultRoute(__CLASS__, __FUNCTION__))
		{
			$this->okt->page->breadcrumb->add($this->okt->galleries->getName(), $this->okt->galleries->config->url);
		}

		# title tag du module
		$this->okt->page->addTitleTag($this->okt->galleries->getTitle());

		# titre de la page
		$this->okt->page->setTitle($this->okt->galleries->getName());

		# titre SEO de la page
		$this->okt->page->setTitleSeo($this->okt->galleries->getNameSeo());

		# affichage du template
		echo $this->okt->tpl->render('galleries/list/'.$this->okt->galleries->config->templates['list']['default'].'/template', array(
			'rsGalleriesList' => $rsGalleriesList
		));
	}

	/**
	 * Affichage d'une galerie.
	 *
	 */
	public function galleriesGallery($aMatches)
	{
		# module actuel
		$this->okt->page->module = 'galleries';
		$this->okt->page->action = 'gallery';

		# récupération de la galerie en fonction du slug
		if (!empty($aMatches[0])) {
			$slug = $aMatches[0];
		}
		else {
			$this->serve404();
		}

		# récupération de la galerie
		$rsGallery = $this->okt->galleries->tree->getGalleries(array(
			'slug' => $slug,
			'active' => 1,
			'language' => $this->okt->user->language
		));

		if ($rsGallery->isEmpty()) {
			$this->serve404();
		}

		# formatage des données avant affichage
		$this->okt->galleries->tree->prepareGallery($rsGallery);

		# un mot de passe ?
		$bGalleryRequirePassword = false;
		if (!empty($rsGallery->password))
		{
			# il y a un mot de passe en session
			if (!empty($_SESSION['okt_gallery_password_'.$rsGallery->id]))
			{
				if ($_SESSION['okt_gallery_password_'.$rsGallery->id] != $rsGallery->password)
				{
					$this->okt->error->set('Le mot de passe ne correspond pas à celui de la galerie.');
					$bGalleryRequirePassword = true;
				}
			}

			# ou il y a un mot de passe venant du formulaire
			elseif (!empty($_POST['okt_gallery_password']))
			{
				$p_password = trim($_POST['okt_gallery_password']);

				if ($p_password != $rsGallery->password)
				{
					$this->okt->error->set('Le mot de passe ne correspond pas à celui de la galerie.');
					$bGalleryRequirePassword = true;
				}
				else {
					$_SESSION['okt_gallery_password_'.$rsGallery->id] = $p_password;
					http::redirect(html::escapeHTML($rsGallery->getGalleryUrl()));
				}
			}

			# sinon on doit afficher le formulaire
			else {
				$bGalleryRequirePassword = true;
			}
		}

		# Récupération de la liste des sous-galeries
		$rsSubGalleriesList = $this->okt->galleries->tree->getGalleries(array(
			'active' => 1,
			'parent_id' => $rsGallery->id,
			'language' => $this->okt->user->language
		));
			# formatage des données avant affichage
			$this->okt->galleries->tree->prepareGalleries($rsSubGalleriesList);

		# Récupération des éléments de la galerie
		$rsItems = $this->okt->galleries->items->getItems(array(
			'gallery_id' => $rsGallery->id,
			'active' => 1,
			'language' => $this->okt->user->language
		));

		# meta description
		if (!empty($rsGallery->meta_description)) {
			$this->okt->page->meta_description = $rsGallery->meta_description;
		}
		elseif (!empty($this->okt->galleries->config->meta_description[$this->okt->user->language])) {
			$this->okt->page->meta_description = $this->okt->galleries->config->meta_description[$this->okt->user->language];
		}
		else {
			$this->okt->page->meta_description = util::getSiteMetaDesc();
		}

		# meta keywords
		if (!empty($rsGallery->meta_keywords)) {
			$this->okt->page->meta_description = $rsGallery->meta_keywords;
		}
		elseif (!empty($this->okt->galleries->config->meta_keywords[$this->okt->user->language])) {
			$this->okt->page->meta_keywords = $this->okt->galleries->config->meta_keywords[$this->okt->user->language];
		}
		else {
			$this->okt->page->meta_keywords = util::getSiteMetaKeywords();
		}

		# title tag
		$this->okt->page->addTitleTag((!empty($rsGallery->title_tag) ? $rsGallery->title_tag : $rsGallery->title));

		# fil d'ariane
		if (!$this->isDefaultRoute(__CLASS__, __FUNCTION__, $slug))
		{
			$this->okt->page->breadcrumb->add($this->okt->galleries->getName(), $this->okt->galleries->config->url);
			
			$rsPath = $this->okt->galleries->tree->getPath($rsGallery->id, true, $this->okt->user->language);
			while ($rsPath->fetch()) {
				$this->okt->page->breadcrumb->add($rsPath->title, galleriesHelpers::getGalleryUrl($rsPath->slug));
			}
		}

		# titre de la page
		$this->okt->page->setTitle($rsGallery->title);

		# titre SEO de la page
		$this->okt->page->setTitleSeo($rsGallery->title_seo);

		# affichage du template
		echo $this->okt->tpl->render('galleries/gallery/'.$this->okt->galleries->config->templates['gallery']['default'].'/template', array(
			'bGalleryRequirePassword' => $bGalleryRequirePassword,
			'rsGallery' => $rsGallery,
			'rsSubGalleries' => $rsSubGalleriesList,
			'rsItems' => $rsItems
		));
	}

	/**
	 * Affichage d'un élément.
	 *
	 */
	public function galleriesItem($aMatches)
	{
		# récupération de l'élément en fonction du slug
		if (!empty($aMatches[0])) {
			$slug = $aMatches[0];
		}
		else {
			$this->serve404();
		}

		# récupération de l'élément
		$rsItem = $this->okt->galleries->items->getItems(array(
			'slug' => $slug,
			'active' => 1,
			'language' => $this->okt->user->language
		));

		if ($rsItem->isEmpty()) {
			$this->serve404();
		}

		# module actuel
		$this->okt->page->module = 'galleries';
		$this->okt->page->action = 'item';

		//$rsItem->image = $rsItem->getImagesInfo();

		if ($this->okt->galleries->config->enable_rte == '' && $rsItem->legend != '') {
			$rsItem->legend = util::nlToP($rsItem->legend);
		}

		# title tag
		$this->okt->page->addTitleTag($this->okt->galleries->getTitle());

		if ($rsItem->title_tag == '') {
			$rsItem->title_tag = $rsItem->title;
		}

		$this->okt->page->addTitleTag($rsItem->title_tag);

		# meta description
		if ($rsItem->meta_description != '') {
			$this->okt->page->meta_description = $rsItem->meta_description;
		}
		else if ($this->okt->galleries->config->meta_description[$this->okt->user->language] != '') {
			$this->okt->page->meta_description = $this->okt->galleries->config->meta_description[$this->okt->user->language];
		}
		else {
			$this->okt->page->meta_description = util::getSiteMetaDesc();
		}

		# meta keywords
		if ($rsItem->meta_keywords != '') {
			$this->okt->page->meta_keywords = $rsItem->meta_keywords;
		}
		else if ($this->okt->galleries->config->meta_keywords[$this->okt->user->language] != '') {
			$this->okt->page->meta_keywords = $this->okt->galleries->config->meta_keywords[$this->okt->user->language];
		}
		else {
			$this->okt->page->meta_keywords = util::getSiteMetaKeywords();
		}

		# fil d'ariane
		if (!$this->isDefaultRoute(__CLASS__, __FUNCTION__, $slug)) 
		{
			$this->okt->page->breadcrumb->add($this->okt->galleries->getName(), $this->okt->galleries->config->url);
			
			$rsPath = $this->okt->galleries->tree->getPath($rsItem->gallery_id, true, $this->okt->user->language);
			while ($rsPath->fetch())
			{
				$this->okt->page->addTitleTag($rsPath->title);
	
				$this->okt->page->breadcrumb->add($rsPath->title, galleriesHelpers::getGalleryUrl($rsPath->slug));
			}

			$this->okt->page->breadcrumb->add($rsItem->title, $rsItem->getItemUrl());
		}
		
		# titre de la page
		$this->okt->page->setTitle($rsItem->title);

		# titre SEO de la page
		$this->okt->page->setTitleSeo(!empty($rsItem->title_seo) ? $rsItem->title_seo : $rsItem->title);

		# affichage du template
		echo $this->okt->tpl->render('galleries/item/'.$this->okt->galleries->config->templates['item']['default'].'/template', array(
			'rsItem' => $rsItem
		));
	}

} # class