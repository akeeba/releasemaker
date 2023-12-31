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
 * Configuration exception: no ARS endpoint specified
 *
 * @since  2.0.0
 */
class NoARSEndpoint extends ConfigurationError
{
	public function __construct(Throwable $previous = null)
	{
		$message = "You need to specify the ARS endpoint URL.";
		$code    = ExceptionCode::CONFIG_NO_ENDPOINT;

		parent::__construct($message, $code, $previous);
	}
}