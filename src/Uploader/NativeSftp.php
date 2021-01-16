<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Uploader;

use Akeeba\ReleaseMaker\Configuration\Connection\S3;
use Akeeba\ReleaseMaker\Contracts\ConnectionConfiguration;
use Akeeba\ReleaseMaker\Contracts\Uploader;
use Akeeba\ReleaseMaker\Exception\ARSError;
use InvalidArgumentException;

class NativeSftp implements Uploader
{
	private $ssh;

	private $fp;

	private S3 $config;

	/**
	 * @noinspection PhpFullyQualifiedNameUsageInspection
	 * @noinspection PhpComposerExtensionStubsInspection
	 */
	public function __construct(ConnectionConfiguration $config)
	{
		if (!($config instanceof S3))
		{
			throw new InvalidArgumentException(sprintf("%s expects a %s conifugration object, %s given.", __CLASS__, S3::class, get_class($config)));
		}

		$this->config = $config;

		if (!\function_exists('ssh2_connect'))
		{
			throw new ARSError('You do not have the SSH2 PHP extension, therefore could not connect to SFTP server.');
		}

		$this->ssh = \ssh2_connect($config->hostname, $config->port);

		if (!$this->ssh)
		{
			throw new ARSError('Could not connect to SFTP server: invalid hostname or port');
		}

		if ($config->publicKey && $config->privateKey)
		{
			if (!@\ssh2_auth_pubkey_file($this->ssh, $config->username, $config->publicKey, $config->privateKey, $config->privateKeyPassword))
			{
				throw new ARSError(\sprintf("Could not connect to SFTP server: invalid username or public/private key file (%s - %s - %s - %s)", $config->username, $config->publicKey, $config->privateKey, $config->privateKeyPassword)
				);
			}

		}
		elseif ($config->password)
		{
			if (!@\ssh2_auth_password($this->ssh, $config->username, $config->password))
			{
				throw new ARSError(\sprintf("Could not connect to SFTP server: invalid username or password (%s:%s)", $config->username, $config->password));
			}
		}
		elseif (!@\ssh2_auth_agent($this->ssh, $config->username))
		{
			throw new ARSError(\sprintf("Could not connect to SFTP server: invalid username (%s) or agent failed to connect.", $config->username)
			);
		}

		$this->fp = \ssh2_sftp($this->ssh);

		if ($this->fp === false)
		{
			throw new ARSError('Could not connect to SFTP server: no SFTP support on this SSH server');
		}

		if (!@\ssh2_sftp_stat($this->fp, $config->directory))
		{
			throw new ARSError(\sprintf("Could not connect to SFTP server: invalid directory (%s)", $config->directory));
		}
	}

	/**
	 * @noinspection PhpFullyQualifiedNameUsageInspection
	 * @noinspection PhpComposerExtensionStubsInspection
	 */
	public function __destruct()
	{
		if (is_resource($this->fp))
		{
			@fclose($this->fp);
		}

		\ssh2_disconnect($this->ssh);

		$this->fp  = null;
		$this->ssh = null;
	}

	/**
	 * @noinspection PhpFullyQualifiedNameUsageInspection
	 */
	public function upload(string $sourcePath, string $destPath): void
	{
		$dir = \dirname($destPath);
		$this->chdir($dir);

		$realdir  = \substr($this->config->directory, -1) == '/' ? \substr($this->config->directory, 0, -1) : $this->config->directory;
		$realdir  .= '/' . $dir;
		$realdir  = \substr($realdir, 0, 1) == '/' ? $realdir : '/' . $realdir;
		$realname = $realdir . '/' . \basename($destPath);

		$fp = @\fopen(\sprintf("ssh2.sftp://%s%s", $this->fp, $realname), 'w');

		if ($fp === false)
		{
			throw new ARSError(\sprintf("Could not open remote file %s for writing", $realname));
		}

		$localfp = @\fopen($sourcePath, 'rb');

		if ($localfp === false)
		{
			throw new ARSError(\sprintf("Could not open local file %s for reading", $sourcePath));
		}

		$res = true;

		while (!\feof($localfp) && ($res !== false))
		{
			$buffer = @\fread($localfp, 524288);
			$res    = @\fwrite($fp, $buffer);
		}

		@\fclose($fp);
		@\fclose($localfp);

		if (!$res)
		{
			// If the file was unreadable, just skip it...
			if (\is_readable($sourcePath))
			{
				throw new ARSError(\sprintf("Uploading %s has failed.", $destPath));
			}

			throw new ARSError(\sprintf("Uploading %s has failed because the file is unreadable.", $destPath));
		}
	}

	public function getConnectionConfiguration(): ConnectionConfiguration
	{
		return $this->config;
	}

	/**
	 * @noinspection PhpFullyQualifiedNameUsageInspection
	 * @noinspection PhpComposerExtensionStubsInspection
	 */
	private function chdir(string $dir): bool
	{
		$dir = \ltrim($dir, '/');

		if (empty($dir))
		{
			return false;
		}

		$realdir = \substr($this->config->directory, -1) == '/' ? \substr($this->config->directory, 0, -1) : $this->config->directory;
		$realdir .= '/' . $dir;
		$realdir = \substr($realdir, 0, 1) == '/' ? $realdir : '/' . $realdir;

		$result = @\ssh2_sftp_stat($this->fp, $realdir);
		if ($result === false)
		{
			// The directory doesn't exist, let's try to create it...
			$this->makeDirectory($dir);

			// After creating it, change into it
			$result = @\ssh2_sftp_stat($this->fp, $realdir);
		}

		if (!$result)
		{
			throw new ARSError(\sprintf("Cannot change into %s directory", $realdir));
		}

		return true;
	}

	/**
	 * @noinspection PhpFullyQualifiedNameUsageInspection
	 * @noinspection PhpComposerExtensionStubsInspection
	 */
	private function makeDirectory($dir)
	{
		$alldirs     = \explode('/', $dir);
		$previousDir = \substr($this->config->directory, -1) == '/' ? \substr($this->config->directory, 0, -1) : $this->config->directory;
		$previousDir = \substr($previousDir, 0, 1) == '/' ? $previousDir : '/' . $previousDir;

		foreach ($alldirs as $curdir)
		{
			$check = $previousDir . '/' . $curdir;
			if (!@\ssh2_sftp_stat($this->fp, $check) && !@\ssh2_sftp_mkdir($this->fp, $check, 0755, true))
			{
				throw new ARSError(\sprintf("Could not create directory %s", $check));
			}
			$previousDir = $check;
		}

		return true;
	}
}
