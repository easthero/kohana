<?php defined('SYSPATH') or die('No direct script access.');
class MainController_Core extends Controller {
	function __construct(){
		parent::__construct();

		$this->session = Session::instance();

		if (!$this->session->get('username')){
			header('Location: login');	
		}
	}
}
?>
