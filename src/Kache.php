<?php

class Kache {
	
	static private $instance_;
	
	static public function setup($options) {
		self::$instance_ = new Kache($options);
	}
	
	static public function instance() {
		if (!self::$instance_) throw new Exception('class not setup - call Kache::setup() first.');
		return self::$instance_;
	}

	private $driver_ = null;
	private $defaultTimeout_ = 600;

	public function __construct($options) {
		if (!isset($options['driver'])) throw new Exception('no driver specified');
		
		$driverName = $options['driver'];
		$defaultTimeout_ = array_key_exists('defaultTimeout', $options) ? $options['defaultTimeout'] : 600;
		
		$this->loadDriver($driverName, $options);
	}
	
	private function loadDriver($driverName, $options) {
		$DriverClass = 'KacheDriver_' . $driverName;
		$driverFile = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'drivers' . DIRECTORY_SEPARATOR . $DriverClass . '.php';
		require_once $driverFile;
		$this->driver_ = new $DriverClass($options);
	}

	public function get($key) {
		return $this->driver_->get($key);
	}

	public function set($key, $value, $timeout = null) {
		if ($timeout === null) $timeout = $this->defaultTimeout_;
		return $this->driver_->set($key, $value, $timeout);
	}
	
	public function delete($key) {
		return $this->driver_->delete($key);
	}
	
	public function clear() {
		return $this->driver_->clear();
	}
	
	public function getOrRun($key, $func, $timeout = null) {
		$o = __c()->get($key);
		if ($o !== null) return $o;
		$o = $func();
		__c()->set($key, $o, $timeout);
		return $o;
	}

}

function __c() {
	return Kache::instance();
}
