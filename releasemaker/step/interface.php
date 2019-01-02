<?php
/**
 * Akeeba Release Maker
 * An automated script to upload and release a new version of an Akeeba component.
 *
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2006-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU/GPLv3
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
