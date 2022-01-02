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
 * Configuration exception: No S3 region was specified for the v4 signature type
 *
 * @since  2.0.0
 */
class NoRegion extends ConfigurationError
{
	public function __construct(Throwable $previous = null)
	{
		$message = "You need to specify a Region for your S3 configuration when using v4 signatures.";
		$code    = ExceptionCode::CONFIG_NO_REGION;

		parent::__construct($message, $code, $previous);
	}
}