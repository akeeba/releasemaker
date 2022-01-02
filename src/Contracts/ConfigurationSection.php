<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Contracts;


use Akeeba\ReleaseMaker\Configuration\Configuration;

interface ConfigurationSection
{
	public function __construct(array $configuration, Configuration $parent);
}