<?php
/*
 * This file is part of Okatea.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Okatea\Tao\Extensions\Themes;

use Okatea\Tao\Extensions\Collection as BaseCollection;
use Symfony\Component\Finder\Finder;

class Collection extends BaseCollection
{
	/**
	 * Default theme identifier.
	 *
	 * @var integer
	 */
	const DEFAULT_THEME = 'DefaultTheme';

	/**
	 * Constructeur.
	 *
	 * @param object $okt Okatea application instance.
	 * @param string $path Chemin du répertoire des thèmes à charger.
	 * @return void
	 */
	public function __construct($okt, $path)
	{
		parent::__construct($okt, $path);

		$this->type = 'theme';

		$this->sCacheId = 'themes';
		$this->sCacheRepositoryId = 'themes_repositories';

		$this->sExtensionClassPatern = 'Okatea\\Themes\\%s\\Theme';

		$this->sInstallerClass = 'Okatea\\Tao\\Extensions\\Themes\\Manage\\Installer';
	}

	/**
	 * Fonction de "pluralisation" des thèmes.
	 *
	 * @param integer $count
	 * @return string
	 */
	public static function pluralizeThemeCount($count)
	{
		if ($count == 1) {
			return __('c_a_themes_one_theme');
		}
		elseif ($count > 1) {
			return sprintf(__('c_a_themes_%s_themes'), $count);
		}

		return __('c_a_themes_no_theme');
	}

	public static function findIcon($sDir)
	{
		return self::findImg($sDir, 'theme_icon');
	}

	public static function findScreenshot($sDir)
	{
		return self::findImg($sDir, 'screenshot');
	}

	protected static function findImg($sDir, $simage)
	{
		$finder = (new Finder())
			->in($sDir)
			->depth('== 0')
			->files()
			->name($simage . '.*');

		foreach ($finder as $file)
		{
			return $file->getFilename();
		}
	}
}
