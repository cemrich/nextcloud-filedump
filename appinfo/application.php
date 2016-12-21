<?php

namespace OCA\FileDump\AppInfo;

use OCP\AppFramework\App;
use OCA\GitExport\Controller\AdminController;

class Application extends App
{
	public function __construct(array $urlParams = [])
	{
		parent::__construct('filedump', $urlParams);
	}

	public function registerSettings() {
		\OCP\App::registerAdmin('filedump', 'admin');
	}
}
