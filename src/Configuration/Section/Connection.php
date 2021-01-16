<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Configuration\Section;


use Akeeba\ReleaseMaker\Configuration\Configuration;
use Akeeba\ReleaseMaker\Configuration\Connection\Configuration as ConnectionConfiguration;
use Akeeba\ReleaseMaker\Contracts\ConfigurationSection;

/**
 * Connection configuration section
 *
 *
 * @since  2.0.0
 */
final class Connection implements ConfigurationSection
{
	private array $connections = [];

	/** @noinspection PhpUnusedParameterInspection */
	public function __construct(array $configuration, Configuration $parent)
	{
		foreach ($configuration as $key => $definition)
		{
			$this->connections[$key] = ConnectionConfiguration::factory($definition);
		}
	}

	public function getConnection(string $name): ConnectionConfiguration
	{
		if (!array_key_exists($name, $this->connections))
		{
			throw new \OutOfBoundsException(sprintf('Connection ‘%s’ not found in configuration.', $name));
		}

		return $this->connections[$name];
	}

	public function getConnectionKeys(): array
	{
		return array_keys($this->connections);
	}

	public function __get($name)
	{
		return $this->getConnection($name);
	}

	public function __isset($name)
	{
		return array_key_exists($name, $this->connections);
	}
}