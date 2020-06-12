<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Transfer;

use RuntimeException;

class SFTPcURL
{
	private $config = null;

	public function __construct($config)
	{
		$this->config = $config;

		$this->connect();
	}

	/**
	 * Uploads a local file to the remote storage
	 *
	 * @param   string  $localFilename   The full path to the local file
	 * @param   string  $remoteFilename  The full path to the remote file
	 *
	 * @return  boolean  True on success
	 */
	public function upload($localFilename, $remoteFilename)
	{
		$fp = @fopen($localFilename, 'rb');

		if ($fp === false)
		{
			throw new RuntimeException("Unreadable local file $localFilename");
		}

		// Note: don't manually close the file pointer, it's closed automatically by uploadFromHandle
		try
		{
			$this->uploadFromHandle($remoteFilename, $fp);
		}
		catch (RuntimeException $e)
		{
			return false;
		}

		return true;
	}

	/**
	 * Returns a cURL resource handler for the remote SFTP server
	 *
	 * @param   string  $remoteFile  Optional. The remote file / folder on the SFTP server you'll be manipulating with
	 *                               cURL.
	 *
	 * @return  resource
	 */
	protected function getCurlHandle($remoteFile = '')
	{
		// Remember, the username has to be URL encoded as it's part of a URI!
		$authentication = urlencode($this->config->username);

		// We will only use username and password authentication if there are no certificates configured.
		if (empty($this->config->pubkeyfile) && !empty($this->config->password))
		{
			// Remember, both the username and password have to be URL encoded as they're part of a URI!
			$password       = urlencode($this->config->password);
			$authentication .= ':' . $password;
		}

		$ftpUri = 'sftp://' . $authentication . '@' . $this->config->hostname;

		if (!empty($this->config->port))
		{
			$ftpUri .= ':' . (int) $this->config->port;
		}

		// Relative path? Append the initial directory.
		if (substr($remoteFile, 0, 1) != '/')
		{
			$ftpUri .= $this->config->directory;
		}

		// Add a remote file if necessary. The filename must be URL encoded since we're creating a URI.
		if (!empty($remoteFile))
		{
			$suffix = '';

			$dirname = dirname($remoteFile);

			// Windows messing up dirname('/'). KILL ME.
			if ($dirname == '\\')
			{
				$dirname = '';
			}

			$dirname  = trim($dirname, '/');
			$basename = basename($remoteFile);

			if ((substr($remoteFile, -1) == '/') && !empty($basename))
			{
				$suffix = '/' . $suffix;
			}

			$ftpUri .= '/' . $dirname . (empty($dirname) ? '' : '/') . urlencode($basename) . $suffix;
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $ftpUri);
		curl_setopt($ch, CURLOPT_TIMEOUT, $this->config->timeout);

		// Do I have to use certificate authentication?
		if (!empty($this->config->pubkeyfile))
		{
			// We always need to provide a public key file
			curl_setopt($ch, CURLOPT_SSH_PUBLIC_KEYFILE, $this->config->pubkeyfile);

			// Since SSH certificates are self-signed we cannot have cURL verify their signatures against a CA.
			curl_setopt($ch, CURLOPT_CAINFO, AKEEBA_CACERT_PEM);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

			/**
			 * This is optional because newer versions of cURL can extract the private key file from a combined
			 * certificate file.
			 */
			if (!empty($this->config->privkeyfile))
			{
				curl_setopt($ch, CURLOPT_SSH_PRIVATE_KEYFILE, $this->config->privkeyfile);
			}

			/**
			 * In case of encrypted (a.k.a. password protected) private key files you need to also specify the
			 * certificate decryption key in the password field. However, if libcurl is compiled against the GnuTLS
			 * library (instead of OpenSSL) this will NOT work because of bugs / missing features in GnuTLS. It's the
			 * same problem you get when libssh is compiled against GnuTLS. The solution to that is having an
			 * unencrypted private key file.
			 */
			if (!empty($this->config->privkeyfile_pass))
			{
				curl_setopt($ch, CURLOPT_KEYPASSWD, $this->config->privkeyfile_pass);
			}
		}
		// Do I have to do SSH Agent authentication?
		elseif (empty($this->config->password))
		{
			curl_setopt($ch, CURLOPT_SSH_AUTH_TYPES, CURLSSH_AUTH_AGENT);
		}

		// Should I enable verbose output? Useful for debugging.
		if ($this->config->verbose)
		{
			curl_setopt($ch, CURLOPT_VERBOSE, 1);
		}

		curl_setopt($ch, CURLOPT_FTP_CREATE_MISSING_DIRS, 1);

		return $ch;
	}

	/**
	 * Test the connection to the SFTP server and whether the initial directory is correct. This is done by attempting
	 * to list the contents of the initial directory. The listing is not parsed (we don't really care!) and we do NOT
	 * check if we can upload files to that remote folder.
	 *
	 * @throws  RuntimeException
	 */
	protected function connect()
	{
		$ch = $this->getCurlHandle($this->config->directory . '/');
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_NOBODY, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		$listing = curl_exec($ch);
		$errNo   = curl_errno($ch);
		$error   = curl_error($ch);
		curl_close($ch);

		if ($errNo)
		{
			throw new RuntimeException("cURL Error $errNo connecting to remote SFTP server: $error", 500);
		}
	}

	/**
	 * Uploads a file using file contents provided through a file handle
	 *
	 * @param   string    $remoteFilename
	 * @param   resource  $fp
	 *
	 * @return  void
	 *
	 * @throws  RuntimeException
	 */
	protected function uploadFromHandle($remoteFilename, $fp)
	{
		// We need the file size. We can do that by getting the file position at EOF
		fseek($fp, 0, SEEK_END);
		$filesize = ftell($fp);
		rewind($fp);

		$ch = $this->getCurlHandle($remoteFilename);
		curl_setopt($ch, CURLOPT_UPLOAD, 1);
		curl_setopt($ch, CURLOPT_INFILE, $fp);
		curl_setopt($ch, CURLOPT_INFILESIZE, $filesize);

		curl_exec($ch);

		$error_no = curl_errno($ch);
		$error    = curl_error($ch);

		curl_close($ch);
		fclose($fp);

		if ($error_no)
		{
			throw new RuntimeException($error, $error_no);
		}
	}
}
