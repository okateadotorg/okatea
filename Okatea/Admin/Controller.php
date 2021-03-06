<?php
/*
 * This file is part of Okatea.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Okatea\Admin;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Okatea\Tao\Controller as BaseController;

class Controller extends BaseController
{
	/**
	 * Constructor.
	 */
	public function __construct($okt)
	{
		parent::__construct($okt);

		# Ajout des fichiers CSS de l'admin
		$this->page->css->addFile($this->okt['public_url'] . '/components/jquery-ui/themes/' . $this->okt['config']->jquery_ui['admin'] . '/jquery-ui.min.css');
		$this->page->css->addFile($this->okt['public_url'] . '/css/init.css');
		$this->page->css->addFile($this->okt['public_url'] . '/css/admin.css');
		$this->page->css->addFile($this->okt['public_url'] . '/css/famfamfam.css');

		# Ajout des fichiers JS de l'admin
		$this->page->js->addFile($this->okt['public_url'] . '/components/jquery/dist/jquery.min.js');
		$this->page->js->addFile($this->okt['public_url'] . '/components/jquery-cookie/jquery.cookie.js');
		$this->page->js->addFile($this->okt['public_url'] . '/components/jquery-ui/ui/minified/jquery-ui.min.js');
		$this->page->js->addFile($this->okt['public_url'] . '/js/common_admin.js');

		# Title tag
		$this->page->addTitleTag($this->page->getSiteTitleTag(null, $this->page->getSiteTitle()));

		# Fil d'ariane administration
		$this->page->addAriane(__('Administration'), $this->generateUrl('home'));
	}

	/**
	 * Generates a URL from the given parameters.
	 *
	 * @param string $route The name of the route
	 * @param mixed $parameters An array of parameters
	 * @param Boolean|string $referenceType The type of reference (one of the constants in UrlGeneratorInterface)
	 *
	 * @return string The generated URL
	 *
	 * @see UrlGeneratorInterface
	 */
	public function generateUrl($route, $parameters = [], $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH)
	{
		return $this->okt['adminRouter']->generate($route, $parameters, $referenceType);
	}
}
