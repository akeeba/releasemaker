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
 * Configuration exception: Invalid S3 signature type
 *
 * @since  2.0.0
 */
class InvalidS3Signature extends ConfigurationError
{
	public function __construct(string $signature, Throwable $previous = null)
	{
		$message = sprintf("Invalid S3 signature type ‘%s’. Expected v4 or v2.", $signature);
		$code    = ExceptionCode::CONFIG_INVALID_SIGNATURE;

		parent::__construct($message, $code, $previous);
	}
}