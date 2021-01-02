<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Step;

use Akeeba\ReleaseMaker\Configuration;
use Akeeba\ReleaseMaker\Utils\ARS;

class Publish extends AbstractStep
{
	/** @var ARS The ARS connector class */
	private $arsConnector = null;

	private $publishInfo = [
		'release' => null,
		'core'    => [],
		'pro'     => [],
		'pdf'     => [],
	];

	public function execute(): void
	{
		$this->io->section('Publishing release and items');

		$this->io->writeln("<info>Initialisation</info>");

		$this->init();

		$this->io->writeln("<info>Publishing Core items</info>");

		$this->publishItems($this->publishInfo['core']);

		$this->io->writeln("<info>Publishing Pro items</info>");

		$this->publishItems($this->publishInfo['pro']);

		$this->io->writeln("<info>Publishing PDF items</info>");

		$this->publishItems($this->publishInfo['pdf']);

		$this->io->writeln("<info>Publishing Release</info>");

		$this->publishRelease($this->publishInfo['release']);

		$this->io->newLine();
	}

	private function init(): void
	{
		$conf = Configuration::getInstance();

		$this->arsConnector = new ARS([
			'host'     => $conf->get('common.arsapiurl', ''),
			'username' => $conf->get('common.username', ''),
			'password' => $conf->get('common.password', ''),
			'apiToken' => $conf->get('common.token', ''),
		]);

		$this->publishInfo = $conf->get('volatile.publishInfo');
	}

	private function publishItems(array $items): void
	{
		if (empty($items))
		{
			return;
		}

		foreach ($items as $item)
		{
			$fileOrURL = ($item->type == 'file') ? $item->filename : $item->url;

			$this->io->writeln(sprintf("Publishing %s", basename($fileOrURL)));

			$item = $this->arsConnector->getItem($this->publishInfo['release']->id, $item->type, $fileOrURL);

			if (!$item->id)
			{
				$this->io->caution(sprintf("Item for %s does not exist.", basename($fileOrURL)));

				continue;
			}

			$item->environments = '';
			$item->published    = 1;
			$result             = $this->arsConnector->saveItem((array) $item);

			if ($result !== false)
			{
				$this->io->success(sprintf("Item %u has been published", $item->id));

				return;
			}

			$this->io->caution(sprintf("Item %u has NOT been published -- Please check manually", $item->id));
		}
	}

	private function publishRelease(object $release): void
	{
		$release->published = 1;

		$this->arsConnector->saveRelease((array) $release);
	}
}
