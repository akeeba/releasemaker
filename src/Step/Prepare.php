<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Step;

use Akeeba\ReleaseMaker\Configuration;
use Akeeba\ReleaseMaker\Exception\FatalProblem;
use DirectoryIterator;
use ZipArchive;

class Prepare extends AbstractStep
{
	private $path;

	private $files = [];

	public function execute(): void
	{
		$this->io->section("Preparation");

		// Get the configuration
		$conf       = Configuration::getInstance();
		$this->path = $conf->get('common.releasedir');

		if (empty($this->path))
		{
			throw new FatalProblem('common.releasedir path is empty', 20);
		}
		if (!\is_dir($this->path))
		{
			throw new FatalProblem('common.releasedir path is not a directory', 21);
		}

		if (!\is_readable($this->path))
		{
			throw new FatalProblem('common.releasedir path is not readable', 22);
		}

		// Find the files
		$this->files['core'] = $this->findFiles();
		$this->files['pro']  = $this->findFiles('pro');
		$this->files['pdf']  = $this->findPdfFiles();

		if (empty($this->files['core']) && empty($this->files['pro']))
		{
			// No files whatsoever? Oops!
			throw new FatalProblem('No extension files (Core or Pro) found. Aborting execution.', 23);
		}

		// Add the files to the volatile config key
		$conf->set('volatile.files', $this->files);

		$this->io->newLine();
	}

	private function findFiles(string $type = 'core'): array
	{
		$this->io->writeln(\sprintf("<info>Finding %s files</info>", \ucfirst($type)));

		$ret = [];

		$conf    = Configuration::getInstance();
		$pattern = $conf->get($type . '.pattern', '');

		if (empty($pattern))
		{
			$pattern = 'pkg_*' . $type . '.zip';
		}

		$dir = new DirectoryIterator($this->path);

		foreach ($dir as $fileinfo)
		{
			if ($fileinfo->isDot())
			{
				continue;
			}

			if (!$fileinfo->isFile())
			{
				continue;
			}

			$fn = $fileinfo->getFilename();

			if (!\fnmatch($pattern, $fn))
			{
				continue;
			}

			$ret[] = $fn;

			$this->io->comment(\sprintf("Found %s", \basename($fn)));
		}

		if (empty($ret))
		{
			$this->io->caution(\sprintf("No %s files were found", \ucfirst($type)));
		}

		return $ret;
	}

	private function findPdfFiles(): array
	{
		$this->io->writeln("<info>Finding PDF / miscellaneous files</info>");

		$ret = [];

		$conf  = Configuration::getInstance();
		$files = $conf->get('pdf.files', '');

		if (empty($files))
		{
			return $ret;
		}

		if (\is_string($files))
		{
			$files = [$files];
		}

		foreach ((new DirectoryIterator($this->path)) as $fileinfo)
		{
			if ($fileinfo->isDot() || !$fileinfo->isFile())
			{
				continue;
			}

			$fn = $fileinfo->getFilename();

			foreach ($files as $file)
			{
				switch ($fn)
				{
					// PDF Files: Zip them
					case $file . '.pdf':
						// Get the ZIP filename
						$zipFileName = $this->path . DIRECTORY_SEPARATOR . $file . '.pdf.zip';

						// Remove old ZIP file
						if (\file_exists($zipFileName))
						{
							\unlink($zipFileName);
						}

						// Compress the PDF
						$zip = new ZipArchive();
						$zip->open($zipFileName, ZIPARCHIVE::CREATE);
						$zip->addFile($this->path . DIRECTORY_SEPARATOR . $fn, \basename($fn));
						$zip->close();

						// Remove the PDF file
						\unlink($this->path . DIRECTORY_SEPARATOR . $fn);

						// Add the ZIP file to the list
						$ret[] = \basename($zipFileName);

						break;


					case $file . '.pdf.zip':
					case $file . '.zip':
					case $file:
						// Just add compressed PDF, compressed or bespoke file
						$ret[] = $fn;

						break;

					default:
						continue 2;
				}

				$this->io->comment(\sprintf("Found %s", \basename($fn)));
			}
		}

		return $ret;
	}
}
