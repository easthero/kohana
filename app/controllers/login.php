<?php defined('SYSPATH') OR die('No direct access allowed.');

class Login_Controller extends NonLoginController {

	const ALLOW_PRODUCTION = TRUE;

	function index(){
		if($this->session->get('username')){
			header('Location: main');	
		}else{
			$login_view = new View('login');
			$login_view->render(TRUE);
		}
	}

	function validate(){
		$post = new Validation($_POST);
		$post->add_rules('username', 'required');
		$post->add_rules('password', 'required');

		if(! $post->validate()){
			echo '必须填写用户名和密码';
			return;
		}

		$username = $_POST['username'];
		$password = $_POST['password'];

		$user_orm = ORM::factory('user')->where(array('name' => $username, 'password' => sha1($password) ))->find();

		if($user_orm->loaded){
			$id = $user_orm->id;
			if($user_orm->active == 1){
				$this->session->set('user_id',$id);
				$this->session->set('username',$username);
				$this->session->set('role_id',$user_orm->role_id);

				respOk(array());
			}else{
				$message = "登录失败，用户处于禁止状态";
				respFailed($message);
			}

		}else{
			$message = "登录失败，用户名或密码错误";			
			respFailed($message);
		}

		return;
	}

	function out(){
		$this->session->destroy();
		$logoutURL = Kohana::config('core.site_domain') . "/login";
		header("Location: $logoutURL");
	}
}
?>
