<?php

/**
 * Akeeba Release Maker
 * An automated script to upload and release a new version of an Akeeba component.
 * Copyright ©2012-2017 Nicholas K. Dionysopoulos / Akeeba Ltd.
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
class ArmConfiguration
{

	/** @var ArmConfiguration Singleton instance */
	static $instance = null;

	/** @var string Default NameSpace */
	private $defaultNameSpace = 'arm';

	/** @var array The registry data */
	private $registry = array();


	/**
	 * Singleton implementation
	 *
	 * @return  ArmConfiguration
	 */
	public static function getInstance()
	{
		if (!(self::$instance instanceof ArmConfiguration))
		{
			self::$instance = new ArmConfiguration();
		}

		return self::$instance;
	}

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
	 * Create a namespace
	 *
	 * @param    string $namespace Name of the namespace to create
	 */
	public function makeNameSpace($namespace)
	{
		$this->registry[ $namespace ] = array('data' => new stdClass());
	}

	/**
	 * Get the list of namespaces
	 *
	 * @return    array    List of namespaces
	 */
	public function getNameSpaces()
	{
		return array_keys($this->registry);
	}

	/**
	 * Get a registry value
	 *
	 * @param    string $regpath Registry path (e.g. global.directory.temporary)
	 * @param    mixed  $default Optional default value
	 *
	 * @return    mixed    Value of entry or null
	 */
	public function get($regpath, $default = null)
	{
		$result = $default;

		// Explode the registry path into an array
		if ($nodes = explode('.', $regpath))
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

			if (isset($this->registry[ $namespace ]))
			{
				$ns        = $this->registry[ $namespace ]['data'];
				$pathNodes = $count - 1;

				for ($i = 1; $i < $pathNodes; $i ++)
				{
					if ((isset($ns->{$nodes[ $i ]})))
					{
						$ns = $ns->{$nodes[ $i ]};
					}
				}

				if (isset($ns->{$nodes[ $i ]}))
				{
					$result = $ns->{$nodes[ $i ]};
				}
			}
		}

		return $result;
	}

	/**
	 * Set a registry value
	 *
	 * @param    string $regpath Registry Path (e.g. global.directory.temporary)
	 * @param    mixed  $value   Value of entry
	 *
	 * @return    mixed    Value of old value or boolean false if operation failed
	 */
	public function set($regpath, $value)
	{
		// Explode the registry path into an array
		$nodes = explode('.', $regpath);

		// Get the namespace
		$count = count($nodes);

		if ($count < 2)
		{
			$namespace = $this->defaultNameSpace;
		}
		else
		{
			$namespace = array_shift($nodes);
			$count --;
		}

		if (!isset($this->registry[ $namespace ]))
		{
			$this->makeNameSpace($namespace);
		}

		$ns = $this->registry[ $namespace ]['data'];

		$pathNodes = $count - 1;

		if ($pathNodes < 0)
		{
			$pathNodes = 0;
		}

		for ($i = 0; $i < $pathNodes; $i ++)
		{
			// If any node along the registry path does not exist, create it
			if (!isset($ns->{$nodes[ $i ]}))
			{
				$ns->{$nodes[ $i ]} = new stdClass();
			}
			$ns = $ns->{$nodes[ $i ]};
		}

		// Set the new values
		if (is_string($value))
		{
			if (substr($value, 0, 10) == '###json###')
			{
				$value = json_decode(substr($value, 10));
			}
		}

		// Post-process certain directory-containing variables
		if ($process_special_vars && in_array($regpath, $this->directory_containing_keys))
		{
			if (!empty($stock_directories))
			{
				$data = $value;
				foreach ($stock_directories as $tag => $content)
				{
					$data = str_replace($tag, $content, $data);
				}
				$ns->{$nodes[ $i ]} = $data;

				return $ns->{$nodes[ $i ]};
			}
		}

		// This is executed if any of the previous two if's is false

		$ns->{$nodes[ $i ]} = $value;

		return $ns->{$nodes[ $i ]};
	}

	/**
	 * Unset (remove) a registry value
	 *
	 * @param    string $regpath Registry Path (e.g. global.directory.temporary)
	 *
	 * @return    bool    True if the node was removed
	 */
	public function remove($regpath)
	{
		// Explode the registry path into an array
		$nodes = explode('.', $regpath);

		// Get the namespace
		$count = count($nodes);

		if ($count < 2)
		{
			$namespace = $this->defaultNameSpace;
		}
		else
		{
			$namespace = array_shift($nodes);
			$count --;
		}

		if (!isset($this->registry[ $namespace ]))
		{
			$this->makeNameSpace($namespace);
		}

		$ns = $this->registry[ $namespace ]['data'];

		$pathNodes = $count - 1;

		if ($pathNodes < 0)
		{
			$pathNodes = 0;
		}

		for ($i = 0; $i < $pathNodes; $i ++)
		{
			// If any node along the registry path does not exist, return false
			if (!isset($ns->{$nodes[ $i ]}))
			{
				return false;
			}
			$ns = $ns->{$nodes[ $i ]};
		}

		unset($ns->{$nodes[ $i ]});

		return true;
	}

	/**
	 * Resets the registry to the default values
	 */
	public function reset()
	{
		$this->loadFile(__DIR__ . '/config.json');
	}

	/**
	 * Merges an associative array of key/value pairs into the registry.
	 * If noOverride is set, only non set or null values will be applied.
	 *
	 * @param    array $array      An associative array. Its keys are registry paths.
	 * @param    bool  $noOverride [optional] Do not override pre-set values.
	 */
	public function mergeArray($array, $noOverride = false)
	{
		if (!$noOverride)
		{
			foreach ($array as $key => $value)
			{
				$this->set($key, $value);
			}
		}
		else
		{
			foreach ($array as $key => $value)
			{
				if (is_null($this->get($key, null)))
				{
					$this->set($key, $value);
				}
			}
		}
	}

	/**
	 * Load configuration from a JSON string
	 *
	 * @param   string $json The JSON string to load
	 */
	public function loadJSON($json = null)
	{
		$array = json_decode($json, true);

		if (empty($array))
		{
			return;
		}

		$this->mergeArray($array);
	}

	/**
	 * Load configuration from a JSON file
	 *
	 * @param   string $filename The path to the file to load
	 */
	public function loadFile($filename = null)
	{
		$data = file_get_contents($filename);
		$this->loadJSON($data);
	}

	/**
	 * Exports the current registry snapshot as an JOSN string.
	 *
	 * @return    string    JSON representation of the registry
	 */
	public function exportJSON()
	{
		$data = array();

		$namespaces = $this->getNameSpaces();
		foreach ($namespaces as $namespace)
		{
			$ns   = $this->registry[ $namespace ]['data'];
			$temp = $this->dumpObject($ns);

			if (!empty($temp))
			{
				foreach ($temp as $k => $v)
				{
					$data[ $namespace . '.' . $k ] = $v;
				}
			}
		}

		return json_encode($data);
	}

	/**
	 * Internal function to dump an object as an array
	 *
	 * @param object $object
	 * @param string $prefix [optional]
	 *
	 * @return
	 */
	private function dumpObject($object, $prefix = '')
	{
		$data = array();
		$vars = get_object_vars($object);
		foreach ($vars as $key => $value)
		{
			if (is_array($value))
			{
				$value = '###json###' . json_encode($value);
			}
			$data[ (empty($prefix) ? '' : $prefix . '.') . $key ] = $value;
		}

		return $data;
	}

	public function postProcess()
	{
		$v = $this->get('pro.pattern', null);

		if (empty($v))
		{
			$this->set('pro.pattern', 'com_*pro.zip');
		}

		$pro_method = $this->get('pro.method', null);

		if (empty($pro_method))
		{
			$this->set('pro.method', 'sftp');
		}
		elseif ($pro_method == 's3')
		{
			$v = $this->get('pro.s3.access', null);

			if (empty($v))
			{
				$this->set('pro.s3.access', $this->get('common.update.s3.access'));
			}

			$v = $this->get('pro.s3.secret', null);

			if (empty($v))
			{
				$this->set('pro.s3.secret', $this->get('common.update.s3.secret'));
			}

			$v = $this->get('pro.s3.reldir', null);

			if (empty($v))
			{
				$this->set('pro.s3.reldir', $this->get('pro.s3.directory'));
			}
		}

		$v = $this->get('core.pattern', null);

		if (empty($v))
		{
			$this->set('core.pattern', 'com_*core.zip');
		}

		$core_method = $this->get('core.method', null);

		if (empty($core_method))
		{
			$this->set('core.method', 'sftp');
		}
		elseif ($core_method == 's3')
		{
			$v = $this->get('core.s3.access', null);

			if (empty($v))
			{
				$this->set('core.s3.access', $this->get('common.update.s3.access'));
			}

			$v = $this->get('core.s3.secret', null);

			if (empty($v))
			{
				$this->set('core.s3.secret', $this->get('common.update.s3.secret'));
			}

			$v = $this->get('core.s3.reldir', null);

			if (empty($v))
			{
				$this->set('core.s3.reldir', $this->get('core.s3.directory'));
			}
		}

		$v = $this->get('pdf.where', null);

		if (empty($v))
		{
			$this->set('pdf.where', 'core');
		}
		/**/
	}
}