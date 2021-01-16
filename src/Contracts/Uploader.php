<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Contracts;

/**
 * Interface to an Uploader object, used to upload files to remote storage.
 *
 * @since  1.1.0
 */
interface Uploader
{
	public function __construct(ConnectionConfiguration $config);

	public function __destruct();

	public function upload(string $sourcePath, string $destPath): void;

	public function getConnectionConfiguration(): ConnectionConfiguration;
}