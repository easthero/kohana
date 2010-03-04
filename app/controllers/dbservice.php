<?php
defined('SYSPATH') OR die('No direct access allowed.');

class Dbservice_Controller extends SmartgwtController {

	const ALLOW_PRODUCTION = TRUE;

	function fetch(){
		$objects = array();
		$data = array();

		$dbservice_orm = ORM::factory('auditservice');

		if(isset($this->req->data->id)){
			$dbservice_orm = $dbservice_orm->where('id', $this->req->data->id);
		}else{	
			$dbservice_orm = $dbservice_orm->where(array('service_type >=' => 16 , 'service_type <=' => 32, 'deleted' => 0));

			if(isset($this->req->data->audit_site_id)) {
				$dbservice_orm = $dbservice_orm->where('audit_site_id', $this->req->data->audit_site_id);
			}

			if (isset($this->req->data->service_type)) {
				$dbservice_orm = $dbservice_orm->where('service_type', $this->req->data->service_type);
			}
		}

		$objects = $dbservice_orm->find_all();

		foreach($objects as $object){
			$data[] = array(
					'id' => $object->id,
					'audit_site_id' => $object->audit_site_id,
					'name' => $object->name,
					'service_type' => $object->service_type,
					'ip' => $object->ip,
					'port' => $object->port,
					'ip_connect_db' => $object->ip_connect_db,
					'login_req' => $object->login_req,
					'user_field' => $object->user_field,
					'session_field' => $object->session_field,
					);
		}

		$count = count($data);

		return $this->respFetch($this->req->startRow,$this->req->startRow+$count,$count,$data);
	}

	function fetchall(){
		$objects = array();
		$data = array();

		$dbservice_orm = ORM::factory('auditservice');

		if(isset($this->req->data->id)){
			$dbservice_orm = $dbservice_orm->where('id', $this->req->data->id);
		}else{	
			$dbservice_orm = $dbservice_orm->where(array('service_type >=' => 16 , 'service_type <=' => 32));

			if(isset($this->req->data->audit_site_id)) {
				$dbservice_orm = $dbservice_orm->where('audit_site_id', $this->req->data->audit_site_id);
			}

			if (isset($this->req->data->service_type)) {
				$dbservice_orm = $dbservice_orm->where('service_type', $this->req->data->service_type);
			}
		}

		$objects = $dbservice_orm->find_all();

		foreach($objects as $object){
			$data[] = array(
					'id' => $object->id,
					'audit_site_id' => $object->audit_site_id,
					'name' => $object->name,
					'service_type' => $object->service_type,
					'ip' => $object->ip,
					'port' => $object->port,
					'ip_connect_db' => $object->ip_connect_db,
					'login_req' => $object->login_req,
					'user_field' => $object->user_field,
					'session_field' => $object->session_field,
					);
		}

		$count = count($data);

		return $this->respFetch($this->req->startRow,$this->req->startRow+$count,$count,$data);
	}

	function add(){
		$dbservice_orm = ORM::factory('auditservice')->where('name',$this->req->data->name)->find();

		if($dbservice_orm->loaded){
			$this->respNameError();	
			exit;
		}

		$dbservice_orm = ORM::factory('auditservice');

		foreach($this->req->data as $key => $value){
			$dbservice_orm->$key = $value;
		}

		$dbservice_orm->save();

		if($dbservice_orm->saved){
			$id = $dbservice_orm->id;
			$result = 0;
			$response_data[] = array_merge(array("id" => $id),$this->req->data_array);
			$this->respOk($response_data);
		}else{			
			$id = 0;
			$result = -1;
			$message = '添加服务失败，未知的错误';
			$this->respFailed($message);
		}

		$this->operationlog_add($result,$id,$this->req->data->name,$this->req->data);

		return;
	}

	function update(){
		$id = $this->get_id();
		$response_data = $this->get_data_array();

		if(isset($this->req->data->name)){	
			$dbservice_orm = ORM::factory('auditservice')->where('name',$this->req->data->name)->find();

			if($dbservice_orm->loaded){
				$this->respNameError();	
				exit;
			}
		}

		$db = Database::instance();
		$db->query('begin');

		$dbservice_orm = ORM::factory('auditservice',$id);

		if($dbservice_orm->loaded){
			$using = $this->is_using("dbservice",$id);

			foreach($this->req->data as $key => $value){
				$dbservice_orm->$key = $value;
			}

			$dbservice_orm->save();

			if($dbservice_orm->saved){
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
				$message = '修改服务失败，未知的错误';
				$this->respFailed($message);
			}

			$diff_data = $this->update_data_diff($this->req->data_array,$this->req->oldValues_array);
			$this->operationlog_update($result,$id,$this->req->oldValues->name,$diff_data);
		}else{
			$message = '修改服务失败，未知的数据库服务ID';
			$this->respFailed($message);
		}
		return;
	}

	function remove(){
		$id = $this->get_id();
		$response_data[] = $this->req->data_array;

		$db = Database::instance();
		$db->query('begin');

		$dbservice_orm = ORM::factory('auditservice')->where('id',$id)->find();

		if($dbservice_orm->loaded){
			$using = $this->is_using("dbservice",$id);
			
			if($using){
				$message = "当前应用配置了审计策略，现在不能删除";
				$this->respFailed($message);
			}else{
				$dbservice_orm->deleted = 1;
				$oldname = $dbservice_orm->name;
				$name = $dbservice_orm->name . "@" . sprintf("%x",time());
				$dbservice_orm->name = $name;
				$dbservice_orm->save();

				if($dbservice_orm->saved){
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
					$message = '删除服务失败，未知的错误';
					$this->respFailed($message);
				}

				$this->operationlog_remove($result,$id,$oldname);
			}
		}else{
			$message = '删除服务失败，未知的数据库服务ID';
			$this->respFailed($message);
		}

		return;
	}
}
?>
