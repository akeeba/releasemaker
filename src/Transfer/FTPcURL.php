<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Transfer;

use Akeeba\ReleaseMaker\Exception\FatalProblem;
use RuntimeException;

class FTPcURL
{
	private $config;

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
		$fp = @\fopen($localFilename, 'rb');

		if ($fp === false)
		{
			throw new FatalProblem(\sprintf("Unreadable local file %s", $localFilename), 80);
		}

		// Note: don't manually close the file pointer, it's closed automatically by uploadFromHandle
		try
		{
			$this->uploadFromHandle($remoteFilename, $fp);
		}
		catch (RuntimeException $runtimeException)
		{
			return false;
		}

		return true;
	}

	/**
	 * Test the connection to the FTP server and whether the initial directory is correct. This is done by attempting to
	 * list the contents of the initial directory. The listing is not parsed (we don't really care!) and we do NOT check
	 * if we can upload files to that remote folder.
	 *
	 * @throws  RuntimeException
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
			throw new FatalProblem(\sprintf("cURL Error %s connecting to remote FTP server: %s", $errNo, $error), 80);
		}
	}

	/**
	 * Uploads a file using file contents provided through a file handle
	 *
	 * @param   string    $remoteFilename  Remote file to write contents to
	 * @param   resource  $fp              File or stream handler of the source data to upload
	 *
	 * @return  void
	 *
	 * @throws  RuntimeException
	 */
	private function uploadFromHandle($remoteFilename, $fp)
	{
		// We need the file size. We can do that by getting the file position at EOF
		\fseek($fp, 0, SEEK_END);
		$filesize = \ftell($fp);
		\rewind($fp);

		/**
		 * The ;type=i suffix forces Binary file transfer mode
		 *
		 * @see  https://curl.haxx.se/mail/archive-2008-05/0089.html
		 */
		$ch = $this->getCurlHandle($remoteFilename . ';type=i');
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

	/**
	 * Returns a cURL resource handler for the remote FTP server
	 *
	 * @param   string  $remoteFile  Optional. The remote file / folder on the FTP server you'll be manipulating with
	 *                               cURL.
	 *
	 * @return  resource
	 */
	private function getCurlHandle($remoteFile = '')
	{
		/**
		 * Get the FTP URI
		 *
		 * VERY IMPORTANT! WE NEED THE DOUBLE SLASH AFTER THE HOST NAME since we are giving an absolute path.
		 * @see https://technicalsanctuary.wordpress.com/2012/11/01/curl-curl-9-server-denied-you-to-change-to-the-given-directory/
		 */

		$ftpUri = 'ftp://' . $this->config->hostname . '/';

		// Relative path? Append the initial directory.
		if (\substr($remoteFile, 0, 1) != '/')
		{
			$ftpUri .= $this->config->directory;
		}

		// Add a remote file if necessary. The filename must be URL encoded since we're creating a URI.
		if (!empty($remoteFile))
		{
			$suffix = '';

			if (\substr($remoteFile, -7, 6) == ';type=')
			{
				$suffix     = \substr($remoteFile, -7);
				$remoteFile = \substr($remoteFile, 0, -7);
			}

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

		// Colons in usernames must be URL escaped
		$username = \str_replace(':', '%3A', $this->config->username);

		$ch = \curl_init();
		\curl_setopt($ch, CURLOPT_URL, $ftpUri);
		\curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $this->config->password);
		\curl_setopt($ch, CURLOPT_PORT, $this->config->port);
		\curl_setopt($ch, CURLOPT_TIMEOUT, $this->config->timeout);

		// Should I enable Implict SSL?
		if ($this->config->method == 'ftpscurl')
		{
			\curl_setopt($ch, CURLOPT_FTP_SSL, CURLFTPSSL_ALL);
			\curl_setopt($ch, CURLOPT_FTPSSLAUTH, CURLFTPAUTH_DEFAULT);

			\curl_setopt($ch, CURLOPT_CAINFO, AKEEBA_CACERT_PEM);
			\curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
			\curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		}

		// Should I ignore the server-supplied passive mode IP address?
		if ($this->config->passive && $this->config->passive_fix)
		{
			\curl_setopt($ch, CURLOPT_FTP_SKIP_PASV_IP, 1);
		}

		// Should I enable active mode?
		if (!$this->config->passive)
		{
			/**
			 * cURL always uses passive mode for FTP transfers. Setting the CURLOPT_FTPPORT flag enables the FTP PORT
			 * command which makes the connection active. Setting it to '-'  lets the library use your system's default
			 * IP address.
			 *
			 * @see https://curl.haxx.se/libcurl/c/CURLOPT_FTPPORT.html
			 */
			\curl_setopt($ch, CURLOPT_FTPPORT, '-');
		}

		// Should I enable verbose output? Useful for debugging.
		if ($this->config->verbose)
		{
			\curl_setopt($ch, CURLOPT_VERBOSE, 1);
		}

		\curl_setopt($ch, CURLOPT_FTP_CREATE_MISSING_DIRS, 1);

		return $ch;
	}

}
