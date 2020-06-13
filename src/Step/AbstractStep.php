<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Step;


use Symfony\Component\Console\Style\SymfonyStyle;

abstract class AbstractStep implements StepInterface
{
	protected $io;

	public function __construct(SymfonyStyle $io)
	{
		$this->io     = $io;
	}
}