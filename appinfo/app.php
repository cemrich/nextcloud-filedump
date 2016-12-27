<?php

namespace OCA\FileDump\AppInfo;

use OCA\FileDump\Jobs\BackupJob;

$app = new Application();
$app->registerSettings();

\OC::$server->getJobList()->add(new BackupJob());
