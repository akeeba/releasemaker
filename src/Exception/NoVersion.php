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
 * Configuration exception: version missing in release
 *
 * @since  2.0.0
 */
class NoVersion extends ConfigurationError
{
	public function __construct(Throwable $previous = null)
	{
		$message = "You need to specify a version in your release.";
		$code    = ExceptionCode::CONFIG_NO_VERSION;

		parent::__construct($message, $code, $previous);
	}
}