<?php

class KacheDriver_redis {

	private $redis_ = null;

	public function __construct($options) {
		$this->redis_ = new Redis();
		$this->redis_->connect($options['server']['host'], $options['server']['port']);
		$this->redis_->select($options['server']['dbindex']);
	}
	
	private function arrayContainsObject($a) {
		foreach ($a as $k => $v) {
			if (is_object($v)) return true;
			if (is_array($v)) {
				$r = $this->arrayContainsObject($v);
				if ($r) return true;
			}
		}
		return false;
	}
	
	private function serialize($value) {
		if (is_string($value)) {
			$s = 's' . $value;
		} else if (is_bool($value)) {
			$s = $value ? 'b1' : 'b0';
		} else if (is_int($value)) {
			$s = 'i' . $value;
		} else if (is_float($value)) {
			$s = 'f' . $value;
		} else if (is_null($value)) {
			$s = 'n';
		} else if (is_object($value)) {
			$s = 'o' . serialize($value);
		} else if (is_array($value)) {
			if ($this->arrayContainsObject($value)) {
				$s = 'o' . serialize($value);
			} else {
				$s = 'j' . json_encode($value);
			}
		} else {
			throw new Exception('cannot serialize type: ' . gettype($value) . ': ' . $value);
		}
		return '1' . $s; // '1' indicates version to allow for possible changes
	}
	
	private function unserialize($value) {
		if (!$value || strlen($value) === 0) return null;
		
		$type = $value[1];
		$value = substr($value, 2);
		
		switch ($type) {
			case 's': return $value;
			case 'i': return (int)$value;
			case 'b': return $value === '1';
			case 'f': return (float)$value;
			case 'o': return unserialize($value);
			case 'j': return json_decode($value, true);
		}
		
		throw new Exception('type not supported: ' . $value);
	}

	public function get($key) {
		$o = $this->redis_->get($key);
		return $o === false ? null : $this->unserialize($o);
	}

	public function set($key, $value, $timeout) {
		return $this->redis_->set($key, $this->serialize($value), $timeout);
	}
	
	public function delete($key) {
		return $this->redis_->delete($key);
	}
	
	public function clear() {
		return $this->redis_->flushDB();
	}

}