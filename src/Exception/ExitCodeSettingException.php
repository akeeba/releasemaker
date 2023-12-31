<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Exception;


/**
 * Notes that the error code of the exception is also a valid program exit code.
 *
 * @since  2.0.0
 */
interface ExitCodeSettingException extends \Throwable
{
}