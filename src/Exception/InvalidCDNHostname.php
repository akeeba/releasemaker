<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Exception;


use Akeeba\ReleaseMaker\Contracts\ExceptionCode;
use Throwable;

/**
 * Configuration exception: Invalid CDN hostname (for S3)
 *
 * @since  2.0.0
 */
class InvalidCDNHostname extends ConfigurationError
{
	public function __construct(string $hostname, Throwable $previous = null)
	{
		$message = sprintf("Invalid CDN hostname ‘%s’ for the S3 connection.", $hostname);
		$code    = ExceptionCode::INVALID_CDN_HOSTNAME;

		parent::__construct($message, $code, $previous);
	}
}