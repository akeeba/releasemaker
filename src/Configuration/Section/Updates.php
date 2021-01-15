<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Configuration\Section;


use Akeeba\ReleaseMaker\Configuration\Update\Source;

/**
 * Update sources configuration section
 *
 * @property-read Source[] $sources Update sources
 *
 * @since  2.0.0
 */
class Updates
{
	private array $sources = [];

	public function __construct(array $configuration)
	{
		$this->sources = array_map(function ($source) {
			return new Source($source);
		}, $configuration);
	}
}