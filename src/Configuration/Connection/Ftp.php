<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Configuration\Connection;


use Akeeba\ReleaseMaker\Contracts\ExceptionCode;
use Akeeba\ReleaseMaker\Contracts\Uploader;
use Akeeba\ReleaseMaker\Exception\InvalidConnectionAuthentication;
use Akeeba\ReleaseMaker\Exception\InvalidHostname;
use Akeeba\ReleaseMaker\Uploader\CurlFtp;
use Akeeba\ReleaseMaker\Uploader\NativeFtp;

/**
 * FTP(S) connection configuration
 *
 * @property-read string $hostname   FTP(S) hostname.
 * @property-read int    $port       FTP(S) port. Default: 21.
 * @property-read string $username   FTP(S) username.
 * @property-read string $password   FTP(S) password.
 * @property-read bool   $passive    Use FTP(S) passive mode for update streams.
 * @property-read bool   $secure     Is this an FTPS connection?
 * @property-read string $directory  FTP(S) directory where files are uploaded to.
 * @property-read bool   $passiveFix When true ignores the IP sent by the server in response to PASV. cURL only.
 * @property-read int    $timeout    Total (connection and upload) timeout in seconds.
 *
 * @since  2.0.0
 */
class Ftp extends Configuration
{
	private string $hostname;

	private int $port = 21;

	private string $username = '';

	private string $password = '';

	private bool $passive = true;

	private bool $secure = false;

	private string $directory = '';

	private bool $passiveFix = true;

	private int $timeout = 3600;

	public function __construct(array $configuration)
	{
		$this->type       = $configuration['type'] ?? 'ftp';
		$isFtp            = in_array($this->type, ['ftp', 'ftpcurl']);
		$this->hostname   = $configuration['hostname'] ?? '';
		$this->port       = (int) ($configuration['port'] ?? 21);
		$this->username   = $configuration['username'] ?? '';
		$this->password   = $configuration['password'] ?? '';
		$this->passive    = (bool) ($configuration['passive'] ?? true);
		$this->secure     = (bool) ($configuration['secure'] ?? ($isFtp ? false : true));
		$this->directory  = $configuration['directory'] ?? '';
		$this->passiveFix = (bool) ($configuration['passive_fix'] ?? ($isFtp ? false : true));
		$this->timeout    = max(30, (int) ($configuration['timeout'] ?? 3600));

		// Make sure the type is something this class can handle.
		if (!in_array($this->type, ['ftp', 'ftps', 'ftpcurl', 'ftpscurl']))
		{
			throw new \LogicException(sprintf("Invalid connection type ‘%s’ for class %s. Make sure you go through %s::factory() instead of instantiating this class directly.", $this->getType(), __CLASS__, Configuration::class), ExceptionCode::INVALID_CONNECTION_TYPE);
		}

		// We cannot allow an empty hostname
		if (empty($this->hostname) || !filter_var($this->hostname, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME))
		{
			throw new InvalidHostname($this->hostname);
		}

		// Valid TCP/IP ports range is 1 to 65535. Other numbers get squashed to the default (port 21).
		if (($this->port < 1) || ($this->port > 65535))
		{
			$this->port = 21;
		}

		// We need both the username and password to connect to the FTP(S) server
		if (empty($this->username) || empty($this->password))
		{
			throw new InvalidConnectionAuthentication(sprintf("You must specify both the %s username and password.", $isFtp ? 'FTP' : 'FTPS'));
		}
	}

	public function getUploader(?string $directory): Uploader
	{
		$config = clone $this;

		if (!empty($directory))
		{
			$config->directory = $directory;
		}

		switch ($this->type)
		{
			case 'ftp':
			case 'ftps':
				return new NativeFtp($config);

			default:
				return new CurlFtp($config);
		}
	}
}
