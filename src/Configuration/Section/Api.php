<?php

/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Configuration\Section;


use Akeeba\ReleaseMaker\Contracts\ExceptionCode;
use Akeeba\ReleaseMaker\Exception\ARSEndpointIsNotAURL;
use Akeeba\ReleaseMaker\Exception\ConfigurationError;
use Akeeba\ReleaseMaker\Exception\NoArsAuthenticationPossible;
use Akeeba\ReleaseMaker\Exception\NoARSEndpoint;
use Akeeba\ReleaseMaker\Mixin\MagicGetterAware;
use InvalidArgumentException;

/**
 * Release configuration section.
 *
 * @property-read  string      $endpoint       URL for the ARS API endpoint
 * @property-read  string      $connector      Connector type ('php' or 'curl')
 * @property-read  null|string $username       Super User username
 * @property-read  null|string $password       Super User password
 * @property-read  null|string $token          FOF Token
 * @property-read  null|string $CACertPath     Absolute path to the custom cacert.pem file
 */
final class Api
{
	use MagicGetterAware;

	private ?string $endpoint;

	private ?string $connector;

	private ?string $username;

	private ?string $password;

	private ?string $token;

	private ?string $CACertPath;

	public function __construct(array $configuration)
	{
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
		else
		{
			$this->token = null;
		}
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
		$customContents  = @\file_get_contents($filePath);

		if (empty($customContents))
		{
			return;
		}

		// Get the default cacert.pem file's contents so we can merge them with our custom file.
		$defaultContents = @file_get_contents(__DIR__ . '/../../cacert.pem');
		$defaultContents = $defaultContents ?: '';

		// Let's use the tmpfile trick. The file will removed once the $CACertPath property goes out of scope.
		$cacertPemFilePointer = \tmpfile();
		$cacertPemFile        = \stream_get_meta_data($cacertPemFilePointer)['uri'];

		// Combine the original cacert.pem with the provided certificate / certificate storage.
		\fwrite($cacertPemFilePointer, $defaultContents . "\n\n" . $customContents);

		// Set the property to our merged file.
		$this->CACertPath = $cacertPemFile;
	}

	private function getCACertPath(): string
	{
		return empty($this->CACertPath) ? (__DIR__ . '/../../cacert.pem') : $this->CACertPath;
	}
}