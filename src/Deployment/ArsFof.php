<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Deployment;

use Akeeba\ReleaseMaker\Configuration\Configuration;
use Akeeba\ReleaseMaker\Exception\ARSError;

/**
 * Akeeba Release System API integration for Akeeba Release Maker
 */
class ArsFof implements ARSInterface
{
	/**
	 * The hostname of the site where ARS is installed, without the index.php
	 *
	 * @var string
	 */
	private $host;

	/**
	 * The username we're going to use to connect to the host
	 *
	 * @var string
	 */
	private $username;

	/**
	 * The password we're going to use to connect to the host
	 *
	 * @var string
	 */
	private $password;

	/**
	 * The API Token we're going to use to connect to the host (if username and password are empty)
	 *
	 * @var string
	 */
	private $apiToken;

	/**
	 * Public class constructor
	 *
	 * @param   array  $config  The configuration variables for this class
	 */
	public function __construct(array $config = [])
	{
		$this->host     = $config['host'] ?? '';
		$this->username = $config['username'] ?? '';
		$this->password = $config['password'] ?? '';
		$this->apiToken = $config['apiToken'] ?? '';
	}

	/**
	 * Get the data object of a release
	 *
	 * @param   integer  $category  The category ID inside which the version is expected
	 * @param   string   $version   The version tag
	 *
	 * @return  object  The data object of the release
	 */
	public function getRelease(int $category, string $version): object
	{
		$arsData = [
			'view'            => 'Releases',
			'task'            => 'browse',
			'category'        => $category,
			'version[method]' => 'exact',
			'version[value]'  => $version,
			'format'          => 'json',
		];

		$response = $this->doApiCall($arsData);

		$response = \json_decode($response);

		if (empty($response))
		{
			return (object) [
				'id'               => null,
				'category_id'      => $category,
				'version'          => $version,
				'alias'            => null,
				'maturity'         => 'stable',
				'description'      => null,
				'notes'            => null,
				'hits'             => 0,
				'created_by'       => 0,
				'created'          => '0000-00-00 00:00:00',
				'modified_by'      => 0,
				'modified'         => '0000-00-00 00:00:00',
				'checked_out'      => 0,
				'checked_out_time' => '0000-00-00 00:00:00',
				'ordering'         => 0,
				'access'           => 1,
				'published'        => 0,
				'language'         => '*',
			];
		}

		return \array_shift($response);
	}

	public function getReleaseById(int $release_id): object
	{
		$arsData = [
			'view'   => 'Releases',
			'task'   => 'read',
			'id'     => $release_id,
			'format' => 'json',
		];

		$response = $this->doApiCall($arsData);

		$response = \json_decode($response);

		return $response ?: (object) [
			'id'               => null,
			'category_id'      => null,
			'version'          => null,
			'alias'            => null,
			'maturity'         => 'stable',
			'description'      => null,
			'notes'            => null,
			'hits'             => 0,
			'created_by'       => 0,
			'created'          => '0000-00-00 00:00:00',
			'modified_by'      => 0,
			'modified'         => '0000-00-00 00:00:00',
			'checked_out'      => 0,
			'checked_out_time' => '0000-00-00 00:00:00',
			'ordering'         => 0,
			'access'           => 1,
			'published'        => 0,
			'language'         => '*',
		];
	}


	public function addRelease(array $releaseData)
	{
		return $this->saveRelease($releaseData);
	}

	public function editRelease(array $releaseData)
	{
		return $this->saveRelease($releaseData);
	}

	public function getItem($release, $type, $fileOrURL)
	{
		// Try to find the item by filename
		$key = ($type == 'file') ? 'filename' : 'url';

		$arsData = [
			'view'    => 'Items',
			'task'    => 'browse',
			'release' => $release,
			'type'    => $type,
			$key      => $fileOrURL,
			'format'  => 'json',
		];

		$response = $this->doApiCall($arsData);
		$response = \json_decode($response);

		if (!empty($response))
		{
			return \array_shift($response);
		}

		return (object) [
			'id'               => null,
			'release_id'       => $release,
			'title'            => null,
			'alias'            => null,
			'description'      => null,
			'type'             => $type,
			'filename'         => ($type == 'file') ? $fileOrURL : null,
			'url'              => ($type == 'link') ? $fileOrURL : null,
			'updatestream'     => null,
			'md5'              => null,
			'sha1'             => null,
			'filesize'         => null,
			'hits'             => 0,
			'created_by'       => 0,
			'created'          => '0000-00-00 00:00:00',
			'modified_by'      => 0,
			'modified'         => '0000-00-00 00:00:00',
			'checked_out'      => 0,
			'checked_out_time' => '0000-00-00 00:00:00',
			'ordering'         => 0,
			'access'           => 1,
			'published'        => 0,
			'language'         => '*',
			'environments'     => null,
		];
	}

	public function getItemById($item_id)
	{
		$arsData = [
			'view'   => 'Items',
			'task'   => 'read',
			'id'     => $item_id,
			'format' => 'json',
		];

		$response = $this->doApiCall($arsData);
		$response = \json_decode($response);

		return $response ?: (object) [
			'id'               => null,
			'release_id'       => null,
			'title'            => null,
			'alias'            => null,
			'description'      => null,
			'type'             => null,
			'filename'         => null,
			'url'              => null,
			'updatestream'     => null,
			'md5'              => null,
			'sha1'             => null,
			'filesize'         => null,
			'hits'             => 0,
			'created_by'       => 0,
			'created'          => '0000-00-00 00:00:00',
			'modified_by'      => 0,
			'modified'         => '0000-00-00 00:00:00',
			'checked_out'      => 0,
			'checked_out_time' => '0000-00-00 00:00:00',
			'ordering'         => 0,
			'access'           => 1,
			'published'        => 0,
			'language'         => '*',
			'environments'     => null,
		];
	}

	public function addItem(array $itemData): string
	{
		return $this->saveItem($itemData);
	}

	public function editItem(array $itemData): string
	{
		return $this->saveItem($itemData);
	}

	private function saveRelease(array $releaseData)
	{
		$arsData = [
			'view'   => 'Releases',
			'task'   => 'save',
			'format' => 'json',
		];

		$arsData = \array_merge($releaseData, $arsData);

		return $this->doApiCall($arsData);
	}

	private function saveItem(array $itemData): string
	{
		$arsData = [
			'view'      => 'Items',
			'task'      => 'save',
			'format'    => 'json',
			'returnurl' => \base64_encode('index.php'),
		];

		$arsData = \array_merge($itemData, $arsData);

		return $this->doApiCall($arsData);
	}

	/**
	 * Perform an ARS API call using the $postData provided
	 *
	 * @param   array  $postData  POST variables to send to ARS
	 */
	private function doApiCall(array $postData = [])
	{
		$arsData = [
			'option'             => 'com_ars',
			'_fofauthentication' => \json_encode([
				'username' => $this->username,
				'password' => $this->password,
			]),
		];

		$postData = \array_merge($postData, $arsData);

		$url = \rtrim($this->host, '/');
		$url = (\substr($url, -4) === '.php') ? $url : ($url . '/index.php');

		$conf                = Configuration::getInstance();
		$communicationMethod = $conf->api->connector;

		switch ($communicationMethod)
		{
			case 'curl':
				return $this->postWithCurl($url, $postData) ?? false;

			case 'php':
			default:
				return $this->postWithPhp($url, $postData) ?? false;
		}
	}

	/**
	 * Do a POST request with cURL.
	 *
	 * @param   string  $url       The URL to POST data to
	 * @param   array   $postData  The POST body data
	 *
	 * @return  null|string
	 */
	private function postWithCurl(string $url, array $postData): ?string
	{
		$ch = \curl_init($url);

		// Do I need to use FOF API Token Authentication instead?
		if (!empty($this->apiToken))
		{
			// Remove the legacy FOF Transparent Authentication header
			unset ($postData['_fofauthentication']);

			// Alternatively I could do $postData['_fofToken'] = $this->apiToken;

			\curl_setopt($ch, CURLOPT_HTTPHEADER, [
				'Authentication: Bearer ' . $this->apiToken,
				'X-FOF-Token: ' . $this->apiToken,
			]);
		}

		\curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		\curl_setopt($ch, CURLOPT_POST, true);
		\curl_setopt($ch, CURLOPT_POSTFIELDS, \http_build_query($postData));
		\curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		\curl_setopt($ch, CURLOPT_FAILONERROR, true);
		\curl_setopt($ch, CURLOPT_HEADER, false);
		\curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		\curl_setopt($ch, CURLOPT_CAINFO, Configuration::getInstance()->api->CACertPath);

		\curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
		\curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		\curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
		\curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		\curl_setopt($ch, CURLOPT_USERAGENT, 'AkeebaReleaseMaker/2.0');

		// In Debug mode I have cURL output everything that's going on to STDERR
		if (defined('ARM_DEBUG'))
		{
			\curl_setopt($ch, CURLOPT_VERBOSE, true);
		}

		$raw = \curl_exec($ch);

		$errno = \curl_errno($ch);
		$error = \curl_error($ch);
		\curl_close($ch);

		if ($raw === false)
		{
			throw new ARSError(\sprintf("ARS API communications error; please check common.username, common.password, common.token, common.arsapiurl and your network status.\ncURL error %s. %s\n", $errno, $error));
		}

		return $raw;
	}

	/**
	 * Do a POST with PHP's native HTTP stream wrappers.
	 *
	 * @param   string  $url       The URL to POST data to
	 * @param   array   $postData  The POST body data
	 *
	 * @return  null|string
	 */
	private function postWithPhp(string $url, array $postData): ?string
	{
		$headers = [
			'Content-type' => 'application/x-www-form-urlencoded',
		];

		// Do I need to use FOF API Token Authentication instead?
		if (!empty($this->apiToken))
		{
			// Remove the legacy FOF Transparent Authentication header
			unset ($postData['_fofauthentication']);

			$headers = array_merge($headers, [
				'Authentication' => sprintf("Bearer %s", $this->apiToken),
				'X-FOF-Token'    => $this->apiToken,
			]);
		}

		$streamOptions = [
			'http' => [
				'header'           => \implode("\r\n", \array_map(function ($key, $value) {
					return \sprintf("%s: %s", $key, $value);
				}, \array_keys($headers), $headers)),
				'method'           => 'POST',
				'user_agent'       => 'AkeebaReleaseMaker/2.0',
				'content'          => \http_build_query($postData),
				'follow_location'  => 1,
				'protocol_version' => '1.1',
				'timeout'          => 30.0,
				'ignore_errors'    => true,
			],
			'ssl'  => [
				'verify_peer'       => true,
				'allow_self_signed' => false,
				'cafile'            => Configuration::getInstance()->api->CACertPath,
				'verify_depth'      => 8,
			],
		];

		$context  = stream_context_create($streamOptions);
		$result   = @file_get_contents($url, false, $context);
		$headers  = $this->getParsedHeaders($http_response_header ?? []);
		$httpCode = $headers['HTTP_RESPONSE_CODE'] ?? 200;

		if ($httpCode === 403)
		{
			throw new ARSError(\sprintf("access denied; please check common.username, common.password, common.token, common.arsapiurl and your network status.\nHTTP error %s\n", $httpCode));
		}

		if ($httpCode === 500 && !empty($result))
		{
			$errorMessage = $this->getParsedARSError($result);
		}

		if (empty($errorMessage) && ($result === false) && ($httpCode === 200))
		{
			$errorMessage = 'The request probably timed out (we got no result)';
		}

		if (empty($errorMessage) && ($result === false))
		{
			$errorMessage = $errorMessage ?? sprintf('HTTP status %d', $httpCode);
		}

		if (!empty($errorMessage))
		{
			throw new ARSError(\sprintf('ARS API communications error: %s', $errorMessage));
		}

		return $result;
	}

	/**
	 * Parse PHP's $http_response_header superglobal data.
	 *
	 * IMPORTANT! $http_response_header is only available right after the file_get_contents() call and only in the
	 * code block context that initiated the stream wrapper access. This means we can't use it directly in this method.
	 * We need to be passed its data as a parameter. DO NOT ATTEMPT TO REFACTOR THIS!
	 *
	 * @param   array  $httpResponseHeader  The data from PHP's $http_response_header
	 *
	 * @return  array  The parsed headers.
	 */
	private function getParsedHeaders(array $httpResponseHeader): array
	{
		$headers = [];

		foreach ($httpResponseHeader as $k => $v)
		{
			$parts = explode(':', $v, 2);

			if (isset($parts[1]))
			{
				$headers[trim($parts[0])] = trim($parts[1]);

				continue;
			}

			$headers[] = $v;

			if (preg_match("#HTTP/[0-9.]+\s+([0-9]+)#", $v, $out))
			{
				$headers['HTTP_RESPONSE_CODE'] = (int) ($out[1]);
			}
		}

		return $headers;
	}

	/**
	 * Extracts the ARS error messages from the ARS error JSON response
	 *
	 * @param   string  $rawResponse  The raw, JSON-encoded ARS error response
	 *
	 * @return  string|null The human readable error. NULL if no message is detected.
	 */
	private function getParsedARSError(string $rawResponse): ?string
	{
		$parsedResponse = @\json_decode($rawResponse, true);

		if (!\is_array($parsedResponse) || (\count($parsedResponse) === 0))
		{
			return null;
		}

		if ($parsedResponse['success'] ?? true)
		{
			return null;
		}

		$message = $parsedResponse['data'] ?? $parsedResponse['message'] ?? \implode("\n", $parsedResponse['messages'] ?? []);

		if (empty(\trim($message)))
		{
			return null;
		}

		return $message;
	}
}
