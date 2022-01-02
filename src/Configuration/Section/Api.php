<?php

/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Configuration\Section;


use Akeeba\ReleaseMaker\Configuration\Configuration;
use Akeeba\ReleaseMaker\Contracts\ConfigurationSection;
use Akeeba\ReleaseMaker\Contracts\ExceptionCode;
use Akeeba\ReleaseMaker\Exception\ARSEndpointIsNotAURL;
use Akeeba\ReleaseMaker\Exception\ConfigurationError;
use Akeeba\ReleaseMaker\Exception\InvalidARSJoomlaConfiguration;
use Akeeba\ReleaseMaker\Exception\NoArsAuthenticationPossible;
use Akeeba\ReleaseMaker\Exception\NoARSEndpoint;
use Akeeba\ReleaseMaker\Mixin\MagicGetterAware;
use Composer\CaBundle\CaBundle;
use InvalidArgumentException;

/**
 * Release configuration section.
 *
 * @property-read  string      $type           Connector type: 'fof' or 'joomla'
 * @property-read  string      $endpoint       URL for the ARS API endpoint
 * @property-read  string      $connector      Connector type ('php' or 'curl')
 * @property-read  null|string $username       Super User username
 * @property-read  null|string $password       Super User password
 * @property-read  null|string $token          FOF Token
 * @property-read  null|string $CACertPath     Absolute path to the custom cacert.pem file
 */
final class Api implements ConfigurationSection
{
	use MagicGetterAware;

	private string $type;

	private ?string $endpoint;

	private ?string $connector;

	private ?string $username;

	private ?string $password;

	private ?string $token;

	private ?string $CACertPath;

	/**
	 * @var resource
	 */
	private $cacertPemFilePointer;

	public function __construct(array $configuration, Configuration $parent)
	{
		$this->setType($configuration['type'] ?? 'fof');
		$this->setEndpoint($configuration['endpoint'] ?? null);

		try
		{
			$this->setConnector($configuration['connector'] ?? 'php');
		}
		catch (InvalidArgumentException $e)
		{
			throw new ConfigurationError($e->getMessage(), ExceptionCode::CONFIG_GENERIC_ERROR, $e);
		}

		$this->setCACertPath($configuration['cacert'] ?? null);

		if (!empty($this->CACertPath) && !defined('AKEEBA_CACERT_PEM'))
		{
			define('AKEEBA_CACERT_PEM', $this->CACertPath);
		}

		$this->username = $configuration['username'] ?? null;
		$this->password = $configuration['password'] ?? null;
		$this->token    = $configuration['token'] ?? null;

		if ((empty($this->username) || empty($this->password)) && empty($this->token))
		{
			throw new NoArsAuthenticationPossible();
		}
		elseif (!empty($this->token))
		{
			$this->username = null;
			$this->password = null;
		}
		elseif ($this->type == 'joomla')
		{
			// No token but 'joomla' API connection type selected. I'm afraid this won't fly.
			throw new InvalidARSJoomlaConfiguration();
		}
		else
		{
			$this->token = null;
		}
	}

	/** @noinspection PhpUnusedParameterInspection */

	/**
	 * @param   string|null  $type
	 */
	public function setType(?string $type): void
	{
		$valid = ['fof', 'joomla'];
		$type  = strtolower($type);

		$this->type = in_array($type, $valid) ? $type : 'fof';
	}

	private function setEndpoint(?string $url): void
	{
		// We always need an endpoint URL
		if (empty($url))
		{
			throw new NoARSEndpoint();
		}

		$url = trim($url);

		if (!filter_var($url, FILTER_VALIDATE_URL))
		{
			throw new ARSEndpointIsNotAURL();
		}

		$url = \rtrim($url, '/');
		$url .= (\substr($url, -4) === '.php') ? '' : '/index.php';

		$this->endpoint = $url;
	}

	private function setConnector(string $connector): void
	{
		$connector = trim(strtolower($connector));

		if (!in_array($connector, ['php', 'curl']))
		{
			throw new InvalidArgumentException(sprintf("Invalid connector ‘%s’. It must be one of ‘php’, ‘curl’.", $connector), ExceptionCode::CONFIG_GENERIC_ERROR);
		}

		$this->connector = $connector;
	}

	private function setCACertPath(?string $filePath)
	{
		// No custom file? Nothing more to do.
		if (empty($filePath))
		{
			return;
		}

		// Make sure the specified file exists and is readable.
		if (!@is_file($filePath) || !@is_readable($filePath))
		{
			return;
		}

		// Try to load the custom file's contents.
		$customContents = @\file_get_contents($filePath);

		if (empty($customContents))
		{
			return;
		}

		// Get the default cacert.pem file's contents so we can merge them with our custom file.
		$defaultContents = @file_get_contents(CaBundle::getBundledCaBundlePath());
		$defaultContents = $defaultContents ?: '';

		// Let's use the tmpfile trick. The file will removed once the $CACertPath property goes out of scope.
		$this->cacertPemFilePointer = \tmpfile();
		$cacertPemFile              = \stream_get_meta_data($this->cacertPemFilePointer)['uri'];

		// Combine the original cacert.pem with the provided certificate / certificate storage.
		\fwrite($this->cacertPemFilePointer, $defaultContents . "\n\n" . $customContents);

		// Set the property to our merged file.
		$this->CACertPath = $cacertPemFile;
	}

	private function getCACertPath(): string
	{
		return empty($this->CACertPath) ? (CaBundle::getBundledCaBundlePath()) : $this->CACertPath;
	}
}