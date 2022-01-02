<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Exception;


use Akeeba\ReleaseMaker\Contracts\ExceptionCode;
use RuntimeException;
use Throwable;

/**
 * Runtime error originating on the remote deployment server
 *
 * @since  2.0.0
 */
class DeploymentError extends RuntimeException implements ExitCodeSettingException
{
	public function __construct(string $message, int $code = ExceptionCode::DEPLOYMENT_ERROR_GENERIC, Throwable $previous = null)
	{
		parent::__construct($message, $code, $previous);
	}
}