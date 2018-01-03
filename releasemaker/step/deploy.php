<?php

/**
 * Akeeba Release Maker
 * An automated script to upload and release a new version of an Akeeba component.
 * Copyright (c)2006-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
class ArmStepDeploy implements ArmStepInterface
{
	public function execute()
	{
		echo "FILE DEPLOYMENT\n";
		echo str_repeat('-', 79) . PHP_EOL;

		$prefixes = array(
			'core',
			'pro',
		);

		foreach ($prefixes as $prefix)
		{
			$this->deployFiles($prefix);
		}

		echo "\tDeploying PDF files\n";
		$this->deployPdf();

		echo PHP_EOL;
	}

	private function deployFiles($prefix, $isPdf = false)
	{
		echo "\tDeploying " . ucfirst($prefix) . " files\n";

		$conf = ArmConfiguration::getInstance();

		$type = $conf->get($prefix . '.method', $conf->get('common.update.method', 'sftp'));

		if ($type == 's3')
		{
			$config = (object)array(
				'access'      => $conf->get($prefix . '.s3.access', $conf->get('common.update.s3.access', '')),
				'secret'      => $conf->get($prefix . '.s3.secret', $conf->get('common.update.s3.secret', '')),
				'bucket'      => $conf->get($prefix . '.s3.bucket', $conf->get('common.update.s3.bucket', '')),
				'usessl'      => $conf->get($prefix . '.s3.usessl', $conf->get('common.update.s3.usessl', true)),
				'signature'   => $conf->get($prefix . '.s3.signature', $conf->get('common.update.s3.signature', 's3')),
				'region'      => $conf->get($prefix . '.s3.region', $conf->get('common.update.s3.region', 'us-east-1')),
				'directory'   => $conf->get($prefix . '.s3.directory', $conf->get('common.update.s3.directory', '')),
				'cdnhostname' => $conf->get($prefix . '.s3.cdnhostname', $conf->get('common.update.s3.cdnhostname', '')),
			);
		}
		else
		{
			$config = (object)array(
				'type'             => $type,
				'hostname'         => $conf->get($prefix . '.ftp.hostname', $conf->get('common.update.ftp.hostname', '')),
				'port'             => $conf->get($prefix . '.ftp.port', $conf->get('common.update.ftp.port', in_array($type, array('sftp', 'sftpcurl')) ? 22 : 21)),
				'username'         => $conf->get($prefix . '.ftp.username', $conf->get('common.update.ftp.username', '')),
				'password'         => $conf->get($prefix . '.ftp.password', $conf->get('common.update.ftp.password', '')),
				'passive'          => $conf->get($prefix . '.ftp.passive', $conf->get('common.update.ftp.passive', true)),
				'directory'        => $conf->get($prefix . '.ftp.directory', $conf->get('common.update.ftp.directory', '')),
				'pubkeyfile'       => $conf->get($prefix . '.ftp.pubkeyfile', $conf->get('common.update.ftp.pubkeyfile', '')),
				'privkeyfile'      => $conf->get($prefix . '.ftp.privkeyfile', $conf->get('common.update.ftp.privkeyfile', '')),
				'privkeyfile_pass' => $conf->get($prefix . '.ftp.privkeyfile_pass', $conf->get('common.update.ftp.privkeyfile_pass', '')),
				'passive_fix'      => $conf->get($prefix . '.ftp.passive_fix', $conf->get('common.update.ftp.passive_fix', false)),
				'timeout'          => $conf->get($prefix . '.ftp.timeout', $conf->get('common.update.ftp.timeout', 3600)),
				'verbose'          => $conf->get($prefix . '.ftp.verbose', $conf->get('common.update.ftp.verbose', false)),
			);
		}

		$volatileFiles = $conf->get('volatile.files');
		if ($isPdf)
		{
			$files = $volatileFiles['pdf'];
		}
		else
		{
			$files = $volatileFiles[$prefix];
		}

		if (empty($files))
		{
			return;
		}

		$path = $conf->get('common.releasedir');

		foreach ($files as $filename)
		{
			echo "\t\tUploading $filename\n";
			$sourcePath = $path . DIRECTORY_SEPARATOR . $filename;

			switch ($type)
			{
				case 's3':
					$this->uploadS3($config, $sourcePath);
					break;
				case 'ftp':
				case 'ftps':
					$this->uploadFtp($config, $sourcePath);
					break;
				case 'ftpcurl':
				case 'ftpscurl':
					$this->uploadFtpCurl($config, $sourcePath);
					break;
				case 'sftp':
					$this->uploadSftp($config, $sourcePath);
					break;
				case 'sftpcurl':
					$this->uploadSftpCurl($config, $sourcePath);
					break;
			}
		}
	}

	private function deployPdf()
	{
		$conf  = ArmConfiguration::getInstance();
		$where = $conf->get('pdf.where', 'core');
		if ($where == 'core')
		{
			$this->deployFiles('core', true);
		}
		else
		{
			$this->deployFiles('pro', true);
		}
	}

	private function uploadS3($config, $sourcePath, $destName = null)
	{
		$config->signature = ($config->signature == 'v4') ? 'v4' : 'v2';

		$configuration = new \Akeeba\Engine\Postproc\Connector\S3v4\Configuration(
			$config->access, $config->secret, $config->signature, $config->region
		);

		// Is SSL enabled and we have a cacert.pem file?
		if (!defined('AKEEBA_CACERT_PEM'))
		{
			$config->usessl = false;
		}

		$configuration->setSSL($config->usessl);

		// Create the S3 client instance
		$s3Client = new \Akeeba\Engine\Postproc\Connector\S3v4\Connector($configuration);

		if (empty($destName))
		{
			$destName = basename($sourcePath);
		}

		$conf    = ArmConfiguration::getInstance();
		$version = $conf->get('common.version');

		$uri = $config->directory . '/' . $version . '/' . $destName;

		if (!empty($config->cdnhostname))
		{
			$acl = \Akeeba\Engine\Postproc\Connector\S3v4\Acl::ACL_PUBLIC_READ;
		}
		else
		{
			$acl = \Akeeba\Engine\Postproc\Connector\S3v4\Acl::ACL_PRIVATE;
		}

		echo "\t\t          with $acl ACL\n";

		$bucket    = $config->bucket;
		$inputFile = realpath($sourcePath);
		$input     = \Akeeba\Engine\Postproc\Connector\S3v4\Input::createFromFile($inputFile);

		$s3Client->putObject($input, $bucket, $uri, $acl, [
			'StorageClass' => 'STANDARD',
			'CacheControl' => 'max-age=600',
		]);

		return true;
	}

	private function uploadFtp($config, $sourcePath, $destName = null)
	{
		if (empty($destName))
		{
			$conf     = ArmConfiguration::getInstance();
			$version  = $conf->get('common.version');
			$destName = $version . '/' . basename($sourcePath);
		}

		$ftp = new ArmFtp($config);
		$ftp->upload($sourcePath, $destName);
	}

	private function uploadFtpCurl($config, $sourcePath, $destName = null)
	{
		if (empty($destName))
		{
			$conf     = ArmConfiguration::getInstance();
			$version  = $conf->get('common.version');
			$destName = $version . '/' . basename($sourcePath);
		}

		$ftp = new ArmFtpcurl($config);
		$ftp->upload($sourcePath, $destName);
	}

	private function uploadSftp($config, $sourcePath, $destName = null)
	{
		if (empty($destName))
		{
			$conf     = ArmConfiguration::getInstance();
			$version  = $conf->get('common.version');
			$destName = $version . '/' . basename($sourcePath);
		}

		$sftp = new ArmSftp($config);
		$sftp->upload($sourcePath, $destName);
	}

	private function uploadSftpCurl($config, $sourcePath, $destName = null)
	{
		if (empty($destName))
		{
			$conf     = ArmConfiguration::getInstance();
			$version  = $conf->get('common.version');
			$destName = $version . '/' . basename($sourcePath);
		}

		$sftp = new ArmSftpcurl($config);
		$sftp->upload($sourcePath, $destName);
	}
}
