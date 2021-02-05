<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

/**
 * @noinspection PhpPrivateFieldCanBeLocalVariableInspection
 * @noinspection PhpUnusedPrivateMethodInspection
 */

namespace Akeeba\ReleaseMaker\Configuration\Volatile;

use Akeeba\ReleaseMaker\Configuration\Configuration;
use Akeeba\ReleaseMaker\Configuration\Connection\S3;
use Akeeba\ReleaseMaker\Contracts\Uploader;
use Akeeba\ReleaseMaker\Mixin\MagicGetterAware;
use Akeeba\ReleaseMaker\Mixin\MagicSetterAware;

/**
 * Processed file definition
 *
 * @property-read string   $sourcePath      Absolute local filesystem path.
 * @property-read string   $destinationPath Relative destination path to the remote filesystem.
 * @property-read string   $fileOrUrl       Relative file path or absolute URL to use in the ARS Item
 * @property-read Uploader $uploader        Uploader object used to upload this file to remote storage.
 * @property-read int      $access          Joomla Access View Level for the created / updated ARS item.
 * @property      int|null $arsItemId       The ARS Item id which was created or edited for this file.
 *
 * @since  2.0.0
 */
class File
{
	use MagicGetterAware, MagicSetterAware;

	private string $sourcePath;

	private string $destinationPath;

	private Uploader $uploader;

	private int $access;

	private ?int $arsItemId;

	public function __construct(string $sourcePath, Uploader $uploader, int $access)
	{
		$this->sourcePath      = $sourcePath;
		$this->destinationPath = Configuration::getInstance()->release->version . '/' . basename($sourcePath);
		$this->uploader        = $uploader;
		$this->access          = $access;
	}

	private function getFileOrUrl(): string
	{
		$config = $this->uploader->getConnectionConfiguration();

		if (!($config instanceof S3))
		{
			return $this->destinationPath;
		}

		/** @var \Akeeba\ReleaseMaker\Configuration\Connection\S3 $s3Config */
		$s3Config = $this->uploader->getConnectionConfiguration();
		$basePath = trim($s3Config->directory, '/');
		$basePath .= empty($basePath) ? '' : '/';

		return sprintf(
			"%s%s/%s%s",
			$config->tls ? 'https://' : 'http://',
			$config->cdnHostname,
			$basePath,
			$this->destinationPath
		);
	}

	private function setArsItemId(?int $arsItemId): void
	{
		$this->arsItemId = $arsItemId;
	}
}