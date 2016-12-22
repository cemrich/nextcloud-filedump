<?php
namespace OCA\FileDump\Service;

use Exception;
use OCP\IDb;
use OCA\FileDump\Service;
use OCP\IDBConnection;
use OCP\ILogger;

class BackupService {

	private $appName;
	private $db;
	private $odb;
	private $configService;
	private $logger;
	private $logContext;
	private $userId;
	private $dataBaseConnection;


	/**
	 * BackupService constructor
	 *
	 * @param $appName
	 * @param IDb $db
	 * @param \OC_DB $odb
	 * @param ConfigService $configService
	 * @param ILogger $logger
	 * @param $userId
	 * @param $dataBaseConnection
	 */
	public function __construct($appName, IDb $db, \OC_DB $odb, IDBConnection $dataBaseConnection, ConfigService $configService, ILogger $logger, $userId){
		$this->appName = $appName;
		$this->db = $db;
		$this->odb = $odb;
		$this->configService = $configService;
		$this->logger = $logger;
		$this->logContext = ['app' => 'filedump'];
		$this->userId = $userId;
		$this->dataBaseConnection = $dataBaseConnection;
	}

	/**
	 * Returns the id of the user or the name of the app if no user is present (for example in a cronjob)
	 *
	 * @return string
	 */
	private function getCallerName() {
		return is_null($this->userId) ? $this->appName : "user " . $this->userId;
	}

	public function createDBBackup() {
		try {
			// TODO: create file dump here
		} catch (Exception $e) {
			$this->logger->error($this->getCallerName() . " thew an exception: " . $e->getMessage(), $this->logContext);
			throw($e);
		}
	}
}
