<?php
defined('SYSPATH') OR die('No direct access allowed.');

class Welcome_Controller extends Controller {
	function index(){
		$welcome_view = new View('welcome');
		$welcome_view->render(TRUE);
	}

}

?>
