<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Configuration\Section;


use Akeeba\ReleaseMaker\Configuration\Configuration;
use Akeeba\ReleaseMaker\Configuration\Source\File;
use Akeeba\ReleaseMaker\Contracts\ConfigurationSection;
use Akeeba\ReleaseMaker\Mixin\MagicGetterAware;

/**
 * File sources configuration section
 *
 * @property-read File[] $sources File sources
 *
 * @since  2.0.0
 */
final class Sources implements ConfigurationSection
{
	use MagicGetterAware;

	private array $sources = [];

	/** @noinspection PhpUnusedParameterInspection */
	public function __construct(array $configuration, Configuration $parent)
	{
		$this->sources = array_map(function ($source) use ($parent) {
			return new File($source, $parent);
		}, $configuration);
	}
}