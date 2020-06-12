<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Step;

use Akeeba\ReleaseMaker\Configuration;
use DirectoryIterator;
use RuntimeException;
use ZipArchive;

class Prepare implements StepInterface
{
	private $path = null;

	private $files = [];

	public function execute(): void
	{
		echo "PREPARATION\n";
		echo str_repeat('-', 79) . PHP_EOL;

		// Get the configuration
		$conf       = Configuration::getInstance();
		$this->path = $conf->get('common.releasedir');

		if (empty($this->path))
		{
			throw new RuntimeException('common.releasedir path is empty');
		}
		if (!is_dir($this->path))
		{
			throw new RuntimeException('common.releasedir path is not a directory');
		}

		if (!is_readable($this->path))
		{
			throw new RuntimeException('common.releasedir path is not readable');
		}

		// Find the files
		$this->files['core'] = $this->findFiles('core');
		$this->files['pro']  = $this->findFiles('pro');
		$this->files['pdf']  = $this->findPdfFiles();

		if (empty($this->files['core']) && empty($this->files['pro']))
		{
			// No files whatsoever? Oops!
			throw new RuntimeException('No extension files (Core or Pro) found. Aborting execution.', 500);
		}

		// Add the files to the volatile config key
		$conf->set('volatile.files', $this->files);

		echo PHP_EOL;
	}

	private function findFiles(string $type = 'core'): array
	{
		echo "\tFinding $type files\n";

		$ret = [];

		$conf    = Configuration::getInstance();
		$pattern = $conf->get($type . '.pattern', '');

		if (empty($pattern))
		{
			$pattern = 'com_*' . $type . '.zip';
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

			if (!fnmatch($pattern, $fn))
			{
				continue;
			}

			$ret[] = $fn;

			echo "\t\t" . basename($fn) . "\n";
		}

		return $ret;
	}

	private function findPdfFiles(): array
	{
		echo "\tFinding pdf files\n";

		$ret = [];

		$conf  = Configuration::getInstance();
		$files = $conf->get('pdf.files', '');

		if (empty($files))
		{
			return $ret;
		}

		if (is_string($files))
		{
			$files = [$files];
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

			foreach ($files as $file)
			{
				if ($fn == $file . '.pdf')
				{
					// Get the ZIP filename
					$zipFileName = $this->path . DIRECTORY_SEPARATOR . $file . '.pdf.zip';

					// Remove old ZIP file
					if (file_exists($zipFileName))
					{
						unlink($zipFileName);
					}

					// Compress the PDF
					$zip = new ZipArchive();
					$zip->open($zipFileName, ZIPARCHIVE::CREATE);
					$zip->addFile($this->path . DIRECTORY_SEPARATOR . $fn, basename($fn));
					$zip->close();

					// Remove the PDF file
					unlink($this->path . DIRECTORY_SEPARATOR . $fn);

					// Add the ZIP file to the list
					$ret[] = basename($zipFileName);

					echo "\t\t" . basename($fn) . "\n";
				}
				elseif ($fn == $file . '.pdf.zip')
				{
					// Just add compressed PDF file
					$ret[] = $fn;

					echo "\t\t" . basename($fn) . "\n";
				}
				elseif ($fn == $file . '.zip')
				{
					// Just add compressed file
					$ret[] = $fn;

					echo "\t\t" . basename($fn) . "\n";
				}
				elseif ($fn == $file)
				{
					// Just add bespoke file, including extension
					$ret[] = $fn;

					echo "\t\t" . basename($fn) . "\n";
				}
			}
		}

		return $ret;
	}
}
