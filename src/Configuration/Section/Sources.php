<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Configuration\Section;


use Akeeba\ReleaseMaker\Configuration\Source\File;

/**
 * File sources configuration section
 *
 * @property-read File[] $sources File sources
 *
 * @since  2.0.0
 */
class Sources
{
	private array $sources = [];

	public function __construct(array $configuration)
	{
		$this->sources = array_map(function ($source) {
			return new File($source);
		}, $configuration);
	}
}