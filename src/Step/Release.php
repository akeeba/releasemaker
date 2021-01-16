<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Step;

use Akeeba\ReleaseMaker\Configuration\Configuration;
use Akeeba\ReleaseMaker\Exception\ARSError;
use Akeeba\ReleaseMaker\Mixin\ARSConnectorAware;

class Release extends AbstractStep
{
	use ARSConnectorAware;

	public function execute(): void
	{
		$this->io->section("Creating or updating release");

		$this->io->writeln("<info>Checking release status</info>");

		$this->initARSConnector();

		$release = $this->getRelease();

		if (is_null($release->id))
		{
			$this->io->text("Creating release");
		}
		else
		{
			$this->io->text(sprintf("Updating release %u", $release->id));
		}

		$this->editRelease($release);

		$this->io->newLine();
	}

	private function editRelease(object &$release): void
	{
		$conf = Configuration::getInstance();

		$release->notes     = $conf->release->releaseNotes;
		$release->published = 0;

		if (is_null($release->alias))
		{
			$release->alias = $this->toSlug(str_replace('.', '-', $conf->release->version));
		}

		// Description is only present in old ARS versions...
		if (property_exists($release, 'description'))
		{
			$release->description = sprintf("<p>Version %s</p>", $conf->release->version);
		}

		if (empty($release->notes))
		{
			$release->notes = "<p>No notes for this release</p>";
		}

		if (!empty($releaseAccess))
		{
			$release->access = $releaseAccess;
		}

		$release->maturity = $this->getMaturity($conf->release->version);

		try
		{
			$this->arsConnector->saveRelease((array) $release);
		}
		catch (\Exception $e)
		{
			throw new ARSError('Failed to create or update the release', $e);
		}

		$conf->volatile->release = $release;
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

	private function toSlug(string $value): string
	{
		//remove any '-' from the string they will be used as concatonater
		$value = str_replace('-', ' ', $value);

		//convert to ascii characters
		$value = $this->toASCII($value);

		//lowercase and trim
		$value = trim(strtolower($value));

		//remove any duplicate whitespace, and ensure all characters are alphanumeric
		$value = preg_replace(['/\s+/', '/[^A-Za-z0-9\-]/'], ['-', ''], $value);

		//limit length
		if (strlen($value) > 100)
		{
			$value = substr($value, 0, 100);
		}

		return $value;
	}

	private function toASCII(string $value): string
	{
		$string = htmlentities(utf8_decode($value));

		return preg_replace(
			['/&szlig;/', '/&(..)lig;/', '/&([aouAOU])uml;/', '/&(.)[^;]*;/'],
			['ss', '$1', '$1e', '$1'],
			$string);
	}
}
