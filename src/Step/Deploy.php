<?php

/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Step;

use Akeeba\ReleaseMaker\Configuration;
use Akeeba\ReleaseMaker\Step\Mixin\UploadAware;
use Akeeba\ReleaseMaker\Transfer\S3;
use Akeeba\ReleaseMaker\Transfer\Uploader;

class Deploy extends AbstractStep
{
	use UploadAware;

	public function execute(): void
	{
		$this->io->section('File deployment');

		$prefixes = [
			'core',
			'pro',
		];

		foreach ($prefixes as $prefix)
		{
			$this->deployFiles($prefix);
		}

		$this->deployPdf();

		$this->io->newLine();
	}

	private function deployFiles(string $prefix, bool $isPdf = false): void
	{
		$this->io->text(\sprintf("<info>Deploying %s files</info>", $isPdf ? 'PDF' : \ucfirst($prefix)));

		$conf          = Configuration::getInstance();
		$volatileFiles = $conf->get('volatile.files');
		$files         = $isPdf ? ($volatileFiles['pdf'] ?? []) : ($volatileFiles[$prefix] ?? []);

		if (empty($files))
		{
			return;
		}

		$path = $conf->get('common.releasedir');

		foreach ($files as $filename)
		{
			$this->io->comment(\sprintf("Uploading %s", $filename));

			$sourcePath = $path . DIRECTORY_SEPARATOR . $filename;
			$destName   = $conf->get('common.version') . '/' . \basename($sourcePath);

			$this->uploadFile($prefix, $sourcePath, $destName, function (Uploader $uploader, string &$localFile, string &$remoteFile): void {
				if (!($uploader instanceof S3))
				{
					return;
				}

				$this->io->comment(\sprintf("with %s ACL", $uploader->getAcl()));
			});
		}
	}

	private function deployPdf(): void
	{
		$conf  = Configuration::getInstance();
		$where = $conf->get('pdf.where', 'core');

		$this->deployFiles($where, true);
	}
}
