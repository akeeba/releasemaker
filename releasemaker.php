<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

// Has the user run composer install already?
use Akeeba\ReleaseMaker\Command\Release;
use Akeeba\ReleaseMaker\Exception\FatalProblem;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Style\SymfonyStyle;

if (!is_file(__DIR__ . '/vendor/autoload.php'))
{
	echo "\n\n\n";
	echo "* * *  E R R O R  * * *\n\n";
	echo "Please run composer install before running this application for the first time.\n";
	echo "If unsure, please take a look at the README.md file. Thank you!\n";
	echo "\n\n\n";
}

// Load Composer's autoloader
$loader = require_once __DIR__ . '/vendor/autoload.php';

$app = new Silly\Application('Akeeba Release Maker');

$app
	->command('release [json] [--debug]', new Release())
	->descriptions(
		'Generates type hints for a specific Joomla! version or installed site',
		[
			'json'     => 'JSON configuration file for the release. Default: src/config.json (will error out)',
			'--debug'  => 'Enable debug mode',
		]
	);

$app->setDefaultCommand('release', true);

try
{
	$app->run();
}
catch (FatalProblem $e)
{
	exit($e->getCode());
}
catch (Throwable $e)
{
	$input  = new ArgvInput();
	$output = new ConsoleOutput(ConsoleOutput::VERBOSITY_DEBUG);
	$io     = new SymfonyStyle($input, $output);

	$io->error([
		sprintf('Unhandled Exception of type %s', get_class($e)),
		sprintf('Error code: %d', $e->getCode()),
		$e->getMessage(),
	]);

	$io->section('Debug trace');
	$io->text(sprintf('#000 %s(%d)', $e->getFile(), $e->getLine()));
	$io->text($e->getTraceAsString());

	exit(255);
}