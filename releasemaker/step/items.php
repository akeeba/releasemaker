<?php
/**
 * Akeeba Release Maker
 * An automated script to upload and release a new version of an Akeeba component.
 * Copyright ©2012-2014 Nicholas K. Dionysopoulos / Akeeba Ltd.
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

class ArmStepItems implements ArmStepInterface
{
	/** @var ArmArs The ARS connector class */
	private $arsConnector = null;

	/** @var stdClass The release we will be saving items to */
	private $release = null;

	private $publishInfo = array(
		'release'	=> null,
		'core'		=> array(),
		'pro'		=> array(),
		'pdf'		=> array(),
	);

	public function execute()
	{
		echo "CREATING OR UPDATING ITEMS\n";
		echo str_repeat('-', 79) . PHP_EOL;

		echo "\tGetting release information\n";
		$this->getReleaseId();
		$this->publishInfo['release'] = $this->release;

		echo "\tCreating items for Core files\n";
		$this->deployFiles('core');

		echo "\tCreating items for Pro files\n";
		$this->deployFiles('pro');

		echo "\tCreating items for PDF files\n";
		$this->deployFiles('core', true);

		echo "\tSaving publish information\n";
		$conf = ArmConfiguration::getInstance();
		$conf->set('volatile.publishInfo', $this->publishInfo);

		echo PHP_EOL;
	}

	/**
	 * Get the record for the release we will be using to save items to and
	 * save it in the private $release class property
	 */
	private function getReleaseId()
	{
		$conf = ArmConfiguration::getInstance();

		$this->arsConnector = new ArmArs(array(
			'host'		=> $conf->get('common.arsapiurl', ''),
			'username'	=> $conf->get('common.username', ''),
			'password'	=> $conf->get('common.password', ''),
		));

		$category	= $conf->get('common.category', 0);
		$version	= $conf->get('common.version', 0);

		$this->release = $this->arsConnector->getRelease($category, $version);
	}

	private function deployFiles($prefix = 'core', $isPdf = false)
	{
		// Get the files
		if ($isPdf || ($prefix == 'pdf'))
		{
			$publishArea = 'pdf';
			$prefix = 'core';
			$isPdf = true;
		}
		else
		{
			$publishArea = $prefix;
		}
		$this->publishInfo[$publishArea] = array();

		$conf = ArmConfiguration::getInstance();

		$type = $conf->get($prefix.'.method', $conf->get('common.update.method', 'sftp'));

		$files = $conf->get('volatile.files');
		if($isPdf) {
			$coreFiles = $files['pdf'];
		} else {
			$coreFiles = $files[$prefix];
		}

		if(empty($coreFiles)) {
			echo "\t\tNO FILES\n";
			return;
		}

		$path = $conf->get('common.releasedir');

		$groups = $conf->get("$prefix.groups", "");
		$access = $conf->get("$prefix.access", "1");

		foreach($coreFiles as $filename) {
			// Get the filename and path used in ARS
			echo "\t\tCreating/updating item for $filename ";

			$type = $conf->get($prefix.'.method', $conf->get('common.update.method', 'sftp'));

			switch($type) {
				case 's3':
					$version = $conf->get('common.version');
					$reldir = $conf->get($prefix.'.s3.reldir');
					$cdnHostname = $conf->get($prefix.'.s3.cdnhostname');
					$destName = $version.'/'.basename($filename);

					if (empty($cdnHostname))
					{
						$fileOrURL = 's3://' . $reldir . '/' . $destName;
						$type = 'file';
					}
					else
					{
						$directory = $conf->get($prefix.'.s3.directory',	$conf->get('common.update.s3.directory', ''));
						$fileOrURL = 'http://' . $cdnHostname . '/' . $directory . '/' . $destName;
						$type = 'link';
					}

					break;
				case 'ftp':
				case 'ftps':
				case 'sftp':
					$version = $conf->get('common.version');
					$fileOrURL = $version.'/'.basename($filename);
					$type = 'file';
					break;
			}

			// Fetch a record of the file
			$item = $this->arsConnector->getItem($this->release->id, $type, $fileOrURL);

			$item->release_id = $this->release->id;
			$item->type = $type;
			$item->filename = ($type == 'file') ? $fileOrURL : '';
			$item->url = ($type == 'link') ? $fileOrURL : '';
			$item->groups = $groups;
			$item->access = $access;
			$item->published = 0;

			if (empty($item->groups))
			{
				$item->groups = '';
			}

			if (is_array($item->groups))
			{
				$item->groups = array_map(function ($x) {
					return trim($x);
				}, $item->groups);
				$item->groups = implode(',', $item->groups);
			}

			if (empty($item->environments))
			{
				$item->environments = '';
			}

			if (is_array($item->environments))
			{
				$item->environments = array_map(function ($x) {
					return trim($x);
				}, $item->environments);
				$item->environments = implode(',', $item->environments);
			}

			$this->publishInfo[$publishArea][] = $item;

			$result = $this->arsConnector->saveItem((array)$item);
			if ($result !== 'false')
			{
				echo " -- OK\n";
			}
			else
			{
				echo " -- FAILED\n";
				die("\n");
			}
		}
	}
}