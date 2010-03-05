<?php
defined('SYSPATH') OR die('No direct access allowed.');

class Setting_Controller extends MainController {

	const ALLOW_PRODUCTION = TRUE;

	function index(){
		$setting_view = new View('setting');
		$setting_view->render(TRUE);
	}
}
?>
			
