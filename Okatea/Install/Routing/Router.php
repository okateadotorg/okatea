<?php
/*
 * This file is part of Okatea.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Okatea\Install\Routing;

use Psr\Log\LoggerInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing\Router as BaseRouter;
use Symfony\Component\Routing\Loader\PhpFileLoader;
use Okatea\Tao\Application;
use Okatea\Tao\Routing\ControllerResolverTrait;

class Router extends BaseRouter
{
	use ControllerResolverTrait;

	/**
	 *
	 * @var Application
	 */
	protected $okt;

	/**
	 */
	public function __construct(Application $okt, $ressources_dir, $cache_dir = null, $debug = false, LoggerInterface $logger = null)
	{
		$this->okt = $okt;

		parent::__construct(new PhpFileLoader(new FileLocator($ressources_dir)), $ressources_dir, [
			'cache_dir' => $cache_dir,
			'debug' => $debug,
			'generator_cache_class' => 'OkateaInstallUrlGenerator',
			'matcher_cache_class' => 'OkateaInstallUrlMatcher'
		], $okt['requestContext'], $logger);
	}
}
