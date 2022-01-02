<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Configuration\Section;

use Akeeba\ReleaseMaker\Configuration\Configuration;
use Akeeba\ReleaseMaker\Contracts\ConfigurationSection;
use Akeeba\ReleaseMaker\Contracts\StepInterface;
use Akeeba\ReleaseMaker\Exception\InvalidStep;
use Akeeba\ReleaseMaker\Mixin\MagicGetterAware;
use Akeeba\ReleaseMaker\Step\Deploy;
use Akeeba\ReleaseMaker\Step\Items;
use Akeeba\ReleaseMaker\Step\Prepare;
use Akeeba\ReleaseMaker\Step\Publish;
use Akeeba\ReleaseMaker\Step\Release as ReleaseStep;
use Akeeba\ReleaseMaker\Step\Updates;

/**
 * Steps configuration section.
 *
 * @property-read  string[] $steps Class names of the steps to follow during the release process
 */
final class Steps implements ConfigurationSection
{
	use MagicGetterAware;

	private array $steps = [];

	/** @noinspection PhpUnusedParameterInspection */
	public function __construct(array $configuration, Configuration $parent)
	{
		$this->setSteps($configuration ?? []);
	}

	private function setSteps(array $steps)
	{
		if (empty($steps))
		{
			$this->steps = [
				Prepare::class,
				Deploy::class,
				ReleaseStep::class,
				Items::class,
				Publish::class,
				Updates::class,
			];

			return;
		}

		$this->steps = array_map(function (string $step) {
			if (class_exists($step))
			{
				return $step;
			}

			$className = '\\Akeeba\\ReleaseMaker\\Step\\' . ucfirst($step);

			if (!class_exists($className) || !in_array(StepInterface::class, class_implements($className)))
			{
				throw new InvalidStep($step);
			}

			return $className;
		}, $steps);
	}
}