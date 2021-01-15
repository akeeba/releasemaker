<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Configuration\Connection;


use Akeeba\ReleaseMaker\Contracts\ExceptionCode;
use Akeeba\ReleaseMaker\Contracts\Uploader;
use Akeeba\ReleaseMaker\Exception\InvalidConnectionAuthentication;
use Akeeba\ReleaseMaker\Exception\InvalidHostname;
use Akeeba\ReleaseMaker\Uploader\CurlSftp;
use Akeeba\ReleaseMaker\Uploader\NativeSftp;
use LogicException;

/**
 * SFTP Connection Configuration
 *
 * @property-read string $hostname             SFTP hostname.
 * @property-read int    $port                 SFTP port. Default: 21.
 * @property-read string $username             SFTP username.
 * @property-read string $password             SFTP password.
 * @property-read string $publicKey            SFTP public key file.
 * @property-read string $privateKey           SFTP private key file.
 * @property-read string $privateKeyPassword   SFTP private key file's password.
 * @property-read int    $timeout              Total (connection and upload) timeout in seconds.
 *
 * @since  2.0.0
 */
class Sftp extends Configuration
{
	private string $hostname;

	private int $port = 22;

	private string $username = '';

	private string $password = '';

	private string $publicKey = '';

	private string $privateKey = '';

	private string $privateKeyPassword = '';

	private int $timeout = 3600;

	public function __construct(array $configuration)
	{
		$this->type               = $configuration['type'] ?? 'ftp';
		$this->hostname           = $configuration['hostname'] ?? '';
		$this->port               = (int) ($configuration['port'] ?? 22);
		$this->directory          = $configuration['directory'] ?? '';
		$this->username           = $configuration['username'] ?? '';
		$this->password           = $configuration['password'] ?? '';
		$this->publicKey          = $configuration['public_key'] ?? '';
		$this->privateKey         = $configuration['private_key'] ?? '';
		$this->privateKeyPassword = $configuration['private_key_password'] ?? '';
		$this->timeout            = max(30, (int) ($configuration['timeout'] ?? 3600));

		// Make sure the type is something this class can handle.
		if (!in_array($this->type, ['sftp', 'sftpcurl']))
		{
			throw new LogicException(sprintf("Invalid connection type ‘%s’ for class %s. Make sure you go through %s::factory() instead of instantiating this class directly.", $this->getType(), __CLASS__, Configuration::class), ExceptionCode::INVALID_CONNECTION_TYPE);
		}

		// We cannot allow an empty hostname
		if (empty($this->hostname) || !filter_var($this->hostname, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME))
		{
			throw new InvalidHostname($this->hostname);
		}

		// Valid TCP/IP ports range is 1 to 65535. Other numbers get squashed to the default (port 21).
		if (($this->port < 1) || ($this->port > 65535))
		{
			$this->port = 22;
		}

		$hasUserAndPass = !empty($this->username) && !empty($this->password);
		$hasPubKeyAuth  = !empty($this->username) && !empty($this->publicKey) && (($this->type = 'sftpcurl') || !empty($this->privateKey));
		$hasAgentAuth   = !empty($this->username) && empty($this->password);

		if (empty($this->username))
		{
			throw new InvalidConnectionAuthentication('Insufficient information for SFTP authentication. A valid, non-empty username is required for all possible SFTP authentication modes.');
		}

		if (!$hasUserAndPass && !$hasPubKeyAuth && !$hasAgentAuth)
		{
			throw new InvalidConnectionAuthentication('Insufficient information for SFTP authentication. You need to specify the username to use SSH Agent authentication; or both the username and password for password authentication; or the username and the public and private key files to use certificate authentication.');
		}
	}

	public function getUploader(?string $directory): Uploader
	{
		$config = clone $this;

		$config->directory = $directory;

		switch ($this->type)
		{
			case 'sftp':
				// Native SFTP will only be used if the SSH2 extension is loaded. Otherwise we fall back to cURL.
				if (function_exists('ssh2_connect'))
				{
					return new NativeSftp($config);
				}

				// Fallback to cURL.
				return new CurlSftp($config);

			default:
				// Explicitly requested cURL.
				return new CurlSftp($config);
		}
	}
}
