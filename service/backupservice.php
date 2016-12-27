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
			$baseDir = $this->configService->getBackupBaseDirectory();
			$this->exportContacts($baseDir);
			$this->exportCalendars($baseDir);
			// TODO: export TODOs
		} catch (Exception $e) {
			$this->logger->error($this->getCallerName() . ' thew an exception: ' . $e->getMessage(), $this->logContext);
			throw($e);
		}
	}

	private function exportContacts($baseDir) {
		// TODO: add timestamp to directory and delete old directories
		$backupDir = $baseDir . '/contacts';
		$this->createDirectory($backupDir);

		// TODO: let user decide which address book to backup
		$statement = $this->db->prepareQuery(
			"SELECT
				carddata,
				principaluri,
				oc_cards.uri AS cards_uri,
				oc_addressbooks.uri AS address_book_uri,
				value AS fullname
			FROM oc_cards, oc_cards_properties, oc_addressbooks
			WHERE oc_cards.id = oc_cards_properties.cardid
				AND oc_addressbooks.id = oc_cards.addressbookid
				AND name = 'FN'
			ORDER BY fullname;"
		);

		$statement->execute(array());

		while ($row = $statement->fetch()) {
			$filename = $this->sanitizeFilename("{$row['fullname']} - {$row['cards_uri']}");
			$directory = $backupDir . '/' . $this->sanitizeFilename($row['principaluri']) . ' - ' . $row['address_book_uri'];
			$this->createDirectory($directory);
			file_put_contents($directory . '/' .$filename, $row['carddata']);
		}
	}

	private function exportCalendars($baseDir) {
		// TODO: add timestamp to directory and delete old directories
		$backupDir = $baseDir . '/calendars';
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
		// create backup dir for user
		$directory = $backupDir . '/' . $this->sanitizeFilename($principalUri);
		$this->createDirectory($directory);

		// save as ical
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

		$filename = $this->sanitizeFilename($calendarUri) . '.ics';
		$icalData .= 'END:VCALENDAR';
		file_put_contents($directory . '/' . $filename, $icalData);
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
