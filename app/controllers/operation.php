<?php
defined('SYSPATH') OR die('No direct access allowed.');

class Operation_Controller extends SmartgwtController {

	const ALLOW_PRODUCTION = TRUE;

	function fetch(){
		$count = 0;
		$objects = array();
		$data = array();

		$operation_orm = ORM::factory('operation');

		if (isset($this->req->data->id)) {
			$operation_orm = $operation_orm->where('id',$this->req->data->id);
		}else{
			$operation_orm = $operation_orm->where('log',1); 
		}

		$objects = $operation_orm->find_all();

		foreach($objects as $object){
			$count++;
			$data[] = array(
					'id' => $object->id,
					'name' => $object->name,
					'roles' => $object->roles,
					'operation' => $object->operation
					);
		}

		$this->respFetch($this->req->startRow,$this->req->startRow+$count,$count,$data);
	}
}
?>
