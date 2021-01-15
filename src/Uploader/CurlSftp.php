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
use Akeeba\ReleaseMaker\Exception\UploaderError;
use InvalidArgumentException;
use RuntimeException;

class CurlSftp implements Uploader
{
	private S3 $config;

	public function __construct(ConnectionConfiguration $config)
	{
		if (!($config instanceof S3))
		{
			throw new InvalidArgumentException(sprintf("%s expects a %s conifugration object, %s given.", __CLASS__, S3::class, get_class($config)));
		}

		$this->config = $config;

		$this->connect();
	}

	public function __destruct()
	{
		// This class does not store a connection object.
	}

	/** @noinspection PhpFullyQualifiedNameUsageInspection */
	public function upload(string $sourcePath, string $destPath): void
	{
		$fp = @\fopen($sourcePath, 'rb');

		if ($fp === false)
		{
			throw new UploaderError(\sprintf("Unreadable local file %s", $sourcePath));
		}

		// Note: don't manually close the file pointer, it's closed automatically by uploadFromHandle
		try
		{
			$this->uploadFromHandle($destPath, $fp);
		}
		catch (RuntimeException $e)
		{
			throw new UploaderError(sprintf('Upload of file %s failed. cURL error %d: %s', $sourcePath, $e->getCode(), $e->getMessage()), $e);
		}
	}

	/**
	 * Returns a cURL resource handler for the remote SFTP server
	 *
	 * @param   string  $remoteFile  Optional. The remote file / folder on the SFTP server you'll be manipulating with
	 *                               cURL.
	 *
	 * @return  resource
	 *
	 * @noinspection PhpFullyQualifiedNameUsageInspection
	 */
	private function getCurlHandle($remoteFile = '')
	{
		// Remember, the username has to be URL encoded as it's part of a URI!
		$authentication = \urlencode($this->config->username);

		// We will only use username and password authentication if there are no certificates configured.
		if (empty($this->config->pubkeyfile) && !empty($this->config->password))
		{
			// Remember, both the username and password have to be URL encoded as they're part of a URI!
			$password       = \urlencode($this->config->password);
			$authentication .= ':' . $password;
		}

		$ftpUri = 'sftp://' . $authentication . '@' . $this->config->hostname;

		if (!empty($this->config->port))
		{
			$ftpUri .= ':' . (int) $this->config->port;
		}

		// Relative path? Append the initial directory.
		if (\substr($remoteFile, 0, 1) != '/')
		{
			$ftpUri .= $this->config->directory;
		}

		// Add a remote file if necessary. The filename must be URL encoded since we're creating a URI.
		if (!empty($remoteFile))
		{
			$suffix = '';

			$dirname = \dirname($remoteFile);

			// Windows messing up dirname('/'). KILL ME.
			if ($dirname == '\\')
			{
				$dirname = '';
			}

			$dirname  = \trim($dirname, '/');
			$basename = \basename($remoteFile);

			if ((\substr($remoteFile, -1) == '/') && !empty($basename))
			{
				$suffix = '/' . $suffix;
			}

			$ftpUri .= '/' . $dirname . (empty($dirname) ? '' : '/') . \urlencode($basename) . $suffix;
		}

		$ch = \curl_init();
		\curl_setopt($ch, CURLOPT_URL, $ftpUri);
		\curl_setopt($ch, CURLOPT_TIMEOUT, $this->config->timeout);

		// Do I have to use certificate authentication?
		if (!empty($this->config->pubkeyfile))
		{
			// We always need to provide a public key file
			\curl_setopt($ch, CURLOPT_SSH_PUBLIC_KEYFILE, $this->config->pubkeyfile);

			// Since SSH certificates are self-signed we cannot have cURL verify their signatures against a CA.
			\curl_setopt($ch, CURLOPT_CAINFO, AKEEBA_CACERT_PEM);
			\curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
			\curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

			/**
			 * This is optional because newer versions of cURL can extract the private key file from a combined
			 * certificate file.
			 */
			if (!empty($this->config->privkeyfile))
			{
				\curl_setopt($ch, CURLOPT_SSH_PRIVATE_KEYFILE, $this->config->privkeyfile);
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
				\curl_setopt($ch, CURLOPT_KEYPASSWD, $this->config->privkeyfile_pass);
			}
		}
		// Do I have to do SSH Agent authentication?
		elseif (empty($this->config->password))
		{
			\curl_setopt($ch, CURLOPT_SSH_AUTH_TYPES, CURLSSH_AUTH_AGENT);
		}

		// Should I enable verbose output? Useful for debugging.
		if ($this->config->verbose)
		{
			\curl_setopt($ch, CURLOPT_VERBOSE, 1);
		}

		\curl_setopt($ch, CURLOPT_FTP_CREATE_MISSING_DIRS, 1);

		return $ch;
	}

	/**
	 * Test the connection to the SFTP server and whether the initial directory is correct. This is done by attempting
	 * to list the contents of the initial directory. The listing is not parsed (we don't really care!) and we do NOT
	 * check if we can upload files to that remote folder.
	 *
	 * @throws  RuntimeException
	 *
	 * @noinspection PhpFullyQualifiedNameUsageInspection
	 */
	private function connect()
	{
		$ch = $this->getCurlHandle($this->config->directory . '/');
		\curl_setopt($ch, CURLOPT_HEADER, 1);
		\curl_setopt($ch, CURLOPT_NOBODY, 1);
		\curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		\curl_exec($ch);

		$errNo = \curl_errno($ch);
		$error = \curl_error($ch);

		\curl_close($ch);

		if ($errNo !== 0)
		{
			throw new UploaderError(\sprintf("cURL Error %s connecting to remote SFTP server: %s", $errNo, $error));
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
	 *
	 * @noinspection PhpFullyQualifiedNameUsageInspection
	 */
	private function uploadFromHandle($remoteFilename, $fp)
	{
		// We need the file size. We can do that by getting the file position at EOF
		\fseek($fp, 0, SEEK_END);
		$filesize = \ftell($fp);
		\rewind($fp);

		$ch = $this->getCurlHandle($remoteFilename);
		\curl_setopt($ch, CURLOPT_UPLOAD, 1);
		\curl_setopt($ch, CURLOPT_INFILE, $fp);
		\curl_setopt($ch, CURLOPT_INFILESIZE, $filesize);

		\curl_exec($ch);

		$error_no = \curl_errno($ch);
		$error    = \curl_error($ch);

		\curl_close($ch);
		\fclose($fp);

		if ($error_no !== 0)
		{
			throw new RuntimeException($error, $error_no);
		}
	}
}
