<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Transfer;

interface Uploader
{
	/**
	 * Creates an uploader object.
	 *
	 * @param   object  $config  Configuration parameters to initialise the remote connection
	 */
	public function __construct(object $config);

	/**
	 * Destructor.
	 *
	 * Tears down any internal objects and closes any active connections. Called on object destruction.
	 */
	public function __destruct();

	/**
	 * Upload a file to remote storage.
	 *
	 * @param   string  $sourcePath  Absolute local filesystem path of the file to upload.
	 * @param   string  $destPath    Relative remote filesystem to upload the file to.
	 *
	 * @throws  \RuntimeException  When an upload error occurs.
	 */
	public function upload(string $sourcePath, string $destPath): void;
}