<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Contracts;

use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Interface for step classes
 */
interface StepInterface
{
	public function __construct(SymfonyStyle $io);

	/**
	 * Execute this step
	 */
	public function execute(): void;
}
