<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Configuration\Connection;

use Akeeba\ReleaseMaker\Contracts\ConnectionConfiguration;
use Akeeba\ReleaseMaker\Contracts\ExceptionCode;
use Akeeba\ReleaseMaker\Mixin\MagicGetterAware;
use Akeeba\ReleaseMaker\Mixin\MagicSetterAware;

/**
 * Connection configuration abstract class.
 *
 * Preferably instantiate a new concrete class using the factory method:
 *
 * ```php
 * $configObject = Akeeba\ReleaseMaker\Configuration\Connection\Configuration::factory($configurationFromFile);
 * ```
 *
 * @property-read string $type      Connection type.
 * @property-read string $directory Directory where files are to be uploaded.
 *
 * @since  2.0.0
 */
abstract class Configuration implements ConnectionConfiguration
{
	use MagicGetterAware, MagicSetterAware;

	protected string $type;

	protected string $directory;

	final static function factory(array $configuration): ConnectionConfiguration
	{
		$type = strtolower($configuration['type'] ?? 'ftp');

		switch ($type)
		{
			case 'ftp':
			case 'ftps':
			case 'ftpcurl':
			case 'ftpscurl':
				return new Ftp($configuration);

			case 'sftp':
			case 'sftpcurl':
				return new Sftp($configuration);

			case 's3':
				return new S3($configuration);

			default:
				if (empty($type))
				{
					throw new \InvalidArgumentException("You have not specified a connection type.");
				}

				throw new \InvalidArgumentException(sprintf("Invalid connection type ‘%s’. Must be one of: ftp, ftps, ftpcurl, ftpscurl, sftp, sftpcurl, s3", $type), ExceptionCode::INVALID_CONNECTION_TYPE);
		}
	}

	final public function getType(): string
	{
		return $this->type;
	}

	final public function setDirectory(string $directory): void
	{
		$this->directory = trim($directory, '/');

		if (empty($this->directory))
		{
			$this->directory = '/';
		}
	}
}