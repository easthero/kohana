<?php
defined('SYSPATH') OR die('No direct access allowed.');

class ResponseSyslog_Controller extends SmartgwtController {

	const ALLOW_PRODUCTION = TRUE;

	function fetch(){
		$objects = array();
		$data = array();

		$responsesyslog_orm = ORM::factory('responsesyslog');

		if(isset($this->req->data->id)){
			$responsesyslog_orm = $responsesyslog_orm->where('id',$this->req->data->id);
		}else{
			$responsesyslog_orm = $responsesyslog_orm->where('deleted', 0);
		}

		$objects = $responsesyslog_orm->find_all();

		foreach($objects as $object){
			$data[] = array(
					'id' => $object->id,
					'name' => $object->name,
					'syslogd_ip' => $object->syslogd_ip,
					'syslogd_port' => $object->syslogd_port,
					'ident' => $object->ident,
					'facility' => $object->facility,
					);
		}

		$count = count($data);

		return $this->respFetch($this->req->startRow,$this->req->startRow+$count,$count,$data);
	}

	function fetchall(){
		$objects = array();
		$data = array();

		$responsesyslog_orm = ORM::factory('responsesyslog');

		if(isset($this->req->data->id)){
			$responsesyslog_orm = $responsesyslog_orm->where('id',$this->req->data->id);
		}

		$objects = $responsesyslog_orm->find_all();

		foreach($objects as $object){
			$data[] = array(
					'id' => $object->id,
					'name' => $object->name,
					'syslogd_ip' => $object->syslogd_ip,
					'syslogd_port' => $object->syslogd_port,
					'ident' => $object->ident,
					'facility' => $object->facility,
					);
		}

		$count = count($data);

		return $this->respFetch($this->req->startRow,$this->req->startRow+$count,$count,$data);
	}

	function add(){
		$responsesyslog_orm = ORM::factory('responsesyslog')->where('name',$this->req->data->name)->find();

		if($responsesyslog_orm->loaded){
			$this->respNameError();	
			exit;
		}

		$responsesyslog_orm = ORM::factory('responsesyslog');

		foreach($this->req->data as $key => $value){
			$responsesyslog_orm->$key = $value;
		}

		$responsesyslog_orm->save();

		if($responsesyslog_orm->saved){
			$id = $responsesyslog_orm->id;
			$result = 0;
			$response_data[] = array_merge(array("id" => $id),$this->req->data_array);
			$this->respOk($response_data);
		}else{
			$id = 0;
			$result = -1;
			$message = '添加syslog响应失败，未知的错误';
			$this->respFailed($message);
		}

		$this->operationlog_add($result,$id,$this->req->data->name,$this->req->data);

		return;
	}

	function update(){
		$id = $this->get_id();
		$response_data = $this->get_data_array();

		if(isset($this->req->data->name)){	
			$responsesyslog_orm = ORM::factory('responsesyslog')->where('name',$this->req->data->name)->find();

			if($responsesyslog_orm->loaded){
				$this->respNameError();	
				exit;
			}
		}

		$db = Database::instance();
		$db->query('begin');

		$responsesyslog_orm = ORM::factory('responsesyslog',$id);

		if($responsesyslog_orm->loaded){
			$using = $this->is_using("responsesyslog",$id);

			foreach($this->req->data as $key => $value){
				$responsesyslog_orm->$key = $value;
			}

			$responsesyslog_orm->save();

			if($responsesyslog_orm->saved){
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
				$message = '修改syslog响应失败，未知的错误';
				$this->respFailed($message);
			}

			$diff_data = $this->update_data_diff($this->req->data_array,$this->req->oldValues_array);
			$this->operationlog_update($result,$id,$this->req->oldValues->name,$diff_data);
		}else{
			$message = '修改syslog响应失败，未知的syslog响应ID';
			$this->respFailed($message);
		}
		return;
	}

	function remove(){
		$id = $this->get_id();
		$response_data[] = $this->req->data_array;

		$responsesyslog_orm = ORM::factory('responsesyslog')->where(array('id' => $id ,'deleted' => 0))->find();

		if($responsesyslog_orm->loaded){
			$using = $this->is_using("responsesyslog",$id);

			if($using){
				$message = "当前syslog响应正在使用中，现在不能删除";
				$this->respFailed($message);
			}else{	
				$responsesyslog_orm->deleted = 1;
				$oldname = $responsesyslog_orm->name;
				$name = $responsesyslog_orm->name . "@" . sprintf("%x",time());
				$responsesyslog_orm->name = $name;
				$responsesyslog_orm->save();

				if($responsesyslog_orm->saved){
					$result = 0;
					$this->respOk($response_data);
				}else{
					$result = -1;
					$message = '删除syslog响应失败，未知的错误';
					$this->respFailed($message);
				}

				$this->operationlog_remove($result,$id,$oldname);
			}
		}else{
			$message = '删除syslog响应失败，未知的syslog响应ID';
			$this->respFailed($message);
		}

		return;
	}
}
?>
