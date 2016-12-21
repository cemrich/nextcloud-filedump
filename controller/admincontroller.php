<?php
namespace OCA\FileDump\Controller;

use OCP\IRequest;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Controller;

class AdminController extends Controller {
	public function __construct($AppName, IRequest $request){
		parent::__construct($AppName, $request);
	}

	public function index() {
		return new TemplateResponse($this->appName, 'admin', [], 'blank');
	}
}
