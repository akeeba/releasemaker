<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Mixin;


use ReflectionClass;
use ReflectionException;

trait ClassConstantValidationAware
{
	protected function isValidClassConstantValue($value, string $className, bool $strict = false): bool
	{
		try
		{
			$refClass  = new ReflectionClass($className);
			$constants = $refClass->getConstants();
		}
		catch (ReflectionException $e)
		{
			// When in doubt, C4.
			return true;
		}

		return in_array($value, $constants, $strict);
	}
}