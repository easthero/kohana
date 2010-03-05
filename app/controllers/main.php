<?php defined('SYSPATH') OR die('No direct access allowed.');

class Main_Controller extends MainController {

	const ALLOW_PRODUCTION = TRUE;

	function index(){
		$main_view = new View('main');
		$main_view->set_global('username', $this->username);
		$main_view->set_global('role_id', $this->role_id);
		$main_view->render(TRUE);
	}

	function systemmenu(){
		echo '系统设置';
	}

	function usermenu(){
		echo '用户设置';
	}

	function reportmenu(){
		echo '报表';
	}
}

