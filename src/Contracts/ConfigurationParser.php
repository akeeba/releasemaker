<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Contracts;

/**
 * Interface for a configuration file parser
 */
interface ConfigurationParser
{
	public function isParsable(string $sourcePath): bool;

	public function parseFile(string $sourcePath): array;
}