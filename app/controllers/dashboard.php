<?php
defined('SYSPATH') OR die('No direct access allowed.');

class Dashboard_Controller extends SmartgwtController {

	const ALLOW_PRODUCTION = TRUE;
	
	function index(){
		$view = new View('dashboard');
		$view->render(TRUE);
	}
}
?>
