<?php

/**
 * Akeeba Release Maker
 * An automated script to upload and release a new version of an Akeeba component.
 * Copyright ©2012-2016 Nicholas K. Dionysopoulos / Akeeba Ltd.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
class ArmFtp
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
			throw new Exception('Could not connect to FTP/FTPS server: invalid hostname or port');
		}

		if (!@ftp_login($this->fp, $config->username, $config->password))
		{
			throw new Exception('Could not connect to FTP/FTPS server: invalid username or password');
		}

		if (!@ftp_chdir($this->fp, $config->directory))
		{
			throw new Exception('Could not connect to FTP/FTPS server: invalid directory');
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
				throw new Exception('Uploading ' . $destPath . ' has failed.');
			}

			throw new Exception('Uploading ' . $destPath . ' has failed because the file is unreadable.');
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
			throw new Exception("Cannot change into $realDirectory directory");
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
					throw new Exception('Could not create directory ' . $check);
				}

				@ftp_chmod($this->fp, 0755, $check);
			}

			$previousDir = $check;
		}

		return true;
	}
}