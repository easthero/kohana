<?php
defined('SYSPATH') OR die('No direct access allowed.');

class Policy_Controller extends SmartgwtController {

	const ALLOW_PRODUCTION = TRUE;

	function fetch(){
		$data = array();

		$sql = "select p.id as id, service_id, priority, p.name as name, criticality, response_type, time_start, time_stop, monday, tuesday, wednesday, thursday, friday, saturday, sunday, user_group_id, ip_group_id, operations, object, keyword, keyword_include, i.name as ipgroup_name, u.name as usergroup_name from policies p left join ipgroups i on p.ip_group_id = i.id left join usergroups u on p.user_group_id = u.id";

		if( isset($this->req->data->service_type) ){
			$sql .= " inner join auditservices a on p.service_id = a.id where a.service_type = " . $this->req->data->service_type . " and p.deleted = 0";			
		}elseif( isset($this->req->data->id) ){
			$sql .= " where p.id = " . $this->req->data->id;
		}elseif( isset($this->req->data->service_id) ){
			$sql .= " where p.service_id = " . $this->req->data->service_id . " and p.deleted = 0";
		}elseif( isset($this->req->data->criticality) ){
			$sql .= " where p.criticality = " . $this->req->data->criticality . " and p.deleted = 0";
		}elseif( isset($this->req->data->response_type) ){
			$sql .= " where p.response_type = " . $this->req->data->response_type . " and p.deleted = 0";
		}elseif( isset($this->req->data->audit_site_id) ){
			$sql .= " inner join auditservices a on a.id = p.service_id where a.audit_site_id = " . $this->req->data->audit_site_id . " and p.deleted = 0";
		}else{
			$sql .= " where p.deleted = 0";
		}

		$sql .= " order by priority";

		$db = Database::instance();				

		$query = $db->query($sql);

		foreach($query->result_array() as $result){	
			$data[] = array(
					"id" => $result->id,					
					"service_id" => $result->service_id,
					"priority" => $result->priority,
					"name" => $result->name,
					"criticality" => $result->criticality,
					"response_type" => $result->response_type,
					"time_start" => $result->time_start,
					"time_stop" => $result->time_stop,
					"monday" => $result->monday,
					"tuesday" => $result->tuesday,
					"wednesday" => $result->wednesday,
					"thursday" => $result->thursday,
					"friday" => $result->friday,
					"saturday" => $result->saturday,
					"sunday" => $result->sunday,
					"user_group_id" => $result->user_group_id,
					"ip_group_id" => $result->ip_group_id,
					"operations" => $result->operations,
					"object" => $result->object,
					"keyword" => $result->keyword,
					"keyword_include" => $result->keyword_include,
					"ipgroup_name" => $result->ipgroup_name,
					"usergroup_name" => $result->usergroup_name,
					);

		}

		$count = count($data);

		return $this->respFetch($this->req->startRow,$this->req->startRow+$count,$count,$data);
	}

	function fetchall(){
		$data = array();

		$sql = "select p.id as id, service_id, priority, p.name as name, criticality, response_type, time_start, time_stop, monday, tuesday, wednesday, thursday, friday, saturday, sunday, user_group_id, ip_group_id, operations, object, keyword, keyword_include, i.name as ipgroup_name, u.name as usergroup_name from policies p left join ipgroups i on p.ip_group_id = i.id left join usergroups u on p.user_group_id = u.id";

		if( isset($this->req->data->service_type) ){
			$sql .= " inner join auditservices a on p.service_id = a.id where a.service_type = " . $this->req->data->service_type;			
		}elseif( isset($this->req->data->id) ){
			$sql .= " where p.id = " . $this->req->data->id;
		}elseif( isset($this->req->data->service_id) ){
			$sql .= " where p.service_id = " . $this->req->data->service_id;
		}elseif( isset($this->req->data->criticality) ){
			$sql .= " where p.criticality = " . $this->req->data->criticality;
		}elseif( isset($this->req->data->response_type) ){
			$sql .= " where p.response_type = " . $this->req->data->response_type;
		}elseif( isset($this->req->data->audit_site_id) ){
			$sql .= " inner join auditservices a on a.id = p.service_id where a.audit_site_id = " . $this->req->data->audit_site_id;
		}

		$sql .= " order by priority";

		$db = Database::instance();				

		$query = $db->query($sql);

		foreach($query->result_array() as $result){	
			$data[] = array(
					"id" => $result->id,					
					"service_id" => $result->service_id,
					"priority" => $result->priority,
					"name" => $result->name,
					"criticality" => $result->criticality,
					"response_type" => $result->response_type,
					"time_start" => $result->time_start,
					"time_stop" => $result->time_stop,
					"monday" => $result->monday,
					"tuesday" => $result->tuesday,
					"wednesday" => $result->wednesday,
					"thursday" => $result->thursday,
					"friday" => $result->friday,
					"saturday" => $result->saturday,
					"sunday" => $result->sunday,
					"user_group_id" => $result->user_group_id,
					"ip_group_id" => $result->ip_group_id,
					"operations" => $result->operations,
					"object" => $result->object,
					"keyword" => $result->keyword,
					"keyword_include" => $result->keyword_include,
					"ipgroup_name" => $result->ipgroup_name,
					"usergroup_name" => $result->usergroup_name,
					);

		}

		$count = count($data);

		return $this->respFetch($this->req->startRow,$this->req->startRow+$count,$count,$data);
	}

	function add(){
		$policy_orm = ORM::factory('policy')->where('name',$this->req->data->name)->find();

		if($policy_orm->loaded){
			$this->respNameError();	
			exit;
		}

		$db = Database::instance();
		$db->query('begin');

		$policy_orm = ORM::factory('policy');

		foreach($this->req->data as $key => $value){
			if($key == 'response_type' && $value == 0){
				$policy_orm->criticality = 0;
			}

			$policy_orm->$key = $value;
		}

		$max_priority_sql = "select max(priority) as max_priority from policies";

		$query = $db->query($max_priority_sql);

		$result = $query->result_array();

		$max_priority = $result[0]->max_priority;

		if($max_priority){
			$policy_orm->priority = $max_priority + 1;
		}else{
			$policy_orm->priority = 1;
		}

		$policy_orm->save();

		if($policy_orm->saved){
			$id = $policy_orm->id;

			$pb = new Protobuf();
			$result = $pb->sendconfig($db);

			if($result['status'] === 0){ 
				$db->query('commit');
				$result = 0;
				$response_data[] = array_merge(array("id" => $id),$this->req->data_array);
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
			$id = 0;
			$result = -1;
			$message = '添加策略失败，未知的错误';
			$this->respFailed($message);
		}

		$this->operationlog_add($result,$id,$this->req->data->name,$this->req->data);

		return;
	}

	function update(){
		$id = $this->get_id();
		$response_data = $this->get_data_array();

		if(isset($this->req->data->name)){	
			$policy_orm = ORM::factory('policy')->where('name',$this->req->data->name)->find();

			if($policy_orm->loaded){
				if($policy_orm->id != $id){
					$this->respNameError();	
					exit;
				}
			}
		}

		$db = Database::instance();
		$db->query('begin');

		$policy_orm = ORM::factory('policy',$id);

		if($policy_orm->loaded){
			if(!isset($this->req->oldValues_array)){
				$this->req->oldValues_array = array(
						"id" => $policy_orm->id,					
						"service_id" => $policy_orm->service_id,
						"priority" => $policy_orm->priority,
						"name" => $policy_orm->name,
						"criticality" => $policy_orm->criticality,
						"response_type" => $policy_orm->response_type,
						"time_start" => $policy_orm->time_start,
						"time_stop" => $policy_orm->time_stop,
						"monday" => $policy_orm->monday,
						"tuesday" => $policy_orm->tuesday,
						"wednesday" => $policy_orm->wednesday,
						"thursday" => $policy_orm->thursday,
						"friday" => $policy_orm->friday,
						"saturday" => $policy_orm->saturday,
						"sunday" => $policy_orm->sunday,
						"user_group_id" => $policy_orm->user_group_id,
						"ip_group_id" => $policy_orm->ip_group_id,
						"operations" => $policy_orm->operations,
						"object" => $policy_orm->object,
						"keyword" => $policy_orm->keyword,
						"keyword_include" => $policy_orm->keyword_include,					
						);
			}

			$this->policy_name = $policy_orm->name;		

			foreach($this->req->data as $key => $value){
				if($key == 'response_type' && $value == 0){
					$policy_orm->criticality = 0;
				}
				$policy_orm->$key = $value;
			}

			$policy_orm->save();

			if($policy_orm->saved){			
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
				$result = -1;
				$message = '修改策略失败，未知的错误';
				$this->respFailed($message);
			}

			$diff_data = $this->update_data_diff($this->req->data_array,$this->req->oldValues_array);
			$this->operationlog_update($result,$id,$this->policy_name,$diff_data);
		}else{
			$message = '修改策略失败，未知的策略ID';
			$this->respFailed($message);
		}
		return;
	}

	function move(){
		$policy_orm = ORM::factory('policy');

		$db = Database::instance();

		$response_data[] = $this->req->data_array;

		$max_priority_sql = "select max(priority) as max_priority from policies";

		$query = $db->query($max_priority_sql);

		$result = $query->result_array();

		$max_priority = $result[0]->max_priority;

		$moved_priority = $policy_orm->where('id',$this->req->data->moved_id)->find()->priority;
		$target_priority = $policy_orm->where('id',$this->req->data->target_id)->find()->priority;

		$db->query('begin');

		if(($this->req->data->moved_id != $this->req->data->target_id) && (($target_priority - $moved_priority) != 1)) {		
			if($this->req->data->target_id == 0){
				$sql_update1 = "update policies set priority = priority - 1 where priority > $moved_priority";
				$sql_update2 = "update policies set priority = $max_priority where id = " . $this->req->data->moved_id;
			}elseif($moved_priority < $target_priority){
				$sql_update1 = "update policies set priority = priority - 1 where priority > $moved_priority and priority < $target_priority";
				$sql_update2 = "update policies set priority = $target_priority-1 where id = " . $this->req->data->moved_id;	
			}elseif($moved_priority > $target_priority){
				$sql_update1 = "update policies set priority = priority + 1 where priority >= $target_priority and priority < $moved_priority"; 
				$sql_update2 = "update policies set priority = $target_priority where id = " . $this->req->data->moved_id;
			}

			$db->query($sql_update1);
			$db->query($sql_update2);

			$pb = new Protobuf();

			$result = $pb->sendconfig($db);

			if($result['status'] === 0){ 
				$db->query('commit');

				$this->respOk($response_data);    
			}else{
				$db->query('rollback'); 
				if($result['message']){
					$message = "规则下发失败，失败原因:". $result['message'];
				}else{
					$message = "规则下发失败";
				}   

				$this->respFailed($message);
			}
		}
		return;		
	}

	function remove(){
		$id = $this->get_id();
		$response_data[] = $this->req->data_array;

		$db = Database::instance();

		$db->query('begin');

		$policy_orm = ORM::factory('policy')->where(array('id' => $id , 'deleted' => 0))->find();

		if($policy_orm->loaded){
			$policy_orm->deleted = 1;
			$oldname = $policy_orm->name;
			$name = $policy_orm->name . "@" . sprintf("%x",time());
			$policy_orm->name = $name;
			$policy_orm->save();

			if($policy_orm->saved){
				if($policy_orm->saved){
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
				$message = '删除策略失败，未知的错误';
				$this->respFailed($message);
			}

			$this->operationlog_remove($result,$id,$oldname);
		}else{
			$message = '删除策略失败，未知的策略ID';
			$this->respFailed($message);
		}
		return;
	}
}
?>
