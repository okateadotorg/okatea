<?php
/*
 * This file is part of Okatea.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Okatea\Tao\Database\ConfigLayers\Dbal;

use Okatea\Tao\Database\ConfigLayers\DriverInterface;

class DrizzlePdoMysql implements DriverInterface
{
	protected $bSupported;

	public function isSupported()
	{
		if (null === $this->bSupported) {
			$this->bSupported = extension_loaded('pdo_mysql');
		}

		return $this->bSupported;
	}
}
