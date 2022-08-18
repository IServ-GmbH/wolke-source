<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Stefan Weil <sw@weilnetz.de>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\Memcache;

use OCP\IMemcacheTTL;

class Redis extends Cache implements IMemcacheTTL {
	/**
	 * @var \Redis $cache
	 */
	private static $cache = null;

	private $logFile;

	public function __construct($prefix = '', string $logFile = '') {
		parent::__construct($prefix);
		$this->logFile = $logFile;
		if (is_null(self::$cache)) {
			self::$cache = \OC::$server->getGetRedisFactory()->getInstance();
		}
	}

	/**
	 * entries in redis get namespaced to prevent collisions between ownCloud instances and users
	 */
	protected function getNameSpace() {
		return $this->prefix;
	}

	public function get($key) {
		if ($this->logFile !== '' && is_writable($this->logFile)) {
			file_put_contents(
				$this->logFile,
				$this->getNameSpace() . '::get::' . $key . "\n",
				FILE_APPEND
			);
		}

		$result = self::$cache->get($this->getNameSpace() . $key);
		if ($result === false && !self::$cache->exists($this->getNameSpace() . $key)) {
			return null;
		} else {
			return json_decode($result, true);
		}
	}

	public function set($key, $value, $ttl = 0) {
		if ($this->logFile !== '' && is_writable($this->logFile)) {
			file_put_contents(
				$this->logFile,
				$this->getNameSpace() . '::set::' . $key . '::' . $ttl . '::' . json_encode($value) . "\n",
				FILE_APPEND
			);
		}

		if ($ttl > 0) {
			return self::$cache->setex($this->getNameSpace() . $key, $ttl, json_encode($value));
		} else {
			return self::$cache->set($this->getNameSpace() . $key, json_encode($value));
		}
	}

	public function hasKey($key) {
		if ($this->logFile !== '' && is_writable($this->logFile)) {
			file_put_contents(
				$this->logFile,
				$this->getNameSpace() . '::hasKey::' . $key . "\n",
				FILE_APPEND
			);
		}

		return (bool)self::$cache->exists($this->getNameSpace() . $key);
	}

	public function remove($key) {
		if ($this->logFile !== '' && is_writable($this->logFile)) {
			file_put_contents(
				$this->logFile,
				$this->getNameSpace() . '::remove::' . $key . "\n",
				FILE_APPEND
			);
		}

		if (self::$cache->del($this->getNameSpace() . $key)) {
			return true;
		} else {
			return false;
		}
	}

	public function clear($prefix = '') {
		if ($this->logFile !== '' && is_writable($this->logFile)) {
			file_put_contents(
				$this->logFile,
				$this->getNameSpace() . '::clear::' . $prefix . "\n",
				FILE_APPEND
			);
		}

		$prefix = $this->getNameSpace() . $prefix . '*';
		$keys = self::$cache->keys($prefix);
		$deleted = self::$cache->del($keys);

		return count($keys) === $deleted;
	}

	/**
	 * Set a value in the cache if it's not already stored
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param int $ttl Time To Live in seconds. Defaults to 60*60*24
	 * @return bool
	 */
	public function add($key, $value, $ttl = 0) {
		// don't encode ints for inc/dec
		if (!is_int($value)) {
			$value = json_encode($value);
		}

		$args = ['nx'];
		if ($ttl !== 0 && is_int($ttl)) {
			$args['ex'] = $ttl;
		}
		if ($this->logFile !== '' && is_writable($this->logFile)) {
			file_put_contents(
				$this->logFile,
				$this->getNameSpace() . '::add::' . $key . '::' . $value . "\n",
				FILE_APPEND
			);
		}


		return self::$cache->set($this->getPrefix() . $key, $value, $args);
	}

	/**
	 * Increase a stored number
	 *
	 * @param string $key
	 * @param int $step
	 * @return int | bool
	 */
	public function inc($key, $step = 1) {
		if ($this->logFile !== '' && is_writable($this->logFile)) {
			file_put_contents(
				$this->logFile,
				$this->getNameSpace() . '::inc::' . $key . "\n",
				FILE_APPEND
			);
		}

		return self::$cache->incrBy($this->getNameSpace() . $key, $step);
	}

	/**
	 * Decrease a stored number
	 *
	 * @param string $key
	 * @param int $step
	 * @return int | bool
	 */
	public function dec($key, $step = 1) {
		if ($this->logFile !== '' && is_writable($this->logFile)) {
			file_put_contents(
				$this->logFile,
				$this->getNameSpace() . '::dec::' . $key . "\n",
				FILE_APPEND
			);
		}

		if (!$this->hasKey($key)) {
			return false;
		}
		return self::$cache->decrBy($this->getNameSpace() . $key, $step);
	}

	/**
	 * Compare and set
	 *
	 * @param string $key
	 * @param mixed $old
	 * @param mixed $new
	 * @return bool
	 */
	public function cas($key, $old, $new) {
		if ($this->logFile !== '' && is_writable($this->logFile)) {
			file_put_contents(
				$this->logFile,
				$this->getNameSpace() . '::cas::' . $key . "\n",
				FILE_APPEND
			);
		}

		if (!is_int($new)) {
			$new = json_encode($new);
		}
		self::$cache->watch($this->getNameSpace() . $key);
		if ($this->get($key) === $old) {
			$result = self::$cache->multi()
				->set($this->getNameSpace() . $key, $new)
				->exec();
			return $result !== false;
		}
		self::$cache->unwatch();
		return false;
	}

	/**
	 * Compare and delete
	 *
	 * @param string $key
	 * @param mixed $old
	 * @return bool
	 */
	public function cad($key, $old) {
		if ($this->logFile !== '' && is_writable($this->logFile)) {
			file_put_contents(
				$this->logFile,
				$this->getNameSpace() . '::cad::' . $key . "\n",
				FILE_APPEND
			);
		}

		self::$cache->watch($this->getNameSpace() . $key);
		if ($this->get($key) === $old) {
			$result = self::$cache->multi()
				->del($this->getNameSpace() . $key)
				->exec();
			return $result !== false;
		}
		self::$cache->unwatch();
		return false;
	}

	public function setTTL($key, $ttl) {
		self::$cache->expire($this->getNameSpace() . $key, $ttl);
	}

	public static function isAvailable() {
		return \OC::$server->getGetRedisFactory()->isAvailable();
	}
}
