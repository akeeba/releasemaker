<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Mixin;

use Akeeba\ReleaseMaker\Configuration\Configuration;
use Akeeba\ReleaseMaker\Deployment\ARS;

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
			'host'     => $armConfig->api->endpoint,
			'username' => $armConfig->api->username,
			'password' => $armConfig->api->password,
			'apiToken' => $armConfig->api->token,
		]);
	}

	/**
	 * Get the ARS release object for the version defined by common.version and common.category
	 *
	 * @return  object
	 */
	protected function getRelease(): object
	{
		$conf     = Configuration::getInstance();
		$category = $conf->release->category;
		$version  = $conf->release->version;

		return $this->arsConnector->getRelease($category, $version);
	}
}