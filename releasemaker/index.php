<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

// DEBUG
ini_set('display_errors', true);
error_reporting(E_ALL & E_DEPRECATED & E_STRICT);

define('AKEEBAENGINE', 1);

// Display banner
$year = date('Y');

echo <<< ENDBANNER
Akeeba Release Maker
================================================================================
An automated script to upload and release a new version of an Akeeba component.
Copyright (c)2006-$year Nicholas K. Dionysopoulos / Akeeba Ltd
This is Free Software distributed under the terms of the GNU GPL v3 or later.
See LICENSE.txt for more information.


ENDBANNER;

// Load our class autoloader
require_once __DIR__ . '/autoloader.php';

// Laod the Composer autoloader
if (!file_exists(__DIR__ . '/vendor/autoload.php'))
{
	echo <<< OOPS

ERROR! You have not initialised Composer

Before using Akeeba Release Maker you need to go into its directory and run:

cd releasemaker
composer install

Then this message will go away and Akeeba Release Maker will run properly.

OOPS;

	exit(2);

}

require_once __DIR__ . '/vendor/autoload.php';

// Get the path to the configuration file
$json_file = $argv[count($argv) - 1];

if (strtolower(substr($json_file, -5)) != '.json')
{
	$json_file = null;
}

if (!@file_exists($json_file))
{
	$json_file = __DIR__ . '/' . $json_file;
}

if (!@file_exists($json_file))
{
	$json_file = null;
}

if (empty($json_file) || !@file_exists($json_file) || !@is_readable($json_file))
{
	echo "Configuration file not found.";

	exit(10);
}

try
{
	// Load the configuration
	$config = ArmConfiguration::getInstance();
	$config->loadFile($json_file);

	// Set up the cacert.pem location
	global $cacertPemFilePointer;
	$cacertPemFilePointer = null;

	$caCertPemFile = $config->getCustomCacertPem();
	define('AKEEBA_CACERT_PEM', $caCertPemFile);

	// Set up the steps to process
	$defaultSteps = ['prepare', 'deploy', 'release', 'items', 'publish', 'updates'];
	$steps        = $config->get('common.steps', $defaultSteps);
	$steps        = empty($steps) ? $defaultSteps : $steps;

	// Run each and every step
	foreach ($steps as $step)
	{
		$stepClass  = 'ArmStep' . ucfirst($step);
		$stepObject = new $stepClass;
		$stepObject->execute();
	}
}
catch (Throwable $exc)
{
	echo PHP_EOL . PHP_EOL;
	echo str_repeat('*', 79) . PHP_EOL;
	echo "*** E R R O R\n";
	echo str_repeat('*', 79) . PHP_EOL . PHP_EOL;
	echo $exc->getMessage() . PHP_EOL . PHP_EOL;
	echo $exc->getFile() . '::L' . $exc->getLine() . PHP_EOL . PHP_EOL;
	echo $exc->getTraceAsString();
	echo PHP_EOL . PHP_EOL;

	exit(127);
}