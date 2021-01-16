<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Step;

use Akeeba\ReleaseMaker\Configuration\Configuration;
use Akeeba\ReleaseMaker\Configuration\Volatile\File as FileInformation;
use Akeeba\ReleaseMaker\Contracts\ExceptionCode;
use Akeeba\ReleaseMaker\Exception\ARSError;
use Akeeba\ReleaseMaker\Exception\DeploymentError;
use Akeeba\ReleaseMaker\Mixin\ARSConnectorAware;

class Publish extends AbstractStep
{
	use ARSConnectorAware;

	public function execute(): void
	{
		$configuration = Configuration::getInstance();

		$this->io->section('Publishing release and items');

		$this->io->writeln("<info>Initialisation</info>");

		$this->initARSConnector();

		$this->io->writeln("<info>Publishing items</info>");

		$publishedItems = array_reduce($configuration->volatile->files, function (bool $carry, FileInformation $fileInfo) {
			return $this->publishItem($fileInfo) && $carry;
		}, true);

		if (!$publishedItems)
		{
			throw new DeploymentError('Some items failed to publish. Will not proceed with the release.', ExceptionCode::DEPLOYMENT_ERROR_ARS_ITEM_PUBLISH_FAILED);
		}

		$this->io->writeln("<info>Publishing Release</info>");

		$this->publishRelease($configuration->volatile->release);

		$this->io->newLine();
	}

	private function publishItem(FileInformation $fileInfo): bool
	{
		try
		{
			$result = $this->arsConnector->saveItem([
				'id'        => $fileInfo->arsItemId,
				'published' => 1,
			]);
			$result = json_decode($result, true, 512, JSON_THROW_ON_ERROR);
		}
		catch (\JsonException $e)
		{
			$this->io->caution(\sprintf("Item %u has NOT been published -- Please check manually", $fileInfo->arsItemId));

			return false;
		}

		$this->io->success(\sprintf("Item %u has been published", $fileInfo->arsItemId));

		return true;
	}

	private function publishRelease(object $release)
	{
		$release->published = 1;

		try
		{
			$result = $this->arsConnector->saveRelease((array) $release);

			Configuration::getInstance()->volatile->release = json_decode($result, false, 512, JSON_THROW_ON_ERROR);
		}
		catch (\Exception $e)
		{
			throw new ARSError('Failed to publish the release', $e);
		}
	}
}
