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
use Akeeba\ReleaseMaker\Configuration\Section\Volatile;
use Akeeba\ReleaseMaker\Contracts\ExceptionCode;
use Akeeba\ReleaseMaker\Exception\ConfigurationError;
use Akeeba\ReleaseMaker\Mixin\MagicGetterAware;
use LogicException;

/**
 * Akeeba Release Maker configuration
 *
 * @property-read Release    $release     Release configuration
 * @property-read Api        $api         ARS API connection configuration
 * @property-read Steps      $steps       Release Maker steps configuration
 * @property-read Connection $connection  Connections configuration, for uploading to remote servers
 * @property-read Updates    $updates     Configuration for publishing updates to a remote server
 * @property-read Sources    $sources     File sources configuration
 * @property-read Volatile   $volatile    Volatile information shared between steps
 *
 * @since  2.0.0
 */
final class Configuration
{
	use MagicGetterAware;

	private static $instance = null;

	private Release $release;

	private Api $api;

	private Steps $steps;

	private Connection $connection;

	private Updates $updates;

	private Sources $sources;

	private Volatile $volatile;

	final private function __construct(array $configuration)
	{
		$this->release    = new Release($configuration['release'] ?? [], $this);
		$this->api        = new Api($configuration['api'] ?? [], $this);
		$this->steps      = new Steps($configuration['steps'] ?? [], $this);
		$this->connection = new Connection($configuration['connections'] ?? [], $this);
		$this->updates    = new Updates($configuration['updates'] ?? [], $this);
		$this->sources    = new Sources($configuration['files'] ?? [], $this);
		$this->volatile   = new Volatile([], $this);
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

	final public static function fromFile(string $configurationFile): self
	{
		$sourceFile = $configurationFile;

		if (!is_file($sourceFile) || !is_readable($sourceFile))
		{
			$sourceFile = __DIR__ . '/' . $configurationFile;
		}

		if (!is_file($sourceFile) || !is_readable($sourceFile))
		{
			$sourceFile = getcwd() . '/' . $configurationFile;
		}

		if (!is_file($sourceFile) || !is_readable($sourceFile))
		{
			throw new ConfigurationError(sprintf('Cannot locate configuration file %s', $configurationFile));
		}

		try
		{
			$parser        = new Parser();
			$configuration = $parser->parseFile($sourceFile);
		}
		catch (\Exception $e)
		{
			throw new ConfigurationError(sprintf("Cannot parse configuration file %s", $sourceFile), ExceptionCode::CONFIG_GENERIC_ERROR, $e);
		}

		return self::getInstance($configuration);
	}
}