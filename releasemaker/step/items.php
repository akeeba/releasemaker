<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
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
				case 'ftpcurl':
				case 'ftps':
				case 'ftpscurl':
				case 'sftp':
				case 'sftpcurl':
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
