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

class ArmStepRelease implements ArmStepInterface
{
	/** @var  ArmArs  ARS API connector */
	private $arsConnector = null;

	private $release = null;

	public function execute()
	{
		echo "CREATING OR UPDATING RELEASE\n";
		echo str_repeat('-', 79) . PHP_EOL;

		echo "\tChecking release status\n";
		$this->checkRelease();

		if (is_null($this->release->id))
		{
			echo "\tCreating release\n";
		}
		else
		{
			echo "\tUpdating release\n";
		}
		$this->editRelease();

		echo PHP_EOL;
	}

	private function checkRelease()
	{
		$conf = ArmConfiguration::getInstance();

		$this->arsConnector = new ArmArs(array(
			'host'		=> $conf->get('common.arsapiurl', ''),
			'username'	=> $conf->get('common.username', ''),
			'password'	=> $conf->get('common.password', ''),
		));

		$category	= $conf->get('common.category', 0);
		$version	= $conf->get('common.version', 0);

		$this->release = $this->arsConnector->getRelease($category, $version);
	}

	private function editRelease()
	{
		$conf = ArmConfiguration::getInstance();

		$category	= $conf->get('common.category', 0);
		$version	= $conf->get('common.version', 0);
		$releaseDir	= $conf->get('common.releasedir', '');
		$releaseGroups = $conf->get('common.releasegroups', '');
		$releaseAccess = $conf->get('common.releaseaccess', 1);

		$this->release->description = $this->readFile('DESCRIPTION.html');
		$this->release->notes = $this->readFile('RELEASENOTES.html');
		$this->release->notes .= $this->readChangelog();
		$this->release->published = 0;
		if (is_null($this->release->alias))
		{
			$this->release->alias = ArmUtilsString::toSlug(str_replace('.', '-', $version));
		}
		if (empty($this->release->description))
		{
			$this->release->description = "<p>Version $version</p>";
		}
		if (empty($this->release->notes))
		{
			$this->release->notes = "<p>No notes for this release</p>";
		}
		if (empty($this->release->groups))
		{
			$this->release->groups = $releaseGroups;
		}
		if (!empty($releaseAccess))
		{
			$this->release->access = $releaseAccess;
		}

		$this->release->maturity = $this->getMaturity($version);

		$result = $this->arsConnector->saveRelease((array) $this->release);
	}

	private function readFile($filename)
	{
		$conf = ArmConfiguration::getInstance();

		$path = $conf->get('common.repodir', '');

		if (file_exists($path.'/'.$filename))
		{
			return @file_get_contents($path.'/'.$filename);
		}
		else
		{
			return null;
		}
	}

	private function getMaturity($version)
	{
		$version = strtolower($version);
		$versionParts = explode('.', $version);
		$lastPart = array_pop($versionParts);

		if (substr($lastPart, 0, 1) == 'a')
		{
			return 'alpha';
		}
		elseif (substr($lastPart, 0, 1) == 'b')
		{
			return 'beta';
		}
		elseif (substr($lastPart, 0, 2) == 'rc')
		{
			return 'rc';
		}
		else
		{
			return 'stable';
		}
	}

	private function readChangelog()
	{
		$conf = ArmConfiguration::getInstance();

		$filename = rtrim($conf->get('common.repodir', ''), '/') . '/CHANGELOG';

		$changelog = file($filename);

		// Remove the first line, it's the PHP die() statement
		array_shift($changelog);

		// Remove the next two lines, they are the version banner
		array_shift($changelog);
		array_shift($changelog);

		// Loop until you find a blank line
		$thisChangelog = array();
		foreach ($changelog as $line)
		{
			$line = trim($line);
			if (!empty($line))
			{
				$thisChangelog[] = $line;
			}
			else
			{
				break;
			}
		}

		if (empty($thisChangelog))
		{
			return '';
		}

		// Sort the array
		asort($thisChangelog);

		// Pick lines by type
		$sorted = array(
			'security'	=> array(),
			'bugfix'	=> array(),
			'language'	=> array(),
			'new'		=> array(),
			'change'	=> array(),
			'misc'		=> array(),
			'removed'	=> array(),
			'critical'	=> array(),
		);
		foreach($thisChangelog as $line)
		{
			list($type, $text) = explode(' ', $line, 2);
			switch ($type)
			{
				case '*':
					$sorted['security'][] = $text;
					break;
				case '#':
					$sorted['bugfix'][] = $text;
					break;
				case '$':
					$sorted['language'][] = $text;
					break;
				case '+':
					$sorted['new'][] = $text;
					break;
				case '^':
					$sorted['change'][] = $text;
					break;
				case '~':
					$sorted['misc'][] = $text;
					break;
				case '-':
					$sorted['removed'][] = $text;
					break;
				case '!':
				case '!!':
					$sorted['critical'][] = $text;
					break;
			}
		}

		// Format the changelog
		$htmlChangelog = "<h3>Changelog</h3>\n";
		foreach ($sorted as $area => $lines)
		{
			if (empty($lines))
			{
				continue;
			}

			switch ($area)
			{
				case 'security':
					$title = 'Security fixes';
					break;
				case 'bugfix':
					$title = 'Bug fixes';
					break;
				case 'language':
					$title = 'Language fixes or changes';
					break;
				case 'new':
					$title = 'New features';
					break;
				case 'change':
					$title = 'Changes';
					break;
				case 'misc':
					$title = 'Miscellaneous changes';
					break;
				case 'removed':
					$title = 'Removed features';
					break;
				case 'critical':
					$title = 'Critical bugs and important changes';
					break;
			}
			$htmlChangelog .= "<h4>$title</h4>\n<ul>\n";

			foreach ($lines as $line)
			{
				$htmlChangelog .= "\t<li>" . htmlspecialchars($line, ENT_COMPAT, 'UTF-8') . "</li>\n";
			}

			$htmlChangelog .= "</ul>\n";
		}

		return $htmlChangelog;
	}
}