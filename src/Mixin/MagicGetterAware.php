<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Mixin;


use ReflectionObject;
use Akeeba\ReleaseMaker\Contracts\ExceptionCode;
use LogicException;

/**
 * Magic read-only access to private properties.
 *
 * Goes through getters, even if they are private themselves. If there is no getter the private property's value is
 * returned as is.
 *
 * This is useful for creating immutable objects. Remember to add the property-read annotations in the class docblock.
 *
 * @since  2.0.0
 */
trait MagicGetterAware
{
	/**
	 * Allows private property access, either through a getter (which can be private) or by returning the private
	 * property itself.
	 *
	 * @param   string  $name  The property name to access
	 *
	 * @return  mixed
	 * @throws  LogicException  When trying to access a non-existent property.
	 *
	 * @since   2.0.0
	 */
	public function __get($name)
	{
		if (method_exists($this, ($method = sprintf("get%s", ucfirst($name)))))
		{
			return $this->{$method}();
		}

		try
		{
			$refObject   = new ReflectionObject($this);
			$refProperty = $refObject->getProperty($name);
			$refProperty->setAccessible(true);

			return $refProperty->getValue($this);
		}
		catch (\ReflectionException $e)
		{
			// Nope. We'll error out.
		}

		throw new LogicException(sprintf('Unknown property %s in class %s.', $name, __CLASS__), ExceptionCode::INVALID_PROPERTY);
	}
}