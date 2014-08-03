<?php
/*
 * This file is part of Okatea.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Okatea\Tao\Extensions;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Okatea\Tao\Extensions\Modules\Collection as ModulesCollection;
use Okatea\Tao\Extensions\Themes\Collection as ThemesCollection;

class ExtensionsServiceProvider implements ServiceProviderInterface
{
	public function register(Container $okt)
	{
		$okt['modules'] = function($okt) {
			return new ModulesCollection(
				$okt,
				$okt['modules_dir']
			);
		};

		$okt['themes'] = function($okt) {
			return new ThemesCollection(
				$okt,
				$okt['themes_dir']
			);
		};
	}
}
