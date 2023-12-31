<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Uploader;

use Akeeba\Engine\Postproc\Connector\S3v4\Configuration as S3Configuration;
use Akeeba\Engine\Postproc\Connector\S3v4\Connector;
use Akeeba\Engine\Postproc\Connector\S3v4\Input;
use Akeeba\ReleaseMaker\Configuration\Connection\S3 as S3ConfigFromFile;
use Akeeba\ReleaseMaker\Contracts\ConnectionConfiguration;
use Akeeba\ReleaseMaker\Contracts\ExceptionCode;
use Akeeba\ReleaseMaker\Contracts\Uploader;
use Akeeba\ReleaseMaker\Exception\ConfigurationError;
use Akeeba\ReleaseMaker\Exception\ARSError;
use Exception;
use InvalidArgumentException;

class S3 implements Uploader
{
	private S3ConfigFromFile $config;

	private S3Configuration $s3Config;

	private Connector $s3Client;

	public function __construct(ConnectionConfiguration $config)
	{
		if (!($config instanceof S3ConfigFromFile))
		{
			throw new InvalidArgumentException(sprintf("%s expects a %s configuration object, %s given.", __CLASS__, S3ConfigFromFile::class, get_class($config)));
		}

		$this->config = $config;

		try
		{
			$this->s3Config = new S3Configuration(
				$config->access, $config->secret, $config->signature, $config->region
			);
			$this->s3Config->setSSL($config->tls);
			$this->s3Client = new Connector($this->s3Config);
		}
		catch (Exception $e)
		{
			throw new ConfigurationError($e->getMessage(), ExceptionCode::CONFIG_GENERIC_ERROR, $e);
		}
	}

	public function __destruct()
	{
		// Nothing to do
	}

	/** @inheritDoc */
	public function upload(string $sourcePath, string $destPath): void
	{
		$uri       = $this->config->directory . '/' . $destPath;
		$bucket    = $this->config->bucket;
		$inputFile = \realpath($sourcePath);
		$input     = Input::createFromFile($inputFile);

		try
		{
			$this->s3Client->putObject($input, $bucket, $uri, $this->config->acl, [
				'StorageClass' => $this->config->storageClass,
				'CacheControl' => sprintf("max-age=%d", $this->config->maxAge),
			]);
		}
		catch (Exception $e)
		{
			throw new ARSError($e->getMessage(), $e);
		}
	}

	public function getConnectionConfiguration(): ConnectionConfiguration
	{
		return $this->config;
	}
}