<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Uploader;

use Akeeba\ReleaseMaker\Configuration\Connection\S3 as FtpConfiguration;
use Akeeba\ReleaseMaker\Contracts\ConnectionConfiguration;
use Akeeba\ReleaseMaker\Contracts\Uploader;
use Akeeba\ReleaseMaker\Exception\UploaderError;
use InvalidArgumentException;

class NativeFtp implements Uploader
{
	/**
	 * The FTP connection resource
	 *
	 * @var resource|null
	 */
	private $ftp;

	private FtpConfiguration $config;

	/**
	 * @noinspection PhpComposerExtensionStubsInspection
	 * @noinspection PhpFullyQualifiedNameUsageInspection
	 */
	public function __construct(ConnectionConfiguration $config)
	{
		if (!($config instanceof FtpConfiguration))
		{
			throw new InvalidArgumentException(sprintf("%s expects a %s conifugration object, %s given.", __CLASS__, FtpConfiguration::class, get_class($config)));
		}

		$this->config = $config;

		if ($config->secure)
		{
			$ftp = @\ftp_ssl_connect($config->hostname, $config->port);
		}
		else
		{
			$ftp = @\ftp_connect($config->hostname, $config->port);
		}

		$this->ftp = ($ftp !== false) ? $ftp : null;

		if (is_null($this->ftp))
		{
			throw new UploaderError('Could not connect to FTP/FTPS server: invalid hostname or port');
		}

		if (!@\ftp_login($this->ftp, $config->username, $config->password))
		{
			\ftp_close($this->ftp);

			throw new UploaderError('Could not connect to FTP/FTPS server: invalid username or password');
		}

		if (!@\ftp_chdir($this->ftp, $config->directory))
		{
			\ftp_close($this->ftp);

			throw new UploaderError('Could not connect to FTP/FTPS server: invalid directory');
		}

		if (!@\ftp_pasv($this->ftp, $config->passive))
		{
			\ftp_close($this->ftp);

			throw new UploaderError(sprintf('Could not set the connection\'s FTP %s Mode', $config->passive ? 'Passive' : 'Active'));
		}
	}

	/**
	 * @noinspection PhpComposerExtensionStubsInspection
	 * @noinspection PhpFullyQualifiedNameUsageInspection
	 */
	public function __destruct()
	{
		if (\is_resource($this->ftp))
		{
			\ftp_close($this->ftp);
		}
	}

	/**
	 * @noinspection PhpComposerExtensionStubsInspection
	 * @noinspection PhpFullyQualifiedNameUsageInspection
	 */
	public function upload(string $sourcePath, string $destPath): void
	{
		\ftp_chdir($this->ftp, $this->config->directory);

		$dir = \dirname($destPath);
		$this->chdir($dir);

		$realDirectory = \substr($this->config->directory, -1) == '/' ? \substr($this->config->directory, 0, -1) : $this->config->directory;
		$realDirectory .= '/' . $dir;
		$realDirectory = \substr($realDirectory, 0, 1) == '/' ? $realDirectory : '/' . $realDirectory;
		$realname      = $realDirectory . '/' . \basename($destPath);
		$res           = @\ftp_put($this->ftp, $realname, $sourcePath, FTP_BINARY);

		if (!$res)
		{
			// If the file was unreadable, just skip it...
			if (\is_readable($sourcePath))
			{
				throw new UploaderError(\sprintf("Uploading %s has failed.", $destPath));
			}

			throw new UploaderError(\sprintf("Uploading %s has failed because the file is unreadable.", $destPath));
		}

		@\ftp_chmod($this->ftp, 0755, $realname);
	}

	/**
	 * @noinspection PhpComposerExtensionStubsInspection
	 * @noinspection PhpFullyQualifiedNameUsageInspection
	 */
	private function chdir(string $dir): void
	{
		$dir = \ltrim($dir, '/');

		if (empty($dir))
		{
			return;
		}

		$realDirectory = \substr($this->config->directory, -1) == '/' ? \substr($this->config->directory, 0, -1) : $this->config->directory;
		$realDirectory .= '/' . $dir;
		$realDirectory = \substr($realDirectory, 0, 1) == '/' ? $realDirectory : '/' . $realDirectory;

		$result = @\ftp_chdir($this->ftp, $realDirectory);

		if (!$result)
		{
			// The directory doesn't exist, let's try to create it...
			$this->makeDirectory($dir);

			// After creating it, change into it
			$result = @\ftp_chdir($this->ftp, $realDirectory);
		}

		if (!$result)
		{
			throw new UploaderError(\sprintf("Cannot change into %s directory", $realDirectory));
		}
	}

	/**
	 * @noinspection PhpComposerExtensionStubsInspection
	 * @noinspection PhpFullyQualifiedNameUsageInspection
	 */
	private function makeDirectory(string $dir): bool
	{
		$alldirs     = \explode('/', $dir);
		$previousDir = \substr($this->config->directory, -1) == '/' ? \substr($this->config->directory, 0, -1) : $this->config->directory;
		$previousDir = \substr($previousDir, 0, 1) == '/' ? $previousDir : '/' . $previousDir;

		foreach ($alldirs as $curdir)
		{
			$check = $previousDir . '/' . $curdir;

			if (!@\ftp_chdir($this->ftp, $check))
			{
				if (@\ftp_mkdir($this->ftp, $check) === false)
				{
					throw new UploaderError(\sprintf("Could not create directory %s", $check));
				}

				@\ftp_chmod($this->ftp, 0755, $check);
			}

			$previousDir = $check;
		}

		return true;
	}
}
