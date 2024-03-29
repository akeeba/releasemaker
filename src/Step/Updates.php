<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Step;

use Akeeba\ReleaseMaker\Configuration\Configuration;
use Akeeba\ReleaseMaker\Configuration\Update\Source as UpdateSource;

class Updates extends AbstractStep
{
	public function execute(): void
	{
		$configuration = Configuration::getInstance();

		$this->io->section("Pushing update information");

		foreach ($configuration->updates->sources as $updateSource)
		{
			$this->io->writeln(sprintf('<info>Pushing updates: %s</info>', $updateSource->title));

			$this->pushUpdate($updateSource);
		}

		$this->io->newLine();
	}

	private function pushUpdate(UpdateSource $updateSource)
	{
		$configuration = Configuration::getInstance();

		if ((int) $updateSource->stream == 0)
		{
			$this->io->note('Will not fetch updates (no update stream ID was configured)');

			return;
		}

		if (($updateSource->baseName == '') || !count($updateSource->formats))
		{
			$this->io->note('Will not fetch updates (no base name or format was configured)');

			return;
		}

		$nonApiUrl = $configuration->api->endpoint;

		if (strpos($nonApiUrl, '/api/index.php'))
		{
			$nonApiUrl = str_replace('/api/index.php', '/index.php', $nonApiUrl);
		}

		$urlPattern = $nonApiUrl . '?option=com_ars&view=update&task=%s&format=%s&id=' . $updateSource->stream;

		foreach ($updateSource->formats as $format)
		{
			$task         = match ($format)
			{
				'xml' => 'stream',
				default => $format
			};
			$joomlaFormat = match ($format)
			{
				'inibare' => 'ini',
				default => $format
			};
			$url          = sprintf($urlPattern, $task, $joomlaFormat);

			$context = \stream_context_create(
				[
					'http' => [
						'method' => 'GET',
					],
					'ssl'  => [
						'verify_peer'  => true,
						'cafile'       => $configuration->api->CACertPath,
						'verify_depth' => 5,
					],
				]
			);
			$data    = \file_get_contents($url, false, $context);

			if ($data === false)
			{
				continue;
			}

			$tempFilePointer = \tmpfile();
			$tempFilePath    = \stream_get_meta_data($tempFilePointer)['uri'];

			$written = fwrite($tempFilePointer, $data);

			if ($written === false)
			{
				continue;
			}

			$destName = $updateSource->baseName;
			$destName .= ($format === 'inibare') ? '' : ('.' . $format);

			$this->io->text(\sprintf("Pushing %s update format", $format));

			$updateSource->uploader->upload($tempFilePath, $destName);

			@fclose($tempFilePointer);
		}
	}
}
