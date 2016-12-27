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
			// TODO: delete old directories
			$baseDir = $this->configService->getBackupBaseDirectory();
			$timestampPrefix = $this->configService->getTimestampPrefix();

			$this->exportAddressBooks($baseDir, $timestampPrefix);
			$this->exportCalendars($baseDir, $timestampPrefix);
			// TODO: export TODOs
		} catch (Exception $e) {
			$this->logger->error($this->getCallerName() . ' thew an exception: ' . $e->getMessage(), $this->logContext);
			throw($e);
		}
	}

	private function exportAddressBooks($baseDir, $timestampPrefix) {
		$backupDir = $baseDir . '/' . $timestampPrefix . 'contacts';
		$this->createDirectory($backupDir);

		// TODO: let user decide which address book to backup
		$statement = $this->db->prepareQuery(
			"SELECT id, uri, principaluri FROM oc_addressbooks;"
		);

		$statement->execute(array());

		while ($row = $statement->fetch()) {
			$this->exportAddressBook(
				$backupDir,
				$row['id'],
				$row['principaluri'],
				$row['uri']);
		}
	}

	private function exportAddressBook($backupDir, $id, $principalUri, $bookUri) {
		$statement = $this->db->prepareQuery(
			"SELECT carddata FROM oc_cards WHERE addressbookid = ?;"
		);

		$statement->execute(array($id));

		$vcfData = '';
		while ($row = $statement->fetch()) {
			$vcfData .= trim($row['carddata']) . "\n\n";
		}

		$filename =
			$this->sanitizeFilename($principalUri) . '__' .
			$this->sanitizeFilename($bookUri) . '.vcf';
		file_put_contents($backupDir . '/' . $filename, $vcfData);
	}

	private function exportCalendars($baseDir, $timestampPrefix) {
		$backupDir = $baseDir . '/' . $timestampPrefix . 'calendars';
		$this->createDirectory($backupDir);

		// TODO: let user decide which calendars to backup
		$statement = $this->db->prepareQuery(
			"SELECT id, uri, principaluri FROM oc_calendars;"
		);

		$statement->execute(array());

		while ($row = $statement->fetch()) {
			$this->exportCalendar(
				$backupDir,
				$row['id'],
				$row['principaluri'],
				$row['uri']);
		}
	}

	private function exportCalendar($backupDir, $id, $principalUri, $calendarUri) {
		$icalData =
"BEGIN:VCALENDAR
PRODID:-//Nextcloud calendar v1.4.1
VERSION:2.0
CALSCALE:GREGORIAN\n";

		$statement = $this->db->prepareQuery(
			"SELECT calendardata
				FROM oc_calendarobjects
				WHERE calendarid = ?
					AND componenttype = 'VEVENT';"
		);

		$statement->execute(array($id));

		while ($row = $statement->fetch()) {
			$matches = array();
			preg_match_all("/^BEGIN:VEVENT.*^END:VEVENT/sm", $row['calendardata'], $matches);
			$icalData .= $matches[0][0] . "\n";
		}

		$icalData .= 'END:VCALENDAR';

		$filename =
			$this->sanitizeFilename($principalUri) . '__' .
			$this->sanitizeFilename($calendarUri) . '.ics';
		file_put_contents($backupDir . '/' . $filename, $icalData);
	}

	/**
	 * Returns the id of the user or the name of the app if no user is present
	 * (for example in a cronjob)
	 *
	 * @return string
	 */
	private function getCallerName() {
		return is_null($this->userId) ? $this->appName : 'user ' . $this->userId;
	}

	private function sanitizeFilename($filename) {
		return str_replace(array('/'), '_', $filename);
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
