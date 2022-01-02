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
 * Configuration exception: Invalid S3 Invalid S3 Storage Class
 *
 * @since  2.0.0
 */
class InvalidS3StorageClass extends ConfigurationError
{
	public function __construct(string $storageClass, Throwable $previous = null)
	{
		$message = sprintf("Invalid S3 Storage Class ‘%s’.", $storageClass);
		$code    = ExceptionCode::CONFIG_INVALID_S3_STORAGECLASS;

		parent::__construct($message, $code, $previous);
	}
}