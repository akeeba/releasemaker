<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Step\Mixin;


use Akeeba\ReleaseMaker\Configuration;
use Akeeba\ReleaseMaker\Utils\ARS;

trait ARSConnectorAware
{
	/**
	 * The ARS connector class
	 *
	 * @var ARS
	 */
	protected $arsConnector;

	/**
	 * Initialise an ARS connector object
	 *
	 * @return  void
	 */
	protected function initARSConnector(): void
	{
		$armConfig = Configuration::getInstance();

		$this->arsConnector = new ARS([
			'host'     => $armConfig->get('common.arsapiurl', ''),
			'username' => $armConfig->get('common.username', ''),
			'password' => $armConfig->get('common.password', ''),
			'apiToken' => $armConfig->get('common.token', ''),
		]);
	}

}