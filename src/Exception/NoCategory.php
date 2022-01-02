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
 * Configuration exception: category missing in release
 *
 * @since  2.0.0
 */
class NoCategory extends ConfigurationError
{
	public function __construct(Throwable $previous = null)
	{
		$message = "You need to specify a category in your release.";
		$code    = ExceptionCode::CONFIG_NO_CATEGORY;

		parent::__construct($message, $code, $previous);
	}
}