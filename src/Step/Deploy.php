<?php

/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Step;

use Akeeba\ReleaseMaker\Configuration\Configuration;

class Deploy extends AbstractStep
{
	public function execute(): void
	{
		$this->io->section('File deployment');

		$configuration = Configuration::getInstance();

		foreach ($configuration->volatile->files as $fileInformation)
		{
			$this->io->comment(\sprintf("Uploading %s", basename($fileInformation->sourcePath)));

			$fileInformation->uploader->upload($fileInformation->sourcePath, $fileInformation->destinationPath);
		}

		$this->io->newLine();
	}
}
