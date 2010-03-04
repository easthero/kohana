<?php
defined('SYSPATH') OR die('No direct access allowed.');

class Querycriteria_Controller extends SmartgwtController {

	const ALLOW_PRODUCTION = TRUE;

	function fetch(){	
		$objects = array();
		$data = array();

		$querycriteria_orm = ORM::factory('querycriteria');

		if(isset($this->req->data->id)){
			$querycriteria_orm = $querycriteria_orm->where('id',$this->req->data->id);
		}else{
			$querycriteria_orm = $querycriteria_orm->where('deleted', 0);
		}

		$objects = $querycriteria_orm->find_all();

		foreach($objects as $object){
			$data[] = array(
					'id' => $object->id,
					'name' => $object->name,
					'criteria' => unserialize($object->criteria),
					);
		}

		$count = count($data);
		$startRow = 0;
		return $this->respFetch($startRow,$startRow+$count,$count,$data);
	}

	function add(){		
		$querycriteria_orm = ORM::factory('querycriteria')->where('name',$this->req->data->name)->find();

		if($querycriteria_orm->loaded){
			$this->respNameError();	
			exit;
		}

		$querycriteria_orm = ORM::factory('querycriteria');

		$querycriteria_orm->name = $this->req->data->name;
		$querycriteria_orm->criteria = serialize($this->req->data->criteria);

		$querycriteria_orm->save();

		if ($querycriteria_orm->saved){
			$id = $querycriteria_orm->id;
			$response_data[] = array_merge(array("id" => $id),$this->req->data_array);
			$this->respOk($response_data);
		}else{
			$message = '保存查询条件失败，未知的错误';
			$this->respFailed($message);
		}

		return;
	}

	function update(){
		$id = $this->get_id();
		$response_data = $this->get_data_array();

		if(isset($this->req->data->name)){	
			$querycriteria_orm = ORM::factory('querycriteria')->where('name',$this->req->data->name)->find();

			if($querycriteria_orm->loaded){
				$this->respNameError();	
				exit;
			}
		}

		$querycriteria_orm = ORM::factory('querycriteria',$id);

		if($querycriteria_orm->loaded){			
			$querycriteria_orm->name = $this->req->data->name;
			$querycriteria_orm->criteria = serialize($this->req->data->criteria);

			$querycriteria_orm->save();

			if($querycriteria_orm->saved){
				$result = 0;
				$this->respOk($response_data);
			}else{
				$message = '修改查询条件，未知的错误';
				$this->respFailed($message);
			}
		}else{
			$message = '修改查询条件，未知的查询条件ID';
			$this->respFailed($message);
		}	
		return;
	}

	function remove(){
		$id = $this->get_id();
		$response_data[] = $this->req->data_array;

		$querycriteria_orm = ORM::factory('querycriteria')->where(array('id' => $id,'deleted' => 0))->find();

		if($querycriteria_orm->loaded){
			$querycriteria_orm->deleted = 1;
			$oldname = $querycriteria_orm->name;
			$name = $querycriteria_orm->name . "@" . sprintf("%x",time());
			$querycriteria_orm->name = $name;
			$querycriteria_orm->save();

			if($querycriteria_orm->saved){
				$this->respOk($response_data);
			}else{
				$message = '删除查询条件失败，未知的错误';
				$this->respFailed($message);
			}
		}else{
			$message = '删除查询条件失败，未知的ID';
			$this->respFailed($message);
		}

		return;
	}
}

?>
