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
 * Configuration exception: Invalid connection key
 *
 * @since  2.0.0
 */
class InvalidConnectionKey extends ConfigurationError
{
	public function __construct(string $updateStream, Throwable $previous = null)
	{
		$message = sprintf("Connection key ‘%s’ is not present in the configuration.", $updateStream);
		$code    = ExceptionCode::CONFIG_INVALID_CONNECTION_KEY;

		parent::__construct($message, $code, $previous);
	}
}