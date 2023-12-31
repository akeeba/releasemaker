<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Configuration\Source;


use Akeeba\ReleaseMaker\Configuration\Configuration;
use Akeeba\ReleaseMaker\Contracts\Uploader;
use Akeeba\ReleaseMaker\Exception\InvalidConnectionKey;
use Akeeba\ReleaseMaker\Mixin\MagicGetterAware;

/**
 * File Source
 *
 * @property-read string   $title             The title for this file source.
 * @property-read string   $connectionName    The name (key) of the connection used to upload this file source.
 * @property-read ?string  $directory         The directory override for the connection.
 * @property-read int      $access            Joomla! View Access Level for the files of this file source.
 * @property-read string   $sourcePathPattern Absolute filesystem path and pattern to find files.
 * @property-read string   $sourceDirectory   Absolute filesystem path to the directory containing the files.
 * @property-read string   $matchPattern      Filesystem (fnmatch) pattern to find the files in this source.
 * @property-read Uploader $uploader          The Uploader object to be used for this file source.
 * @property-read string[] $files             Absolute filesystem paths to files matching this source's pattern.
 *
 * @since  2.0.0
 */
class File
{
	use MagicGetterAware;

	private string $title;

	private string $connectionName;

	private ?string $directory;

	private string $sourcePathPattern;

	private int $access = 2;

	private string $sourceDirectory;

	private string $matchPattern;

	private Configuration $parent;

	public function __construct(array $configuration, Configuration $parent)
	{
		$this->parent = $parent;

		$this->setSourcePath($configuration['source'] ?? '');

		$this->title          = $configuration['title'] ?? sprintf('File matching ' . $this->matchPattern);
		$this->connectionName = $configuration['connection'] ?? '';
		$this->directory      = $configuration['directory'] ?? null;
		$this->access         = (int) ($configuration['access'] ?? 2);

		if (!in_array($this->connectionName, $this->parent->connection->getConnectionKeys()))
		{
			throw new InvalidConnectionKey($this->connectionName);
		}
	}

	private function setSourcePath(string $sourcePathPattern): void
	{
		$this->sourcePathPattern = $sourcePathPattern;
		$this->sourceDirectory   = dirname($sourcePathPattern);
		$this->matchPattern      = basename($sourcePathPattern);
	}

	/** @noinspection PhpUnusedPrivateMethodInspection */
	private function getUploader(): Uploader
	{
		$connection = $this->parent->connection->getConnection($this->connectionName);

		return $connection->getUploader($this->directory);
	}

	/** @noinspection PhpUnusedPrivateMethodInspection */
	private function getFiles(): array
	{
		$files = [];

		/** @var \DirectoryIterator $file */
		foreach ((new \DirectoryIterator($this->sourceDirectory)) as $file)
		{
			if ($file->isDot() || !$file->isFile() || !$file->isReadable())
			{
				continue;
			}

			if (!fnmatch($this->matchPattern, $file->getFilename()))
			{
				continue;
			}

			$files[] = $file->getPathname();
		}

		return $files;
	}
}