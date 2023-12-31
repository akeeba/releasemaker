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
 * Configuration exception: Invalid update format
 *
 * @since  2.0.0
 */
class InvalidUpdateFormat extends ConfigurationError
{
	public function __construct(string $updateFormat, Throwable $previous = null)
	{
		$message = sprintf("Invalid update format ‘%s’.", $updateFormat);
		$code    = ExceptionCode::CONFIG_INVALID_UPDATE_FORMAT;

		parent::__construct($message, $code, $previous);
	}
}