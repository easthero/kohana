<?php
defined('SYSPATH') OR die('No direct access allowed.');

class ResponseEmail_Controller extends SmartgwtController {

	const ALLOW_PRODUCTION = TRUE;

	function fetch(){
		$objects = array();
		$data = array();

		$responseemail_orm = ORM::factory('responseemail');

		if(isset($this->req->data->id)){
			$responseemail_orm = $responseemail_orm->where('id',$this->req->data->id);
		}else{
			$responseemail_orm = $responseemail_orm->where('deleted', 0);
		}

		$objects = $responseemail_orm->find_all();

		foreach($objects as $object){
			$data[] = array(
					'id' => $object->id,
					'name' => $object->name,
					'email' => $object->email,
					'smtp_ip' => $object->smtp_ip,
					'smtp_user' => $object->smtp_user,
					'smtp_password' => $object->smtp_password,
					);
		}

		$count = count($data);

		return $this->respFetch($this->req->startRow,$this->req->startRow+$count,$count,$data);
	}

	function fetchall(){
		$objects = array();
		$data = array();

		$responseemail_orm = ORM::factory('responseemail');

		if(isset($this->req->data->id)){
			$responseemail_orm = $responseemail_orm->where('id',$this->req->data->id);
		}

		$objects = $responseemail_orm->find_all();

		foreach($objects as $object){
			$data[] = array(
					'id' => $object->id,
					'name' => $object->name,
					'email' => $object->email,
					'smtp_ip' => $object->smtp_ip,
					'smtp_user' => $object->smtp_user,
					'smtp_password' => $object->smtp_password,
					);
		}

		$count = count($data);

		return $this->respFetch($this->req->startRow,$this->req->startRow+$count,$count,$data);
	}

	function add(){
		$responseemail_orm = ORM::factory('responseemail')->where('name',$this->req->data->name)->find();

		if($responseemail_orm->loaded){
			$this->respNameError();	
			exit;
		}

		$responseemail_orm = ORM::factory('responseemail');

		foreach($this->req->data as $key => $value){
			$responseemail_orm->$key = $value;
		}

		$responseemail_orm->save();

		if($responseemail_orm->saved){
			$id = $responseemail_orm->id;
			$result = 0;
			$response_data[] = array_merge(array("id" => $id),$this->req->data_array);
			$this->respOk($response_data);
		}else{
			$id = 0;
			$result = -1;
			$message = '添加email响应失败，未知的错误';
			$this->respFailed($message);
		}

		$this->operationlog_add($result,$id,$this->req->data->name,$this->req->data);

		return;
	}

	function update(){
		$id = $this->get_id();
		$response_data = $this->get_data_array();

		if(isset($this->req->data->name)){	
			$responseemail_orm = ORM::factory('responseemail')->where('name',$this->req->data->name)->find();

			if($responseemail_orm->loaded){
				$this->respNameError();	
				exit;
			}
		}

		$db = Database::instance();
		$db->query('begin');

		$responseemail_orm = ORM::factory('responseemail',$id);

		if($responseemail_orm->loaded){
			$using = $this->is_using("responseemail",$id);

			foreach($this->req->data as $key => $value){
				$responseemail_orm->$key = $value;
			}

			$responseemail_orm->save();

			if($responseemail_orm->saved){
				if($using){
					$pb = new Protobuf();
					$result = $pb->sendconfig($db);

					if($result['status'] === 0){
						$db->query('commit');
						$result = 0;
						$this->respOk($response_data);
					}else{
						$db->query('rollback');
						$result = -1;
						if($result['message']){
							$message = "规则下发失败，失败原因:". $result['message'];
						}else{
							$message = "规则下发失败";
						}

						$this->respFailed($message);
					}
				}else{
					$db->query('commit');
					$result = 0;
					$this->respOk($response_data);
				}
			}else{
				$result = -1;
				$message = '修改email响应失败，未知的错误';
				$this->respFailed($message);
			}

			$diff_data = $this->update_data_diff($this->req->data_array,$this->req->oldValues_array);
			$this->operationlog_update($result,$id,$this->req->oldValues->name,$diff_data);
		}else{
			$message = '修改email响应失败，未知的email响应ID';
			$this->respFailed($message);
		}
		return;
	}

	function remove(){
		$id = $this->get_id();
		$response_data[] = $this->req->data_array;

		$responseemail_orm = ORM::factory('responseemail')->where(array('id' => $id,'deleted' => 0))->find();

		if($responseemail_orm->loaded){
			$using = $this->is_using("responseemail",$id);

			if($using){
				$message = "当前email响应正在使用中，现在不能删除";
				$this->respFailed($message);
			}else{
				$responseemail_orm->deleted = 1;
				$oldname = $responseemail_orm->name;
				$name = $responseemail_orm->name . "@" . sprintf("%x",time());
				$responseemail_orm->name = $name;
				$responseemail_orm->save();

				if($responseemail_orm->saved){
					$result = 0;
					$this->respOk($response_data);
				}else{
					$result = -1;
					$message = '删除email响应失败，未知的错误';
					$this->respFailed($message);
				}

				$this->operationlog_remove($result,$id,$oldname);
			}
		}else{
			$message = '删除email响应失败，未知的email响应ID';
			$this->respFailed($message);
		}

		return;
	}
}
?>
