<?php defined('SYSPATH') or die('No direct script access.');
require_once kohana::find_file('helpers','response');

class NonLoginController_Core extends Controller {
	function __construct(){
		parent::__construct();
		$this->session = Session::instance();
	}
	
}
?>


