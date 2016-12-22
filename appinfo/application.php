<?php

namespace OCA\FileDump\AppInfo;

use OCP\AppFramework\App;
use OCA\FileDump\Controller\AdminController;

class Application extends App {

	public function __construct(array $urlParams = []) {
		parent::__construct('filedump', $urlParams);

		$container = $this->getContainer();
		$container->registerService('AdminController', function($c) {
			return new AdminController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query('BackupService')
			);
		});
	}

	public function registerSettings() {
		\OCP\App::registerAdmin('filedump', 'admin');
	}
}
