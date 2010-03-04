<?php
defined('SYSPATH') OR die('No direct access allowed.');

class Protobuf extends Controller{

	function __construct(){
		include Kohana::find_file('helpers','protobuf/message/pb_message');
		include Kohana::find_file('helpers','protobuf/pb_proto_auditconfig');
	}

	function sendconfig($db){
		$service_port = '1680';
		// $server_ip = '192.168.10.254';
		$server_ip = '127.0.0.1';

		$pb = $this->create_protobuf($db);

		$pb_length = strlen($pb);

		if(!@$socket = socket_create(AF_INET,SOCK_STREAM,0)){			
			$data['status'] = -1;
			$data['message'] = "socket create error" . socket_strerror(socket_last_error());
			return $data;
		}else{
			$timeout = array('sec' => 5,'usec' => 0); 
			socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, $timeout);
		}

		if(!@socket_connect($socket,$server_ip,$service_port)){			
			$data['status'] = -1;
			$data['message'] =  "socket connect error " . socket_strerror(socket_last_error());
			return $data;
		}

		$head = pack("CCCCCCCCNN", ord('D'), ord('P'), 0x98, 0, 1, 3, 1, 1,$pb_length,1);  

		if(@socket_write($socket,$head,strlen($head)) === false){			
			$data['status'] = -1;
			$data['message'] =  "socket write head error " . socket_strerror(socket_last_error());
			return $data;
		}

		if(@socket_write($socket,$pb,$pb_length) === false){			
			$data['status'] = -1;
			$data['message'] =  "socket write pb error " . socket_strerror(socket_last_error());
			return $data;
		}

		if(@$response_head = socket_read($socket,16)){			
			$head = unpack("C8flag/N2length",$response_head);
			if(@$response_data = socket_read($socket,$head['length1'])){
				$response = json_decode($response_data);
				$data['status'] = $response->status;			
			}
		}

		socket_close($socket);

		return $data;
	}

	function create_protobuf($db){
		$config = new _AuditConfig();

		$auditsites = $db->query("select * from auditsites where deleted = 0");

		foreach($auditsites->result_array() as $site){
			$siteinfo = $config->add_audit_site();
			$this->getSiteInfo($siteinfo,$site,$db);
		}

		$ipgroups = $db->query("select * from ipgroups where deleted = 0");

		foreach($ipgroups->result_array() as $ipgroup){
			$ipgroupinfo = $config->add_ip_group();
			$this->getIpGroupInfo($ipgroupinfo,$ipgroup,$db);
		}

		$usergroups = $db->query("select * from usergroups where deleted = 0");

		foreach($usergroups->result_array() as $usergroup){
			$usergroupinfo = $config->add_user_group();
			$this->getUserGroupInfo($usergroupinfo,$usergroup,$db);
		}

		$string = $config->SerializeToString();

		return $string;
	}

	function getSiteInfo($siteinfo,$site,$db){
		$siteinfo->set_id($site->id);
		$siteinfo->set_name($site->name);

		$sql_appservice = "select * from auditservices where deleted = 0 and service_type >= 32 and service_type <= 48 and audit_site_id = " . $site->id;

		$appservices = $db->query($sql_appservice);

		foreach($appservices->result_array()  as $service){
			$serviceinfo = $siteinfo->add_service();
			$this->getappServiceInfo($serviceinfo,$service,$db);
		}

		$sql_dbservice = "select * from auditservices where deleted = 0 and service_type >= 16 and service_type <= 32 and audit_site_id = " . $site->id;

		$dbservices = $db->query($sql_dbservice);

		foreach($dbservices->result_array() as $service){
			$serviceinfo = $siteinfo->add_service();
			$this->getdbServiceInfo($serviceinfo,$service,$db);
		}
	}

	function getappServiceInfo($serviceinfo,$service,$db){
		$serviceinfo->set_id($service->id);
		$serviceinfo->set_name($service->name);
		$serviceinfo->set_service_type($service->service_type);
		$serviceinfo->set_ip($service->ip);
		$serviceinfo->set_port($service->port);
		$serviceinfo->set_ip_connect_db($service->ip_connect_db);
		$serviceinfo->set_login_req($service->login_req);
		$serviceinfo->set_user_field($service->user_field);

		$sql_policy = "select * from policies where deleted = 0 and service_id = " . $service->id . " order by priority";

		$policies = $db->query($sql_policy);

		foreach($policies->result_array() as $policy){
			$policyinfo = $serviceinfo->add_policy();
			$this->getPolicyInfo($policyinfo,$policy,$db);
		}
	}

	function getdbServiceInfo($serviceinfo,$service,$db){
		$serviceinfo->set_id($service->id);
		$serviceinfo->set_name($service->name);
		$serviceinfo->set_service_type($service->service_type);
		$serviceinfo->set_ip($service->ip);
		$serviceinfo->set_port($service->port);
		$serviceinfo->set_ip_connect_db($service->ip_connect_db);
		$serviceinfo->set_login_req($service->login_req);
		$serviceinfo->set_user_field($service->user_field);

		$sql_policy = "select * from policies where deleted = 0 and service_id = " . $service->id . " order by priority";

		$policies = $db->query($sql_policy);

		foreach($policies->result_array() as $policy){
			$policyinfo = $serviceinfo->add_policy();
			$this->getPolicyInfo($policyinfo,$policy,$db);
		}
	}

	function getPolicyInfo($policyinfo,$policy,$db){
		$policy->time_start = str_replace(":",'',$policy->time_start); 
		$policy->time_stop = str_replace(":",'',$policy->time_stop);

		$policyinfo->set_id($policy->id);
		$policyinfo->set_name($policy->name);
		$policyinfo->set_response_type($policy->response_type);
		$policyinfo->set_criticality($policy->criticality);
		$policyinfo->set_time_start($policy->time_start);
		$policyinfo->set_time_stop($policy->time_stop);
		$policyinfo->set_monday($policy->monday);
		$policyinfo->set_tuesday($policy->tuesday);
		$policyinfo->set_wednesday($policy->wednesday);
		$policyinfo->set_thursday($policy->thursday);
		$policyinfo->set_friday($policy->friday);
		$policyinfo->set_saturday($policy->saturday);
		$policyinfo->set_sunday($policy->sunday);
		$policyinfo->set_user_group_id($policy->user_group_id);
		$policyinfo->set_ip_group_id($policy->ip_group_id);
		$policyinfo->set_object($policy->object);
		$policyinfo->set_keyword($policy->keyword);
		$policyinfo->set_keyword_include($policy->keyword_include);

		$operations = explode(",", $policy->operations);

		foreach($operations as $operation){
			if(trim($operation) == ''){
				continue;
			}
			$policyinfo->append_operation($operation);
		}

		$sql_count = 'select count(*) as count from (
				select policy_id from PolicyRespEmails where policy_id = ' .  $policy->id 
				. ' and deleted = 0 union 
				select policy_id from PolicyRespSnmps where policy_id = ' . $policy->id 
				. ' and deleted = 0 union
				select policy_id from PolicyRespSyslogs where policy_id = ' . $policy->id
				. ' and deleted = 0 ) t';

		$count_query = $db->query($sql_count);

		$count_result = $count_query->result_array();

		$resp_count = $count_result[0]->count;

		if($resp_count > 0){
			$response = $policyinfo->add_response();

			$sql_email_response = 'select r.* from ResponseEmails r, PolicyRespEmails p where r.id = p.resp_id and p.deleted = 0 and r.deleted = 0 and p.policy_id = ' . $policy->id;

			$email_responses = $db->query($sql_email_response);

			foreach($email_responses->result_array() as $r){
				$respInfo = $response->add_email_response();
				$this->getEmailRespInfo($respInfo,$r);
			}

			$sql_snmp_response = 'select r.* from ResponseSnmps r, PolicyRespSnmps p where r.id = p.resp_id and p.deleted = 0 and r.deleted = 0 and p.policy_id = ' . $policy->id;

			$snmp_responses = $db->query($sql_snmp_response);

			foreach($snmp_responses->result_array() as $r){
				$respInfo = $response->add_snmp_response();
				$this->getSnmpRespInfo($respInfo, $r);
			}

			$sql_syslog_response = 'select r.* from ResponseSyslogs r, PolicyRespSyslogs p where r.id = p.resp_id and p.deleted = 0 and r.deleted = 0 and p.policy_id = ' . $policy->id;

			$syslog_responses = $db->query($sql_syslog_response);

			foreach($syslog_responses->result_array() as $r){
				$respInfo = $response->add_syslog_response();
				$this->getSyslogRespInfo($respInfo, $r);
			}
		}
	}

	function getEmailRespInfo($respInfo,$r){
		$respInfo->set_email($r->email);
		$respInfo->set_smtp_ip($r->smtp_ip);
		$respInfo->set_smtp_user($r->smtp_user);
		$respInfo->set_smtp_password($r->smtp_password);
	}

	function getSnmpRespInfo($respInfo,$r){
		$respInfo->set_receiver_ip($r->receiver_ip);
		$respInfo->set_community($r->community);
	}

	function getSyslogRespInfo($respInfo,$r){
		$respInfo->set_syslogd_ip($r->syslogd_ip);
		$respInfo->set_syslogd_port($r->syslogd_port);
		$respInfo->set_ident($r->ident);
		$respInfo->set_facility($r->facility);
	}

	function getIpGroupInfo($ipgroupinfo,$ipgroup){
		$ipgroupinfo->set_id($ipgroup->id);
		$ipgroupinfo->set_name($ipgroup->name);

		$members = explode(";",$ipgroup->members);

		foreach($members as $member){
			if(trim($member) == ''){
				continue;
			}

			$memberinfo = $ipgroupinfo->add_member();
			$member = explode("-",$member);

			if(count($member) == 1){
				$memberinfo->set_first($member[0]);
				$memberinfo->set_last($member[0]);
			}else{
				$memberinfo->set_first($member[0]);
				$memberinfo->set_last($member[1]);
			}
		}
	}

	function getUserGroupInfo($usergroupinfo,$usergroup){
		$usergroupinfo->set_id($usergroup->id);
		$usergroupinfo->set_name($usergroup->name);

		$members = explode(";",$usergroup->members);

		foreach($members as $member){
			if(trim($member) == ''){
				continue;
			}

			$usergroupinfo->append_member($member);
		}
	}
}

?>
