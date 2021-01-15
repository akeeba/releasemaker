<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Utils;

class StringHelper
{
	public static function toSlug($value)
	{
		//remove any '-' from the string they will be used as concatonater
		$value = \str_replace('-', ' ', $value);

		//convert to ascii characters
		$value = self::toASCII($value);

		//lowercase and trim
		$value = \trim(\strtolower($value));

		//remove any duplicate whitespace, and ensure all characters are alphanumeric
		$value = \preg_replace(['/\s+/', '/[^A-Za-z0-9\-]/'], ['-', ''], $value);

		//limit length
		if (\strlen($value) > 100)
		{
			$value = \substr($value, 0, 100);
		}

		return $value;
	}

	public static function toASCII($value)
	{
		$string = \htmlentities(\utf8_decode($value));

		return \preg_replace(
			['/&szlig;/', '/&(..)lig;/', '/&([aouAOU])uml;/', '/&(.)[^;]*;/'],
			['ss', '$1', '$1e', '$1'],
			$string);
	}
}