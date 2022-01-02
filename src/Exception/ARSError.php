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
 * Error originating on the remote Akeeba Release System installation
 *
 * @since  2.0.0
 */
class ARSError extends DeploymentError
{
	public function __construct(string $message, Throwable $previous = null)
	{
		$code = ExceptionCode::DEPLOYMENT_ERROR_ARS_GENERIC;

		parent::__construct($message, $code, $previous);
	}
}