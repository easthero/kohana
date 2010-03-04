<?php
defined('SYSPATH') OR die('No direct access allowed.');

class Auditsite_Controller extends SmartgwtController {

	const ALLOW_PRODUCTION = TRUE;
	
	function fetch(){
		$objects = array();
		$data = array();

		$auditsite_orm = ORM::factory('auditsite');

		if (isset($this->req->data->id)) {
			$auditsite_orm = $auditsite_orm->where('id',$this->req->data->id);
		}else{
			$auditsite_orm = $auditsite_orm->where('deleted', 0); 
		}

		$objects = $auditsite_orm->find_all();

		foreach($objects as $object){
			$data[] = array(
					'id' => $object->id,
					'name' => $object->name,
					'sensor_id' => $object->sensor_id, 
					'active' => $object->active,
					);
		}

		$count = count($data);
		
		$this->respFetch($this->req->startRow,$this->req->startRow+$count,$count,$data);
	}

	function fetchall(){
		$objects = array();
		$data = array();

		$auditsite_orm = ORM::factory('auditsite');

		if (isset($this->req->data->id)) {
			$auditsite_orm = $auditsite_orm->where('id',$this->req->data->id);
		}

		$objects = $auditsite_orm->find_all();

		foreach($objects as $object){
			$data[] = array(
					'id' => $object->id,
					'name' => $object->name,
					'sensor_id' => $object->sensor_id, 
					'active' => $object->active,
					);
		}

		$count = count($data);

		$this->respFetch($this->req->startRow,$this->req->startRow+$count,$count,$data);
	}

	function add(){
		$auditsite_orm = ORM::factory('auditsite')->where('name',$this->req->data->name)->find();

		if($auditsite_orm->loaded){
			$this->respNameError();	
			exit;
		}

		$auditsite_orm = ORM::factory('auditsite');

		foreach($this->req->data as $key => $value){
			$auditsite_orm->$key = $value;
		}

		$auditsite_orm->save();

		if($auditsite_orm->saved){
			$id = $auditsite_orm->id;
			$result = 0;
			$response_data[] = array_merge(array("id" => $id),$this->req->data_array);		
			$this->respOk($response_data);			
		}else{
			$id = 0;
			$result = -1;
			$message = '添加应用失败，未知的错误';
			$this->respFailed($message);
		}	

		$this->operationlog_add($result,$id,$this->req->data->name,$this->req->data);	

		return;
	}

	function update(){
		$id = $this->get_id();
		$response_data = $this->get_data_array();

		if(isset($this->req->data->name)){	
			$auditsite_orm = ORM::factory('auditsite')->where('name',$this->req->data->name)->find();

			if($auditsite_orm->loaded){
				$this->respNameError();	
				exit;
			}
		}

		$auditsite_orm = ORM::factory('auditsite',$id);

		if($auditsite_orm->loaded){			
			foreach($this->req->data as $key => $value){
				$auditsite_orm->$key = $value;
			}

			$auditsite_orm->save();

			if($auditsite_orm->saved){
				$result = 0;
				$this->respOk($response_data);
			}else{
				$result = -1;
				$message = '修改应用失败，未知的错误';
				$this->respFailed($message);
			}

			$diff_data = $this->update_data_diff($this->req->data_array,$this->req->oldValues_array);
			$this->operationlog_update($result,$id,$this->req->oldValues->name,$diff_data);
		}else{
			$message = '修改应用失败，未知的应用ID';
			$this->respFailed($message);
		}	
		return;
	}

	function remove(){
		$id = $this->get_id();

		$response_data[] = $this->req->data_array;

		$auditsite_orm = ORM::factory('auditsite')->where(array('id' => $id ,'deleted' => 0))->find();

		if($auditsite_orm->loaded){
			$auditsite_orm->deleted = 1;
			$oldname = $auditsite_orm->name;
			$name = $auditsite_orm->name . "@" . sprintf("%x",time());
			$auditsite_orm->name = $name;
			$auditsite_orm->save();

			if($auditsite_orm->saved){
				$result = 0;
				$this->respOk($response_data);			
			}else{
				$result = -1;
				$message = '删除应用失败，未知的错误';
				$this->respFailed($message);
			}	

			$this->operationlog_remove($result,$id,$oldname);
		}else{
			$message = '删除应用失败，未知的应用ID';
			$this->respFailed($message);
		}

		return;
	}
}
?>
