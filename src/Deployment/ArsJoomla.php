<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Deployment;

use Akeeba\ReleaseMaker\Configuration\Configuration;
use Akeeba\ReleaseMaker\Exception\ARSError;
use function array_keys;
use function array_map;
use function array_shift;
use function count;
use function curl_close;
use function curl_errno;
use function curl_error;
use function curl_exec;
use function curl_init;
use function curl_setopt;
use function http_build_query;
use function implode;
use function is_array;
use function json_decode;
use function rtrim;
use function sprintf;
use function substr;
use function trim;

/**
 * Akeeba Release System API integration for Akeeba Release Maker
 */
class ArsJoomla implements ARSInterface
{
	/**
	 * The hostname of the site where ARS is installed, without the index.php
	 *
	 * @var string
	 */
	private $host;

	/**
	 * The API Token we're going to use to connect to the host
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

	public function getRelease(int $category, string $version): object
	{
		$path    = '/v1/ars/releases';
		$arsData = [
			'category_id' => $category,
			'search'      => 'version:' . $version,
		];

		$response = $this->doApiCall('GET', $path, $arsData);

		return $this->extractFirstItem($response) ?? (object) [
				'id'               => null,
				'category_id'      => $category,
				'version'          => $version,
				'alias'            => null,
				'maturity'         => 'stable',
				'description'      => null,
				'notes'            => null,
				'hits'             => 0,
				'created_by'       => null,
				'created'          => null,
				'modified_by'      => null,
				'modified'         => null,
				'checked_out'      => 0,
				'checked_out_time' => '0000-00-00 00:00:00',
				'ordering'         => 0,
				'access'           => 1,
				'published'        => 0,
				'language'         => '*',
			];
	}

	public function getReleaseById(int $release_id): object
	{
		$path    = '/v1/ars/releases/' . $release_id;
		$arsData = [];

		$response = $this->doApiCall('GET', $path, $arsData);

		return @json_decode($this->extractOnlyItem($response)) ?? (object) [
				'id'               => null,
				'category_id'      => null,
				'version'          => null,
				'alias'            => null,
				'maturity'         => 'stable',
				'description'      => null,
				'notes'            => null,
				'hits'             => 0,
				'created_by'       => null,
				'created'          => null,
				'modified_by'      => null,
				'modified'         => null,
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
		$path = '/v1/ars/releases';

		return $this->extractOnlyItem(
			$this->doApiCall('POST', $path, $releaseData)
		);
	}

	public function editRelease(array $releaseData)
	{
		$path = '/v1/ars/releases/' . (int) $releaseData['id'];

		return $this->extractOnlyItem(
			$this->doApiCall('PATCH', $path, $releaseData)
		);
	}

	public function getItem($release, $type, $fileOrURL)
	{
		$path = '/v1/ars/items';

		// Find the item by filename or URL
		$arsData = [
			'release_id' => $release,
			'search'     => $type . ":" . $fileOrURL,
		];

		$response = $this->doApiCall('GET', $path, $arsData);

		return $this->extractFirstItem($response) ?? (object) [
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
				'created_by'       => null,
				'created'          => null,
				'modified_by'      => null,
				'modified'         => null,
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
		$path = '/v1/ars/items';

		return $this->extractOnlyItem(
			$this->doApiCall('POST', $path, $itemData)
		);
	}

	public function editItem(array $itemData): string
	{
		$path = '/v1/ars/items/' . (int) $itemData['id'];

		return $this->extractOnlyItem(
			$this->doApiCall('PATCH', $path, $itemData)
		);
	}

	public function getItemById($item_id)
	{
		$path    = '/v1/ars/items/' . $item_id;
		$arsData = [];

		$response = $this->doApiCall('GET', $path, $arsData);

		return @json_decode($this->extractOnlyItem($response)) ?? (object) [
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
				'created_by'       => null,
				'created'          => null,
				'modified_by'      => null,
				'modified'         => null,
				'checked_out'      => 0,
				'checked_out_time' => '0000-00-00 00:00:00',
				'ordering'         => 0,
				'access'           => 1,
				'published'        => 0,
				'language'         => '*',
				'environments'     => null,
			];
	}

	private function extractFirstItem($response): ?object
	{
		if ($response === false)
		{
			return null;
		}

		$rawDocument = @json_decode($response, true);

		if (is_null($rawDocument))
		{
			return null;
		}

		$data = $rawDocument['data'] ?? [];

		if (empty($data) || !is_array($data))
		{
			return null;
		}

		$firstRow = array_shift($data);

		if (!is_array($firstRow) || !isset($firstRow['attributes']) || !is_array($firstRow['attributes']))
		{
			return null;
		}

		return (object) $firstRow['attributes'];
	}

	private function extractOnlyItem($response): string
	{
		if ($response === false)
		{
			return 'invalid';
		}

		$rawDocument = @json_decode($response, true);

		if (is_null($rawDocument))
		{
			return 'invalid';
		}

		$data = $rawDocument['data'] ?? [];

		if (empty($data) || !is_array($data) || !isset($data['attributes']) || !is_array($data['attributes']))
		{
			return 'invalid';
		}

		return json_encode($data['attributes']);
	}

	/**
	 * Perform an ARS API call using the $postData provided
	 *
	 * @param   string  $path      Relative path to the API endpoint
	 * @param   array   $postData  POST variables to send to ARS
	 *
	 * @return false|string
	 */
	private function doApiCall(string $method, string $path, array $postData = [])
	{
		$url = rtrim($this->host, '/');
		$url = (substr($url, -4) === '.php') ? $url : ($url . '/index.php');
		$url .= '/' . trim($path, '/');

		if (strpos($url, '/index.php') !== false && strpos($url, '/api/index.php') === false)
		{
			$url = str_replace('/index.php', '/api/index.php', $url);
		}

		$conf                = Configuration::getInstance();
		$communicationMethod = $conf->api->connector;

		switch ($communicationMethod)
		{
			case 'curl':
				return $this->requestWithCurl($url, $postData, $method) ?? false;

			case 'php':
			default:
				return $this->requestWithPhp($url, $postData, $method) ?? false;
		}
	}

	/**
	 * Perform a request with cURL.
	 *
	 * @param   string  $url       The URL to POST data to
	 * @param   array   $postData  The POST body data
	 *
	 * @return  null|string
	 */
	private function requestWithCurl(string $url, array $postData, string $method = 'POST'): ?string
	{
		if (strtoupper($method) === 'GET')
		{
			$url .= ((strpos($url, '?') === false) ? '?' : '&') . http_build_query($postData);
		}

		$ch = curl_init($url);

		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Authentication: Bearer ' . $this->apiToken,
			'X-Joomla-Token: ' . $this->apiToken,
			'Accept: application/vnd.api+json',
		]);

		switch (strtoupper($method))
		{
			case 'HEAD':
			case 'OPTIONS':
			case 'CONNECT':
				curl_setopt($ch, CURLOPT_NOBODY, 1);
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
				break;

			case 'GET':
				curl_setopt($ch, CURLOPT_HTTPGET, 1);
				break;

			case 'POST':
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
				/**
				 * The following line is unnecessary. Setting CURLOPT_POSTFIELDS implicitly sets CURLOPT_POST to 1.
				 * See https://curl.se/libcurl/c/CURLOPT_POSTFIELDS.html
				 */
				// \curl_setopt($ch, CURLOPT_POST, 1);
				break;

			case 'PUT':
			case 'PATCH':
			case 'DELETE':
			case 'TRACK':
			case 'TRACE':
			default:
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
				break;
		}

		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt($ch, CURLOPT_FAILONERROR, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CAINFO, Configuration::getInstance()->api->CACertPath);

		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_USERAGENT, 'AkeebaReleaseMaker/2.0');

		// In Debug mode I have cURL output everything that's going on to STDERR
		if (defined('ARM_DEBUG'))
		{
			curl_setopt($ch, CURLOPT_VERBOSE, true);
		}

		$raw = curl_exec($ch);

		$errno = curl_errno($ch);
		$error = curl_error($ch);
		curl_close($ch);

		if ($raw === false)
		{
			throw new ARSError(sprintf("ARS API communications error; please check common.username, common.password, common.token, common.arsapiurl and your network status.\ncURL error %s. %s\n", $errno, $error));
		}

		return $raw;
	}

	/**
	 * Perform a request with PHP's native HTTP stream wrappers.
	 *
	 * @param   string  $url       The URL to POST data to
	 * @param   array   $postData  The POST body data
	 *
	 * @return  null|string
	 */
	private function requestWithPhp(string $url, array $postData, string $method = 'POST'): ?string
	{
		$headers = [
			'Authentication' => 'Bearer ' . $this->apiToken,
			'X-Joomla-Token' => $this->apiToken,
			'Accept'         => 'application/vnd.api+json',
		];

		switch (strtoupper($method))
		{
			case 'HEAD':
			case 'OPTIONS':
			case 'CONNECT':
			case 'GET':
				$url      .= ((strpos($url, '?') === false) ? '?' : '&') . http_build_query($postData);
				$postData = null;
				break;

			case 'POST':
			case 'PUT':
			case 'PATCH':
			case 'DELETE':
			case 'TRACK':
			case 'TRACE':
			default:
				$postData = empty($postData) ? null : json_encode($postData);

				if (!empty($postData))
				{
					$headers['Content-Type'] = 'application/x-www-form-urlencoded';
				}
				break;
		}

		$streamOptions = [
			'http' => [
				'header'           => implode("\r\n", array_map(function ($key, $value) {
					return sprintf("%s: %s", $key, $value);
				}, array_keys($headers), $headers)),
				'method'           => strtoupper($method),
				'user_agent'       => 'AkeebaReleaseMaker/2.0',
				'content'          => $postData,
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

		if (empty($streamOptions['http']['content']))
		{
			unset($streamOptions['http']['content']);
		}

		$context  = stream_context_create($streamOptions);
		$result   = @file_get_contents($url, false, $context);
		$headers  = $this->getParsedHeaders($http_response_header ?? []);
		$httpCode = $headers['HTTP_RESPONSE_CODE'] ?? 200;

		if ($httpCode === 403)
		{
			throw new ARSError(sprintf("access denied; please check common.username, common.password, common.token, common.arsapiurl and your network status.\nHTTP error %s\n", $httpCode));
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
			throw new ARSError(sprintf('ARS API communications error: %s', $errorMessage));
		}

		try
		{
			$parsed = @json_decode($result, true, 512, JSON_THROW_ON_ERROR);

			if (!isset($parsed['errors']))
			{
				return $result;
			}

			$errorMessage = '';

			foreach ($parsed['errors'] as $error)
			{
				$errorMessage .= $error['title'] ?? '';
			}

			throw new ARSError(sprintf('ARS API communications error: %s', $errorMessage));
		}
		catch (\JsonException $e)
		{
			return $result;
		}
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
		$parsedResponse = @json_decode($rawResponse, true);

		if (!is_array($parsedResponse) || (count($parsedResponse) === 0))
		{
			return null;
		}

		if ($parsedResponse['success'] ?? true)
		{
			return null;
		}

		$message = $parsedResponse['data'] ?? $parsedResponse['message'] ?? implode("\n", $parsedResponse['messages'] ?? []);

		if (empty(trim($message)))
		{
			return null;
		}

		return $message;
	}
}
