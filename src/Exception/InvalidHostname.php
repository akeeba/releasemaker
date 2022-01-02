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
 * Configuration exception: Invalid connection hostname
 *
 * @since  2.0.0
 */
class InvalidHostname extends ConfigurationError
{
	public function __construct(string $hostname, Throwable $previous = null)
	{
		$message = sprintf("Invalid hostname ‘%s’ for the connection.", $hostname);
		$code    = ExceptionCode::INVALID_HOSTNAME;

		parent::__construct($message, $code, $previous);
	}
}