<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Configuration;


use Akeeba\ReleaseMaker\Contracts\ConfigurationParser;
use Akeeba\ReleaseMaker\Contracts\ExceptionCode;
use Akeeba\ReleaseMaker\Exception\ConfigurationError;

class Parser implements ConfigurationParser
{
	private static $parsers = [];

	public function isParsable(string $sourcePath): bool
	{
		/** @var ConfigurationParser $parser */
		foreach ($this->getParsers() as $parser)
		{
			$supportedExtensions   = $this->getExtensions($parser);
			$hasSupportedExtension = call_user_func(function (string $filePath) use ($supportedExtensions) {
				foreach ($supportedExtensions as $extension)
				{
					if (substr($filePath, -strlen($extension) - 1) === ('.' . $extension))
					{
						return true;
					}
				}

				return false;
			}, $sourcePath);

			if (!empty($supportedExtensions) && !$hasSupportedExtension)
			{
				continue;
			}

			if ($parser->isParsable($sourcePath))
			{
				return true;
			}
		}

		return false;
	}

	public function parseFile(string $sourcePath): array
	{
		/** @var ConfigurationParser $parser */
		foreach ($this->getParsers() as $parser)
		{
			$supportedExtensions   = $this->getExtensions($parser);
			$hasSupportedExtension = call_user_func(function (string $filePath) use ($supportedExtensions) {
				foreach ($supportedExtensions as $extension)
				{
					if (substr($filePath, -strlen($extension) - 1) === ('.' . $extension))
					{
						return true;
					}
				}

				return false;
			}, $sourcePath);

			if (!empty($supportedExtensions) && !$hasSupportedExtension)
			{
				continue;
			}

			if (!$parser->isParsable($sourcePath))
			{
				continue;
			}

			return $parser->parseFile($sourcePath);
		}

		throw new ConfigurationError(sprintf("Cannot find a suitable parser for configuration file %s", $sourcePath), ExceptionCode::CONFIG_GENERIC_ERROR);
	}

	private function getParsers(): array
	{
		// Return cached parsers
		if (!empty(self::$parsers))
		{
			return self::$parsers;
		}

		// Initialise
		self::$parsers = [];

		// Load a sorted list of parsers
		/** @var \DirectoryIterator $file */
		foreach ((new \DirectoryIterator(__DIR__ . '/Parser')) as $file)
		{
			if (!$file->isFile() || !$file->isReadable() || ($file->getExtension() != 'php'))
			{
				continue;
			}

			$className = __NAMESPACE__ . '\\Parser\\' . $file->getBasename('.php');

			if (!class_exists($className))
			{
				continue;
			}

			try
			{
				$refClass   = new \ReflectionClass($className);
				$docComment = $refClass->getDocComment();
			}
			catch (\ReflectionException $e)
			{
				continue;
			}

			if ($docComment === false)
			{
				continue;
			}

			$priority = $this->parsePriority($docComment);

			if (empty($priority))
			{
				continue;
			}

			$parser = new $className;

			if (!($parser instanceof ConfigurationParser))
			{
				continue;
			}

			self::$parsers[$priority] = $parser;
		}

		return self::$parsers;
	}

	private function parsePriority(string $docComment): ?int
	{
		$docComment = trim($docComment);

		if (empty($docComment))
		{
			return null;
		}

		$hasMatches = preg_match('/@priority\s+(\d+)/', $docComment, $matches);

		if ($hasMatches === false)
		{
			return null;
		}

		return (int) $matches[1];
	}

	private function getExtensions(ConfigurationParser $parser): array
	{
		try
		{
			$refClass   = new \ReflectionClass($parser);
			$docComment = $refClass->getDocComment();
		}
		catch (\ReflectionException $e)
		{
			return [];
		}

		if ($docComment === false)
		{
			return [];
		}

		$hasMatches = preg_match('/\s*\*\s+@extension\s+(.*)$/m', $docComment, $matches);

		if ($hasMatches === false)
		{
			return [];
		}

		$extensions = array_map('trim', explode(',', $matches[1]));

		return array_unique($extensions);
	}
}