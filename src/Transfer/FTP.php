<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Transfer;

use Akeeba\ReleaseMaker\Exception\FatalProblem;

class FTP
{
	private $fp = null;

	private $config = null;

	public function __construct($config)
	{
		$this->config = $config;

		if ($config->method == 'ftps')
		{
			$this->fp = @ftp_ssl_connect($config->hostname, $config->port);
		}
		else
		{
			$this->fp = @ftp_connect($config->hostname, $config->port);
		}

		if (!$this->fp)
		{
			throw new FatalProblem('Could not connect to FTP/FTPS server: invalid hostname or port', 80);
		}

		if (!@ftp_login($this->fp, $config->username, $config->password))
		{
			throw new FatalProblem('Could not connect to FTP/FTPS server: invalid username or password', 80);
		}

		if (!@ftp_chdir($this->fp, $config->directory))
		{
			throw new FatalProblem('Could not connect to FTP/FTPS server: invalid directory', 80);
		}

		@ftp_pasv($this->fp, $config->passive);
	}

	public function __destruct()
	{
		if (is_resource($this->fp))
		{
			ftp_close($this->fp);
		}
	}

	public function upload($sourcePath, $destPath)
	{
		ftp_chdir($this->fp, $this->config->directory);

		$dir = dirname($destPath);
		$this->chdir($dir);

		$realDirectory = substr($this->config->directory, -1) == '/' ? substr($this->config->directory, 0, -1) : $this->config->directory;
		$realDirectory .= '/' . $dir;
		$realDirectory = substr($realDirectory, 0, 1) == '/' ? $realDirectory : '/' . $realDirectory;
		$realname      = $realDirectory . '/' . basename($destPath);
		$res           = @ftp_put($this->fp, $realname, $sourcePath, FTP_BINARY);

		if (!$res)
		{
			// If the file was unreadable, just skip it...
			if (is_readable($sourcePath))
			{
				throw new FatalProblem('Uploading ' . $destPath . ' has failed.', 80);
			}

			throw new FatalProblem('Uploading ' . $destPath . ' has failed because the file is unreadable.', 80);
		}
		else
		{
			@ftp_chmod($this->fp, 0755, $realname);
		}
	}

	private function chdir($dir)
	{
		$dir = ltrim($dir, '/');

		if (empty($dir))
		{
			return;
		}

		$realDirectory = substr($this->config->directory, -1) == '/' ? substr($this->config->directory, 0, -1) : $this->config->directory;
		$realDirectory .= '/' . $dir;
		$realDirectory = substr($realDirectory, 0, 1) == '/' ? $realDirectory : '/' . $realDirectory;

		$result = @ftp_chdir($this->fp, $realDirectory);

		if ($result === false)
		{
			// The directory doesn't exist, let's try to create it...
			$this->makeDirectory($dir);

			// After creating it, change into it
			$result = @ftp_chdir($this->fp, $realDirectory);
		}

		if (!$result)
		{
			throw new FatalProblem("Cannot change into $realDirectory directory", 80);
		}
	}

	private function makeDirectory($dir)
	{
		$alldirs     = explode('/', $dir);
		$previousDir = substr($this->config->directory, -1) == '/' ? substr($this->config->directory, 0, -1) : $this->config->directory;
		$previousDir = substr($previousDir, 0, 1) == '/' ? $previousDir : '/' . $previousDir;

		foreach ($alldirs as $curdir)
		{
			$check = $previousDir . '/' . $curdir;

			if (!@ftp_chdir($this->fp, $check))
			{
				if (@ftp_mkdir($this->fp, $check) === false)
				{
					throw new FatalProblem('Could not create directory ' . $check, 80);
				}

				@ftp_chmod($this->fp, 0755, $check);
			}

			$previousDir = $check;
		}

		return true;
	}
}
