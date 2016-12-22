<?php
namespace OCA\FileDump\Controller;

use OCP\IRequest;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;

class AdminController extends Controller {

	public function __construct($AppName, IRequest $request){
		parent::__construct($AppName, $request);
	}

	public function index() {
		return new TemplateResponse($this->appName, 'admin', [], 'blank');
	}

	/**
	 * Creates a new backup
	 *
	 * @return DataResponse
	 */
	public function doCreateBackup() {
		$message = "A new backup has been created.";
		try {
			// $this->backupService->createDBBackup();
		} catch(\Exception $e) {
			$message = "Could not create backup: " . $e->getMessage();
		}

		return new DataResponse([
			'message' => $message
		]);
	}
}
