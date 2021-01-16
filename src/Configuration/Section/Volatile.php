<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Configuration\Section;

use Akeeba\ReleaseMaker\Configuration\Volatile\File;

/**
 * Volatile data, used while the application is running
 *
 * @property File[] $files   Files being processed
 * @property object $release Release item being processed
 */
class Volatile
{
	private array $files;

	private object $release;
}