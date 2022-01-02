<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Configuration\Section;


use Akeeba\ReleaseMaker\Configuration\Configuration;
use Akeeba\ReleaseMaker\Configuration\Update\Source;
use Akeeba\ReleaseMaker\Contracts\ConfigurationSection;
use Akeeba\ReleaseMaker\Mixin\MagicGetterAware;

/**
 * Update sources configuration section
 *
 * @property-read Source[] $sources Update sources
 *
 * @since  2.0.0
 */
final class Updates implements ConfigurationSection
{
	use MagicGetterAware;

	private array $sources = [];

	public function __construct(array $configuration, Configuration $parent)
	{
		$this->sources = array_map(function ($source) use ($parent) {
			return new Source($source, $parent);
		}, $configuration);
	}
}