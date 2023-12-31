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
 * Configuration exception: it's not possible to authenticate to ARS with the given configuration
 *
 * @since  2.0.0
 */
class NoArsAuthenticationPossible extends ConfigurationError
{
	public function __construct(Throwable $previous = null)
	{
		$message = "You need to specify an ARS authentication method: either a username and password; or a FOF token.";
		$code    = ExceptionCode::CONFIG_NO_ARS_AUTHENTICATION;

		parent::__construct($message, $code, $previous);
	}
}