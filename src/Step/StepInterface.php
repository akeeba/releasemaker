<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Step;

use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Interace for step classes
 */
interface StepInterface
{
	public function __construct(SymfonyStyle $io);

	/**
	 * Execute this step
	 */
	public function execute(): void;
}
