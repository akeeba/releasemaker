<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Configuration\Parser;

use Akeeba\ReleaseMaker\Contracts\ConfigurationParser;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml as YamlParser;

/**
 * Modern YAML configuration file parser
 *
 * @since     2.0.0
 * @priority  50
 * @extension yaml, yml
 */
class Yaml implements ConfigurationParser
{
	public function isParsable(string $sourcePath): bool
	{
		// The file must exist and be readable
		if (!@is_file($sourcePath) || !@is_readable($sourcePath))
		{
			return false;
		}

		// I'll try to parse the file to inspect its contents.
		$content = @file_get_contents($sourcePath);

		try
		{
			$test = YamlParser::parse($content);
		}
		catch (ParseException $e)
		{
			// Um... this is invalid YAML.
			return false;
		}

		// Do I have the necessary keys and are they the correct type?
		if (
			!is_array($test['release'] ?? null) ||
			!is_array($test['api'] ?? null) ||
			!is_array($test['connections'] ?? null) ||
			!is_array($test['files'] ?? null)
		)
		{
			return false;
		}

		return true;
	}

	public function parseFile(string $sourcePath): array
	{
		if (!$this->isParsable($sourcePath))
		{
			throw new \InvalidArgumentException(sprintf('Configuration file %s cannot be parsed as an Akeeba Release System YAML file.', $sourcePath));
		}

		$content = @file_get_contents($sourcePath);
		$raw     = YamlParser::parse($content);
		$config  = [
			'release'     => [],
			'api'         => [],
			'steps'       => [],
			'connections' => [],
			'updates'     => [],
			'files'       => [],
		];

		foreach (array_keys($config) as $section)
		{
			$sectionConfig    = $raw[$section] ?? [];
			$config[$section] = is_array($sectionConfig) ? $sectionConfig : [];
		}

		return $config;
	}
}