<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Exception;


use Akeeba\ReleaseMaker\Contracts\ExceptionCode;
use Throwable;

/**
 * Configuration exception: Invalid connection authentication
 *
 * @since  2.0.0
 */
class InvalidConnectionAuthentication extends ConfigurationError
{
	public function __construct(string $additionalInformation = '', Throwable $previous = null)
	{
		$message = sprintf('Invalid connection authentication. %s', $additionalInformation);
		$code    = ExceptionCode::INVALID_CONNECTION_AUTH;

		parent::__construct($message, $code, $previous);
	}
}