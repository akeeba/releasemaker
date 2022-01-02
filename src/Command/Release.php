<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Command;

use Akeeba\ReleaseMaker\Configuration\Configuration;
use Akeeba\ReleaseMaker\Contracts\StepInterface;
use InvalidArgumentException;
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
			define('ARM_DEBUG', 1);
		}

		// Create a Symfony styled output handler
		$input = new ArgvInput();
		$io    = new SymfonyStyle($input, $output);

		// Display the banner if necessary
		$this->banner($io);

		// Load the configuration
		$config = Configuration::fromFile($json);

		// Make sure all steps exist
		foreach ($config->steps as $stepClass)
		{
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
		foreach ($config->steps->steps as $stepClass)
		{
			(new $stepClass($io))->execute();
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
}