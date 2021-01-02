<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Command;

use Akeeba\ReleaseMaker\Configuration;
use Akeeba\ReleaseMaker\Exception\FatalProblem;
use Akeeba\ReleaseMaker\Step\StepInterface;
use http\Exception\InvalidArgumentException;
use LogicException;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\OutputStyle;
use Symfony\Component\Console\Style\SymfonyStyle;

class Release
{
	public function __invoke(string $json, bool $debug, OutputInterface $output)
	{
		// Enable debug mode if necessary
		if ($debug)
		{
			ini_set('display_errors', true);
			error_reporting(E_ALL & E_DEPRECATED & E_STRICT);
		}

		// Create a Symfony styled output handler
		$input = new ArgvInput();
		$io    = new SymfonyStyle($input, $output);

		// Display the banner if necessary
		$this->banner($io);

		// Load the configuration
		$jsonFile = $this->getJsonPath($json, $io);
		$config   = Configuration::getInstance();
		$config->loadFile($jsonFile);

		// Set up the cacert.pem location
		global $cacertPemFilePointer;

		$cacertPemFilePointer = null;
		$caCertPemFile        = $config->getCustomCacertPem();

		define('AKEEBA_CACERT_PEM', $caCertPemFile);

		// Set up the steps to process
		$defaultSteps = ['prepare', 'deploy', 'release', 'items', 'publish', 'updates'];
		$steps        = $config->get('common.steps', $defaultSteps);
		$steps        = empty($steps) ? $defaultSteps : $steps;

		// Make sure all steps exist
		foreach ($steps as $step)
		{
			$stepClass = '\\Akeeba\\ReleaseMaker\\Step\\' . ucfirst($step);

			if (!class_exists($stepClass))
			{
				throw new InvalidArgumentException(sprintf("Class %s does not exist.", $stepClass), 91);
			}

			if (!class_implements($stepClass, StepInterface::class))
			{
				throw new LogicException(sprintf("Class %s does not implement StepInterface.", $stepClass), 91);
			}
		}

		// Run each and every step in the order specified
		foreach ($steps as $step)
		{
			$stepClass = '\\Akeeba\\ReleaseMaker\\Step\\' . ucfirst($step);
			/** @var StepInterface $stepObject */
			$stepObject = new $stepClass($io);
			$stepObject->execute();
		}
	}

	/**
	 * Shows the application banner
	 *
	 * @param   OutputStyle  $io
	 */
	private function banner(OutputStyle $io)
	{
		$io->writeln('<info>Akeeba Release Maker</info>');
		$io->writeln(sprintf('<info>%s</info>', str_repeat('=', 80)));
		$io->writeln('<info>An automated script to upload and release a new version of an Akeeba component.</info>');
		$io->writeln(sprintf('<info>Copyright (c)2012-%s Nicholas K. Dionysopoulos / Akeeba Ltd</info>', date('Y')));
		$io->writeln('<info>This is Free Software distributed under the terms of the GNU GPL v3 or later.</info>');
		$io->writeln('<info>See LICENSE.txt for more information.</info>');
		$io->newLine(1);
	}

	/**
	 * Get the absolute path to the JSON file. Errors out if the file doesn't exist.
	 *
	 * @param   string        $json
	 * @param   SymfonyStyle  $io
	 */
	private function getJsonPath(string $json, SymfonyStyle $io): string
	{
		if (strtolower(substr($json, -5)) != '.json')
		{
			$json = null;
		}

		if (!@file_exists($json))
		{
			$candidates = [
				$json = getcwd() . '/' . $json,
				$json = __DIR__ . '/' . $json,
			];

			foreach ($candidates as $file)
			{
				if (!file_exists($file))
				{
					continue;
				}

				$json = $file;

				break;
			}
		}

		if (!@file_exists($json))
		{
			$json = null;
		}

		if (empty($json) || !@file_exists($json) || !@is_readable($json))
		{
			$io->error("Configuration file not found.");

			throw new FatalProblem("Configuration file not found.", 10);
		}

		return $json;
	}
}