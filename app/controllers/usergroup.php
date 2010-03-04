<?php
defined('SYSPATH') OR die('No direct access allowed.');

class Usergroup_Controller extends SmartgwtController {

	const ALLOW_PRODUCTION = TRUE;

	function fetch(){
		$objects = array();
		$data = array();

		$usergroup_orm = ORM::factory('usergroup');

		if(isset($this->req->data->id)){
			$usergroup_orm = $usergroup_orm->where('id',$this->req->data->id);
		}else{
			$usergroup_orm = $usergroup_orm->where('deleted', 0);
		}

		$objects = $usergroup_orm->find_all();

		foreach($objects as $object){
			$data[] = array(
					'id' => $object->id,
					'name' => $object->name,
					'members' => $object->members,
					);
		}

		$count = count($data);

		return $this->respFetch($this->req->startRow,$this->req->startRow+$count,$count,$data);
	}

	function fetchall(){
		$objects = array();
		$data = array();

		$usergroup_orm = ORM::factory('usergroup');

		if(isset($this->req->data->id)){
			$usergroup_orm = $usergroup_orm->where('id',$this->req->data->id);
		}

		$objects = $usergroup_orm->find_all();

		foreach($objects as $object){
			$data[] = array(
					'id' => $object->id,
					'name' => $object->name,
					'members' => $object->members,
					);
		}

		$count = count($data);

		return $this->respFetch($this->req->startRow,$this->req->startRow+$count,$count,$data);
	}

	function add(){
		$usergroup_orm = ORM::factory('usergroup')->where('name',$this->req->data->name)->find();

		if($usergroup_orm->loaded){
			$this->respNameError();	
			exit;
		}

		$usergroup_orm = ORM::factory('usergroup');

		foreach($this->req->data as $key => $value){
			$usergroup_orm->$key = $value;
		}

		$usergroup_orm->save();

		if($usergroup_orm->saved){
			$id = $usergroup_orm->id;
			$result = 0;
			$response_data[] = array_merge(array("id" => $id),$this->req->data_array);
			$this->respOk($response_data);
		}else{
			$id = 0;
			$result = -1;
			$message = '添加用户组失败，未知的错误';
			$this->respFailed($message);
		}

		$this->operationlog_add($result,$id,$this->req->data->name,$this->req->data);

		return;
	}

	function update(){
		$id = $this->get_id();
		$response_data = $this->get_data_array();

		if(isset($this->req->data->name)){	
			$usergroup_orm = ORM::factory('usergroup')->where('name',$this->req->data->name)->find();

			if($usergroup_orm->loaded){
				$this->respNameError();	
				exit;
			}
		}

		$db = Database::instance();
		$db->query('begin');

		$usergroup_orm = ORM::factory('usergroup',$id);

		if($usergroup_orm->loaded){
			$using = $this->is_using("usergroup",$id);

			foreach($this->req->data as $key => $value){
				$usergroup_orm->$key = $value;
			}

			$usergroup_orm->save();

			if($usergroup_orm->saved){
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
				$message = '修改用户组失败，未知的错误';
				$this->respFailed($message);
			}

			$diff_data = $this->update_data_diff($this->req->data_array,$this->req->oldValues_array);
			$this->operationlog_update($result,$id,$this->req->oldValues->name,$diff_data);
		}else{
			$message = '修改用户组失败，未知的用户组ID';
			$this->respFailed($message);
		}
		return;
	}

	function remove(){
		$id = $this->get_id();
		$response_data[] = $this->req->data_array;

		$usergroup_orm = ORM::factory('usergroup')->where(array('id' => $id , 'deleted' => 0))->find();

		if($usergroup_orm->loaded){
			$using = $this->is_using("ipgroup",$id);

			if($using){
				$message = "当前用户组正在使用中，现在不能删除";
				$this->respFailed($message);
			}else{
				$usergroup_orm->deleted = 1;
				$oldname = $usergroup_orm->name;
				$name = $usergroup_orm->name . "@" . sprintf("%x",time());
				$usergroup_orm->name = $name;
				$usergroup_orm->save();

				if($usergroup_orm->saved){
					$result = 0;
					$this->respOk($response_data);
				}else{
					$result = -1;
					$message = '删除用户组失败，未知的错误';
					$this->respFailed($message);
				}

				$this->operationlog_remove($result,$id,$oldname);
			}
		}else{
			$message = '删除用户组失败，未知的用户组ID';
			$this->respFailed($message);
		}

		return;
	}
}
?>
