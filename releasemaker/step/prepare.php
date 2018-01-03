<?php

/**
 * Akeeba Release Maker
 * An automated script to upload and release a new version of an Akeeba component.
 * Copyright (c)2006-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
class ArmStepPrepare implements ArmStepInterface
{
	private $path = null;

	private $files = array();

	public function execute()
	{
		echo "PREPARATION\n";
		echo str_repeat('-', 79) . PHP_EOL;

		// Get the configuration
		$conf       = ArmConfiguration::getInstance();
		$this->path = $conf->get('common.releasedir');

		if (empty($this->path))
		{
			throw new Exception('common.releasedir path is empty');
		}
		if (!is_dir($this->path))
		{
			throw new Exception('common.releasedir path is not a directory');
		}

		if (!is_readable($this->path))
		{
			throw new Exception('common.releasedir path is not readable');
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

	private function findFiles($type = 'core')
	{
		echo "\tFinding $type files\n";

		$ret = array();

		$conf    = ArmConfiguration::getInstance();
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
			if ($fileinfo->isFile())
			{
				$fn = $fileinfo->getFilename();
				if (!fnmatch($pattern, $fn))
				{
					continue;
				}

				$ret[] = $fn;
				echo "\t\t" . basename($fn) . "\n";
			}
		}

		return $ret;
	}

	private function findPdfFiles()
	{
		echo "\tFinding pdf files\n";

		$ret = array();

		$conf  = ArmConfiguration::getInstance();
		$files = $conf->get('pdf.files', '');

		if (empty($files))
		{
			return $ret;
		}

		if (is_string($files))
		{
			$files = array($files);
		}

		$dir = new DirectoryIterator($this->path);
		foreach ($dir as $fileinfo)
		{
			if ($fileinfo->isDot())
			{
				continue;
			}
			if ($fileinfo->isFile())
			{
				$fn = $fileinfo->getFilename();
				foreach ($files as $file)
				{
					if ($fn == $file . '.pdf')
					{
						// Get the ZIP filename
						$zipFileName = $this->path . DIRECTORY_SEPARATOR .
							$file . '.pdf.zip';
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
		}

		return $ret;
	}
}
