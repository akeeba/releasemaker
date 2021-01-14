<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Step;

use Akeeba\Engine\Postproc\Connector\S3v4\Acl;
use Akeeba\Engine\Postproc\Connector\S3v4\Configuration as S3Configuration;
use Akeeba\Engine\Postproc\Connector\S3v4\Connector;
use Akeeba\Engine\Postproc\Connector\S3v4\Input;
use Akeeba\ReleaseMaker\Configuration;
use Akeeba\ReleaseMaker\Transfer\FTP;
use Akeeba\ReleaseMaker\Transfer\FTPcURL;
use Akeeba\ReleaseMaker\Transfer\SFTP;
use Akeeba\ReleaseMaker\Transfer\SFTPcURL;

class Deploy extends AbstractStep
{
	public function execute(): void
	{
		$this->io->section('File deployment');

		$prefixes = [
			'core',
			'pro',
		];

		foreach ($prefixes as $prefix)
		{
			$this->deployFiles($prefix);
		}

		$this->deployPdf();

		$this->io->newLine();
	}

	private function deployFiles(string $prefix, bool $isPdf = false): void
	{
		if (!$isPdf)
		{
			$this->io->text(\sprintf("<info>Deploying %s files</info>", \ucfirst($prefix)));
		}
		else
		{
			$this->io->text(\sprintf("<info>Deploying PDF files</info>", \ucfirst($prefix)));
		}

		$conf = Configuration::getInstance();
		$type = $conf->get($prefix . '.method', $conf->get('common.update.method', 'sftp'));

		if ($type == 's3')
		{
			$config = (object) [
				'access'      => $conf->get($prefix . '.s3.access', $conf->get('common.update.s3.access', '')),
				'secret'      => $conf->get($prefix . '.s3.secret', $conf->get('common.update.s3.secret', '')),
				'bucket'      => $conf->get($prefix . '.s3.bucket', $conf->get('common.update.s3.bucket', '')),
				'usessl'      => $conf->get($prefix . '.s3.usessl', $conf->get('common.update.s3.usessl', true)),
				'signature'   => $conf->get($prefix . '.s3.signature', $conf->get('common.update.s3.signature', 's3')),
				'region'      => $conf->get($prefix . '.s3.region', $conf->get('common.update.s3.region', 'us-east-1')),
				'directory'   => $conf->get($prefix . '.s3.directory', $conf->get('common.update.s3.directory', '')),
				'cdnhostname' => $conf->get($prefix . '.s3.cdnhostname', $conf->get('common.update.s3.cdnhostname', '')),
			];
		}
		else
		{
			$config = (object) [
				'type'             => $type,
				'hostname'         => $conf->get($prefix . '.ftp.hostname', $conf->get('common.update.ftp.hostname', '')),
				'port'             => $conf->get($prefix . '.ftp.port', $conf->get('common.update.ftp.port', \in_array($type, [
					'sftp', 'sftpcurl',
				]) ? 22 : 21)),
				'username'         => $conf->get($prefix . '.ftp.username', $conf->get('common.update.ftp.username', '')),
				'password'         => $conf->get($prefix . '.ftp.password', $conf->get('common.update.ftp.password', '')),
				'passive'          => $conf->get($prefix . '.ftp.passive', $conf->get('common.update.ftp.passive', true)),
				'directory'        => $conf->get($prefix . '.ftp.directory', $conf->get('common.update.ftp.directory', '')),
				'pubkeyfile'       => $conf->get($prefix . '.ftp.pubkeyfile', $conf->get('common.update.ftp.pubkeyfile', '')),
				'privkeyfile'      => $conf->get($prefix . '.ftp.privkeyfile', $conf->get('common.update.ftp.privkeyfile', '')),
				'privkeyfile_pass' => $conf->get($prefix . '.ftp.privkeyfile_pass', $conf->get('common.update.ftp.privkeyfile_pass', '')),
				'passive_fix'      => $conf->get($prefix . '.ftp.passive_fix', $conf->get('common.update.ftp.passive_fix', false)),
				'timeout'          => $conf->get($prefix . '.ftp.timeout', $conf->get('common.update.ftp.timeout', 3600)),
				'verbose'          => $conf->get($prefix . '.ftp.verbose', $conf->get('common.update.ftp.verbose', false)),
			];
		}

		$volatileFiles = $conf->get('volatile.files');

		$files = $volatileFiles[$prefix] ?? [];

		if ($isPdf)
		{
			$files = $volatileFiles['pdf'] ?? [];
		}

		if (empty($files))
		{
			return;
		}

		$path = $conf->get('common.releasedir');

		foreach ($files as $filename)
		{
			$this->io->comment(\sprintf("Uploading %s", $filename));

			$sourcePath = $path . DIRECTORY_SEPARATOR . $filename;

			switch ($type)
			{
				case 's3':
					$this->uploadS3($config, $sourcePath);

					break;

				case 'ftp':
				case 'ftps':
					$this->uploadFtp($config, $sourcePath);

					break;

				case 'ftpcurl':
				case 'ftpscurl':
					$this->uploadFtpCurl($config, $sourcePath);

					break;

				case 'sftp':
					if (\function_exists('ssh2_connect'))
					{
						$this->uploadSftp($config, $sourcePath);

						break;
					}

					// Fallback to SFTP over cURL for build environment with no SSH2 support
					$this->uploadSftpCurl($config, $sourcePath);

					break;

				case 'sftpcurl':
					$this->uploadSftpCurl($config, $sourcePath);

					break;
			}
		}
	}

	private function deployPdf(): void
	{
		$conf  = Configuration::getInstance();
		$where = $conf->get('pdf.where', 'core');

		$this->deployFiles($where, true);
	}

	private function uploadS3(object $config, string $sourcePath, ?string $destName = null): void
	{
		$config->signature = ($config->signature == 'v4') ? 'v4' : 'v2';

		$configuration = new S3Configuration(
			$config->access, $config->secret, $config->signature, $config->region
		);

		// Is SSL enabled and we have a cacert.pem file?
		if (!\defined('AKEEBA_CACERT_PEM'))
		{
			$config->usessl = false;
		}

		$configuration->setSSL($config->usessl);

		// Create the S3 client instance
		$s3Client = new Connector($configuration);

		if (empty($destName))
		{
			$destName = \basename($sourcePath);
		}

		$conf    = Configuration::getInstance();
		$version = $conf->get('common.version');
		$uri     = $config->directory . '/' . $version . '/' . $destName;

		$acl = Acl::ACL_PRIVATE;

		if (!empty($config->cdnhostname))
		{
			$acl = Acl::ACL_PUBLIC_READ;
		}

		$this->io->comment(\sprintf("with %s ACL", $acl));

		$bucket    = $config->bucket;
		$inputFile = \realpath($sourcePath);
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
			$conf     = Configuration::getInstance();
			$version  = $conf->get('common.version');
			$destName = $version . '/' . \basename($sourcePath);
		}

		$ftp = new FTP($config);
		$ftp->upload($sourcePath, $destName);
	}

	private function uploadFtpCurl(object $config, string $sourcePath, ?string $destName = null): void
	{
		if (empty($destName))
		{
			$conf     = Configuration::getInstance();
			$version  = $conf->get('common.version');
			$destName = $version . '/' . \basename($sourcePath);
		}

		$ftp = new FTPcURL($config);
		$ftp->upload($sourcePath, $destName);
	}

	private function uploadSftp(object $config, string $sourcePath, ?string $destName = null): void
	{
		if (empty($destName))
		{
			$conf     = Configuration::getInstance();
			$version  = $conf->get('common.version');
			$destName = $version . '/' . \basename($sourcePath);
		}

		$sftp = new SFTP($config);
		$sftp->upload($sourcePath, $destName);
	}

	private function uploadSftpCurl(object $config, string $sourcePath, ?string $destName = null): void
	{
		if (empty($destName))
		{
			$conf     = Configuration::getInstance();
			$version  = $conf->get('common.version');
			$destName = $version . '/' . \basename($sourcePath);
		}

		$sftp = new SFTPcURL($config);
		$sftp->upload($sourcePath, $destName);
	}
}
