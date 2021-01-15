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
 * Configuration exception: invalid step
 *
 * @since  2.0.0
 */
class InvalidStep extends ConfigurationError
{
	public function __construct(string $step, Throwable $previous = null)
	{
		$message = sprintf("Invalid step ‘%s’. Unknown class or does not implement StepInterface.", $step);
		$code    = ExceptionCode::INVALID_STEP;

		parent::__construct($message, $code, $previous);
	}
}