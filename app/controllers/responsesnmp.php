<?php
defined('SYSPATH') OR die('No direct access allowed.');

class ResponseSnmp_Controller extends SmartgwtController {

	const ALLOW_PRODUCTION = TRUE;

	function fetch(){
		$objects = array();
		$data = array();

		$responsesnmp_orm = ORM::factory('responsesnmp');

		if(isset($this->req->data->id)){
			$responsesnmp_orm = $responsesnmp_orm->where('id',$this->req->data->id);
		}else{
			$responsesnmp_orm = $responsesnmp_orm->where('deleted', 0);
		}

		$objects = $responsesnmp_orm->find_all();

		foreach($objects as $object){
			$data[] = array(
					'id' => $object->id,
					'name' => $object->name,
					'receiver_ip' => $object->receiver_ip,
					'community' => $object->community,
					);
		}

		$count = count($data);

		return $this->respFetch($this->req->startRow,$this->req->startRow+$count,$count,$data);
	}

	function fetchall(){
		$objects = array();
		$data = array();

		$responsesnmp_orm = ORM::factory('responsesnmp');

		if(isset($this->req->data->id)){
			$responsesnmp_orm = $responsesnmp_orm->where('id',$this->req->data->id);
		}

		$objects = $responsesnmp_orm->find_all();

		foreach($objects as $object){
			$data[] = array(
					'id' => $object->id,
					'name' => $object->name,
					'receiver_ip' => $object->receiver_ip,
					'community' => $object->community,
					);
		}

		$count = count($data);

		return $this->respFetch($this->req->startRow,$this->req->startRow+$count,$count,$data);
	}

	function add(){
		$responsesnmp_orm = ORM::factory('responsesnmp')->where('name',$this->req->data->name)->find();

		if($responsesnmp_orm->loaded){
			$this->respNameError();	
			exit;
		}

		$responsesnmp_orm = ORM::factory('responsesnmp');

		foreach($this->req->data as $key => $value){
			$responsesnmp_orm->$key = $value;
		}

		$responsesnmp_orm->save();

		if($responsesnmp_orm->saved){
			$id = $responsesnmp_orm->id;
			$result = 0;
			$response_data[] = array_merge(array("id" => $id),$this->req->data_array);
			$this->respOk($response_data);
		}else{
			$id = 0;
			$result = -1;
			$message = '添加snmp响应失败，未知的错误';
			$this->respFailed($message);
		}

		$this->operationlog_add($result,$id,$this->req->data->name,$this->req->data);

		return;
	}

	function update(){
		$id = $this->get_id();
		$response_data = $this->get_data_array();

		if(isset($this->req->data->name)){	
			$responsesnmp_orm = ORM::factory('responsesnmp')->where('name',$this->req->data->name)->find();

			if($responsesnmp_orm->loaded){
				$this->respNameError();	
				exit;
			}
		}

		$db = Database::instance();
		$db->query('begin');

		$responsesnmp_orm = ORM::factory('responsesnmp',$id);

		if($responsesnmp_orm->loaded){
			$using = $this->is_using("responsesnmp",$id);

			foreach($this->req->data as $key => $value){
				$responsesnmp_orm->$key = $value;
			}

			$responsesnmp_orm->save();

			if($responsesnmp_orm->saved){
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
				$message = '修改snmp响应失败，未知的错误';
				$this->respFailed($message);
			}

			$diff_data = $this->update_data_diff($this->req->data_array,$this->req->oldValues_array);
			$this->operationlog_update($result,$id,$this->req->oldValues->name,$diff_data);
		}else{
			$message = '修改snmp响应失败，未知的snmp响应ID';
			$this->respFailed($message);
		}
		return;
	}

	function remove(){
		$id = $this->get_id();
		$response_data[] = $this->req->data_array;

		$responsesnmp_orm = ORM::factory('responsesnmp')->where(array('id' => $id,'deleted' => 0))->find();

		if($responsesnmp_orm->loaded){
			$using = $this->is_using("responsesnmp",$id);

			if($using){
				$message = "当前snmp响应正在使用中，现在不能删除";
				$this->respFailed($message);
			}else{
				$responsesnmp_orm->deleted = 1;
				$oldname = $responsesnmp_orm->name;
				$name = $responsesnmp_orm->name . "@" . sprintf("%x",time());
				$responsesnmp_orm->name = $name;
				$responsesnmp_orm->save();

				if($responsesnmp_orm->saved){
					$result = 0;
					$this->respOk($response_data);
				}else{
					$result = -1;
					$message = '删除snmp响应失败，未知的错误';
					$this->respFailed($message);
				}

				$this->operationlog_remove($result,$id,$oldname);
			}
		}else{
			$message = '删除snmp响应失败，未知的snmp响应ID';
			$this->respFailed($message);
		}

		return;
	}
}
?>
