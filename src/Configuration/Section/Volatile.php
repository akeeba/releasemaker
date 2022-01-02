<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Configuration\Section;

use Akeeba\ReleaseMaker\Configuration\Configuration;
use Akeeba\ReleaseMaker\Configuration\Volatile\File;
use Akeeba\ReleaseMaker\Contracts\ConfigurationSection;
use Akeeba\ReleaseMaker\Mixin\MagicGetterAware;
use Akeeba\ReleaseMaker\Mixin\MagicSetterAware;

/**
 * Volatile data, used while the application is running
 *
 * @property File[] $files   Files being processed
 * @property object $release Release item being processed
 */
final class Volatile implements ConfigurationSection
{
	use MagicGetterAware, MagicSetterAware;

	private array $files;

	private ?object $release;

	/** @noinspection PhpUnusedParameterInspection */
	public function __construct(array $configuration, Configuration $parent)
	{
		$this->files   = [];
		$this->release = null;
	}

	/** @noinspection PhpUnusedPrivateMethodInspection */
	private function setFiles(array $files): void
	{
		$this->files = $files;
	}

	/** @noinspection PhpUnusedPrivateMethodInspection */
	private function &getRelease(): ?object
	{
		return $this->release;
	}

	/** @noinspection PhpUnusedPrivateMethodInspection */
	private function setRelease(?object $release): void
	{
		$this->release = $release;
	}

	/** @noinspection PhpUnusedPrivateMethodInspection */
	private function &getFiles(): array
	{
		return $this->files;
	}
}