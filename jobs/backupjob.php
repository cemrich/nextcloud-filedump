<?php

namespace OCA\FileDump\Jobs;

use OC\BackgroundJob\TimedJob;
use OCA\FileDump\AppInfo\Application;
use OCA\FileDump\Service\BackupService;
use OCP\App;

class BackupJob extends TimedJob {

	public function __construct(){
		$this->setInterval(60 * 60 * 24); // run once every day
	}

	/**
	 * @param array $arguments
	 */
	public function run($arguments) {
		if (!App::isEnabled('filedump')){
			return;
		}

		$app = new Application();
		$container = $app->getContainer();

		/** @var BackupService $backupService */
		$backupService = $container->query('OCA\FileDump\Service\BackupService');
		$backupService->createDBBackup();
	}
}
