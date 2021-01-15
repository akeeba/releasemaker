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
 * Configuration exception: Invalid ARS update stream ID
 *
 * @since  2.0.0
 */
class InvalidUpdateStream extends ConfigurationError
{
	public function __construct(?int $updateStream, Throwable $previous = null)
	{
		$message = sprintf("Invalid ARS update stream ID ‘%d’.", $updateStream ?? 0);
		$code    = ExceptionCode::CONFIG_INVALID_UPDATE_STREAM;

		parent::__construct($message, $code, $previous);
	}
}