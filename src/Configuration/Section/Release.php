<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Configuration\Section;


use Akeeba\ReleaseMaker\Configuration\Configuration;
use Akeeba\ReleaseMaker\Contracts\ConfigurationSection;
use Akeeba\ReleaseMaker\Contracts\ExceptionCode;
use Akeeba\ReleaseMaker\Exception\ConfigurationError;
use Akeeba\ReleaseMaker\Exception\InvalidStep;
use Akeeba\ReleaseMaker\Exception\NoCategory;
use Akeeba\ReleaseMaker\Exception\NoVersion;
use Akeeba\ReleaseMaker\Mixin\MagicGetterAware;
use DateTime;
use Exception;
use League\CommonMark\GithubFlavoredMarkdownConverter;

/**
 * Release configuration section.
 *
 * @property-read  string      $version                Version being released.
 * @property-read  DateTime    $date                   Date and time of the release.
 * @property-read  int         $category               ARS category identifier.
 * @property-read  int         $access                 Joomla view access level.
 * @property-read  null|string $releaseNotesFile       Absolute filesystem path to the release notes file.
 * @property-read  null|string $changelog              Absolute filesystem path to the changelog file.
 * @property-read  string      $releaseNotes           HTML content for the release notes. Constructed as needed.
 */
final class Release implements ConfigurationSection
{
	use MagicGetterAware;

	private ?string $version;

	private DateTime $date;

	private ?int $category;

	private int $access = 1;

	private ?string $releaseNotesFile = null;

	private ?string $changelog = null;

	private ?string $releaseNotes = null;

	/** @noinspection PhpUnusedParameterInspection */
	public function __construct(array $configuration, Configuration $parent)
	{
		$this->version = $configuration['version'] ?? null;

		$this->setDate($configuration['date'] ?? 'now');

		$this->category         = (int) ($configuration['category'] ?? 0);
		$this->access           = (int) ($configuration['access'] ?? 1);
		$this->releaseNotesFile = $configuration['release_notes'] ?? null;
		$this->changelog        = $configuration['changelog'] ?? null;

		// Make sure the release notes file exists and is readable. Otherwise don't try to use it.
		if (!empty($this->releaseNotesFile) || !is_file($this->releaseNotesFile) || !is_readable($this->releaseNotesFile))
		{
			$releaseNotesFile = null;
		}

		// Make sure the changelog file exists and is readable. Otherwise don't try to use it.
		if (!empty($this->changelog) || !is_file($this->changelog) || !is_readable($this->changelog))
		{
			$releaseNotesFile = null;
		}

		// We always need a version
		if (empty($this->version))
		{
			throw new NoVersion();
		}

		if (!is_numeric($this->category) || ($this->category <= 0))
		{
			throw new NoCategory();
		}
	}

	private function setDate(string $dateString): void
	{
		try
		{
			$timeZone = new \DateTimeZone('GMT');

			// If the date is numeric assume a unix timestamp and convert it.
			if (is_numeric($dateString))
			{
				date_default_timezone_set('UTC');
				$dateString = date('c', $dateString);
			}

			$this->date = new DateTime($dateString, $timeZone);

		}
		catch (Exception $e)
		{
			throw new ConfigurationError(sprintf("Invalid release date ‘%s’.", $dateString), ExceptionCode::CONFIG_GENERIC_ERROR, $e);
		}
	}

	/**
	 * Return the release notes as HTML, creating them from the release notes and CHANGELOG files if applicable.
	 *
	 * @return string
	 * @since  2.0.0
	 *
	 * @noinspection PhpUnusedPrivateMethodInspection
	 */
	private function getReleaseNotes(): string
	{
		// Returned cached result, if present.
		if (!is_null($this->releaseNotes))
		{
			return $this->releaseNotes;
		}

		/**
		 * Create new release notes with the contents of the release note file and the parsed changelog file, if either
		 * file was specified. If no file is specified we get an empty string.
		 */
		$releaseNotes =
			$this->parseReleaseNotesFile($this->releaseNotesFile) .
			$this->parseChangelog($this->changelog);

		return $releaseNotes;
	}

	private function parseReleaseNotesFile(?string $filePath): string
	{
		// Make sure the file exists.
		if (empty($filePath) || !is_file($filePath) || !is_readable($filePath))
		{
			return '';
		}

		// Get the file contents. If we fail to read it (WHY?!) we assume it's totally empty.
		$fileContents = @file_get_contents($filePath) ?: '';

		// Get the base name of the file.
		$baseName = basename($filePath);

		if (empty($baseName))
		{
			// No idea how this happens. But, hey, I can't rule anything out!
			return $fileContents;
		}

		// Try to find the extension.
		$lastDot = strrpos($baseName, '.');

		// If the file has no extension we assume HTML content.
		if ($lastDot === false)
		{
			return $fileContents;
		}

		$extension = strtolower(substr($baseName, $lastDot + 1));

		// Anything other than .md is assumed to be HTML content.
		if ($extension !== 'md')
		{
			return $fileContents;
		}

		// Okay. We assume we have GitHub Flavoured Markdown.
		$converter = new GithubFlavoredMarkdownConverter();

		return $converter->convertToHtml($fileContents);
	}

	private function parseChangelog(?string $filePath): string
	{
		// Make sure the file exists.
		if (empty($filePath) || !is_file($filePath) || !is_readable($filePath))
		{
			return '';
		}

		$changelog = @\file($filePath);

		if (!\is_array($changelog) || (\count($changelog) == 0))
		{
			return '';
		}

		// Remove the first line. It's either the version banner or a PHP die statement.
		$firstLine = \array_shift($changelog);
		$hasDie = strpos($firstLine, '<?php') !== false;

		/**
		 * Remove the second line. If we had a die() statement it's the version banner. Otherwise it is a separator OR
		 * the start of the latest version's changelog. Keep it so we can examine.
		 */
		$secondLine = \array_shift($changelog);

		/**
		 * If the file had a die() statement at the very top the second line is definitely the version banner. The THIRD
		 * line is the separator OR the start of the latest version's changelog. So let's get THIS one to examine.
		 */

		if ($hasDie)
		{
			$secondLine = \array_shift($changelog);
		}

		/**
		 * If the line below the banner doesn't look like a separator consisting of at least three consecutive -, =, –,
		 * —, ~, or _ characters we will add it back to what we have to process.
		 */
		if (
			(strpos($secondLine, '---') === false) && (strpos($secondLine, '===') === false) &&
			(strpos($secondLine, '–––') === false) && (strpos($secondLine, '———') === false) &&
			(strpos($secondLine, '~~~') === false) && (strpos($secondLine, '___') === false)
		) {
			array_unshift($changelog, $secondLine);
		}

		// Loop until you find a blank line
		$thisChangelog = [];
		$haveNonEmptyLine = false;

		foreach ($changelog as $line)
		{
			$line = \trim($line);

			if (empty($line))
			{
				/**
				 * I have already processed non-empty lines. Therefore the empty line is our separator from the previous
				 * version. In this case I'm done processing.
				 */
				if ($haveNonEmptyLine)
				{
					break;
				}

				/**
				 * I haven't seen a non-empty line yet. Ignore this line, it's a line between the version banner or
				 * separator and the actual CHANGELOG content.
				 */
				continue;
			}

			// Yup. I found a non-empty line! Add it to the CHANGELOG lines to be processed.
			$haveNonEmptyLine = true;
			$thisChangelog[] = $line;
		}

		if (empty($thisChangelog))
		{
			return '';
		}

		// Sort the array
		\asort($thisChangelog);

		// Pick lines by type
		$sorted = [
			'security' => [],
			'critical' => [],
			'new'      => [],
			'removed'  => [],
			'change'   => [],
			'bugfix'   => [],
			'language' => [],
			'misc'     => [],
		];

		foreach ($thisChangelog as $line)
		{
			[$type, $text] = \explode(' ', $line, 2);

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

			$htmlChangelog .= \sprintf("<h4>%s</h4>\n<ul>\n", $title);

			foreach ($lines as $line)
			{
				$htmlChangelog .= "\t<li>" . \htmlspecialchars($line, ENT_COMPAT, 'UTF-8') . "</li>\n";
			}

			$htmlChangelog .= "</ul>\n";
		}

		return $htmlChangelog;
	}

}