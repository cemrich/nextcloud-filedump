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
			$this->exportCalendars();
			// TODO: export TODOs
		} catch (Exception $e) {
			$this->logger->error($this->getCallerName() . ' thew an exception: ' . $e->getMessage(), $this->logContext);
			throw($e);
		}
	}

	private function exportContacts() {
		// TODO: add timestamp to directory and delete old directories
		$backupDir = $this->configService->getBackupBaseDirectory() . '/contacts';
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

	private function exportCalendars() {
		// TODO: add timestamp to directory and delete old directories
		$backupDir = $this->configService->getBackupBaseDirectory() . '/calendars';
		$this->createDirectory($backupDir);

		// TODO: let user decide which calendars to backup
		$statement = $this->db->prepareQuery(
			"SELECT
					calendardata,
					firstoccurence,
					principaluri,
					oc_calendarobjects.uri AS object_uri,
					oc_calendars.uri AS calendar_uri
				FROM oc_calendarobjects, oc_calendars
				WHERE oc_calendars.id = oc_calendarobjects.calendarid
					AND componenttype = 'VEVENT';"
		);

		$calendars = array();

		$statement->execute(array());
		while ($row = $statement->fetch()) {
			$uri = $row['calendar_uri'];

			if (!isset($calendars[$uri])) {
				$directory = $backupDir . '/' . $this->sanitizeFilename($row['principaluri']);
				$this->createDirectory($directory);

				$calendars[$uri]['directory'] = $directory;
				$calendars[$uri]['data'] =
"BEGIN:VCALENDAR
PRODID:-//Nextcloud calendar v1.4.1
VERSION:2.0
CALSCALE:GREGORIAN\n";
			}

			$data = array_slice(explode("\n", $row['calendardata']), 4, -1);
			$calendars[$uri]['data'] .= join("\n", $data);
		}

		foreach ($calendars as $uri => $calendar) {
			$filename = $this->sanitizeFilename($uri) . '.ics';
			$data = $calendar['data'] . "\nEND:VCALENDAR";
			file_put_contents($calendar['directory'] . '/' . $filename, $data);
		}
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
