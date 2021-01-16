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
use Akeeba\ReleaseMaker\Exception\DeploymentError;
use Akeeba\ReleaseMaker\Mixin\ARSConnectorAware;

class Items extends AbstractStep
{
	use ARSConnectorAware;

	public function execute(): void
	{
		$configuration = Configuration::getInstance();

		$this->io->section('Creating or updating items');

		$this->initARSConnector();

		if (!is_object($configuration->volatile->release) || !property_exists($configuration->volatile->release, 'id'))
		{
			$this->io->writeln("<info>Getting release information</info>");

			$configuration->volatile->release = $this->getRelease();

			$this->io->text(\sprintf("Retrieved information for release %u", $configuration->volatile->release->id));
		}

		$this->io->writeln(sprintf("<info>Creating items into release %u</info>", $configuration->volatile->release->id));

		foreach ($configuration->volatile->files as $fileInfo)
		{
			$this->io->text(sprintf('Creating / updating item for %s', basename($fileInfo->sourcePath)));

			$this->processFile($fileInfo);
		}

		$this->io->newLine();
	}

	private function processFile(FileInformation $fileInfo): void
	{
		$configuration = Configuration::getInstance();
		$releaseId     = $configuration->volatile->release->id;

		$isLink    = (strpos($fileInfo->fileOrUrl, 'http://') === 0) || (strpos($fileInfo->fileOrUrl, 'https://') === 0);
		$itemType  = $isLink ? 'link' : 'file';

		// Fetch a record of the file
		$item  = $this->arsConnector->getItem($releaseId, $itemType, $fileInfo->fileOrUrl);
		$oldId = $item->id;

		$item->release_id = $releaseId;
		$item->type       = $itemType;
		$item->filename   = $isLink ? '' : $fileInfo->fileOrUrl;
		$item->url        = $isLink ? $fileInfo->fileOrUrl : '';
		$item->access     = $fileInfo->access;
		$item->published  = 0;

		$result = $this->arsConnector->saveItem((array) $item);

		if ($result !== 'false')
		{
			$action   = $oldId ? "updated" : "created";
			$itemMeta = \json_decode($result);

			$this->io->success(\sprintf("Item %u has been %s", $itemMeta->id, $action));

			$fileInfo->arsItemId = $itemMeta->id;

			return;
		}

		$this->io->error("Failed to create item");

		throw new DeploymentError(\sprintf("Failed to create item for file %s, item type ‘%s’", basename($fileInfo->sourcePath), $itemType), ExceptionCode::DEPLOYMENT_ERROR_ARS_ITEM_EDIT_FAILED);
	}
}
