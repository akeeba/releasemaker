<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Step\Mixin;

use Akeeba\ReleaseMaker\Configuration;
use Akeeba\ReleaseMaker\Transfer\FTP;
use Akeeba\ReleaseMaker\Transfer\FTPcURL;
use Akeeba\ReleaseMaker\Transfer\S3;
use Akeeba\ReleaseMaker\Transfer\SFTP;
use Akeeba\ReleaseMaker\Transfer\SFTPcURL;
use Akeeba\ReleaseMaker\Transfer\Uploader;

/**
 * Trait for steps which can upload files to remote storage
 *
 * @package Akeeba\ReleaseMaker\Step\Mixin
 */
trait UploadAware
{
	/**
	 * Upload a file to remote storage.
	 *
	 * The $onBeforeUpload callable has the following signature:
	 *
	 * function(Uploader $uploader, string &$localFile, string &$remoteFile): void
	 *
	 * @param   string         $prefix          The uploader area prefix, e.g. common.update for updates.
	 * @param   string         $localFile       Full local filesystem path of the file to upload.
	 * @param   string         $remoteFile      Relative remote filesystem path where the file will be uploaded to.
	 * @param   callable|null  $onBeforeUpload  Execute this callable before the upload takes place.
	 *
	 * @return  void
	 * @throws  \RuntimeException When the upload fails
	 */
	protected function uploadFile(string $prefix, string $localFile, string $remoteFile, callable $onBeforeUpload = null): void
	{
		$config = $this->getUploaderConfig($prefix);
		$type   = $config->type ?? 'ftp';

		$uploader = $this->getUploader($type, $config);

		if (is_callable($onBeforeUpload))
		{
			call_user_func_array($onBeforeUpload, [$uploader, &$localFile, &$remoteFile]);
		}

		$uploader->upload($localFile, $remoteFile);
	}

	/**
	 * Gets the correct uploader object for the given uploader type
	 *
	 * @param   string  $type    Uploader type, e.g. 's3', 'ftp', 'sftp' and so on.
	 * @param   object  $config  Configuration object for the uploader
	 *
	 * @return  Uploader
	 */
	private function getUploader(string $type, object $config): Uploader
	{
		switch (strtolower($type))
		{
			case 's3':
				return new S3($config);

			case 'ftp':
			case 'ftps':
				return new FTP($config);

			case 'ftpcurl':
			case 'ftpscurl':
				return new FTPcURL($config);

			case 'sftp':
				if (\function_exists('ssh2_connect'))
				{
					return new SFTP($config);
				}

				return new SFTPcURL($config);

			case 'sftpcurl':
				return new SFTPcURL($config);

			default:
				throw new \RuntimeException(sprintf('Unknown uploader type ‘%s’.', $type));
		}
	}

	/**
	 * Get the uploader configuration object
	 *
	 * @param   string  $prefix  The uploader area prefix, e.g. common.update for updates.
	 *
	 * @return  object  The uploader configuration object
	 */
	private function getUploaderConfig(string $prefix = 'common.update'): object
	{
		$type             = $this->getUploaderType($prefix);
		$armConfiguration = Configuration::getInstance();

		if ($type == 's3')
		{
			return (object) [
				'type'        => $type,
				'access'      => $armConfiguration->get($prefix . '.s3.access', $armConfiguration->get('common.update.s3.access', '')),
				'secret'      => $armConfiguration->get($prefix . '.s3.secret', $armConfiguration->get('common.update.s3.secret', '')),
				'bucket'      => $armConfiguration->get($prefix . '.s3.bucket', $armConfiguration->get('common.update.s3.bucket', '')),
				'usessl'      => $armConfiguration->get($prefix . '.s3.usessl', $armConfiguration->get('common.update.s3.usessl', true)),
				'signature'   => $armConfiguration->get($prefix . '.s3.signature', $armConfiguration->get('common.update.s3.signature', 's3')),
				'region'      => $armConfiguration->get($prefix . '.s3.region', $armConfiguration->get('common.update.s3.region', 'us-east-1')),
				'directory'   => $armConfiguration->get($prefix . '.s3.directory', $armConfiguration->get('common.update.s3.directory', '')),
				'cdnhostname' => $armConfiguration->get($prefix . '.s3.cdnhostname', $armConfiguration->get('common.update.s3.cdnhostname', '')),
			];
		}

		return (object) [
			'type'             => $type,
			'hostname'         => $armConfiguration->get($prefix . '.ftp.hostname', $armConfiguration->get('common.update.ftp.hostname', '')),
			'port'             => $armConfiguration->get($prefix . '.ftp.port', $armConfiguration->get('common.update.ftp.port', \in_array($type, [
				'sftp', 'sftpcurl',
			]) ? 22 : 21)),
			'username'         => $armConfiguration->get($prefix . '.ftp.username', $armConfiguration->get('common.update.ftp.username', '')),
			'password'         => $armConfiguration->get($prefix . '.ftp.password', $armConfiguration->get('common.update.ftp.password', '')),
			'passive'          => $armConfiguration->get($prefix . '.ftp.passive', $armConfiguration->get('common.update.ftp.passive', true)),
			'directory'        => $armConfiguration->get($prefix . '.ftp.directory', $armConfiguration->get('common.update.ftp.directory', '')),
			'pubkeyfile'       => $armConfiguration->get($prefix . '.ftp.pubkeyfile', $armConfiguration->get('common.update.ftp.pubkeyfile', '')),
			'privkeyfile'      => $armConfiguration->get($prefix . '.ftp.privkeyfile', $armConfiguration->get('common.update.ftp.privkeyfile', '')),
			'privkeyfile_pass' => $armConfiguration->get($prefix . '.ftp.privkeyfile_pass', $armConfiguration->get('common.update.ftp.privkeyfile_pass', '')),
			'passive_fix'      => $armConfiguration->get($prefix . '.ftp.passive_fix', $armConfiguration->get('common.update.ftp.passive_fix', false)),
			'timeout'          => $armConfiguration->get($prefix . '.ftp.timeout', $armConfiguration->get('common.update.ftp.timeout', 3600)),
			'verbose'          => $armConfiguration->get($prefix . '.ftp.verbose', $armConfiguration->get('common.update.ftp.verbose', false)),
		];
	}

	private function getUploaderType(string $prefix = 'common.update'): string
	{
		$armConfiguration = Configuration::getInstance();
		$updateMethod     = $armConfiguration->get('common.update.method', 'sftp');

		if ($prefix === 'common.update')
		{
			return $updateMethod;
		}

		return $armConfiguration->get($prefix . '.method', $updateMethod);
	}
}