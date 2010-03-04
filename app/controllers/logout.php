<?php defined('SYSPATH') OR die('No direct access allowed.');

class Logout_Controller extends SmartgwtController{

	const ALLOW_PRODUCTION = TRUE;

	function index(){
		$result = 0;
		$message = "注销系统";
		$this->operationlog_misc($result,$this->session->get('user_id'),$this->session->get('username'),$message);

		$this->session->destroy();	

		$data = array();
		$this->respOk($data);
	}
}
?>
