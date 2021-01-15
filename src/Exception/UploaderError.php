<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Exception;


use Akeeba\ReleaseMaker\Contracts\ExceptionCode;
use RuntimeException;
use Throwable;

/**
 * Runtime error trying to upload files to remote storage
 *
 * @since  2.0.0
 */
class UploaderError extends RuntimeException implements ExitCodeSettingException
{
	public function __construct(string $message, Throwable $previous = null)
	{
		$code    = ExceptionCode::UPLOADER_ERROR;

		parent::__construct($message, $code, $previous);
	}
}