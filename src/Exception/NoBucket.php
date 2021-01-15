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
 * Configuration exception: No S3 bucket was specified
 *
 * @since  2.0.0
 */
class NoBucket extends ConfigurationError
{
	public function __construct(Throwable $previous = null)
	{
		$message = "You need to specify a Bucket for your S3 configuration.";
		$code    = ExceptionCode::CONFIG_NO_BUCKET;

		parent::__construct($message, $code, $previous);
	}
}