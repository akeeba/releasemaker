<?php
/**
 * Akeeba Release Maker
 * An automated script to upload and release a new version of an Akeeba component.
 * Copyright ©2012-2017 Nicholas K. Dionysopoulos / Akeeba Ltd.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

// DEBUG
ini_set('display_errors', true);
error_reporting(E_ALL & E_DEPRECATED & E_STRICT);

// Setup path to cacert.pem
define('AKEEBA_CACERT_PEM', __DIR__ . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'cacert.pem');
define('AKEEBAENGINE', 1);

// Display banner
echo <<< ENDBANNER
Akeeba Release Maker
================================================================================
An automated script to upload and release a new version of an Akeeba component.
Copyright ©2012-2017 Nicholas K. Dionysopoulos / Akeeba Ltd.
This is Free Software distributed under the terms of the GNU GPL v3 or later.
See LICENSE.txt for more information.


ENDBANNER;

// Load the class autoloader
require_once __DIR__.'/autoloader.php';

// Laod the Composer autoloader
if (!file_exists(__DIR__.'/vendor/autoload.php'))
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

require_once __DIR__.'/vendor/autoload.php';

// Get the path to the configuration file
end($argv);
$json_file = current($argv);
if (strtolower(substr($json_file, -5)) != '.json')
{
	$json_file = null;
}
if (!file_exists($json_file))
{
	if (file_exists(__DIR__ . '/' . $json_file))
	{
		$json_file = __DIR__ . '/' . $json_file;
	}
	else
	{
		$json_file = null;
	}
}

// Load the configuration
require_once __DIR__.'/configuration/configuration.php';
$config = ArmConfiguration::getInstance();
if (file_exists($json_file))
{
	$config->loadFile($json_file);
}
elseif (file_exists(__DIR__.'/config.json')) {
	$config->loadFile(__DIR__.'/config.json');
}
else
{
	die("Configuration file not found.");
}
$config->postProcess();

// Set up the steps to process
$steps = array(
	'prepare',
	'deploy',
	'release',
	'items',
	'publish',
	'updates'
);

foreach($steps as $step) {
	$stepClass = 'ArmStep'.ucfirst($step);
	$stepObject = new $stepClass;
	try {
		$stepObject->execute();
	} catch (Exception $exc) {
		echo PHP_EOL.PHP_EOL;
		echo str_repeat('*', 79) . PHP_EOL;
		echo "*** E R R O R\n";
		echo str_repeat('*', 79) . PHP_EOL . PHP_EOL;
		echo $exc->getMessage() . PHP_EOL . PHP_EOL;
		echo $exc->getTraceAsString();
		die(PHP_EOL . PHP_EOL);
	}
}