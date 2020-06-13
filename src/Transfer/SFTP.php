<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Transfer;

use Akeeba\ReleaseMaker\Exception\FatalProblem;

class SFTP
{
	private $ssh = null;

	private $fp = null;

	private $config = null;

	public function __construct($config)
	{
		$this->config = $config;

		if (!function_exists('ssh2_connect'))
		{
			throw new FatalProblem('You do not have the SSH2 PHP extension, therefore could not connect to SFTP server.', 80);
		}

		$this->ssh = ssh2_connect($config->hostname, $config->port);

		if (!$this->ssh)
		{
			throw new FatalProblem('Could not connect to SFTP server: invalid hostname or port', 80);
		}

		if ($config->pubkeyfile && $config->privkeyfile)
		{
			if (!@ssh2_auth_pubkey_file($this->ssh, $config->username, $config->pubkeyfile, $config->privkeyfile, $config->privkeyfile_pass))
			{
				throw new FatalProblem(sprintf("Could not connect to SFTP server: invalid username or public/private key file (%s - %s - %s - %s)", $config->username, $config->pubkeyfile, $config->privkeyfile, $config->privkeyfile_pass),
					80
				);
			}

		}
		elseif ($config->password)
		{
			if (!@ssh2_auth_password($this->ssh, $config->username, $config->password))
			{
				throw new FatalProblem(sprintf("Could not connect to SFTP server: invalid username or password (%s:%s)", $config->username, $config->password), 80);
			}
		}
		else
		{
			if (!@ssh2_auth_agent($this->ssh, $config->username))
			{
				throw new FatalProblem(sprintf("Could not connect to SFTP server: invalid username (%s) or agent failed to connect.", $config->username),
					80
				);
			}
		}


		$this->fp = ssh2_sftp($this->ssh);

		if ($this->fp === false)
		{
			throw new FatalProblem('Could not connect to SFTP server: no SFTP support on this SSH server', 80);
		}

		if (!@ssh2_sftp_stat($this->fp, $config->directory))
		{
			throw new FatalProblem('Could not connect to SFTP server: invalid directory (' . $config->directory . ')', 80);
		}
	}

	public function upload($sourcePath, $destPath)
	{
		$dir = dirname($destPath);
		$this->chdir($dir);

		$realdir  = substr($this->config->directory, -1) == '/' ? substr($this->config->directory, 0, -1) : $this->config->directory;
		$realdir  .= '/' . $dir;
		$realdir  = substr($realdir, 0, 1) == '/' ? $realdir : '/' . $realdir;
		$realname = $realdir . '/' . basename($destPath);

		$fp = @fopen("ssh2.sftp://{$this->fp}$realname", 'w');

		if ($fp === false)
		{
			throw new FatalProblem("Could not open remote file $realname for writing", 80);
		}

		$localfp = @fopen($sourcePath, 'rb');

		if ($localfp === false)
		{
			throw new FatalProblem("Could not open local file $sourcePath for reading", 80);
		}

		$res = true;

		while (!feof($localfp) && ($res !== false))
		{
			$buffer = @fread($localfp, 524288);
			$res    = @fwrite($fp, $buffer);
		}

		@fclose($fp);
		@fclose($localfp);

		if (!$res)
		{
			// If the file was unreadable, just skip it...
			if (is_readable($sourcePath))
			{
				throw new FatalProblem('Uploading ' . $destPath . ' has failed.', 80);
			}
			else
			{
				throw new FatalProblem('Uploading ' . $destPath . ' has failed because the file is unreadable.', 80);
			}
		}
	}

	private function chdir(string $dir): bool
	{
		$dir = ltrim($dir, '/');

		if (empty($dir))
		{
			return false;
		}

		$realdir = substr($this->config->directory, -1) == '/' ? substr($this->config->directory, 0, -1) : $this->config->directory;
		$realdir .= '/' . $dir;
		$realdir = substr($realdir, 0, 1) == '/' ? $realdir : '/' . $realdir;

		$result = @ssh2_sftp_stat($this->fp, $realdir);
		if ($result === false)
		{
			// The directory doesn't exist, let's try to create it...
			$this->makeDirectory($dir);

			// After creating it, change into it
			$result = @ssh2_sftp_stat($this->fp, $realdir);
		}

		if (!$result)
		{
			throw new FatalProblem("Cannot change into $realdir directory", 80);
		}

		return true;
	}

	private function makeDirectory($dir)
	{
		$alldirs     = explode('/', $dir);
		$previousDir = substr($this->config->directory, -1) == '/' ? substr($this->config->directory, 0, -1) : $this->config->directory;
		$previousDir = substr($previousDir, 0, 1) == '/' ? $previousDir : '/' . $previousDir;

		foreach ($alldirs as $curdir)
		{
			$check = $previousDir . '/' . $curdir;
			if (!@ssh2_sftp_stat($this->fp, $check))
			{
				if (@ssh2_sftp_mkdir($this->fp, $check, 0755, true) === false)
				{
					throw new FatalProblem('Could not create directory ' . $check, 80);
				}
			}
			$previousDir = $check;
		}

		return true;
	}
}
