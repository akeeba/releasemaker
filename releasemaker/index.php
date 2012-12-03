<?php
/**
 * Akeeba Release Maker
 * An automated script to upload and release a new version of an Akeeba component.
 * Copyright ©2012 Nicholas K. Dionysopoulos / Akeeba Ltd.
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

// Display banner
echo <<< ENDBANNER
Akeeba Release Maker
================================================================================
An automated script to upload and release a new version of an Akeeba component.
Copyright ©2012 Nicholas K. Dionysopoulos / Akeeba Ltd.
This is Free Software distributed under the terms of the GNU GPL v3 or later.
See LICENSE.txt for more information.


ENDBANNER;

// Load the class autoloader
require_once __DIR__.'/autoloader.php';

// Load the configuration
require_once __DIR__.'/configuration/configuration.php';
$config = ArmConfiguration::getInstance();
if(file_exists(__DIR__.'/config.json')) {
	$config->loadFile(__DIR__.'/config.json');
}
$config->postProcess();

// Set up the steps to process
$steps = array(
	'prepare', 'deploy', 'release', 'items', 'publish', 'updates'
);

foreach($steps as $step) {
	$stepClass = 'ArmStep'.ucfirst($step);
	$stepObject = new $stepClass;
	$stepObject->execute();
}