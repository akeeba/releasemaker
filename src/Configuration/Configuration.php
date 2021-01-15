<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Configuration;


use Akeeba\ReleaseMaker\Configuration\Section\Api;
use Akeeba\ReleaseMaker\Configuration\Section\Connection;
use Akeeba\ReleaseMaker\Configuration\Section\Release;
use Akeeba\ReleaseMaker\Configuration\Section\Sources;
use Akeeba\ReleaseMaker\Configuration\Section\Steps;
use Akeeba\ReleaseMaker\Configuration\Section\Updates;
use Akeeba\ReleaseMaker\Mixin\MagicGetterAware;
use LogicException;

/**
 * Akeeba Release Maker configuration
 *
 * @property-read Release    $release    Release configuration
 * @property-read Api        $api        ARS API connection configuration
 * @property-read Steps      $steps      Release Maker steps configuration
 * @property-read Connection $connection Connections configuration, for uploading to remote servers
 * @property-read Updates    $updates    Configuration for publishing updates to a remote server
 * @property-read Sources    $sources    File sources configuration
 *
 * @since  2.0.0
 */
final class Configuration
{
	use MagicGetterAware;

	private Release $release;

	private Api $api;

	private Steps $steps;

	private Connection $connection;

	private Updates $updates;

	private Sources $sources;

	private static $instance = null;

	final private function __construct(array $configuration)
	{
		$this->release    = new Release($configuration['release'] ?? []);
		$this->api        = new Api($configuration['api'] ?? []);
		$this->steps      = new Steps($configuration['steps'] ?? []);
		$this->connection = new Connection($configuration['connection'] ?? []);
		$this->updates    = new Updates($configuration['updates'] ?? []);
		$this->sources    = new Sources($configuration['sources'] ?? []);
	}

	final public static function getInstance(?array $configuration = null): self
	{
		if (!is_null(self::$instance))
		{
			return self::$instance;
		}

		if (is_null($configuration))
		{
			throw new LogicException("Release Maker configuration has not been initialised yet.");
		}

		self::$instance = new self($configuration);

		return self::$instance;
	}
}