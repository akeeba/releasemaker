<?php
/**
 * Akeeba Release Maker
 * An automated script to upload and release a new version of an Akeeba component.
 * Copyright ©2012 Nicholas K. Dionysopoulos / Akeeba Ltd.
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

class ArmStepUpdates implements ArmStepInterface
{
	public function execute()
	{
		echo "PUSHING UPDATE INFORMATION\n";
		echo str_repeat('-', 79) . PHP_EOL;
		
		echo "\tPushing Core updates\n";
		$this->deployUpdates('core');
		
		echo "\tPushing Pro updates\n";
		$this->deployUpdates('pro');
		
		echo PHP_EOL;
	}
	
	private function deployUpdates($prefix = 'core')
	{
		$conf = ArmConfiguration::getInstance();
		
		$type = $conf->get('common.update.method', 'sftp');
		
		if($type == 's3') {
			$config = (object)array(
				'access'		=> $conf->get('common.update.s3.access', ''),
				'secret'		=> $conf->get('common.update.s3.secret', ''),
				'bucket'		=> $conf->get('common.update.s3.bucket', ''),
				'usessl'		=> $conf->get('common.update.s3.usessl', true),
				'directory'		=> $conf->get('common.update.s3.directory', ''),
				'cdnhostname'	=> $conf->get('common.update.s3.cdnhostname', ''),
			);
		} else {
			$config = (object)array(
				'type'			=> $type,
				'hostname'		=> $conf->get('common.update.ftp.hostname', ''),
				'port'			=> $conf->get('common.update.ftp.port', ($type == 'sftp') ? 22 : 21),
				'username'		=> $conf->get('common.update.ftp.usernname', ''),
				'password'		=> $conf->get('common.update.ftp.password', ''),
				'passive'		=> $conf->get('common.update.ftp.passive', true),
				'directory'		=> $conf->get('common.update.ftp.directory', ''),
			);
		}
		
		$stream_id = $conf->get($prefix . '.update.stream', 0);
		$formats = $conf->get($prefix . '.update.formats', array());
		$basename = $conf->get($prefix . '.update.basename', '');
		$url = $conf->get('common.arsapiurl', '');
		
		// No base name means that no updates are set here
		if(empty($basename))
		{
			return;
		}
		
		$tempPath = realpath(__DIR__ . '/../tmp/');
		
		foreach ($formats as $format_raw)
		{
			echo "\t\tPushing $format_raw update format over $type\n";
			
			switch ($format_raw)
			{
				case 'ini':
					$extension = '.ini';
					$format = 'ini';
					break;
				
				case 'inibare':
					$extension = '';
					$format = 'ini';
					break;
				
				case 'xml':
					$extension = '.xml';
					$format = 'xml';
					break;
			}
			
			$temp_filename = $tempPath . '/' . $basename . $extension;
			
			$updateURL = $url . "/index.php?option=com_ars&view=update&format=$format&id=$stream_id";
			
			$data = file_get_contents($updateURL);
			//var_dump($updateURL, $data);die();
			file_put_contents($temp_filename, $data);
			
			switch($type) {
				case 's3':
					$this->uploadS3($config, $temp_filename);
					break;
				case 'ftp':
				case 'ftps':
					$this->uploadFtp($config, $temp_filename);
					break;
				case 'sftp':
					$this->uploadSftp($config, $temp_filename);
					break;
			}
			
			unlink($temp_filename);
		}
	}

	private function uploadS3($config, $sourcePath, $destName = null)
	{
		$s3 = ArmAmazonS3::getInstance($config->access, $config->secret, $config->usessl);
		
		$inputFile = realpath($sourcePath);
		$bucket = $config->bucket;
		if(empty($destName)) {
			$destName = basename($sourcePath);
		}
		$uri = $config->directory . '/' . $destName;
		if(isset($config->cdnhostname)) {
			$acl = ArmAmazonS3::ACL_PUBLIC_READ;
		} else {
			$acl = ArmAmazonS3::ACL_PRIVATE;
		}

		$requestHeaders = array(
			'Cache-Control' => 'max-age=600'
		);
		$input = ArmAmazonS3::inputFile($inputFile, true);
		$result = $s3->putObject($input, $bucket, $uri, $acl, array(), $requestHeaders);
	}
	
	private function uploadFtp($config, $sourcePath, $destName = null)
	{
		if(empty($destName)) {
			$destName = basename($sourcePath);
		}
		
		$ftp = new ArmFtp($config);
		$ftp->upload($sourcePath, $destName);
	}
	
	private function uploadSftp($config, $sourcePath, $destName = null)
	{
		if(empty($destName)) {
			$destName = basename($sourcePath);
		}
		
		$sftp = new ArmSftp($config);
		$sftp->upload($sourcePath, $destName);
	}
}