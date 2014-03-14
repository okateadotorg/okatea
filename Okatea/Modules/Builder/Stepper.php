<?php
/*
 * This file is part of Okatea.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Okatea\Modules\Builder;

use Okatea\Tao\Html\Stepper as BaseStepper;

class Stepper extends BaseStepper
{
	public function __construct($sBaseUrl, $sCurrentStep)
	{
		$aStepsList = array(
			array(
				'step' 		=> 'start',
				'title' 	=> __('m_builder_step_start')
			),
			array(
				'step' 		=> 'version',
				'title' 	=> __('m_builder_step_version')
			),
			array(
				'step' 		=> 'copy',
				'title' 	=> __('m_builder_step_copy')
			),
			array(
				'step' 		=> 'cleanup',
				'title' 	=> __('m_builder_step_cleanup')
			),
			array(
				'step' 		=> 'config',
				'title' 	=> __('m_builder_step_config')
			),
			array(
				'step' 		=> 'options',
				'title' 	=> __('m_builder_step_options')
			),
			array(
				'step' 		=> 'modules',
				'title' 	=> __('m_builder_step_modules')
			),
			array(
				'step' 		=> 'themes',
				'title' 	=> __('m_builder_step_themes')
			),
			array(
				'step' 		=> 'digests',
				'title' 	=> __('m_builder_step_digests')
			),
			array(
				'step' 		=> 'packages',
				'title' 	=> __('m_builder_step_packages')
			),
			array(
				'step' 		=> 'end',
				'title' 	=> __('m_builder_step_end')
			)
		);

		parent::__construct($aStepsList, $sCurrentStep);
	}
}
