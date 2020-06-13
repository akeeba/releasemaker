<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Exception;

use RuntimeException;

/**
 * FatalProblem: Something went so horribly bad ARM has to terminate immediately
 *
 * The error code of the exception will be the application's exit code
 */
class FatalProblem extends RuntimeException
{

}