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
 * Configuration exception: the specified ARS endpoint is not a URL
 *
 * @since  2.0.0
 */
class ARSEndpointIsNotAURL extends ConfigurationError
{
	public function __construct(Throwable $previous = null)
	{
		$message = "The ARS endpoint you specified is not a valid URL.";
		$code    = ExceptionCode::CONFIG_INVALID_ARS_ENDPOINT;

		parent::__construct($message, $code, $previous);
	}
}