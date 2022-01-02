<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Mixin;


use Akeeba\ReleaseMaker\Contracts\ExceptionCode;
use LogicException;

/**
 * Magic write access to private properties. Always goes through setters, even if they are private themselves.
 *
 * Trying to set a protected or private property which does not have a setter will result in a LogicException.
 *
 * This is useful for creating virtual properties which modify multiple real properties at once, or for forcing a setter
 * to always be used when setting the value of a property (e.g. to enforce validation of the value being set).
 *
 * @since  2.0.0
 */
trait MagicSetterAware
{
	/**
	 * Allows setting private properties, as long as they have a setter (which can be private).
	 *
	 * @param   string  $name
	 * @param   mixed   $value
	 *
	 * @return  void
	 * @since   2.0.0
	 *
	 * @throws  LogicException  When trying to set a non-existent property or one with no setter.
	 */
	public function __set($name, $value)
	{
		if (method_exists($this, ($method = sprintf('set%s', ucfirst($name)))))
		{
			$this->{$method}($value);

			return;
		}

		if (property_exists($this, $name))
		{
			throw new LogicException(sprintf('Property %s in class %s is immutable.', $name, __CLASS__), ExceptionCode::INVALID_PROPERTY);
		}

		throw new LogicException(sprintf('Unknown property %s in class %s.', $name, __CLASS__), ExceptionCode::INVALID_PROPERTY);
	}

}