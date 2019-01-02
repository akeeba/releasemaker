<?php
/**
 * Akeeba Release Maker
 * An automated script to upload and release a new version of an Akeeba component.
 *
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2006-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU/GPLv3
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

		if ($type == 's3')
		{
			$config = (object)array(
				'access'      => $conf->get('common.update.s3.access', ''),
				'secret'      => $conf->get('common.update.s3.secret', ''),
				'bucket'      => $conf->get('common.update.s3.bucket', ''),
				'usessl'      => $conf->get('common.update.s3.usessl', true),
				'signature'   => $conf->get('common.update.s3.signature', 's3'),
				'region'      => $conf->get('common.update.s3.region', 'us-east-1'),
				'directory'   => $conf->get('common.update.s3.directory', ''),
				'cdnhostname' => $conf->get('common.update.s3.cdnhostname', ''),
			);
		}
		else
		{
			$config = (object)array(
				'type'             => $type,
				'hostname'         => $conf->get('common.update.ftp.hostname', ''),
				'port'             => $conf->get('common.update.ftp.port', in_array($type, array('sftp', 'sftpcurl')) ? 22 : 21),
				'username'         => $conf->get('common.update.ftp.username', ''),
				'password'         => $conf->get('common.update.ftp.password', ''),
				'passive'          => $conf->get('common.update.ftp.passive', true),
				'directory'        => $conf->get('common.update.ftp.directory', ''),
				'pubkeyfile'       => $conf->get('common.update.ftp.pubkeyfile', ''),
				'privkeyfile'      => $conf->get('common.update.ftp.privkeyfile', ''),
				'privkeyfile_pass' => $conf->get('common.update.ftp.privkeyfile_pass', ''),
				'passive_fix'      => $conf->get('common.update.ftp.passive_fix', false),
				'timeout'          => $conf->get('common.update.ftp.timeout', 3600),
				'verbose'          => $conf->get('common.update.ftp.verbose', false),
			);
		}

		$stream_id = $conf->get($prefix . '.update.stream', 0);
		$formats   = $conf->get($prefix . '.update.formats', array());
		$basename  = $conf->get($prefix . '.update.basename', '');
		$url       = $conf->get('common.arsapiurl', '');

		// No base name means that no updates are set here
		if (empty($basename))
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
					$format    = 'ini';
					$task      = '';
					break;

				case 'inibare':
					$extension = '';
					$format    = 'ini';
					$task      = '';
					break;

				case 'xml':
					$extension = '.xml';
					$format    = 'xml';
					$task      = '&task=stream';
					break;
			}

			$temp_filename = $tempPath . '/' . $basename . $extension;

			$updateURL = $url . "/index.php?option=com_ars&view=update$task&format=$format&id=$stream_id" . $task;

			$data = file_get_contents($updateURL);
			file_put_contents($temp_filename, $data);

			switch ($type)
			{
				case 's3':
					$this->uploadS3($config, $temp_filename);
					break;
				case 'ftp':
				case 'ftps':
					$this->uploadFtp($config, $temp_filename);
					break;
				case 'ftpcurl':
				case 'ftpscurl':
					$this->uploadFtpCurl($config, $temp_filename);
					break;
				case 'sftp':
					if (function_exists('ssh2_connect'))
					{
						$this->uploadSftp($config, $temp_filename);

						break;
					}


					// Fallback to SFTP over cURL for build environment with no SSH2 support
					$this->uploadSftpCurl($config, $temp_filename);
					break;
				case 'sftpcurl':
					$this->uploadSftpCurl($config, $temp_filename);
					break;
			}

			unlink($temp_filename);
		}
	}

	private function uploadS3($config, $sourcePath, $destName = null)
	{
		$config->signature = ($config->signature == 'v4') ? 'v4' : 'v2';

		$configuration = new \Akeeba\Engine\Postproc\Connector\S3v4\Configuration(
			$config->access, $config->secret, $config->signature, $config->region
		);

		// Is SSL enabled and we have a cacert.pem file?
		if (!defined('AKEEBA_CACERT_PEM'))
		{
			$config->usessl = false;
		}

		$configuration->setSSL($config->usessl);

		// Create the S3 client instance
		$s3Client = new \Akeeba\Engine\Postproc\Connector\S3v4\Connector($configuration);

		if (empty($destName))
		{
			$destName = basename($sourcePath);
		}

		$uri = $config->directory . '/' . $destName;

		if (!empty($config->cdnhostname))
		{
			$acl = \Akeeba\Engine\Postproc\Connector\S3v4\Acl::ACL_PUBLIC_READ;
		}
		else
		{
			$acl = \Akeeba\Engine\Postproc\Connector\S3v4\Acl::ACL_PRIVATE;
		}

		$bucket    = $config->bucket;
		$inputFile = realpath($sourcePath);
		$input     = \Akeeba\Engine\Postproc\Connector\S3v4\Input::createFromFile($inputFile);

		$s3Client->putObject($input, $bucket, $uri, $acl, [
			'StorageClass' => 'STANDARD',
			'CacheControl' => 'max-age=600',
		]);

		return true;
	}

	private function uploadFtp($config, $sourcePath, $destName = null)
	{
		if (empty($destName))
		{
			$destName = basename($sourcePath);
		}

		$ftp = new ArmFtp($config);
		$ftp->upload($sourcePath, $destName);
	}

	private function uploadFtpCurl($config, $sourcePath, $destName = null)
	{
		if (empty($destName))
		{
			$destName = basename($sourcePath);
		}

		$ftp = new ArmFtpcurl($config);
		$ftp->upload($sourcePath, $destName);
	}

	private function uploadSftp($config, $sourcePath, $destName = null)
	{
		if (empty($destName))
		{
			$destName = basename($sourcePath);
		}

		$sftp = new ArmSftp($config);
		$sftp->upload($sourcePath, $destName);
	}

	private function uploadSftpCurl($config, $sourcePath, $destName = null)
	{
		if (empty($destName))
		{
			$destName = basename($sourcePath);
		}

		$sftp = new ArmSftpcurl($config);
		$sftp->upload($sourcePath, $destName);
	}
}
