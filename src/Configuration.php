<?php
/**
 * @package    AkeebaReleaseMaker
 * @copyright  Copyright (c)2012-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseMaker;

use stdClass;

class Configuration
{
	/** @var self Singleton instance */
	static $instance;

	/** @var string Default NameSpace */
	private $defaultNameSpace = 'arm';

	/** @var array The registry data */
	private $registry = [];


	/**
	 * Constructor
	 */
	public function __construct()
	{
		// Create the default namespace
		$this->makeNameSpace($this->defaultNameSpace);

		// Create a default configuration
		$this->reset();
	}

	/**
	 * Singleton implementation
	 *
	 * @return  self
	 */
	public static function getInstance(): self
	{
		if (!(self::$instance instanceof Configuration))
		{
			self::$instance = new Configuration();
		}

		return self::$instance;
	}

	/**
	 * Get a registry value
	 *
	 * @param   string  $path     Registry path (e.g. global.directory.temporary)
	 * @param   mixed   $default  Optional default value
	 *
	 * @return    mixed    Value of entry or null
	 */
	public function get(string $path, $default = null)
	{
		$result = $default;

		// Explode the registry path into an array
		$nodes = explode('.', $path);

		if ($nodes !== [])
		{
			// Get the namespace
			$count = count($nodes);

			if ($count < 2)
			{
				$namespace = $this->defaultNameSpace;
				$nodes[1]  = $nodes[0];
			}
			else
			{
				$namespace = $nodes[0];
			}

			if (isset($this->registry[$namespace]))
			{
				$ns        = $this->registry[$namespace]['data'];
				$pathNodes = $count - 1;

				for ($i = 1; $i < $pathNodes; $i++)
				{
					if ((isset($ns->{$nodes[$i]})))
					{
						$ns = $ns->{$nodes[$i]};
					}
				}

				if (isset($ns->{$nodes[$i]}))
				{
					$result = $ns->{$nodes[$i]};
				}
			}
		}

		return $result;
	}

	/**
	 * Set a registry value
	 *
	 * @param   string  $path   Registry Path (e.g. global.directory.temporary)
	 * @param   mixed   $value  Value of entry
	 *
	 * @return  mixed  Value of old value or boolean false if operation failed
	 */
	public function set(string $path, $value)
	{
		// Explode the registry path into an array
		$nodes = explode('.', $path);

		// Get the namespace
		$count = count($nodes);

		if ($count < 2)
		{
			$namespace = $this->defaultNameSpace;
		}
		else
		{
			$namespace = array_shift($nodes);
			$count--;
		}

		if (!isset($this->registry[$namespace]))
		{
			$this->makeNameSpace($namespace);
		}

		$ns = $this->registry[$namespace]['data'];

		$pathNodes = $count - 1;

		if ($pathNodes < 0)
		{
			$pathNodes = 0;
		}

		for ($i = 0; $i < $pathNodes; $i++)
		{
			// If any node along the registry path does not exist, create it
			if (!isset($ns->{$nodes[$i]}))
			{
				$ns->{$nodes[$i]} = new stdClass();
			}
			$ns = $ns->{$nodes[$i]};
		}

		// Set the new values
		if (is_string($value) && substr($value, 0, 10) == '###json###')
		{
			$value = json_decode(substr($value, 10));
		}

		// Unset keys when they are being set to null
		if (is_null($value))
		{
			$ret = $ns->{$nodes[$i]};

			unset($ns->{$nodes[$i]});

			return $ret;
		}

		$ns->{$nodes[$i]} = $value;

		return $ns->{$nodes[$i]};
	}

	/**
	 * Load configuration from a JSON file
	 *
	 * @param   string  $filename  The path to the file to load
	 */
	public function loadFile(?string $filename = null): void
	{
		if (empty($filename))
		{
			return;
		}

		$data = file_get_contents($filename);

		$this->loadJSON($data);
	}

	/**
	 * Exports the current registry snapshot as an JSON string.
	 *
	 * @return    string    JSON representation of the registry
	 * @noRector  \Rector\Privatization\Rector\ClassMethod\PrivatizeLocalOnlyMethodRector
	 */
	public function exportJSON(): string
	{
		$data = [];

		$namespaces = $this->getNameSpaces();

		foreach ($namespaces as $namespace)
		{
			$ns   = $this->registry[$namespace]['data'];
			$temp = $this->dumpObject($ns);

			foreach ($temp as $k => $v)
			{
				$data[$namespace . '.' . $k] = $v;
			}
		}

		return json_encode($data, JSON_PRETTY_PRINT);
	}

	/**
	 * Set up a cacert.pem file which merges the built in one with the user-defined one.
	 *
	 * @return  string
	 */
	public function getCustomCacertPem(): string
	{
		global $cacertPemFilePointer;

		$cacertPemFile   = __DIR__ . '/cacert.pem';
		$customCacertPem = $this->get('common.cacert');

		if (empty($customCacertPem))
		{
			return $cacertPemFile;
		}

		$customContents = null;

		if (!empty($customCacertPem) && @is_readable($customCacertPem))
		{
			$customContents = @file_get_contents($customCacertPem);
		}

		if (empty($customContents))
		{
			return $cacertPemFile;
		}

		// Let's use the tmpfile trick. The file will removed once the self::$cacertPemFilePointer goes out of scope.
		$cacertPemFilePointer = tmpfile();
		$cacertPemFile        = stream_get_meta_data($cacertPemFilePointer)['uri'];

		// Combine the original cacert.pem with the provided certificate / certificate storage
		fwrite($cacertPemFilePointer, file_get_contents(__DIR__ . '/cacert.pem') . "\n\n" . $customContents);

		// DO NOT CALL fclose(). THAT WOULD DELETE OUR TEMPORARY FILE!
		return $cacertPemFile;
	}

	/**
	 * Create a namespace
	 *
	 * @param   string  $namespace  Name of the namespace to create
	 */
	private function makeNameSpace(string $namespace): void
	{
		$this->registry[$namespace] = ['data' => new stdClass()];
	}

	/**
	 * Get the list of namespaces
	 *
	 * @return    string[]    List of namespaces
	 */
	private function getNameSpaces(): array
	{
		return array_keys($this->registry);
	}

	/**
	 * Resets the registry to the default values
	 */
	private function reset(): void
	{
		$this->loadFile(__DIR__ . '/config.json');
	}

	/**
	 * Merges an associative array of key/value pairs into the registry.
	 * If noOverride is set, only non set or null values will be applied.
	 *
	 * @param   array  $array       An associative array. Its keys are registry paths.
	 * @param   bool   $noOverride  [optional] Do not override pre-set values.
	 */
	private function mergeArray(array $array, bool $noOverride = false): void
	{
		if (!$noOverride)
		{
			foreach ($array as $key => $value)
			{
				$this->set($key, $value);
			}

			return;
		}

		foreach ($array as $key => $value)
		{
			if (is_null($this->get($key)))
			{
				$this->set($key, $value);
			}
		}
	}

	/**
	 * Load configuration from a JSON string
	 *
	 * @param   string|null  $json  The JSON string to load
	 */
	private function loadJSON(?string $json = null)
	{
		$array = json_decode($json, true);

		if (empty($array))
		{
			return;
		}

		$this->mergeArray($array);

		$this->postProcess();
	}

	/**
	 * Post-process the configuration, filling in any gaps
	 */
	private function postProcess(): void
	{
		$v = $this->get('pro.pattern');

		if (empty($v))
		{
			$this->set('pro.pattern', 'pkg_*pro.zip');
		}

		$pro_method = $this->get('pro.method');

		if (empty($pro_method))
		{
			$this->set('pro.method', 'sftp');
		}

		if ($pro_method == 's3')
		{
			$v = $this->get('pro.s3.access');

			if (empty($v))
			{
				$this->set('pro.s3.access', $this->get('common.update.s3.access'));
			}

			$v = $this->get('pro.s3.secret');

			if (empty($v))
			{
				$this->set('pro.s3.secret', $this->get('common.update.s3.secret'));
			}

			$v = $this->get('pro.s3.reldir');

			if (empty($v))
			{
				$this->set('pro.s3.reldir', $this->get('pro.s3.directory'));
			}
		}

		$v = $this->get('core.pattern');

		if (empty($v))
		{
			$this->set('core.pattern', 'pkg_*core.zip');
		}

		$core_method = $this->get('core.method');

		if (empty($core_method))
		{
			$this->set('core.method', 'sftp');
		}

		if ($core_method == 's3')
		{
			$v = $this->get('core.s3.access');

			if (empty($v))
			{
				$this->set('core.s3.access', $this->get('common.update.s3.access'));
			}

			$v = $this->get('core.s3.secret');

			if (empty($v))
			{
				$this->set('core.s3.secret', $this->get('common.update.s3.secret'));
			}

			$v = $this->get('core.s3.reldir');

			if (empty($v))
			{
				$this->set('core.s3.reldir', $this->get('core.s3.directory'));
			}
		}

		$v = $this->get('pdf.where');

		if (empty($v))
		{
			$this->set('pdf.where', 'core');
		}
	}

	/**
	 * Internal function to dump an object as an array
	 *
	 * @param   object  $object
	 * @param   string  $prefix  [optional]
	 *
	 * @return  array
	 */
	private function dumpObject(object $object, string $prefix = ''): array
	{
		$data = [];
		$vars = get_object_vars($object);

		foreach ($vars as $key => $value)
		{
			if (is_array($value))
			{
				$value = '###json###' . json_encode($value);
			}

			$data[(empty($prefix) ? '' : $prefix . '.') . $key] = $value;
		}

		return $data;
	}
}
