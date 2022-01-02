<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Step;

use Akeeba\ReleaseMaker\Configuration\Configuration;
use Akeeba\ReleaseMaker\Configuration\Volatile\File as FileInformation;
use Akeeba\ReleaseMaker\Contracts\ExceptionCode;
use Akeeba\ReleaseMaker\Exception\ConfigurationError;

class Prepare extends AbstractStep
{
	public function execute(): void
	{
		$this->io->section("Preparation");

		// Get the configuration
		$conf = Configuration::getInstance();

		$files = [];

		foreach ($conf->sources->sources as $fileSource)
		{
			$this->io->write(sprintf('<info>Scanning ‘%s’ files</info>', $fileSource->title));

			$filePaths = $fileSource->files;

			if (empty($filePaths))
			{
				$this->io->caution(sprintf('No ‘%s’ files found', $fileSource->title));
			}

			foreach ($filePaths as $sourcePath)
			{
				$this->io->comment(\sprintf("Found %s", \basename($sourcePath)));
				$files[] = new FileInformation($sourcePath, $fileSource->uploader, $fileSource->access);
			}
		}

		if (empty($files))
		{
			// No files whatsoever? Oops!
			throw new ConfigurationError('No extension files (Core or Pro) found. Aborting execution.', ExceptionCode::NO_FILES_FOUND);
		}

		$conf->volatile->files = $files;

		$this->io->newLine();
	}
}
