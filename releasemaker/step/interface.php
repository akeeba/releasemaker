<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

/**
 * Interace for step classes
 */
interface ArmStepInterface
{
	/**
	 * Execute this step
	 */
	public function execute();
}
