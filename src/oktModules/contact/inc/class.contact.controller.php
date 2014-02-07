<?php
/**
 * @ingroup okt_module_contact
 * @brief Controller public.
 *
 */

class contactController extends oktController
{
	/**
	 * Affichage de la page contact.
	 *
	 */
	public function contactPage()
	{
		# module actuel
		$this->okt->page->module = 'contact';
		$this->okt->page->action = 'form';

		# -- CORE TRIGGER : publicModuleContactControllerStart
		$this->okt->triggers->callTrigger('publicModuleContactControllerStart', $this->okt, $this->okt->contact->config->captcha);

		# liste des champs
		$this->okt->contact->rsFields = $this->okt->contact->getFields(array('active'=>true, 'language'=>$this->okt->user->language));

		# -- CORE TRIGGER : publicModuleContactControllerBeforeFieldsValues
		$this->okt->triggers->callTrigger('publicModuleContactControllerBeforeInitFieldsValues', $this->okt);

		# intitialisation des données des champs
		while ($this->okt->contact->rsFields->fetch())
		{
			switch ($this->okt->contact->rsFields->type)
			{
				default:
				case 1 : # Champ texte
				case 2 : # Zone de texte
					$this->okt->contact->aPostedData[$this->okt->contact->rsFields->id] =
						!empty($_REQUEST[$this->okt->contact->rsFields->html_id])
						? $_REQUEST[$this->okt->contact->rsFields->html_id]
						: $this->okt->contact->rsFields->value;
				break;

				case 3 : # Menu déroulant
					$this->okt->contact->aPostedData[$this->okt->contact->rsFields->id] =
						isset($_REQUEST[$this->okt->contact->rsFields->html_id])
						? $_REQUEST[$this->okt->contact->rsFields->html_id]
						: '';
				break;

				case 4 : # Boutons radio
					$this->okt->contact->aPostedData[$this->okt->contact->rsFields->id] =
						isset($_REQUEST[$this->okt->contact->rsFields->html_id])
						? $_REQUEST[$this->okt->contact->rsFields->html_id]
						: '';
				break;

				case 5 : # Cases à cocher
					$this->okt->contact->aPostedData[$this->okt->contact->rsFields->id] =
						!empty($_REQUEST[$this->okt->contact->rsFields->html_id]) && is_array($_REQUEST[$this->okt->contact->rsFields->html_id])
						? $_REQUEST[$this->okt->contact->rsFields->html_id]
						: array();
				break;
			}
		}

		# -- CORE TRIGGER : publicModuleContactControllerAfterInitFieldsValues
		$this->okt->triggers->callTrigger('publicModuleContactControllerAfterInitFieldsValues', $this->okt);

		# formulaire envoyé
		if (!empty($_POST['send']))
		{
			# vérification des champs obligatoires
			while ($this->okt->contact->rsFields->fetch())
			{
				if ($this->okt->contact->rsFields->active == 2 && empty($this->okt->contact->aPostedData[$this->okt->contact->rsFields->id])) {
					$this->okt->error->set('Vous devez renseigner le champ "'.html::escapeHtml($this->okt->contact->rsFields->title).'".');
				}
				else if ($this->okt->contact->rsFields->id == 4 && !text::isEmail($this->okt->contact->aPostedData[4])) {
					$this->okt->error->set('Veuillez saisir une adresse email valide.');
				}
			}

			# -- CORE TRIGGER : publicModuleContactControllerFormCheckValues
			$this->okt->triggers->callTrigger('publicModuleContactControllerFormCheckValues', $this->okt, $this->okt->contact->config->captcha);

			# si on as pas d'erreur on se préparent à envoyer le mail
			if ($this->okt->error->isEmpty())
			{
				$oMail = new oktMail($this->okt);

				# -- CORE TRIGGER : publicModuleContactBeforeBuildMail
				$this->okt->triggers->callTrigger('publicModuleContactBeforeBuildMail', $this->okt, $oMail);

				# from to & reply to
				if ($this->okt->contact->config->from_to == 'website')
				{
					$oMail->setFrom();

					$oMail->message->setReplyTo($this->okt->contact->getReplyTo());
				}
				else {
					$oMail->message->setFrom($this->okt->contact->getFromTo());
				}

				# sujet
				$oMail->message->setSubject($this->okt->contact->getSubject());

				# corps du message
				$oMail->message->setBody($this->okt->contact->getBody());

				# destinataires
				$oMail->message->setTo($this->okt->contact->getRecipientsTo());

				# destinataires en copie
				$aRecipientsCc = $this->okt->contact->getRecipientsCc();
				if (!empty($aRecipientsCc)) {
					$oMail->message->setCc($aRecipientsCc);
				}

				# destinataires en copie cachée
				$aRecipientsBc = $this->okt->contact->getRecipientsBcc();
				if (!empty($aRecipientsBc)) {
					$oMail->message->setBcc($aRecipientsBc);
				}

				# -- CORE TRIGGER : publicModuleContactBeforeSendMail
				$this->okt->triggers->callTrigger('publicModuleContactBeforeSendMail', $this->okt, $oMail);

				if ($oMail->send())
				{
					# -- CORE TRIGGER : publicModuleContactAfterMailSent
					$this->okt->triggers->callTrigger('publicModuleContactAfterMailSent', $this->okt, $oMail);

					http::redirect($this->okt->contact->config->url.'?sended=1');
				}
			}
		}

		# meta description
		if ($this->okt->contact->config->meta_description[$this->okt->user->language] != '') {
			$this->okt->page->meta_description = $this->okt->contact->config->meta_description[$this->okt->user->language];
		}
		else {
			$this->okt->page->meta_description = util::getSiteMetaDesc();
		}

		# meta keywords
		if ($this->okt->contact->config->meta_keywords[$this->okt->user->language] != '') {
			$this->okt->page->meta_keywords = $this->okt->contact->config->meta_keywords[$this->okt->user->language];
		}
		else {
			$this->okt->page->meta_keywords = util::getSiteMetaKeywords();
		}

		# title tag du module
		$this->okt->page->addTitleTag($this->okt->contact->getTitle());

		# fil d'ariane
		if (!$this->isDefaultRoute(__CLASS__, __FUNCTION__)) {
			$this->okt->page->breadcrumb->add($this->okt->contact->getName(), $this->okt->contact->config->url);
		}

		# titre de la page
		$this->okt->page->setTitle($this->okt->contact->getName());

		# titre SEO de la page
		$this->okt->page->setTitleSeo($this->okt->contact->getNameSeo());

		# affichage du template
		echo $this->okt->tpl->render('contact/contact/'.$this->okt->contact->config->templates['contact']['default'].'/template');
	}

	/**
	 * Affichage de la page du plan d'accès.
	 *
	 */
	public function contactMapPage()
	{
		# si la page n'est pas active -> 404
		if (!$this->okt->contact->config->google_map['enable']) {
			$this->serve404();
		}

		# module actuel
		$this->okt->page->module = 'contact';
		$this->okt->page->action = 'map';

		# meta description
		if ($this->okt->contact->config->meta_description_map[$this->okt->user->language] != '') {
			$this->okt->page->meta_description = $this->okt->contact->config->meta_description_map[$this->okt->user->language];
		}
		else {
			$this->okt->page->meta_description = util::getSiteMetaDesc();
		}

		# meta keywords
		if ($this->okt->contact->config->meta_keywords_map[$this->okt->user->language] != '') {
			$this->okt->page->meta_keywords = $this->okt->contact->config->meta_keywords_map[$this->okt->user->language];
		}
		else {
			$this->okt->page->meta_keywords = util::getSiteMetaKeywords();
		}

		# title tag de la page
		$sTitle = null;
		if (isset($this->okt->contact->config->title_map[$this->okt->user->language])) {
			$sTitle = $this->okt->contact->config->title_map[$this->okt->user->language];
		}
		elseif ($this->okt->contact->config->title_map[$this->okt->config->language]) {
			$sTitle = $this->okt->contact->config->title_map[$this->okt->config->language];
		}
		$this->okt->page->addTitleTag($sTitle);

		# titre de la page
		$sName = null;
		if (isset($this->okt->contact->config->name_map[$this->okt->user->language])) {
			$sName = $this->okt->contact->config->name_map[$this->okt->user->language];
		}
		elseif ($this->okt->contact->config->name_map[$this->okt->config->language]) {
			$sName = $this->okt->contact->config->name_map[$this->okt->config->language];
		}
		$this->okt->page->setTitle($sName);

		# titre SEO de la page
		$sNameSeo = null;
		if (isset($this->okt->contact->config->name_seo_map[$this->okt->user->language])) {
			$sNameSeo = $this->okt->contact->config->name_seo_map[$this->okt->user->language];
		}
		elseif ($this->okt->contact->config->name_seo_map[$this->okt->config->language]) {
			$sNameSeo = $this->okt->contact->config->name_seo_map[$this->okt->config->language];
		}
		$this->okt->page->setTitleSeo($sNameSeo);

		# fil d'ariane
		if (!$this->isDefaultRoute(__CLASS__, __FUNCTION__)) {
			$this->okt->page->breadcrumb->add($sName, $this->okt->contact->config->map_url);
		}

		# affichage du template
		echo $this->okt->tpl->render('contact/map/'.$this->okt->contact->config->templates['map']['default'].'/template');
	}

} # class