<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Exception;


use Akeeba\ReleaseMaker\Contracts\ExceptionCode;
use RuntimeException;
use Throwable;

/**
 * Configuration file error.
 *
 * @since 2.0.0
 */
class ConfigurationError extends RuntimeException implements ExitCodeSettingException
{
	public function __construct(?string $message = null, ?int $code = null, Throwable $previous = null)
	{
		$message ??= "Configuration error";
		$code    ??= ExceptionCode::CONFIG_GENERIC_ERROR;

		/**
		 * Since we implement ExitCodeSettingException we must clamp the code between 0 and 255 (valid exit codes, at
		 * least for *NIX systems).
		 */
		if (($code < 0) || ($code > 255))
		{
			$code = ExceptionCode::CONFIG_GENERIC_ERROR;;
		}

		parent::__construct($message, $code, $previous);
	}

}