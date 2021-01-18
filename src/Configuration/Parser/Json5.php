<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Configuration\Parser;

use Akeeba\ReleaseMaker\Contracts\ConfigurationParser;
use ColinODell\Json5\Json5Decoder;

/**
 * Modern JSON5 configuration file parser
 *
 * @since     2.0.0
 * @priority  10
 * @extension json5, json
 */
class Json5 implements ConfigurationParser
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
			$test = Json5Decoder::decode($content, true);
		}
		catch (\JsonException $e)
		{
			// Um... this is invalid JSON / JSON5.
			return false;
		}

		// Do I have a legacy format file?
		if (isset($test['common.version']) && isset($test['common.arsapiurl']))
		{
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
			throw new \InvalidArgumentException(sprintf('Configuration file %s cannot be parsed as an Akeeba Release System JSON5 file.', $sourcePath));
		}

		$content = @file_get_contents($sourcePath);
		$raw     = Json5Decoder::decode($content, true);
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