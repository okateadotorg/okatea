<?php
/*
 * This file is part of Okatea.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Okatea\Modules\Builder\Tools;

class Themes extends Extensions
{

	public function __construct($okt)
	{
		parent::__construct($okt);
		
		$this->sPackagesDir = $this->sPackageDir . '/themes/' . $this->okt->getVersion();
		
		$this->sTempDir = $this->getTempDir($this->okt['themes_path']);
		
		$this->aConfig = $this->okt->module('Builder')->config['themes'];
	}
}
