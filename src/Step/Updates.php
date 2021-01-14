<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Step;

use Akeeba\ReleaseMaker\Configuration;
use Akeeba\ReleaseMaker\Step\Mixin\UploadAware;

class Updates extends AbstractStep
{
	use UploadAware;

	public function execute(): void
	{
		$this->io->section("Pushing update information");

		$this->io->writeln("<info>Pushing Core updates</info>");

		$this->deployUpdates();

		$this->io->writeln("<info>Pushing Pro updates</info>");

		$this->deployUpdates('pro');

		$this->io->newLine();
	}

	private function deployUpdates(string $prefix = 'core'): void
	{
		$conf = Configuration::getInstance();
		$type = $conf->get('common.update.method', 'sftp');

		if ($type === 'none')
		{
			$this->io->note(\sprintf("Skipping %s updates (format set to “none”)", \ucfirst($prefix)));

			return;
		}

		$stream_id = $conf->get($prefix . '.update.stream', 0);
		$formats   = $conf->get($prefix . '.update.formats', []);
		$basename  = $conf->get($prefix . '.update.basename', '');
		$url       = $conf->get('common.arsapiurl', '');

		// No base name means that no updates are set here
		if (empty($basename))
		{
			$this->io->note(\sprintf("There are no %s updates", \ucfirst($prefix)));

			return;
		}

		$tempPath = \realpath(__DIR__ . '/../../tmp/');

		foreach ($formats as $format_raw)
		{
			$this->io->text(\sprintf("Pushing %s update format over %s", $format_raw, $type));

			// Default to the XML update format
			$extension = '.xml';
			$format    = 'xml';
			$task      = '&task=stream';

			switch ($format_raw)
			{
				case 'ini':
					$extension = '.ini';
					$format    = 'ini';
					$task      = '';
					break;

				case 'inibare':
					$extension = '';
					$format    = 'ini';
					$task      = '';
					break;
			}

			$temp_filename = $tempPath . '/' . $basename . $extension;
			$url           .= (\substr($url, -4) === '.php') ? '' : '/index.php';
			$updateURL     = \sprintf("%s?option=com_ars&view=update%s&format=%s&id=%s%s", $url, $task, $format, $stream_id, $task);

			$context = \stream_context_create([
				'http' => [
					'method' => 'GET',
				],
				'ssl'  => [
					'verify_peer'  => true,
					'cafile'       => AKEEBA_CACERT_PEM,
					'verify_depth' => 5,
				],
			]);
			$data    = \file_get_contents($updateURL, false, $context);

			/**
			 * When we do not have updates for a specific item we might choose to use a fake update stream ID, e.g.
			 * 99999. In this case trying to access its URL will throw an error which means that file_get_contents
			 * returns false. This if-block makes sure this won't break everything.
			 */
			if ($data === false)
			{
				continue;
			}

			\file_put_contents($temp_filename, $data);

			$destName = \basename($temp_filename);

			$this->uploadFile($prefix, $temp_filename, $destName);

			\unlink($temp_filename);
		}
	}
}
