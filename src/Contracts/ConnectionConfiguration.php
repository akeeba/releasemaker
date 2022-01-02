<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Contracts;

/**
 * Interface to a Connection configuration object.
 *
 * @since  2.0.0
 */
interface ConnectionConfiguration
{
	public function getType(): string;

	public function setDirectory(string $directory): void;

	public function getUploader(?string $directory): Uploader;
}