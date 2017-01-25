<?php

namespace OCA\FileDump\Service;

use Exception;
use \OCP\IConfig;

class ConfigService {

	/**
	 *
	 * @var \OCP\IConfig
	 */
	protected $owncloudConfig;

	/**
	 *
	 * @var string
	 */
	protected $appName;

	public function __construct($appName, IConfig $owncloudConfig) {
		$this->appName = $appName;
		$this->owncloudConfig = $owncloudConfig;
	}

	/**
	 * @return string
	 * @throws Exception
	 */
	public function getBackupBaseDirectory() {
		$backupDir = $this->getDataDir() . '/filedump';
		$this->createDirectory($backupDir);
		return $backupDir;
	}

	/**
	 * @return string
	 * @throws Exception
	 */
	public function getTimestampPrefix() {
		return date("Y-m-d_H-i-s_");
	}

	/**
	 *
	 * @return string
	 */
	public function getDataDir() {
		return \OC::$server->getConfig()->getSystemValue("datadirectory", \OC::$SERVERROOT . '/data');
	}

	/**
	 * Looks up a system wide defined value
	 *
	 * @param string $key the key of the value, under which it was saved
	 * @param mixed $default the default value to be returned if the value isn't set
	 * @return mixed the value or $default
	 */
	public function getSystemValue($key, $default = '') {
		return $this->owncloudConfig->getSystemValue($key, $default);
	}

	/**
	 * Sets a new system wide value
	 *
	 * @param string $key the key of the value, under which will be saved
	 * @param mixed $value the value that should be stored
	 */
	public function setSystemValue($key, $value) {
		return $this->owncloudConfig->setSystemValue($key, $value);
	}

	/**
	 * create new backup folder if it not exists
	 */
	private function createDirectory($path) {
		if (!file_exists($path)) {
			if (!mkdir($path)) {
				throw new Exception("Cannot create backup dir: $path");
			}
		}
	}
}
