<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

// Has the user run composer install already?
use Akeeba\ReleaseMaker\Command\Release;
use Akeeba\ReleaseMaker\Exception\ExitCodeSettingException;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Style\SymfonyStyle;

// Required for the S3 library
define('AKEEBAENGINE', 1);

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
			'json'    => 'JSON configuration file for the release. Default: src/config.json (will error out)',
			'--debug' => 'Enable debug mode',
		]
	);

$app->setDefaultCommand('release', true);
$app->setCatchExceptions(false);

try
{
	$app->run();
}
catch (Throwable $e) {
	$exitCode = ($e instanceof ExitCodeSettingException) ? $e->getCode() : 255;

	$input  = new ArgvInput();
	$output = new ConsoleOutput(ConsoleOutput::VERBOSITY_DEBUG);
	$io     = new SymfonyStyle($input, $output);

	// Always show detailed errors when XDebug is enabled
	$isXDebug = function_exists('xdebug_is_enabled') && xdebug_is_enabled();

	if (!defined('ARM_DEBUG') && !$isXDebug)
	{
		$io->error($e->getMessage());
	}
	else
	{
		$io->error([
			sprintf('Exception of type %s', get_class($e)),
			sprintf('Error code: %d', $e->getCode()),
			$e->getMessage(),
		]);

		$io->section('Debug trace');
		$io->text(sprintf('#000 %s(%d)', $e->getFile(), $e->getLine()));
		$io->text($e->getTraceAsString());
	}

	exit($exitCode);
}