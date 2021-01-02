<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Step;

use Akeeba\Engine\Postproc\Connector\S3v4\Acl;
use Akeeba\Engine\Postproc\Connector\S3v4\Configuration;
use Akeeba\Engine\Postproc\Connector\S3v4\Connector;
use Akeeba\Engine\Postproc\Connector\S3v4\Input;
use Akeeba\ReleaseMaker\Transfer\FTP;
use Akeeba\ReleaseMaker\Transfer\FTPcURL;
use Akeeba\ReleaseMaker\Transfer\SFTP;
use Akeeba\ReleaseMaker\Transfer\SFTPcURL;

class Updates extends AbstractStep
{
	public function execute(): void
	{
		$this->io->section("Pushing update information");

		$this->io->writeln("<info>Pushing Core updates</info>");

		$this->deployUpdates('core');

		$this->io->writeln("<info>Pushing Pro updates</info>");

		$this->deployUpdates('pro');

		$this->io->newLine();
	}

	private function deployUpdates(string $prefix = 'core'): void
	{
		$conf = \Akeeba\ReleaseMaker\Configuration::getInstance();

		$type = $conf->get('common.update.method', 'sftp');

		if ($type === 'none')
		{
			$this->io->note(sprintf("Skipping %s updates (format set to “none”)", ucfirst($prefix)));

			return;
		}

		if ($type == 's3')
		{
			$config = (object) [
				'access'      => $conf->get('common.update.s3.access', ''),
				'secret'      => $conf->get('common.update.s3.secret', ''),
				'bucket'      => $conf->get('common.update.s3.bucket', ''),
				'usessl'      => $conf->get('common.update.s3.usessl', true),
				'signature'   => $conf->get('common.update.s3.signature', 's3'),
				'region'      => $conf->get('common.update.s3.region', 'us-east-1'),
				'directory'   => $conf->get('common.update.s3.directory', ''),
				'cdnhostname' => $conf->get('common.update.s3.cdnhostname', ''),
			];
		}
		else
		{
			$config = (object) [
				'type'             => $type,
				'hostname'         => $conf->get('common.update.ftp.hostname', ''),
				'port'             => $conf->get('common.update.ftp.port', in_array($type, [
					'sftp', 'sftpcurl',
				]) ? 22 : 21),
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
			];
		}

		$stream_id = $conf->get($prefix . '.update.stream', 0);
		$formats   = $conf->get($prefix . '.update.formats', []);
		$basename  = $conf->get($prefix . '.update.basename', '');
		$url       = $conf->get('common.arsapiurl', '');

		// No base name means that no updates are set here
		if (empty($basename))
		{
			$this->io->note(sprintf("There are no %s updates", ucfirst($prefix)));

			return;
		}

		$tempPath = realpath(__DIR__ . '/../../tmp/');

		foreach ($formats as $format_raw)
		{
			$this->io->text(sprintf("Pushing %s update format over %s", $format_raw, $type));

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
			$url           = (substr($url, -4) === '.php') ? $url : ($url . '/index.php');
			$updateURL     = $url . "?option=com_ars&view=update$task&format=$format&id=$stream_id" . $task;

			$context = stream_context_create([
				'http' => [
					'method' => 'GET',
				],
				'ssl'  => [
					'verify_peer'  => true,
					'cafile'       => AKEEBA_CACERT_PEM,
					'verify_depth' => 5,
				],
			]);
			$data    = file_get_contents($updateURL, false, $context);

			/**
			 * When we do not have updates for a specific item we might choose to use a fake update stream ID, e.g.
			 * 99999. In this case trying to access its URL will throw an error which means that file_get_contents
			 * returns false. This if-block makes sure this won't break everything.
			 */
			if ($data === false)
			{
				continue;
			}

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

	private function uploadS3(object $config, string $sourcePath, ?string $destName = null): void
	{
		$config->signature = ($config->signature == 'v4') ? 'v4' : 'v2';

		$configuration = new Configuration(
			$config->access, $config->secret, $config->signature, $config->region
		);

		// Is SSL enabled and we have a cacert.pem file?
		if (!defined('AKEEBA_CACERT_PEM'))
		{
			$config->usessl = false;
		}

		$configuration->setSSL($config->usessl);

		// Create the S3 client instance
		$s3Client = new Connector($configuration);

		if (empty($destName))
		{
			$destName = basename($sourcePath);
		}

		$uri = $config->directory . '/' . $destName;
		$acl = Acl::ACL_PRIVATE;

		if (!empty($config->cdnhostname))
		{
			$acl = Acl::ACL_PUBLIC_READ;
		}

		$bucket    = $config->bucket;
		$inputFile = realpath($sourcePath);
		$input     = Input::createFromFile($inputFile);

		$s3Client->putObject($input, $bucket, $uri, $acl, [
			'StorageClass' => 'STANDARD',
			'CacheControl' => 'max-age=600',
		]);
	}

	private function uploadFtp(object $config, string $sourcePath, ?string $destName = null): void
	{
		if (empty($destName))
		{
			$destName = basename($sourcePath);
		}

		$ftp = new FTP($config);

		$ftp->upload($sourcePath, $destName);
	}

	private function uploadFtpCurl(object $config, string $sourcePath, ?string $destName = null): void
	{
		if (empty($destName))
		{
			$destName = basename($sourcePath);
		}

		$ftp = new FTPcURL($config);

		$ftp->upload($sourcePath, $destName);
	}

	private function uploadSftp(object $config, string $sourcePath, ?string $destName = null): void
	{
		if (empty($destName))
		{
			$destName = basename($sourcePath);
		}

		$sftp = new SFTP($config);

		$sftp->upload($sourcePath, $destName);
	}

	private function uploadSftpCurl(object $config, string $sourcePath, ?string $destName = null): void
	{
		if (empty($destName))
		{
			$destName = basename($sourcePath);
		}

		$sftp = new SFTPcURL($config);

		$sftp->upload($sourcePath, $destName);
	}
}
