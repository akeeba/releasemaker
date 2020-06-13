<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Step;

use Akeeba\ReleaseMaker\Configuration;
use Akeeba\ReleaseMaker\Utils\ARS;
use Akeeba\ReleaseMaker\Utils\StringHelper;

class Release implements StepInterface
{
	/** @var  ARS  ARS API connector */
	private $arsConnector = null;

	private $release = null;

	public function execute(): void
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

	private function checkRelease(): void
	{
		$conf = Configuration::getInstance();

		$this->arsConnector = new ARS([
			'host'     => $conf->get('common.arsapiurl', ''),
			'username' => $conf->get('common.username', ''),
			'password' => $conf->get('common.password', ''),
			'apiToken' => $conf->get('common.token', ''),
		]);

		$category = $conf->get('common.category', 0);
		$version  = $conf->get('common.version', 0);

		$this->release = $this->arsConnector->getRelease($category, $version);
	}

	private function editRelease(): void
	{
		$conf = Configuration::getInstance();

		$version       = $conf->get('common.version', 0);
		$releaseAccess = $conf->get('common.releaseaccess', 1);

		$this->release->description = $this->readFile('DESCRIPTION.html');
		$this->release->notes       = $this->readFile('RELEASENOTES.html');
		$this->release->notes       .= $this->changelogAsHtml();
		$this->release->published   = 0;

		if (is_null($this->release->alias))
		{
			$this->release->alias = StringHelper::toSlug(str_replace('.', '-', $version));
		}

		if (empty($this->release->description))
		{
			$this->release->description = "<p>Version $version</p>";
		}

		if (empty($this->release->notes))
		{
			$this->release->notes = "<p>No notes for this release</p>";
		}

		if (!empty($releaseAccess))
		{
			$this->release->access = $releaseAccess;
		}

		$this->release->maturity = $this->getMaturity($version);

		$result = $this->arsConnector->saveRelease((array) $this->release);
	}

	private function readFile(string $filename): ?string
	{
		$conf = Configuration::getInstance();
		$path = $conf->get('common.repodir', '');
		$ret  = @file_get_contents($path . '/' . $filename);

		if ($ret === false)
		{
			return null;
		}

		return $ret;
	}

	private function getMaturity(string $version): string
	{
		$version      = strtolower($version);
		$versionParts = explode('.', $version);
		$lastPart     = array_pop($versionParts);

		if (substr($lastPart, 0, 1) == 'a')
		{
			return 'alpha';
		}

		if (substr($lastPart, 0, 1) == 'b')
		{
			return 'beta';
		}

		if (substr($lastPart, 0, 2) == 'rc')
		{
			return 'rc';
		}

		return 'stable';
	}

	private function changelogAsHtml(): string
	{
		$conf      = Configuration::getInstance();
		$filename  = rtrim($conf->get('common.repodir', ''), '/') . '/CHANGELOG';
		$changelog = @file($filename);

		if (!is_array($changelog) || (count($changelog) == 0))
		{
			return "";
		}

		// Remove the first line, it's the PHP die() statement
		array_shift($changelog);

		// Remove the next two lines, they are the version banner
		array_shift($changelog);
		array_shift($changelog);

		// Loop until you find a blank line
		$thisChangelog = [];

		foreach ($changelog as $line)
		{
			$line = trim($line);

			if (empty($line))
			{
				break;
			}

			$thisChangelog[] = $line;
		}

		if (empty($thisChangelog))
		{
			return '';
		}

		// Sort the array
		asort($thisChangelog);

		// Pick lines by type
		$sorted = [
			'security' => [],
			'bugfix'   => [],
			'language' => [],
			'new'      => [],
			'change'   => [],
			'misc'     => [],
			'removed'  => [],
			'critical' => [],
		];

		foreach ($thisChangelog as $line)
		{
			[$type, $text] = explode(' ', $line, 2);

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
