<?php
/**
 * Akeeba Release Maker
 * An automated script to upload and release a new version of an Akeeba component.
 * Copyright ©2012-2013 Nicholas K. Dionysopoulos / Akeeba Ltd.
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
			
			$item->environments = null;
			$item->published = 1;
			$result = $this->arsConnector->saveItem((array) $item);
			
			if ($result)
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