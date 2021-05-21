<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker\Configuration\Parser;


use Akeeba\Engine\Postproc\Connector\S3v4\Acl;
use Akeeba\Engine\Postproc\Connector\S3v4\StorageClass;
use Akeeba\ReleaseMaker\Contracts\ConfigurationParser;

/**
 * Legacy JSON configuration file parser
 *
 * @since     2.0.0
 * @priority  90
 * @extension json
 */
class Legacy implements ConfigurationParser
{
	public function isParsable(string $sourcePath): bool
	{
		// The file must exist and be readable
		if (!@is_file($sourcePath) || !@is_readable($sourcePath))
		{
			return false;
		}

		// I'll try to parse the file to inspect its contents.
		$content = @file_get_contents($sourcePath);

		try
		{
			$test = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
		}
		catch (\JsonException $e)
		{
			// Um... this is invalid JSON.
			return false;
		}

		// Do I have a legacy format file?
		if (!isset($test['common.version']) && !isset($test['common.arsapiurl']))
		{
			return false;
		}

		return true;
	}

	public function parseFile(string $sourcePath): array
	{
		if (!$this->isParsable($sourcePath))
		{
			throw new \InvalidArgumentException(sprintf('Configuration file %s cannot be parsed as an Akeeba Release System Legacy JSON file.', $sourcePath));
		}

		// We'll load the legacy file and map it to the new format. Easy-peasy.
		$content = @file_get_contents($sourcePath);
		$raw     = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

		$releaseDir = rtrim($raw['common.releasedir'] ?? '/???', '/' . DIRECTORY_SEPARATOR);
		$repoDir    = rtrim($raw['common.repodir'] ?? '/???', '/' . DIRECTORY_SEPARATOR);;

		$extractConnection = function ($prefix) use ($raw) {
			return [
				'type'                 => $raw["{$prefix}.method"] ?? 'ftp',
				'hostname'             => $raw["{$prefix}.ftp.hostname"] ?? '',
				'port'                 => $raw["{$prefix}.ftp.port"] ?? null,
				'username'             => $raw["{$prefix}.ftp.username"] ?? '',
				'password'             => $raw["{$prefix}.ftp.password"] ?? '',
				'passive'              => (bool) ($raw["{$prefix}.ftp.passive"] ?? true),
				'directory'            => (($raw["{$prefix}.method"] ?? 'ftp') == 's3') ? $raw["{$prefix}.s3.directory"] ?? '' : $raw["{$prefix}.ftp.directory"] ?? '',
				'passive_fix'          => (bool) ($raw["{$prefix}.ftp.passive_fix"] ?? true),
				'timeout'              => (int) ($raw["{$prefix}.ftp.timeout"] ?? 3600),
				'public_key'           => $raw["{$prefix}.ftp.pubkeyfile"] ?? '',
				'private_key'          => $raw["{$prefix}.ftp.privkeyfile"] ?? '',
				'private_key_password' => $raw["{$prefix}.ftp.privkeyfile_pass"] ?? '',
				'endpoint'             => $raw["{$prefix}.s3.endpoint"] ?? null,
				'access'               => $raw["{$prefix}.s3.access"] ?? '',
				'secret'               => $raw["{$prefix}.s3.secret"] ?? '',
				'bucket'               => $raw["{$prefix}.s3.bucket"] ?? '',
				'tls'                  => (bool) ($raw["{$prefix}.s3.usessl"] ?? true),
				'signature'            => $raw["{$prefix}.s3.signature"] ?? 'v4',
				'region'               => $raw["{$prefix}.s3.region"] ?? 'us-east-1',
				'cdnhostname'          => $raw["{$prefix}.s3.cdnhostname"] ?? null,
				'acl'                  => $raw["{$prefix}.s3.acl"] ?? Acl::ACL_PUBLIC_READ,
				'storage_class'        => $raw["{$prefix}.s3.storageclass"] ?? StorageClass::STANDARD,
				'maximum_age'          => (int) ($raw["{$prefix}.s3.maxage"] ?? 600),
			];
		};

		$config = [
			'release'     => [
				'version'       => $raw['common.version'] ?? '',
				'date'          => $raw['common.date'] ?? 'now',
				'category'      => $raw['common.category'] ?? 0,
				'access'        => $raw['common.releaseaccess'] ?? 1,
				'release_notes' => $repoDir . '/RELEASENOTES.html',
				'changelog'     => $repoDir . '/CHANGELOG',
			],
			'api'         => [
				'endpoint'  => $raw['common.arsapiurl'] ?? '',
				'connector' => $raw['common.ars.communication'] ?? 'php',
				'username'  => $raw['common.username'] ?? '',
				'password'  => $raw['common.password'] ?? '',
				'token'     => $raw['common.token'] ?? '',
				'cacert'    => $raw['common.cacert'] ?? '',
				'type'      => $raw['common.ars.apitype'] ?? 'fof',
			],
			'steps'       => $raw['common.steps'] ?? [],
			'connections' => [
				'update' => $extractConnection('common.update'),
				'core'   => $extractConnection('core'),
				'pro'    => $extractConnection('pro'),
			],
			'updates'     => [
				// Core
				[
					'title'      => 'Core updates',
					'connection' => 'core',
					'stream'     => (int) ($raw['core.update.stream'] ?? 0),
					'base_name'  => $raw['core.update.basename'] ?? '',
					'formats'    => $raw['core.update.formats'],
				],
				// Pro
				[
					'title'      => 'Pro updates',
					'connection' => 'pro',
					'stream'     => (int) ($raw['pro.update.stream'] ?? 0),
					'base_name'  => $raw['pro.update.basename'] ?? '',
					'formats'    => $raw['pro.update.formats'],
				],
			],
			'files'       => [
				// Core
				'core' => [
					'title'      => 'Core files',
					'connection' => 'core',
					'source'     => $releaseDir . '/' . ($raw['core.pattern'] ?? ''),
					'access'     => $raw['core.access'] ?? 1,
				],
				// Pro
				'pro'  => [
					'title'      => 'Pro files',
					'connection' => 'pro',
					'source'     => $releaseDir . '/' . ($raw['pro.pattern'] ?? ''),
					'access'     => $raw['pro.access'] ?? 2,
				],
			],
		];

		// Add PDF / additional files
		$pdfFiles      = $raw['pdf.files'] ?? [];
		$pdfConnection = $raw['pdf.where'] ?? 'core';

		foreach ($pdfFiles as $fileName)
		{
			if (empty($fileName))
			{
				continue;
			}

			$config['files'][] = [
				'title'      => sprintf('Additional file %s', $fileName),
				'connection' => $pdfConnection,
				'source'     => $releaseDir . '/' . $fileName,
				'access'     => $raw[$pdfConnection . '.access'] ?? ($pdfConnection == 'core' ? 1 : 2),
			];
		}

		// Empty steps don't make sense
		if (empty($config['steps']))
		{
			unset($config['steps']);
		}

		// Filter out invalid updates
		$config['updates'] = array_filter($config['updates'], function ($update) {
			return ($update['stream'] > 0) && !empty($update['base_name']) && !empty($update['formats']);
		});

		// Filter out invalid files
		if (empty($raw['core.pattern'] ?? ''))
		{
			unset($config['files']['core']);
		}

		if (empty($raw['pro.pattern'] ?? ''))
		{
			unset($config['files']['pro']);
		}

		// Filter out unused connections
		$usedConnections = array_unique(array_map(function ($fileConfig) {
			return $fileConfig['connection'];
		}, $config['files']));

		$unusedConnections = array_diff(array_keys($config['connections']), $usedConnections);

		foreach ($unusedConnections as $unusedConnection)
		{
			unset($config[$unusedConnection]);
		}

		return $config;
	}
}