<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Step;

/**
 * Interace for step classes
 */
interface StepInterface
{
	/**
	 * Execute this step
	 */
	public function execute(): void;
}
