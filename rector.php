<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

declare(strict_types=1);

use Rector\Core\Configuration\Option;
use Rector\Set\ValueObject\SetList;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
	// Required to prevent early death for classes references the S3 library
	if (!defined('AKEEBAENGINE'))
	{
		define('AKEEBAENGINE', 1);
	}

	// get parameters
	$parameters = $containerConfigurator->parameters();

	// Use PHP 7.2 features
	$parameters->set(Option::PHP_VERSION_FEATURES, '7.2');

	// Paths to include
	$parameters->set(Option::PATHS, [
		__DIR__ . '/src',
	]);

	/**
	 * Paths to exclude.
	 */
	$parameters->set(Option::EXCLUDE_PATHS, [
		__DIR__ . '/src/config.json',
		__DIR__ . '/composer.json',
		__DIR__ . '/composer.lock',
		__DIR__ . '/releasemaker/*',
		__DIR__ . '/tmp/*',
		__DIR__ . '/vendor/*',
	]);

	/**
	 * Rectors to exclude.
	 */
	$parameters->set(Option::EXCLUDE_RECTORS, [
		// This Rector seems to die consistently in Rector 0.8
		\Rector\CodeQuality\Rector\Isset_\IssetOnPropertyObjectToPropertyExistsRector::class,

		// WATCH OUT! This does crazy things, like convert $ret['ErrorException'] to $ret[\ErrorException::class] which
		// is unfortunate and messes everything up.
		//\Rector\Php55\Rector\String_\StringClassNameToClassConstantRector::class,

		//\Rector\Php56\Rector\FunctionLike\AddDefaultValueForUndefinedVariableRector::class,
	]);

	// Define what rule sets will be applied
	$parameters->set(Option::SETS, [
		SetList::EARLY_RETURN,
		SetList::DEAD_CODE,
		SetList::PHP_52,
		SetList::PHP_53,
		SetList::PHP_54,
		SetList::PHP_55,
		SetList::PHP_56,
		SetList::PHP_70,
		SetList::PHP_71,
		SetList::PHP_72,
		SetList::PSR_4,
		SetList::PRIVATIZATION,
		SetList::CODING_STYLE,
		SetList::CODE_QUALITY,
		SetList::CODE_QUALITY_STRICT,
		SetList::PERFORMANCE,
		SetList::UNWRAP_COMPAT,

		// Only valid in Rector 0.9
		// SetList::DEAD_DOC_BLOCK,
	]);

	// get services (needed for register a single rule)
	// $services = $containerConfigurator->services();

	// register a single rule
	// $services->set(TypedPropertyRector::class);
};
