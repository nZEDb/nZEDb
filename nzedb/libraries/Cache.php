<?php
namespace nzedb\libraries;

/**
 * Class Cache
 *
 * Class for connecting to a memcached or redis server to cache data.
 *
 * @package nzedb\libraries
 */
Class Cache
{
	const SERIALIZER_PHP      = 0;
	const SERIALIZER_IGBINARY = 1;
	const SERIALIZER_NONE     = 2;

	const TYPE_DISABLED  = 0;
	const TYPE_MEMCACHED = 1;
	const TYPE_REDIS     = 2;

	/**
	 * @var \Memcached|\Redis
	 */
	private $server = null;

	/**
	 * Are we connected to the cache server?
	 * @var bool
	 */
	private $connected = false;

	/**
	 * Optional socket file location.
	 * @var bool|string
	 */
	private $socketFile;

	/**
	 * Serializer type.
	 * @var bool|int
	 */
	private $serializerType;

	/**
	 * Are we using redis or memcached?
	 * @var bool
	 */
	private $isRedis = true;

	/**
	 * Does the user have igBinary support and wants to use it?
	 * @var bool
	 */
	private $IgBinarySupport = false;

	/**
	 * Store data on the cache server.
	 *
	 * @param string       $key        Key we can use to retrieve the data.
	 * @param string|array $data       Data to store on the cache server.
	 * @param int          $expiration Time before the data expires on the cache server.
	 *
	 * @return bool Success/Failure.
	 * @access public
	 */
	public function set($key, $data, $expiration)
	{
		if ($this->connected === true && $this->ping() === true) {
			return $this->server->set(
				$key,
				($this->isRedis ? ($this->IgBinarySupport ? igbinary_serialize($data) : serialize($data)) : $data),
				$expiration
			);
		}
		return false;
	}

	/**
	 * Attempt to retrieve a value from the cache server, if not set it.
	 *
	 * @param string $key Key we can use to retrieve the data.
	 *
	 * @return bool|string False on failure or String, data belonging to the key.
	 * @access public
	 */
	public function get($key)
	{
		if ($this->connected === true && $this->ping() === true) {
			$data = $this->server->get($key);
			return ($this->isRedis ? ($this->IgBinarySupport ? igbinary_unserialize($data) : unserialize($data)) : $data);
		}
		return false;
	}

	/**
	 * Delete data tied to a key on the cache server.
	 *
	 * @param string $key Key we can use to retrieve the data.
	 *
	 * @return bool True if deleted, false if not.
	 * @access public
	 */
	public function delete($key)
	{
		if ($this->connected === true && $this->ping() === true) {
			return (bool) $this->server->delete($key);
		}
		return false;
	}

	/**
	 * Flush all data from the cache server?
	 */
	public function flush()
	{
		if ($this->connected === true && $this->ping() === true) {
			if ($this->isRedis === true) {
				$this->server->flushAll();
			} else {
				$this->server->flush();
			}
		}
	}

	/**
	 * Create a SHA1 hash from a string which can be used to store/retrieve data.
	 *
	 * @param string $string
	 *
	 * @return string SHA1 hash of the input string.
	 * @access public
	 */
	public function createKey($string)
	{
		return sha1($string);
	}

	/**
	 * Get cache server statistics.
	 *
	 * @return array|string
	 * @access public
	 */
	public function serverStatistics()
	{
		if ($this->connected === true && $this->ping() === true) {
			if ($this->isRedis === true) {
				return $this->server->info();
			} else {
				return $this->server->getStats();
			}
		}
		return array();
	}

	/**
	 * Verify the user's cache settings, try to connect to the cache server.
	 */
	public function __construct()
	{
		if (!defined('nZEDb_CACHE_HOSTS')) {
			throw new \CacheException(
				'The nZEDb_CACHE_HOSTS is not defined! Define it in settings.php'
			);
		}

		if (!defined('nZEDb_CACHE_TIMEOUT')) {
			throw new \CacheException(
				'The nZEDb_CACHE_TIMEOUT is not defined! Define it in settings.php, it is the time in seconds to time out from your cache server.'
			);
		}

		$this->socketFile = false;
		if (defined('nZEDb_CACHE_SOCKET_FILE') && nZEDb_CACHE_SOCKET_FILE != '') {
			$this->socketFile = true;
		}

		$this->serializerType = false;
		if (defined('nZEDb_CACHE_SERIALIZER')) {
			$this->serializerType = true;
		}

		switch(nZEDb_CACHE_TYPE) {

			case self::TYPE_REDIS:
				if (!extension_loaded('redis')) {
					throw new CacheException('The redis extension is not loaded!');
				}
				$this->server = new \Redis();
				$this->isRedis = true;
				$this->connect();
				if ($this->serializerType !== false) {
					$this->serializerType = $this->verifySerializer();
					$this->server->setOption(\Redis::OPT_SERIALIZER, $this->serializerType);
				}
				break;

			case self::TYPE_MEMCACHED:
				if (!extension_loaded('memcached')) {
					throw new CacheException('The memcached extension is not loaded!');
				}
				$this->server = new \Memcached();
				$this->isRedis = false;
				if ($this->serializerType !== false) {
					$this->serializerType = $this->verifySerializer();
					$this->server->setOption(\Memcached::OPT_SERIALIZER, $this->serializerType);
				}
				$this->server->setOption(\Memcached::OPT_COMPRESSION, (defined('nZEDb_CACHE_COMPRESSION') ? nZEDb_CACHE_COMPRESSION : false));
				$this->connect();
				break;

			case self::TYPE_DISABLED:
			default:
				return;
		}
	}

	/**
	 * Destroy the connections.
	 */
	public function __destruct()
	{
		switch(nZEDb_CACHE_TYPE) {
			case self::TYPE_REDIS:
				$this->server->close();
				break;
			case self::TYPE_MEMCACHED:
				$this->server->quit();
				break;
		}
	}

	/**
	 * Connect to the cache server(s).
	 *
	 * @throws CacheException
	 * @access private
	 */
	private function connect()
	{
		$this->connected = false;
		if ($this->isRedis === true) {
			if ($this->socketFile === false) {
				$servers = unserialize(nZEDb_CACHE_HOSTS);
				foreach ($servers as $server) {
					if ($this->server->connect($server['host'], $server['port'], (float)nZEDb_CACHE_TIMEOUT) === false) {
						throw new CacheException('Error connecting to the Redis server!');
					} else {
						$this->connected = true;
					}
				}
			} else {
				if ($this->server->connect(nZEDb_CACHE_SOCKET_FILE) === false) {
					throw new CacheException('Error connecting to the Redis server!');
				} else {
					$this->connected = true;
				}
			}
		} else {
			if ($this->socketFile === false) {
				if ($this->server->addServers(unserialize(nZEDb_CACHE_HOSTS)) === false) {
					throw new CacheException('Error connecting to the Memcached server!');
				} else {
					$this->connected = true;
				}
			} else {
				if ($this->server->addServers(array(array(nZEDb_CACHE_SOCKET_FILE, 'port' => 0))) === false) {
					throw new CacheException('Error connecting to the Memcached server!');
				} else {
					$this->connected = true;
				}
			}
		}
	}

	/**
	 * Redis supports ping'ing the server, so use it.
	 */
	private function ping()
	{
		if ($this->isRedis === true) {
			try {
				return (bool) $this->server->ping();
			} catch (\RedisException $error) {
				$this->connect();
				return $this->connected;
			}
		}
		return true;
	}

	/**
	 * Verify the user selected serializer, return the memcached or redis appropriate serializer option.
	 *
	 * @return int
	 * @throws CacheException
	 * @access private
	 */
	private function verifySerializer()
	{
		switch(nZEDb_CACHE_SERIALIZER) {
			case self::SERIALIZER_IGBINARY:
				if (extension_loaded('igbinary')) {
					$this->IgBinarySupport = true;
					if ($this->isRedis === true) {
						return \Redis::SERIALIZER_IGBINARY;
					} else {
						if (\Memcached::HAVE_IGBINARY > 0) {
							return \Memcached::SERIALIZER_IGBINARY;
						} else {
							throw new CacheException('Error: You have not compiled Memcached with igbinary support!');
						}
					}
				} else {
					throw new CacheException('Error: The igbinary extension is not loaded!');
				}
			case self::SERIALIZER_NONE:
				if ($this->isRedis === true) {
					return \Redis::SERIALIZER_NONE;
				} else {
					throw new CacheException('Error: Disabled serialization is not available on Memcached!');
				}
			case self::SERIALIZER_PHP:
			default:
				if ($this->isRedis === true) {
					return \Redis::SERIALIZER_PHP;
				} else {
					return \Memcached::SERIALIZER_PHP;
				}
		}
	}

}

/**
 * Class CacheException
 *
 * @package nzedb\libraries
 */
Class CacheException extends \Exception {}
