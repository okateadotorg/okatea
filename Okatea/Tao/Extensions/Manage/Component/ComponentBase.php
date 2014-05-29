<?php
/*
 * This file is part of Okatea.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Okatea\Tao\Extensions\Manage\Component;

use Okatea\Tao\ApplicationShortcuts;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

abstract class ComponentBase extends ApplicationShortcuts
{

	protected $extension;

	protected $checklist;

	protected $fs;

	public function __construct($okt, $extension)
	{
		parent::__construct($okt);

		$this->extension = $extension;
		$this->checklist = $extension->checklist;
	}

	protected function getFs()
	{
		if (null === $this->fs)
		{
			$this->fs = new Filesystem();
		}

		return $this->fs;
	}

	protected function getFinder()
	{
		return Finder::create();
	}

	protected function yamlParse($input, $exceptionOnInvalidType = false, $objectSupport = false)
	{
		return Yaml::parse($input, $exceptionOnInvalidType, $objectSupport);
	}

	protected function yamlDump($array, $inline = 2, $indent = 4, $exceptionOnInvalidType = false, $objectSupport = false)
	{
		return Yaml::dump($array, $inline, $indent, $exceptionOnInvalidType, $objectSupport);
	}
}
