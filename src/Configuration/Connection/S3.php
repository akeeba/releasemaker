<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Configuration\Connection;

use Akeeba\Engine\Postproc\Connector\S3v4\Acl;
use Akeeba\Engine\Postproc\Connector\S3v4\StorageClass;
use Akeeba\ReleaseMaker\Contracts\ExceptionCode;
use Akeeba\ReleaseMaker\Contracts\Uploader;
use Akeeba\ReleaseMaker\Exception\InvalidCDNHostname;
use Akeeba\ReleaseMaker\Exception\InvalidConnectionAuthentication;
use Akeeba\ReleaseMaker\Exception\InvalidHostname;
use Akeeba\ReleaseMaker\Exception\InvalidS3Acl;
use Akeeba\ReleaseMaker\Exception\InvalidS3Signature;
use Akeeba\ReleaseMaker\Exception\InvalidS3StorageClass;
use Akeeba\ReleaseMaker\Exception\NoBucket;
use Akeeba\ReleaseMaker\Exception\NoRegion;
use Akeeba\ReleaseMaker\Mixin\ClassConstantValidationAware;
use Akeeba\ReleaseMaker\Uploader\S3 as S3Uploader;
use LogicException;

/**
 * SFTP Connection Configuration
 *
 * @property-read string $endpoint     Custom endpoint URL for S3-compatible services. Empty for Amazon S3 proper.
 * @property-read string $access       S3 Access Key.
 * @property-read string $secret       S3 Secret Key.
 * @property-read string $bucket       The Bucket where the files will be uploaded to.
 * @property-read bool   $tls          Should I use HTTPS? Default true
 * @property-read string $signature    S3 request signature method: 'v4' or 'v2'. Default: v4
 * @property-read string $region       Only when signature is 'v4'. The Amazon S3 region where your bucket lives.
 * @property-read string $cdnHostname  The hostname of the CDN where the files are publicly accessible from
 * @property-read string $acl          Amazon S3 ACL for the uploaded files
 * @property-read string $storageClass Amazon S3 storage class.
 * @property-read int    $maxAge       Cache control maximum age in seconds.
 *
 * @since  2.0.0
 */
class S3 extends Configuration
{
	use ClassConstantValidationAware;

	private string $endpoint;

	private string $access;

	private string $secret;

	private string $bucket;

	private bool $tls = true;

	private string $signature = 'v4';

	private string $region = 'us-east-1';

	private string $cdnHostname = '';

	private string $acl = Acl::ACL_PUBLIC_READ;

	private string $storageClass = StorageClass::STANDARD;

	private int $maxAge = 600;

	public function __construct(array $configuration)
	{
		$this->type         = $configuration['type'] ?? 'ftp';
		$this->directory    = $configuration['directory'] ?? '';
		$this->endpoint     = $configuration['endpoint'] ?? '';
		$this->access       = $configuration['access'] ?? '';
		$this->secret       = $configuration['secret'] ?? '';
		$this->bucket       = $configuration['bucket'] ?? '';
		$this->signature    = $configuration['signature'] ?? 'v4';
		$this->region       = $configuration['region'] ?? 'us-east-1';
		$this->cdnHostname  = $configuration['cdnhostname'] ?? '';
		$this->acl          = $configuration['acl'] ?? Acl::ACL_PUBLIC_READ;
		$this->storageClass = $configuration['storage_class'] ?? StorageClass::STANDARD;
		$this->tls          = (bool) ($configuration['endpoint'] ?? true);
		$this->maxAge       = max(60, (int) ($configuration['maximum_age'] ?? 600));

		// Make sure the type is something this class can handle.
		if ($this->type != 's3')
		{
			throw new LogicException(sprintf("Invalid connection type ‘%s’ for class %s. Make sure you go through %s::factory() instead of instantiating this class directly.", $this->getType(), __CLASS__, Configuration::class), ExceptionCode::INVALID_CONNECTION_TYPE);
		}

		// We cannot allow an invalid endpoint if it's not empty
		if (!empty($this->endpoint) && !filter_var($this->endpoint, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME))
		{
			throw new InvalidHostname($this->endpoint);
		}

		// We cannot allow an invalid CDN hostname
		if (!empty($this->cdnHostname) && !filter_var($this->cdnHostname, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME))
		{
			throw new InvalidCDNHostname($this->cdnHostname);
		}

		if (empty($this->access) || empty($this->secret))
		{
			throw new InvalidConnectionAuthentication('Insufficient information for S3 authentication. You need to provide the Access and Secret key.');
		}

		if (empty($this->bucket))
		{
			throw new NoBucket();
		}

		if (!in_array($this->signature, ['v4', 'v2']))
		{
			throw new InvalidS3Signature($this->signature);
		}

		if (($this->signature == 'v4') && empty($this->region))
		{
			throw new NoRegion();
		}

		if (!$this->isValidClassConstantValue($this->acl, Acl::class, true))
		{
			throw new InvalidS3Acl($this->acl);
		}

		if (!$this->isValidClassConstantValue($this->storageClass, StorageClass::class, true))
		{
			throw new InvalidS3StorageClass($this->storageClass);
		}
	}

	public function getUploader(?string $directory): Uploader
	{
		$config = clone $this;

		if (!empty($directory))
		{
			$config->directory = $directory;
		}

		return new S3Uploader($config);
	}
}
