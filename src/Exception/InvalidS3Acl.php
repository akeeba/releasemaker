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
 * Configuration exception: Invalid S3 Invalid S3 ACL
 *
 * @since  2.0.0
 */
class InvalidS3Acl extends ConfigurationError
{
	public function __construct(string $acl, Throwable $previous = null)
	{
		$message = sprintf("Invalid S3 ACL ‘%s’.", $acl);
		$code    = ExceptionCode::CONFIG_INVALID_S3_ACL;

		parent::__construct($message, $code, $previous);
	}
}