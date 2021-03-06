<?php defined('SYSPATH') or die('No direct script access.');
require_once kohana::find_file('helpers','response');

class MainController_Core extends Controller {
	function __construct(){
		parent::__construct();

		$this->session = Session::instance();

		if (!$this->session->get('username')){
			header('Location: login');	
		}

		$this->username = $this->session->get('username');
		$this->role_id = $this->session->get('role_id');
	}
}
?>
