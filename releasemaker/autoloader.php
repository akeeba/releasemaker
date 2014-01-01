<?php
/**
 * Akeeba Release Maker
 * An automated script to upload and release a new version of an Akeeba component.
 * Copyright ©2012-2014 Nicholas K. Dionysopoulos / Akeeba Ltd.
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

function _arm_autoloader($class_name) {
	static $armPath = null;

	// Make sure the class has an Arm prefix
	if(substr($class_name,0,3) != 'Arm') return;

	if(is_null($armPath)) {
		$armPath = __DIR__;
	}

	// Remove the prefix
	$class = substr($class_name, 3);

	// Change from camel cased (e.g. ViewHtml) into a lowercase array (e.g. 'view','html')
	$class = preg_replace('/(\s)+/', '_', $class);
	$class = strtolower(preg_replace('/(?<=\\w)([A-Z])/', '_\\1', $class));
	$class = explode('_', $class);

	// First try finding in structured directory format (preferred)
	$path = $armPath . '/' . implode('/', $class) . '.php';
	if(@file_exists($path)) {
		include_once $path;
	}

	// Then try the duplicate last name structured directory format (not recommended)
	if(!class_exists($class_name, false)) {
		$lastPart = array_pop($class);
		array_push($class, $lastPart);
		$path = $armPath . '/' . implode('/', $class) . '/' . $lastPart . '.php';
		if(@file_exists($path)) {
			include_once $path;
		}
	}

	// If it still fails, try looking in the legacy folder
	if(!class_exists($class_name, false)) {
		$path = $armPath . '/legacy/' . implode('/', $class) . '.php';
		if(@file_exists($path)) {
			include_once $path;
		}
	}

	// If that failed, try the legacy flat directory structure
	if(!class_exists($class_name, false)) {
		$path = $armPath . '/' . implode('.', $class) . '.php';
		if(@file_exists($path)) {
			include_once $path;
		}
	}
}

if(function_exists('__autoload')) spl_autoload_register('__autoload');
spl_autoload_register('_arm_autoloader');