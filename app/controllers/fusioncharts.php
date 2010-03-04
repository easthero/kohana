<?php
defined('SYSPATH') OR die('No direct access allowed.');

class Fusioncharts_Controller extends Controller {

	const ALLOW_PRODUCTION = TRUE;

	function __construct(){
		parent::__construct();
	}

	function index(){
		$view = new View('dashboard');
		$view->render(TRUE);


	}
}
?>
