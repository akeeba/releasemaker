<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Exception;


use Akeeba\ReleaseMaker\Contracts\ExceptionCode;
use Throwable;

/**
 * Configuration exception: Using the Joomla API application requires using a Joomla! API token
 *
 * @since  2.0.0
 */
class InvalidARSJoomlaConfiguration extends ConfigurationError
{
	public function __construct(Throwable $previous = null)
	{
		$message = "You must specify a Joomla API token when using the 'joomla' ARS connection type.";
		$code    = ExceptionCode::CONFIG_ARS_JOOMLA_REQUIRES_TOKEN;

		parent::__construct($message, $code, $previous);
	}
}