<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Transfer;

use Akeeba\Engine\Postproc\Connector\S3v4\Acl;
use Akeeba\Engine\Postproc\Connector\S3v4\Configuration as S3Configuration;
use Akeeba\Engine\Postproc\Connector\S3v4\Connector;
use Akeeba\Engine\Postproc\Connector\S3v4\Input;

class S3 implements Uploader
{
	/**
	 * Configuration settings
	 *
	 * @var object
	 */
	private $config;

	/**
	 * Amazon S3 ACL string
	 *
	 * @var null|string
	 */
	private $acl;

	/**
	 * The S3 connector's configuration object
	 *
	 * @var S3Configuration
	 */
	private $s3Config;

	/**
	 * The Amazon S3 connector object
	 *
	 * @var Connector
	 */
	private $s3Client;

	/** @inheritDoc */
	public function __construct(object $config)
	{
		$this->config = $config;

		$config->signature = (($config->signature ?? 'v2') == 'v4') ? 'v4' : 'v2';

		$this->s3Config = new S3Configuration(
			$config->access, $config->secret, $config->signature, $config->region
		);

		// Is SSL enabled and we have a cacert.pem file?
		if (!\defined('AKEEBA_CACERT_PEM'))
		{
			$config->usessl = false;
		}

		$this->s3Config->setSSL($config->usessl);

		// Create the S3 client instance
		$this->s3Client = new Connector($this->s3Config);

		$this->acl = Acl::ACL_PRIVATE;

		if (!empty($this->config->cdnhostname))
		{
			$this->acl = Acl::ACL_PUBLIC_READ;
		}
	}

	/** @inheritDoc */
	public function __destruct()
	{
		$this->s3Client = null;
		$this->s3Config = null;
	}

	/** @inheritDoc */
	public function upload(string $sourcePath, string $destPath): void
	{
		$uri       = $this->config->directory . '/' . $destPath;
		$bucket    = $this->config->bucket;
		$inputFile = \realpath($sourcePath);
		$input     = Input::createFromFile($inputFile);

		$this->s3Client->putObject($input, $bucket, $uri, $this->acl, [
			'StorageClass' => 'STANDARD',
			'CacheControl' => 'max-age=600',
		]);
	}

	/**
	 * Get the ACL string to be sent to Amazon S3
	 *
	 * @return string|null
	 */
	public function getAcl(): ?string
	{
		return $this->acl;
	}

	/**
	 * Set the ACL string to be sent to Amazon S3
	 *
	 * @param   string|null  $acl
	 *
	 * @return  self
	 */
	public function setAcl(?string $acl): self
	{
		$this->acl = $acl;

		return $this;
	}
}