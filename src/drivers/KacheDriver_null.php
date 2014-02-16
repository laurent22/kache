<?php

class KacheDriver_null {

	public function __construct($options) {}
	public function get($key) { return null; }
	public function set($key, $value, $timeout) { return null; }
	public function delete($key) { return null; }
	public function clear() { return null; }

}