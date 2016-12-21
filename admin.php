<?php

namespace OCA\FileDump;

use OCA\FileDump\AppInfo\Application;

$app = new Application();
$container = $app->getContainer();
$response = $container->query('\OCA\FileDump\Controller\AdminController')->index();
return $response->render();
