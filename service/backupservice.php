<?php
namespace OCA\FileDump\Service;

use Exception;
use OCP\IDb;
use OCP\IDBConnection;
use OCP\ILogger;

use OCA\FileDump\Service;

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

	public function createDBBackup() {
		try {
			$this->exportContacts();
			// TODO: export calendars
			// TODO: export TODOs
		} catch (Exception $e) {
			$this->logger->error($this->getCallerName() . ' thew an exception: ' . $e->getMessage(), $this->logContext);
			throw($e);
		}
	}

	private function exportContacts() {
		// TODO: add timestamp to directory and delete old directories
		$backupDir = $this->configService->getBackupBaseDirectory() . '/contacts';

		// create new backup folder if it not exists
		if (!file_exists($backupDir)) {
			if (!mkdir($backupDir)) {
				throw new Exception("Cannot create backup dir: $backupDir");
			}
		}

		// TODO: let user decide which address book to backup
		// TODO: save address books in subdirectory
		$statement = $this->db->prepareQuery(
			"SELECT uri, carddata, value AS fullname
			FROM oc_cards, oc_cards_properties
			WHERE oc_cards.id = oc_cards_properties.cardid
				AND name = 'FN'
			ORDER BY fullname;"
		);

		$statement->execute(array());

		while ($row = $statement->fetch()) {
			$filename = $this->sanitizeFilename("{$row['fullname']} - {$row['uri']}");
			file_put_contents($backupDir . '/' . $filename, $row['carddata']);
		}
	}

	/**
	 * Returns the id of the user or the name of the app if no user is present (for example in a cronjob)
	 *
	 * @return string
	 */
	private function getCallerName() {
		return is_null($this->userId) ? $this->appName : 'user ' . $this->userId;
	}

	private function sanitizeFilename($filename) {
		return str_replace(array('/'), '_', $filename);
	}
}
