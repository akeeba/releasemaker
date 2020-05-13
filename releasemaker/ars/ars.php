<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

/**
 * Akeeba Release System API integration for Akeeba Release Maker
 */
class ArmArs
{
	/** @var string The hostname of the site where ARS is installed, without the index.php */
	private $host = null;

	/** @var string The username we're going to use to connect to the host */
	private $username = null;

	/** @var string The password we're going to use to connect to the host */
	private $password = null;

	/** @var string The API Token we're going to use to connect to the host (if username and password are empty) */
	private $apiToken = null;

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
	 * Perform an ARS API call using the $postData provided
	 *
	 * @param   array  $postData  POST variables to send to ARS
	 */
	public function doApiCall(array $postData = [])
	{
		$arsData  = [
			'option'             => 'com_ars',
			'_fofauthentication' => json_encode([
				'username' => $this->username,
				'password' => $this->password,
			]),
		];

		$postData = array_merge($postData, $arsData);

		$url = rtrim($this->host, '/') . '/index.php';

		$ch = curl_init($url);

		// Do I need to use FOF API Token Authentication instead?
		if (empty($this->username) && !empty($this->apiToken))
		{
			// Remove the legacy FOF Transparent Authentication header
			unset ($postData['_fofauthentication']);

			// Alternatively I could do $postData['_fofToken'] = $this->apiToken;
			curl_setopt($ch, CURLOPT_HTTPHEADER, [
				'Authentication: Bearer ' . $this->apiToken
			]);
		}

		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
		curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt($ch, CURLOPT_FAILONERROR, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($ch, CURLOPT_TIMEOUT, 180);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (iPhone; U; CPU iPhone OS 4_3_3 like Mac OS X; en-us) AppleWebKit/533.17.9 (KHTML, like Gecko) Version/5.0.2 Mobile/8J2 Safari/6533.18.5');

		$raw = curl_exec($ch);

		$errno = curl_errno($ch);
		$error = curl_error($ch);
		curl_close($ch);

		if ($raw === false)
		{
			$comboURL = $url . http_build_query($postData);
			echo "\n\n\n$comboURL\n\n\n";

			if (($errno == 22) && strstr($error, ': 403'))
			{
				echo 'ARS API communications error; please check common.username, common.password, common.token, common.arsapiurl and your network status.' . "\ncURL error $errno. $error\n";

				return json_encode(false);
			}
			else
			{
				throw new Exception('ARS API communications error; please check common.username, common.password, common.token, common.arsapiurl and your network status.' . "\ncURL error $errno. $error\n");
			}
		}

		return $raw;
	}

	/**
	 * Get the data object of a release
	 *
	 * @param   integer  $category  The category ID inside which the version is expected
	 * @param   string   $version   The version tag
	 *
	 * @return  stdClass  The data object of the release
	 */
	public function getRelease($category, $version)
	{
		$arsData = [
			'view'            => 'releases',
			'task'            => 'browse',
			'category'        => $category,
			'version[method]' => 'exact',
			'version[value]'  => $version,
			'format'          => 'json',
		];

		$response = $this->doApiCall($arsData);

		$response = json_decode($response);

		if (empty($response))
		{
			$response = (object) [
				'id'               => null,
				'category_id'      => $category,
				'version'          => $version,
				'alias'            => null,
				'maturity'         => 'stable',
				'description'      => null,
				'notes'            => null,
				'groups'           => null,
				'hits'             => 0,
				'created'          => 0,
				'created_by'       => '0000-00-00 00:00:00',
				'modified'         => 0,
				'modified_by'      => '0000-00-00 00:00:00',
				'checked_out'      => 0,
				'checked_out_time' => '0000-00-00 00:00:00',
				'ordering'         => 0,
				'access'           => 1,
				'published'        => 0,
				'language'         => '*',
			];
		}
		else
		{
			$response = array_shift($response);
		}

		return $response;
	}

	/**
	 * Save the changes to an existing release or create a new release
	 *
	 * @param   array  $releaseData  The release data to save. At the bare minimum we need an ID and a title!
	 *
	 * @return  bool  As returned by ARS' JSON API
	 */
	public function saveRelease(array $releaseData)
	{
		$arsData = [
			'view'   => 'releases',
			'task'   => 'save',
			'format' => 'json',
		];

		foreach (['groups'] as $key)
		{
			if (empty($releaseData[$key]))
			{
				$releaseData[$key] = '';
			}

			if (is_array($releaseData[$key]))
			{
				$releaseData[$key] = array_map(function ($x) {
					return trim($x);
				}, $releaseData[$key]);
				$releaseData[$key] = implode(',', $releaseData[$key]);
			}
		}

		$arsData = array_merge($releaseData, $arsData);

		$response = $this->doApiCall($arsData);

		return $response;
	}

	/**
	 * Get the data object of an item
	 *
	 * @param   integer  $release    The release ID inside which the item is expected
	 * @param   string   $type       The item type, one of 'file' or 'link'
	 * @param   string   $fileOrURL  The relative path or absolute URL of the item
	 *
	 * @return  stdClass  The data object of the item
	 */
	public function getItem($release, $type, $fileOrURL)
	{
		$key = ($type == 'file') ? 'filename' : 'url';

		$arsData = [
			'view'    => 'items',
			'task'    => 'browse',
			'release' => $release,
			'type'    => $type,
			$key      => $fileOrURL,
			'format'  => 'json',
		];

		$response = $this->doApiCall($arsData);

		$response = json_decode($response);

		if (empty($response))
		{
			$response = (object) [
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
				'groups'           => '',
				'hits'             => 0,
				'created'          => 0,
				'created_by'       => '0000-00-00 00:00:00',
				'modified'         => 0,
				'modified_by'      => '0000-00-00 00:00:00',
				'checked_out'      => 0,
				'checked_out_time' => '0000-00-00 00:00:00',
				'ordering'         => 0,
				'access'           => 1,
				'published'        => 0,
				'language'         => '*',
				'environments'     => null,
			];
		}
		else
		{
			$response = array_shift($response);
		}

		return $response;
	}

	/**
	 * Save the changes to an existing item or create a new item
	 *
	 * @param   array  $itemData  The item data to save.
	 *
	 * @return  bool  As returned by ARS' JSON API
	 */
	public function saveItem(array $itemData)
	{
		$arsData = [
			'view'      => 'items',
			'task'      => 'save',
			'format'    => 'json',
			'returnurl' => base64_encode('index.php'),
		];

		foreach (['groups', 'environments'] as $key)
		{
			if (empty($itemData[$key]))
			{
				$itemData[$key] = '';
			}

			if (is_array($itemData[$key]))
			{
				$itemData[$key] = array_map(function ($x) {
					return trim($x);
				}, $itemData[$key]);
				$itemData[$key] = implode(',', $itemData[$key]);
			}
		}

		$arsData = array_merge($itemData, $arsData);

		$response = $this->doApiCall($arsData);

		return $response;
	}

}
