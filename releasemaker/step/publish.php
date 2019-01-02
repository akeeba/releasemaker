<?php
/**
 * Akeeba Release Maker
 * An automated script to upload and release a new version of an Akeeba component.
 *
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2006-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU/GPLv3
 */

class ArmStepPublish implements ArmStepInterface
{
	/** @var ArmArs The ARS connector class */
	private $arsConnector = null;

	private $publishInfo = array(
		'release'	=> null,
		'core'		=> array(),
		'pro'		=> array(),
		'pdf'		=> array(),
	);

	public function execute()
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

	/**
	 * Initialise the process, getting an arsConnector and
	 */
	private function init()
	{
		$conf = ArmConfiguration::getInstance();

		$this->arsConnector = new ArmArs(array(
			'host'		=> $conf->get('common.arsapiurl', ''),
			'username'	=> $conf->get('common.username', ''),
			'password'	=> $conf->get('common.password', ''),
		));

		$this->publishInfo = $conf->get('volatile.publishInfo');
	}

	private function publishItems(Array $items)
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
			else
			{
				echo " ({$item->id})";
			}

			$item->environments = '';
			$item->published = 1;
			$result = $this->arsConnector->saveItem((array) $item);

			if ($result !== false)
			{
				echo " -- OK!\n";
			}
			else
			{
				echo " -- FAILED\n";
			}
		}
	}

	private function publishRelease($release)
	{
		$release->published = 1;
		$this->arsConnector->saveRelease((array) $release);
	}
}
