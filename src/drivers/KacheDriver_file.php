<?php

class KacheDriver_file {
	
	private $cacheFolder_ = null;
	private $cacheFolderPermissions_ = 0666;
	private $useSubFolders_ = true;
	private $securityToken_ = null;

	public function __construct($options) {
		$this->cacheFolder_ = rtrim(rtrim($options['path'], '/'), "\\");
		$this->useSubFolders_ = isset($options['useSubFolders']) ? (bool)$options['useSubFolders'] : true;
		$this->securityToken_ = isset($options['securityToken']) ? (string)$options['securityToken'] : null;
	}

	private function encodeFilename($key) {
		$output = rtrim(base64_encode($key), '=');
		if (!$this->securityToken_) return $output;
		return $output . '_' . $this->securityToken_;
	}

	private function decodeFilename($filename) {
		if ($this->securityToken_) $filename = substr($filename, 0, strlen($filename) - strlen($this->securityToken_) - 1);
		return base64_decode($filename);
	}

	private function filePath($key) {
		$filename = $this->encodeFilename($key);
		$folder = $this->useSubFolders_ ? $this->cacheFolder_ . DIRECTORY_SEPARATOR . substr($filename, 0, 2) : $this->cacheFolder_;
		if (!is_dir($folder)) {
			@mkdir($folder, $this->cacheFolderPermissions_, true);
			if (!is_dir($folder)) throw new Exception('cannot create cache folder: ' . $folder);
		}
		return $folder . DIRECTORY_SEPARATOR . $filename;
	}
	
	private function encodeFileContent($value, $timeout) {
		return ($timeout ? time() . ',' . $timeout : ',') . "\n" . $value;
	}
	
	private function decodeFileContent($content) {
		$pos = strpos($content, "\n");
		if ($pos === false) throw new Exception('invalid file content: ' . $content);
		$header = explode(',', substr($content, 0, $pos));
		if (count($header) != 2) throw new Exception('invalid file header: ' . $content);
		return array(
			'timestamp' => (int)$header[0],
			'timeout' => (int)$header[1],
			'value' => substr($content, $pos + 1),
		);
	}

	public function get($key) {
		$filePath = $this->filePath($key);
		$content = @file_get_contents($filePath);
		if (!$content) return null;
		
		$content = $this->decodeFileContent($content);
		if (!$content['timeout']) return $content['value'];
		if ($content['timestamp'] + $content['timeout'] < time()) {
			@unlink($filePath);
			return null;
		}
		return $content['value'];
	}

	public function set($key, $value, $timeout) {
		file_put_contents($this->filePath($key), $this->encodeFileContent($value, $timeout));
	}
	
	public function delete($key) {
		@unlink($this->filePath($key));
	}

	public function clear() {
		@unlink($this->cacheFolder_);
	}

}
