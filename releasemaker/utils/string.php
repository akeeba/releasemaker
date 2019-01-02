<?php
/**
 * Akeeba Release Maker
 * An automated script to upload and release a new version of an Akeeba component.
 *
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2006-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU/GPLv3
 */

class ArmUtilsString
{
	public static function toSlug($value)
	{
		//remove any '-' from the string they will be used as concatonater
		$value = str_replace('-', ' ', $value);
		
		//convert to ascii characters
		$value = self::toASCII($value);
		
		//lowercase and trim
		$value = trim(strtolower($value));
		
		//remove any duplicate whitespace, and ensure all characters are alphanumeric
		$value = preg_replace(array('/\s+/','/[^A-Za-z0-9\-]/'), array('-',''), $value);
		
		//limit length
		if (strlen($value) > 100) {
			$value = substr($value, 0, 100);
		}
		
		return $value;
	}
	
	public static function toASCII($value)
	{
		$string = htmlentities(utf8_decode($value));
		$string = preg_replace(
			array('/&szlig;/','/&(..)lig;/', '/&([aouAOU])uml;/','/&(.)[^;]*;/'),
			array('ss',"$1","$1".'e',"$1"),
			$string);

		return $string;
	}
}
