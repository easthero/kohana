<?php defined('SYSPATH') OR die('No direct access allowed.');

class Main_Controller extends MainController {

	const ALLOW_PRODUCTION = TRUE;

	function index(){
		$main_view = new View('main');
		$main_view->render(TRUE);
	}
}

