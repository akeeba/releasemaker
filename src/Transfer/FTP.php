<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Transfer;

use Akeeba\ReleaseMaker\Exception\FatalProblem;

class FTP implements Uploader
{
	/**
	 * The FTP connection resource
	 *
	 * @var resource|null
	 */
	private $ftp;

	private $config;

	/** @inheritDoc */
	public function __construct(object $config)
	{
		$this->config = $config;

		if ($config->method == 'ftps')
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
			throw new FatalProblem('Could not connect to FTP/FTPS server: invalid hostname or port', 80);
		}

		if (!@\ftp_login($this->ftp, $config->username, $config->password))
		{
			\ftp_close($this->ftp);

			throw new FatalProblem('Could not connect to FTP/FTPS server: invalid username or password', 80);
		}

		if (!@\ftp_chdir($this->ftp, $config->directory))
		{
			\ftp_close($this->ftp);

			throw new FatalProblem('Could not connect to FTP/FTPS server: invalid directory', 80);
		}

		if (!@\ftp_pasv($this->ftp, $config->passive))
		{
			\ftp_close($this->ftp);

			throw new FatalProblem('Could not set the connection\'s FTP %s Mode', $config->passive ? 'Passive' : 'Active');
		}
	}

	/** @inheritDoc */
	public function __destruct()
	{
		if (\is_resource($this->ftp))
		{
			\ftp_close($this->ftp);
		}
	}

	/** @inheritDoc */
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
				throw new FatalProblem(\sprintf("Uploading %s has failed.", $destPath), 80);
			}

			throw new FatalProblem(\sprintf("Uploading %s has failed because the file is unreadable.", $destPath), 80);
		}

		@\ftp_chmod($this->ftp, 0755, $realname);
	}

	private function chdir($dir)
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
			throw new FatalProblem(\sprintf("Cannot change into %s directory", $realDirectory), 80);
		}
	}

	private function makeDirectory($dir)
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
					throw new FatalProblem(\sprintf("Could not create directory %s", $check), 80);
				}

				@\ftp_chmod($this->ftp, 0755, $check);
			}

			$previousDir = $check;
		}

		return true;
	}
}
