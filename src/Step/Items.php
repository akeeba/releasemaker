<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Step;

use Akeeba\ReleaseMaker\Configuration;
use Akeeba\ReleaseMaker\Utils\ARS;
use stdClass;

class Items implements StepInterface
{
	/** @var ARS The ARS connector class */
	private $arsConnector = null;

	/** @var stdClass The release we will be saving items to */
	private $release = null;

	private $publishInfo = [
		'release' => null,
		'core'    => [],
		'pro'     => [],
		'pdf'     => [],
	];

	public function execute(): void
	{
		echo "CREATING OR UPDATING ITEMS\n";
		echo str_repeat('-', 79) . PHP_EOL;

		echo "\tGetting release information\n";

		$this->retrieveReleaseInfo();
		$this->publishInfo['release'] = $this->release;

		echo "\tCreating items for Core files\n";

		$this->deployFiles('core');

		echo "\tCreating items for Pro files\n";

		$this->deployFiles('pro');

		echo "\tCreating items for PDF files\n";

		$this->deployFiles('core', true);

		echo "\tSaving publish information\n";

		$conf = Configuration::getInstance();
		$conf->set('volatile.publishInfo', $this->publishInfo);

		echo PHP_EOL;
	}

	/**
	 * Set $this->release to the record for the release we will be using to save items to.
	 */
	private function retrieveReleaseInfo(): void
	{
		$conf = Configuration::getInstance();

		$this->arsConnector = new ARS([
			'host'     => $conf->get('common.arsapiurl', ''),
			'username' => $conf->get('common.username', ''),
			'password' => $conf->get('common.password', ''),
			'apiToken' => $conf->get('common.token', ''),
		]);

		$category = $conf->get('common.category', 0);
		$version  = $conf->get('common.version', 0);

		$this->release = $this->arsConnector->getRelease($category, $version);
	}

	private function deployFiles(string $prefix = 'core', bool $isPdf = false): void
	{
		// Get the files
		$publishArea = $prefix;

		if ($isPdf || ($prefix == 'pdf'))
		{
			$publishArea = 'pdf';
			$prefix      = 'core';
			$isPdf       = true;
		}

		$this->publishInfo[$publishArea] = [];

		$conf      = Configuration::getInstance();
		$files     = $conf->get('volatile.files');
		$coreFiles = $files[$prefix] ?? [];

		if ($isPdf)
		{
			$coreFiles = $files['pdf'] ?? [];
		}

		if (empty($coreFiles))
		{
			echo "\t\tNO FILES\n";

			return;
		}

		$access = $conf->get("$prefix.access", "1");

		foreach ($coreFiles as $filename)
		{
			// Get the filename and path used in ARS
			echo "\t\tCreating/updating item for $filename ";

			$type = $conf->get($prefix . '.method', $conf->get('common.update.method', 'sftp'));

			switch ($type)
			{
				// TODO Add GitHub case

				case 's3':
					$version     = $conf->get('common.version');
					$reldir      = $conf->get($prefix . '.s3.reldir');
					$cdnHostname = $conf->get($prefix . '.s3.cdnhostname');
					$destName    = $version . '/' . basename($filename);

					if (empty($cdnHostname))
					{
						$fileOrURL = 's3://' . $reldir . '/' . $destName;
						$type      = 'file';
					}
					else
					{
						$directory = $conf->get($prefix . '.s3.directory', $conf->get('common.update.s3.directory', ''));
						$fileOrURL = 'https://' . $cdnHostname . '/' . $directory . '/' . $destName;
						$type      = 'link';
					}

					break;

				case 'ftp':
				case 'ftpcurl':
				case 'ftps':
				case 'ftpscurl':
				case 'sftp':
				case 'sftpcurl':
				default:
					$version   = $conf->get('common.version');
					$fileOrURL = $version . '/' . basename($filename);
					$type      = 'file';
					break;
			}

			// Fetch a record of the file
			$item = $this->arsConnector->getItem($this->release->id, $type, $fileOrURL);

			$item->release_id = $this->release->id;
			$item->type       = $type;
			$item->filename   = ($type == 'file') ? $fileOrURL : '';
			$item->url        = ($type == 'link') ? $fileOrURL : '';
			$item->access     = $access;
			$item->published  = 0;

			$this->publishInfo[$publishArea][] = $item;

			$result = $this->arsConnector->saveItem((array) $item);

			if ($result !== 'false')
			{
				echo " -- OK\n";

				return;
			}

			echo " -- FAILED\n";
			die("\n");
		}
	}
}