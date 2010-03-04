<?php
defined('SYSPATH') OR die('No direct access allowed.');

class Ipgroup_Controller extends SmartgwtController {

	const ALLOW_PRODUCTION = TRUE;

	function fetch(){
		$objects = array();
		$data = array();

		$ipgroup_orm = ORM::factory('ipgroup');

		if(isset($this->req->data->id)){
			$ipgroup_orm = $ipgroup_orm->where('id',$this->req->data->id);
		}else{
			$ipgroup_orm = $ipgroup_orm->where('deleted', 0);
		}

		$objects = $ipgroup_orm->find_all();

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

		$ipgroup_orm = ORM::factory('ipgroup');

		if(isset($this->req->data->id)){
			$ipgroup_orm = $ipgroup_orm->where('id',$this->req->data->id);
		}

		$objects = $ipgroup_orm->find_all();

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
		$ipgroup_orm = ORM::factory('ipgroup')->where('name',$this->req->data->name)->find();

		if($ipgroup_orm->loaded){
			$this->respNameError();	
			exit;
		}

		$ipgroup_orm = ORM::factory('ipgroup');

		foreach($this->req->data as $key => $value){
			$ipgroup_orm->$key = $value;
		}

		$ipgroup_orm->save();

		if($ipgroup_orm->saved){
			$id = $ipgroup_orm->id;
			$result = 0;
			$response_data[] = array_merge(array("id" => $id),$this->req->data_array);
			$this->respOk($response_data);
		}else{
			$id = 0;
			$result = -1;
			$message = '添加IP组失败，未知的错误';
			$this->respFailed($message);
		}

		$this->operationlog_add($result,$id,$this->req->data->name,$this->req->data);

		return;
	}

	function update(){
		$id = $this->get_id();
		$response_data = $this->get_data_array();

		if(isset($this->req->data->name)){	
			$ipgroup_orm = ORM::factory('ipgroup')->where('name',$this->req->data->name)->find();

			if($ipgroup_orm->loaded){
				$this->respNameError();	
				exit;
			}
		}

		$db = Database::instance();
		$db->query('begin');

		$ipgroup_orm = ORM::factory('ipgroup',$id);

		if($ipgroup_orm->loaded){
			$using = $this->is_using("ipgroup",$id);

			foreach($this->req->data as $key => $value){
				$ipgroup_orm->$key = $value;
			}

			$ipgroup_orm->save();

			if($ipgroup_orm->saved){
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
				$message = '修改IP组失败，未知的错误';
				$this->respFailed($message);
			}

			$diff_data = $this->update_data_diff($this->req->data_array,$this->req->oldValues_array);
			$this->operationlog_update($result,$id,$this->req->oldValues->name,$diff_data);
		}else{
			$message = '修改IP组失败，未知的IP组ID';
			$this->respFailed($message);
		}
		return;
	}

	function remove(){
		$id = $this->get_id();
		$response_data[] = $this->req->data_array;

		$ipgroup_orm = ORM::factory('ipgroup')->where(array('id' => $id, 'deleted' => 0))->find();

		if($ipgroup_orm->loaded){
			$using = $this->is_using("ipgroup",$id);

			if($using){
				$message = "当前IP组正在使用中，现在不能删除";
				$this->respFailed($message);
			}else{
				$ipgroup_orm->deleted = 1;
				$oldname = $ipgroup_orm->name;
				$name = $ipgroup_orm->name . "@" . sprintf("%x",time());
				$ipgroup_orm->name = $name;
				$ipgroup_orm->save();

				if($ipgroup_orm->saved){
					$result = 0;
					$this->respOk($response_data);
				}else{
					$result = -1;
					$message = '删除IP组失败，未知的错误';
					$this->respFailed($message);
				}

				$this->operationlog_remove($result,$id,$oldname);
			}
		}else{
			$message = '删除IP组失败，未知的IP组ID';
			$this->respFailed($message);
		}

		return;
	}
}
?>
