<?php
defined('SYSPATH') OR die('No direct access allowed.');

class Auditservice_Controller extends SmartgwtController {

	const ALLOW_PRODUCTION = TRUE;

	function fetch(){
		$objects = array();
		$data = array();

		$appservice_orm = ORM::factory('auditservice');

		if(isset($this->req->data->id)){
			$appservice_orm = $appservice_orm->where('id', $this->req->data->id);
		}else{
			$appservice_orm = $appservice_orm->where(array('deleted' => 0));

			if(isset($this->req->data->audit_site_id)) {
				$appservice_orm = $appservice_orm->where('audit_site_id',$this->req->data->audit_site_id);

			}

			if (isset($this->req->data->service_type)) {
				$appservice_orm = $appservice_orm->where('service_type', $this->req->data->service_type);
			}
		}

		$objects = $appservice_orm->find_all();

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

		$appservice_orm = ORM::factory('auditservice');

		if(isset($this->req->data->id)){
			$appservice_orm = $appservice_orm->where('id', $this->req->data->id);
		}else{
			if(isset($this->req->data->audit_site_id)) {
				$appservice_orm = $appservice_orm->where('audit_site_id',$this->req->data->audit_site_id);

			}

			if (isset($this->req->data->service_type)) {
				$appservice_orm = $appservice_orm->where('service_type', $this->req->data->service_type);
			}
		}

		$objects = $appservice_orm->find_all();

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
}
?>
