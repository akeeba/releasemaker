<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Step;

use Akeeba\ReleaseMaker\Configuration;
use Akeeba\ReleaseMaker\Utils\ARS;

class Publish implements StepInterface
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
		echo "PUBLISHING RELEASE AND ITEMS\n";
		echo str_repeat('-', 79) . PHP_EOL;

		echo "\tInitialisation\n";

		$this->init();

		echo "\tPublishing Core items\n";

		$this->publishItems($this->publishInfo['core']);

		echo "\tPublishing Pro items\n";

		$this->publishItems($this->publishInfo['pro']);

		echo "\tPublishing PDF items\n";

		$this->publishItems($this->publishInfo['pdf']);

		echo "\tPublishing release\n";

		$this->publishRelease($this->publishInfo['release']);

		echo PHP_EOL;
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

			echo "\t\t" . basename($fileOrURL);

			$item = $this->arsConnector->getItem($this->publishInfo['release']->id, $item->type, $fileOrURL);

			if (!$item->id)
			{
				echo " -- NOT EXISTS!\n";

				continue;
			}
			echo " ({$item->id})";

			$item->environments = '';
			$item->published    = 1;
			$result             = $this->arsConnector->saveItem((array) $item);

			if ($result !== false)
			{
				echo " -- OK!\n";

				return;
			}

			echo " -- FAILED\n";
		}
	}

	private function publishRelease(object $release): void
	{
		$release->published = 1;

		$this->arsConnector->saveRelease((array) $release);
	}
}
