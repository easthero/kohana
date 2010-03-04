<?php
defined('SYSPATH') OR die('No direct access allowed.');

class User_Controller extends SmartgwtController {

	const ALLOW_PRODUCTION = TRUE;

	function fetch(){
		$objects = array();
		$data = array();

		$user_orm = ORM::factory('user');

		if(isset($this->req->data->id)){
			$user_orm = $user_orm->where('id',$this->req->data->id);
		}else{
			if( isset($this->req->data->role_id) ){
				$user_orm = $user_orm->where(array('role_id' => $this->req->data->role_id));  
			}

			$user_orm = $user_orm->where('deleted', 0); 
		}

		$objects = $user_orm->find_all();

		foreach($objects as $object){
			$user_data = array(
					'id' => $object->id,
					'name' => $object->name,
					'role_id' => $object->role_id,
					//					'restrict_ip' => $object->restrict_ip,
					//					'permited_ip' => $object->permited_ip,
					'active' => $object->active,
					);
			if($object->new_password !== ''){
				$data[] = array_merge($user_data,array('review' => 1));
			}else{
				$data[] = array_merge($user_data,array('review' => 0));
			}
		}

		$count = count($data);

		return $this->respFetch($this->req->startRow,$this->req->startRow+$count,$count,$data);
	}

	function add(){
		$user_orm = ORM::factory('user')->where('name',$this->req->data->name)->find();

		if($user_orm->loaded){
			$this->respNameError();	
			exit;
		}

		$user_orm = ORM::factory('user');

		$this->req->data->password = sha1($this->req->data->password);

		foreach($this->req->data as $key => $value){
			$user_orm->$key = $value;
		}

		$user_orm->role_id = 1;

		$user_orm->save();

		if($user_orm->saved){
			$id = $user_orm->id;
			$result = 0;
			$response_data[] = array("id" => $id, "name" => $this->req->data->name);
			$this->respOk($response_data);
		}else{
			$id = 0;
			$result = -1;
			$message = '添加用户失败，未知的错误';
			$this->respFailed($message);
		}

		$this->operationlog_add($result,$id,$this->req->data->name,$this->req->data);

		return;
	}

	/*
	 * 管理员修改所有用户密码的方法
	 */
	function update(){
		$id = $this->get_id();
		$response_data = array('id' => $id);

		$user_orm = ORM::factory('user',$id);

		if($user_orm->loaded){
			$name = $user_orm->name;
			$user_orm->new_password = sha1($this->req->data->new_password);

			$user_orm->save();

			if($user_orm->saved){
				$result = 0;
				$this->respOk($response_data);
				$message = "修改用户 $name 的密码成功";
			}else{
				$result = -1;
				$message = "修改用户 $name 的密码失败";
				$this->respFailed($message);
			}

			$this->operationlog_misc($result,$id,$name,$message);
		}else{
			$this->respOk($response_data);
		}
		return;
	}

	function remove(){
		$id = $this->get_id();
		$response_data[] = $this->req->data_array;

		$user_orm = ORM::factory('user')->where(array('id' => $id , 'deleted' => 0))->find();

		if($user_orm->loaded){
			$user_orm->deleted = 1;
			$oldname = $user_orm->name;
			$name = $user_orm->name . "@" . sprintf("%x",time());
			$user_orm->name = $name;	
			$user_orm->save();

			if($user_orm->saved){
				$result = 0;
				$this->respOk($response_data);
			}else{
				$result = -1;
				$message = '删除用户失败';
				$this->respFailed($message);
			}

			$this->operationlog_remove($result,$id,$oldname);
		}else{
			$message = "删除用户失败，未知的用户ID";
			$this->respFailed($message);
		}

		return;
	}

	function changepwd(){		
		$user_orm = ORM::factory('user')->where(array('name' => $this->session->get('username') , 'password' => sha1($this->req->data->old_password)))->find();

		$return_data = array('name',$this->session->get('username'));		

		if($user_orm->loaded){
			$id = $user_orm->id;
			$user_orm->password = sha1($this->req->data->new_password);		
			$user_orm->save();

			if($user_orm->saved){
				$this->respOk($return_data);
				$result = 0;
				$message = "修改密码成功";
			}else{
				$result = -1;
				$message = "修改密码失败,未知的原因";
				$this->respFailed($message);
			}
			$this->operationlog_misc($result,$id,$this->session->get('username'),$message);
		}else{
			$message = '密码不正确，请重新输入';
			$data = array_merge($return_data,array('status',-5));		
			$this->respOK($data);
		}

		return;	
	}

	function enable(){
		$user_orm = ORM::factory('user')->where('id',$this->req->data->id)->find();

		$id = $this->req->data->id;

		if($user_orm->loaded){
			$user_orm->active = 1;
			$name = $user_orm->name;

			$user_orm->save();

			if($user_orm->saved){
				$result = 0;
				$this->respOk($this->req->data_array);
				$message = "启用用户 $name 成功";			
			}else{
				$result = -1;
				$message = "启用用户 $name 失败";	
				$this->respFailed($message);		
			}
			$this->operationlog_misc($result,$id,$name,$message);
		}else{
			$id = 0;
			$message = "启用用户失败，未知的用户ID";
			$this->respFailed($message);
		}

		return;
	}

	function disable(){
		$user_orm = ORM::factory('user')->where('id',$this->req->data->id)->find();

		$id = $this->req->data->id;

		if($user_orm->loaded){
			$name = $user_orm->name;

			$user_orm->active = 0;

			$user_orm->save();

			if($user_orm->saved){
				$result = 0;
				$this->respOk($this->req->data_array);	
				$message = "禁用用户 $name 成功";					
			}else{
				$result = -1;
				$message = "禁用用户 $name 失败";	
				$this->respFailed($message);		
			}
			$this->operationlog_misc($result,$id,$name,$message);
		}else{
			$message = "禁用用户失败，未知的用户ID";
			$this->respFailed($message);
		}

		return;
	}

	function review(){
		$user_orm = ORM::factory('user')->where('id',$this->req->data->id)->find();

		$id = $this->req->data->id;

		if($user_orm->loaded){			
			$name = $user_orm->name;

			if($user_orm->new_password !== ''){
				$user_orm->password = $user_orm->new_password;
				$user_orm->new_password = '';
			}	

			$user_orm->save();

			if($user_orm->saved){
				$result = 0;
				$message = "审核用户 $name 成功";					
				$this->respOk($this->req->data_array);			
			}else{
				$result = -1;
				$message = "审核 $name 失败";	
				$this->respFailed($message);	
			}
			$this->operationlog_misc($result,$id,$name,$message);
		}else{
			$message = "审核用户失败，未知的用户ID";
			$this->respFailed($message);
		}

		return;
	}
}
?>
