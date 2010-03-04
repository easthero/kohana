<?php
defined('SYSPATH') OR die('No direct access allowed.');

class Auditevent_Controller extends SmartgwtController {

		const ALLOW_PRODUCTION = TRUE;

		private $sql_count;
		private $sql_select;
		private $db_info_array;

		function __construct(){
				parent::__construct();

				$this->max_query_number = 65000;

				/*
				 * 请求的时间格式为 YYYY-mm-dd hh:mm:ss
				 * 使用strtotime转换为unix timestamp
				 * 如果没有设置起止时间,则默认结束时间为当前时间,开始时间为当前时间前3小时
				 */

				if (isset($this->req->data->startTime) && isset($this->req->data->endTime)) {
						$this->req->data->startTime = strtotime($this->req->data->startTime);
						$this->req->data->endTime = strtotime($this->req->data->endTime);
				}

				if(isset($this->req->data->time)){
						$this->req->data->time = strtotime($this->req->data->time);
				}
		}

		private function clear_cache(){
				$_SERVER["PHP_AUTH_USER"] = "xcache";
				$_SERVER["PHP_AUTH_PW"] = "xcache";
				$xcache_count = xcache_count(XC_TYPE_VAR);

				for ($cacheid = 0; $cacheid < $xcache_count; $cacheid ++) {
						xcache_clear_cache(XC_TYPE_VAR, $cacheid);
				}
		}

		/*
		 * 生成几种条件下的公用SQL语句
		 */
		private function sql_common($type,$view,$startTime,$endTime,$data){
				if($type == 'count'){
						$sql = "select count(occur_time) from " . $view; 
				}else{
						$sql = "select id from " .$view;
				}

				$sql .= " where occur_time >= $startTime and occur_time <= $endTime";

				if(isset($data->criticality)) {
						$sql .= " and criticality = " . $data->criticality;
				}

				if(isset($data->auditSiteId)){
						$sql .= " and audit_site_id = " . $data->auditSiteId; 
				}

				return $sql;
		}

		/*
		 * 如果只有 $req->data->dbpCriteria,则根据 dbCriteria 的成员生成相应的条件语句
		 */
		private function sql_dbCriteria($dbCriteria){
				$sql = "";

				foreach($dbCriteria as $key => $value){

						if ($key == "serviceId"){
								$sql .= " and service_Id = $value ";
						}elseif ($key == "clientIpGroupId"){
								$sql .= " and ip_group_id = $value ";
						}elseif ($key == "clientIp"){
								$sql .= " and client_ip = '" . $value . "'";
						}elseif ($key == "clientUserGroupId"){
								$sql .= " and user_group_id = $value ";
						}elseif ($key == "clientUser"){
								$sql .= " and user_name = '" . $value . "'";
						}elseif ($key == "keyword"){
								$sql .= " and content like '%" . $value . "%'";
						}elseif ($key == "operation"){
								$sql .= " and operation like '" . $value . "'";
						}
				}
				return $sql;
		}

		/*
		 *  如果只有 $req->data->appCriteria,则根据 appCriteria 的成员生成相应的条件语句
		 */
		private function sql_appCriteria($appCriteria){
				$sql = "";

				foreach($appCriteria as $key => $value){
						if ($key == "serviceId"){
								$sql .= " and service_Id = $value ";
						}elseif ($key == "request"){
								$sql .= " and operation = '" . $value . "'";
						}elseif ($key == "clientIpGroupId"){
								$sql .= " and ip_group_id = $value ";
						}elseif ($key == "clientIp"){
								$sql .= " and client_ip = '" . $value . "'";
						}elseif ($key == "clientUserGroupId"){
								$sql .= " and user_group_id = $value ";
						}elseif ($key == "clientUser"){
								$sql .= " and user_name = '" . $value . "'";
						}elseif ($key == "keyword"){
								$sql .= " and content like '%" . $value . "%'";
						}

				}
				return $sql;
		}

		/*
		 *  如果 $req->data->dbCriteria 和 $req->data->appCriteria 都设置了,则根据条件生成相应的条件语句
		 */
		private function sql_db_app_Criteria($dbCriteria,$appCriteria){
				$sql = "";

				foreach($dbCriteria as $key => $value){
						if ($key == "serviceId"){
								$sql .= " and service_Id = $value ";
						}elseif ($key == "clientIpGroupId"){
								$sql .= " and ip_group_id = $value ";
						}elseif ($key == "clientIp"){
								$sql .= " and client_ip = '" . $value . "'";
						}elseif ($key == "clientUserGroupId"){
								$sql .= " and user_group_id = $value ";
						}elseif ($key == "clientUser"){
								$sql .= " and user_name = '" . $value . "'";
						}elseif ($key == "keyword"){
								$sql .= " and content like '%" . $value . "%'";
						}elseif ($key == "operation"){
								$sql .= " and operation like '" . $value . "'";
						}
				}

				foreach($appCriteria as $key => $value){
						if ($key == "serviceId"){
								$sql .= " and `service_Id:1` = $value ";
						}elseif ($key == "request"){
								$sql .= " and `operation:1` = '" . $value . "'";
						}elseif ($key == "clientIpGroupId"){
								$sql .= " and `ip_group_id:1` = $value ";
						}elseif ($key == "clientIp"){
								$sql .= " and `client_ip:1` = '" . $value . "'";
						}elseif ($key == 'clientUserGroupId'){
								$sql .= " and `user_group_id:1` = $value ";
						}elseif ($key == "clientUser"){
								$sql .= " and `user_name:1` = '" . $value . "'";
						}elseif ($key == "keyword"){
								$sql .= " and `content:1` like '%" . $value . "%'";
						}
				}

				return $sql;
		}

		/*
		 * 得到不同条件下的统计总数和查询记录的SQL语句 $this->sql_count 和 $this->sql_select
		 */
		private function generate_sql($req){
				if(isset($req->data->appCriteria) && isset($req->data->dbCriteria)){
						$this->sql_count = $this->sql_common('count','db_app_event',$this->req->data->startTime,$this->req->data->endTime,$req->data);
						$this->sql_select = $this->sql_common('select','db_app_event',$this->req->data->startTime,$this->req->data->endTime,$req->data);

						if(isset($req->data->dbCriteria->operation) && isset($req->data->dbCriteria->object)){
								$req->data->dbCriteria->operation = "%{" . $req->data->dbCriteria->operation . ' => ' . $req->data->dbCriteria->object . '%';
						} elseif ( isset($req->data->dbCriteria->operation )){
								$req->data->dbCriteria->operation = "%{" . $req->data->dbCriteria->operation . ' =>%';
						} elseif ( isset($req->data->dbCriteria->object )){
								$req->data->dbCriteria->operation = "%=> " . $req->data->dbCriteria->object . '%';
						}

						$sql_db_app_Criteria = $this->sql_db_app_Criteria($req->data->dbCriteria,$req->data->appCriteria);

						$this->sql_count .= $sql_db_app_Criteria;
						$this->sql_select .= $sql_db_app_Criteria;

				}elseif(isset($req->data->dbCriteria)){

						$this->sql_count = $this->sql_common('count','dbevent',$this->req->data->startTime,$this->req->data->endTime,$req->data);
						$this->sql_select = $this->sql_common('select','dbevent',$this->req->data->startTime,$this->req->data->endTime,$req->data);

						if(isset($req->data->dbCriteria->operation) && isset($req->data->dbCriteria->object)){
								$req->data->dbCriteria->operation = "%{" . $req->data->dbCriteria->operation . ' => ' . $req->data->dbCriteria->object . '%';
						} elseif ( isset($req->data->dbCriteria->operation )){
								$req->data->dbCriteria->operation = "%{" . $req->data->dbCriteria->operation . ' =>%';
						} elseif ( isset($req->data->dbCriteria->object )){
								$req->data->dbCriteria->operation = "%=> " . $req->data->dbCriteria->object . '%';
						}

						$sql_dbCriteria = $this->sql_dbCriteria($req->data->dbCriteria);
						$this->sql_count .= $sql_dbCriteria;
						$this->sql_select .= $sql_dbCriteria;	

				}elseif(isset($req->data->appCriteria)){
						$this->sql_count = $this->sql_common('count','appevent',$this->req->data->startTime,$this->req->data->endTime,$req->data);
						$this->sql_select = $this->sql_common('select','appevent',$this->req->data->startTime,$this->req->data->endTime,$req->data);

						$sql_appCriteria = $this->sql_appCriteria($req->data->appCriteria);
						$this->sql_count .= $sql_appCriteria;
						$this->sql_select .= $sql_appCriteria;

				}else{
						$this->sql_count = $this->sql_common('count','eventview',$this->req->data->startTime,$this->req->data->endTime,$req->data);
						$this->sql_select = $this->sql_common('select','eventview',$this->req->data->startTime,$this->req->data->endTime,$req->data);
				}

				$this->sql_select .= " order by occur_time desc, id desc";		
		}

		/*
		 * 对查询时间范围内的每个数据库进行一次查询操作
		 * 剔除无效的数据库(可能库不存在或者表不存在)
		 * 把有效的库名对应的时间的unix timestamp放在 array_TS 数组中
		 */
		private function strip_db($startTime, $endTime){		
				$StartTimeTS = mktime(0, 0, 0, date("m",$startTime), date("d",$startTime), date("Y",$startTime));
				$EndTimeTS = mktime(0, 0, 0, date("m",$endTime), date("d",$endTime), date("Y",$endTime));

				$array_TS = array();

				for ($CurrentTimeTS = $EndTimeTS; $CurrentTimeTS >= $StartTimeTS; $CurrentTimeTS -= 86400) {
						$year = date('Y',$CurrentTimeTS);
						$mon = date('m',$CurrentTimeTS);
						$mday = date('d',$CurrentTimeTS);

						$dbfile = Kohana::config('core.datapath') . "/$year/$mon/$mday.db";

						$sql = "select 'ok' from event limit 1";

						if(file_exists($dbfile)){
								$db = new PDO("sqlite:$dbfile");
								if($db->query($sql)){
										$array_TS[] = $CurrentTimeTS;
								}else{
										Kohana::log('alert', "can not open $dbfile");
										continue;
								}
						}else{
								Kohana::log('alert', "can not open $dbfile");
								continue;
						}
				}

				return $array_TS;		
		}

		/*
		 * 统计符合条件的记录总数，如果总数大于65000条，则停止统计，返回总数为65000条
		 * 同时将符合条件的数据库的相关数据(当前库的记录总数，当前库之前包括其它库的记录总数)放在$db_info_array数组
		 * $db_info_array的结构如下，其中 db为库名，total是指当前库之前的记录总数，current是当前库的记录总数
		 * $db_info_array = array(
		 * array('db' => '2009-09-03', 'total' = 0, 'current' => 100);
		 * array('db' => '2009-09-02', 'total' = 100, 'current' => 200);
		 * array('db' => '2009-09-01', 'total' = 300, 'current' => 100)
		 * );
		 */
		private function calc_count($array_TS){
				$this->totalRows = 0;
				$this->db_info_array = array();

				foreach($array_TS as $ts){
						$year = date('Y',$ts);
						$mon = date('m',$ts);
						$mday = date('d',$ts);

						$dbfile = Kohana::config('core.datapath') . "/$year/$mon/$mday.db";

						$db = new PDO("sqlite:$dbfile");

						$result = $db->query($this->sql_count);

						$row = $result->fetch();

						if($row[0] == 0){
								continue;
						} elseif ( $this->totalRows + $row[0] > $this->max_query_number ) {
								$this->db_info_array[] = array('db' => $ts, 'total' => $this->totalRows, 'current' => $this->max_query_number - $this->totalRows);
								$this->totalRows = $this->max_query_number;
								break;
						} else {
								$this->db_info_array[] = array('db' => $ts,'total' => $this->totalRows, 'current' => $row[0]);
								$this->totalRows += $row[0];			
						}

				}
		}

		/*
		 * 把符合条件所有记录(最多65000条)的id查询出来
		 */
		private function fetch_id(){
				$id_array = array();

				foreach($this->db_info_array as $item){	
						$sql = $this->sql_select . " limit " . $item['current'];

						$year = date('Y',$item['db']);
						$mon = date('m',$item['db']);
						$mday = date('d',$item['db']);

						$dbfile = Kohana::config('core.datapath') . "/$year/$mon/$mday.db";

						$db = new PDO("sqlite:$dbfile");

						$sth = $db->prepare($sql);
						$sth->execute();

						$result = $sth->fetchAll(PDO::FETCH_COLUMN);

						$id_array = array_merge($id_array,$result);					
				}

				return $id_array;
		}

		/*
		 *  从 $id_array 获取当次查询所需要的记录ID，再根据ID把数据查询出来
		 */
		private function fetch_data($ids_array){
				if($this->fetch_type == 'query'){		
						$fetch_ids = array_slice($ids_array,$this->req->startRow,$this->req->endRow - $this->req->startRow);
						$sql = "select id, session_id, service_id, related_event_id, related_session_id, datetime(occur_time,'unixepoch','localtime') as occur_time, audit_site_id, event_type, criticality, operation, client_ip, user_name, effect from ";
				}else{
						$fetch_ids = $ids_array;
						$sql = "select id, datetime(occur_time,'unixepoch','localtime') as occur_time, audit_site_id, service_id, event_type, criticality, user_name, client_ip, client_port, client_mac, operation, content from ";
				}

				$fetch_ids = implode($fetch_ids,',');

				$data_array = array();

				if (isset($req->data->appCriteria) && isset($req->data->dbCriteria)){
						$sql .= " db_app_event";
				} elseif (isset($req->data->dbCriteria) ){
						$sql .= " dbevent";
				}elseif(isset($req->data->appCriteria)){
						$sql .= " appevent";
				}else{
						$sql .= " eventview";
				}

				$sql = $sql . " where id in (" . $fetch_ids . ") order by occur_time desc, id desc";

				foreach($this->db_info_array as $item){
						$year = date('Y',$item['db']);
						$mon = date('m',$item['db']);
						$mday = date('d',$item['db']);

						$dbfile = Kohana::config('core.datapath') . "/$year/$mon/$mday.db";

						$db = new PDO("sqlite:$dbfile");

						$sth = $db->prepare($sql);
						$sth->execute();
						$result = $sth->fetchAll(PDO::FETCH_ASSOC);

						$data_array = array_merge($data_array,$result);					
				}

				if($this->fetch_type == 'export'){
						$data_array = array_reverse($data_array);
				}

				$return_data = array($this->req->startRow,$this->req->endRow,$this->totalRows,$data_array);

				return $return_data;
		}

		/*
		 * 根据事件id获取单条记录
		 */
		private function fetch_single($req){
				if($this->fetch_type == 'query'){
						$sql_select = "select id, event_type, datetime(occur_time,'unixepoch','localtime') as occur_time, criticality, audit_site_id, service_id, user_name, client_ip, client_port, client_mac, operation, content, effect, params, session_id, related_event_id, related_session_id from eventview where id = " . $req->data->id;
				}else{
						$sql_select = "select id, datetime(occur_time,'unixepoch','localtime') as occur_time, audit_site_id, service_id, event_type, criticality, user_name, client_ip, client_port, client_mac, operation, content from eventview where id = " . $req->data->id;
				}

				$year = date('Y',$this->req->data->time);
				$mon = date('m',$this->req->data->time);
				$mday = date('d',$this->req->data->time);

				$dbfile = Kohana::config('core.datapath') . "/$year/$mon/$mday.db";

				$db = new PDO("sqlite:$dbfile");

				$data = array();

				if ($query = $db->query($sql_select)){
						if($result = $query->fetch(PDO::FETCH_ASSOC)){
								$encodings = array('ASCII','UTF-8','GBK');
								$result['content'] = mb_convert_encoding($result['content'],"UTF-8",$encodings);
								$data[] = $result;
						}
				}

				$return_params = '';
				if ($data[0]['params']){
						$params = explode("\x01", $data[0]['params']);

						foreach($params as $param){
								if($param){
										$values = explode("\x02", $param);
										$return_params .= $values[0] . '=' . $values[1] . "\n";
								}
						}
				}

				$data[0]['params'] = $return_params;

				$req->startRow = 0;
				$req->endRow = 1;
				$totalRows = 1;
				return $return_data = array($req->startRow,$req->endRow,$totalRows,$data);
		}

		/*
		 * 根据 sessionId 获取记录
		 */
		private function fetch_bysessionid($req){
				if($this->fetch_type == 'query'){
						$sql_select = "select id, event_type, datetime(occur_time, 'unixepoch', 'localtime') as occur_time, criticality, audit_site_id, service_id, user_name, client_ip, client_port, client_mac, operation, content, effect, params, session_id, related_event_id, related_session_id from eventview where session_id = " . $req->data->sessionId . " order by occur_time desc, id desc";
				}else{
						$sql_select = "select id, datetime(occur_time,'unixepoch','localtime') as occur_time, audit_site_id, service_id, event_type, criticality, user_name, client_ip, client_port, client_mac, operation, content from eventview where session_id = " . $req->data->sessionId . " order by occur_time asc, id asc";
				}

				$today_year = date('Y',$this->req->data->time);
				$today_mon = date('m',$this->req->data->time);
				$today_mday = date('d',$this->req->data->time);

				$tomorrow_year = date('Y', $this->req->data->time+86400);
				$tomorrow_mon = date('m',$this->req->data->time+86400);
				$tomorrow_mday = date('d',$this->req->data->time+86400);

				$dbfiles = array(Kohana::config('core.datapath') . "/$today_year/$today_mon/$today_mday.db", Kohana::config('core.datapath') . "/$tomorrow_year/$tomorrow_mon/$tomorrow_mday.db");

				$sql_test = "select 'ok' from event limit 1";

				$data_array = array();

				foreach($dbfiles as $dbfile){
						if(file_exists($dbfile)){
								$db = new PDO("sqlite:$dbfile");
								if($db->query($sql_test)){
										$sth = $db->prepare($sql_select);				
										$sth->execute();
										$result = $sth->fetchAll(PDO::FETCH_ASSOC);
										$data_array = array_merge($data_array,$result);
								}
						}
				}

				$req->startRow = 0;
				$totalRows = count($data_array);
				$req->endRow = $totalRows;

				return $return_data = array($req->startRow,$req->endRow,$totalRows,$data_array);
		}

		function fetch(){	
				if (!isset($this->req->data)){
						echo file_get_contents(Kohana::find_file('libraries','login_request'));	
						exit;
				}

				/**
				 *  如果开始行大于结束行，则开始行设为0
				 */
				if ($this->req->startRow > $this->req->endRow){
						$this->req->startRow = 0;
				}

				/**
				 *  如果开始时间大于结束时间，则报错退出
				 */
				if(isset($this->req->data->startTime) && isset($this->req->data->endTime)){
						if($this->req->data->startTime > $this->req->data->endTime){
								$message = '结束时间必须大于开始时间，请重新选择时间';
								$this->respFailed($message);
								return;
						}
				}

				$this->fetch_type = "query";

				/*
				 * 如果查询条件中包含 $this->req->data->id 和 $this->req->data->detail，则是按id查询详细纪录，该查询是前台自动发出请求
				 * 如果查询条件中包含 $this->req->data->id 则是按id查询记录
				 * 如果查询条件中包含 $this->req->data->sessionId 则是按sessionid查询记录
				 * 根据条件生成md5，然后去xcache中取得相应的数据，如果取得，则根据startrow和endrow取得当次查询所需要的1000个id，再根据id去查询记录
				 * 如果没有取得，则先扫描所有的数据库，生成 $array_TS，根据 $array_TS 统计符合条件的记录总数，如果总数大于65000，返回总数为-1
				 * 如果请求中包含 force_fetch，则取得前65000行数据的ID，将ID存入xcache
				 * 根据startrow和endrow取得当次查询所需要的1000个id,再根据id去查询记录
				 */

				if(isset($this->req->data->id) && isset($this->req->data->detail)){
						$return_data = $this->fetch_single($this->req);
				}elseif(isset($this->req->data->id)){
						$this->session->set('data_session',$this->req->data);
						$return_data = $this->fetch_single($this->req);
				}elseif(isset($this->req->data->sessionId)){
						$this->session->set('data_session',$this->req->data);
						$return_data = $this->fetch_bysessionid($this->req);
				}else{
						/*
						 *  对 $this->req->data 序列化后进行hash，作为存储在 xcache 中数据的 key
						 */	
						$req_temp = $this->req->data;

						if(isset($this->req->data->force_fetch)){
								unset($req_temp->data->force_fetch);
						}

						$key = md5(serialize($req_temp));

						$id_key = $key . "_id";
						$totalRows_key = $key . "_totalRows";
						$db_info_array_key = $key . "_db_info_array";

						if( xcache_isset($id_key) && xcache_isset($totalRows_key) && xcache_isset($db_info_array_key) ){
								$ids_array = xcache_get($id_key);
								$this->totalRows = xcache_get($totalRows_key);
								$this->db_info_array = xcache_get($db_info_array_key);
								$return_data = $this->fetch_data($ids_array);
						}else{
								if(xcache_isset($totalRows_key)){
										$this->totalRows = xcache_get($totalRows_key);
								}else{
										$array_TS = $this->strip_db($this->req->data->startTime, $this->req->data->endTime);

										if (count($array_TS) == 0 ){
												$message = "in selected time range, all databases is invalid";
												Kohana::log('alert',"$message");
												$return_data = array(0,0,0,array());
												$this->respFetch($return_data[0],$return_data[1],$return_data[2],$return_data[3]);
												return;
										}else{
												$this->session->set('data_session',$this->req->data);				
												$this->generate_sql($this->req);
												$this->calc_count($array_TS);

												$set_totalRows_result = xcache_set($totalRows_key,$this->totalRows,1800);

												if(!$set_totalRows_result){
														$this->clear_cache();
														xcache_set($totalRows_key,$this->totalRows,1800);
												}
										}
								}

								if($this->totalRows == 0){
										$return_data = array(0,0,0,array());
								}elseif( !isset($this->req->data->force_fetch) && $this->totalRows == $this->max_query_number) {
										$return_data = array(0,0,-1,array());
								}else{
										/*
										 * 如果开始行大于总行数，则开始行设为0
										 */
										if ($this->req->startRow > $this->totalRows){
												$message = "startRow can not large than totalRows, startRow will set to 0 ";
												Kohana::log('alert',"$message");
												$this->req->startRow = 0;
										}

										/*
										 * 如果结束行大于总行数，则结束行设为总行数
										 */
										if ($this->req->endRow > $this->totalRows){
												$this->req->endRow = $this->totalRows;
										}

										$ids_array = $this->fetch_id();

										$set_id_result = xcache_set($id_key,$ids_array,1800);
										$set_db_info_result = xcache_set($db_info_array_key,$this->db_info_array,1800);

										if(!$set_id_result || !$set_db_info_result){
												$this->clear_cache();
												xcache_set($id_key,$ids_array,1800);
												xcache_set($db_info_array_key,$this->db_info_array,1800);
										}

										$return_data = $this->fetch_data($ids_array);
								}
						}
				}

				$this->respFetch($return_data[0],$return_data[1],$return_data[2],$return_data[3]);

				return;
		}

		/**
		 * 取回包数据
		 */
		function fetch_response_data(){	
				include Kohana::find_file('helpers','protobuf/message/pb_message');
				include Kohana::find_file('helpers','protobuf/pb_proto_dbresultset');

				$year = date('Y',$this->req->data->time);
				$mon = date('m',$this->req->data->time);
				$mday = date('d',$this->req->data->time);

				$dbfile = Kohana::config('core.datapath') . "/$year/$mon/$mday.db";
				$db = new PDO("sqlite:$dbfile");
				$sql = "select response_data from event where id = " . $this->req->data->id;
				$sth = $db->prepare($sql);
				$sth->execute();
				$result = $sth->fetchAll(PDO::FETCH_ASSOC);

				$data = array();

				if ( $result ){
						$pb = $result[0]['response_data'];	

						$dbresultset = new _DbResultSet();		

						$dbresultset->parseFromString($pb);

						$field_name_size = $dbresultset->field_name_size();

						$row_size = $dbresultset->row_size();

						/**
						 * 生成字段名的数组，此外做了一个特殊处理，主要针对oracle的返回字段名中不带表名，有可能造成字段名的重复
						 */
						$field_names = array();
						for($i=0; $i < $field_name_size; $i++){
								$field_name = $dbresultset->field_name($i);
								$k = 1;
								while (in_array($field_name, $field_names)){
										$field_name .= "@$k";
										$k++;
								}
								$field_names[] = $field_name;
						}

						for($x=0; $x < $row_size; $x++){
								$row = $dbresultset->row($x);

								$value_size = $row->value_size();

								for($y=0; $y < $value_size; $y++){
										if($row->value($y) === FALSE){
												$data[$x][$field_names[$y]] = '';
										}else{
												$encodings = array('ASCII','UTF-8','GBK');
												$data[$x][$field_names[$y]] = mb_convert_encoding($row->value($y), "UTF-8", $encodings);
										}
								}
						}
				}

				$count = count($data);
				$this->respFetch(0, $count, $count, $data);

		}

		/*
		 * 把事件类型ID转换为事件类型名称
		 */
		private function map_event_type($event_type_id){
				switch ($event_type_id) {
						case "17":
								$event_type = 'Oracle';
						break;
						case "18":
								$event_type = 'MS SQL Server';
						break;
						case "33":
								$event_type = 'Web';
						break;
						case "34":
								$event_type = 'Telnet';
						break;
						case "35":
								$event_type = 'FTP';
						break;
						default:
						$event_type = $event_type_id;
				}

				return $event_type;
		}

		/*
		 * 把事件危急程度ID转换为事件危急程度名称
		 */
		private function map_criticality($criticality){
				switch ($criticality) {
						case "1":
								$criticality = '记录';
						break;
						case "2":
								$criticality = '轻微';
						break;
						case "3":
								$criticality = '敏感';
						break;
						case "4":
								$criticality = '危险';
						break;
				}

				return $criticality;
		}

		/*
		 * 查询配置库
		 * 生成审计应用信息数据 $this->audit_site_id_info 
		 * 生成审计服务信息数据 $this->service_id_info
		 */
		private function get_config_info(){	
				$auditsites = ORM::factory('auditsite')->find_all();

				$this->audit_site_id_info = array();
				$this->service_id_info = array();

				foreach($auditsites as $auditsite){
						if($auditsite->deleted == 1){
								if(preg_match('/^(.+?)@\w{8}$/',$auditsite->name,$matches)){
										$auditsite->name = $matches[1];
								}
						}

						$this->audit_site_id_info[$auditsite->id] = $auditsite->name;
				}

				$auditservices = ORM::factory('auditservice')->find_all();

				foreach($auditservices as $auditservice){
						if($auditservice->deleted == 1){
								if(preg_match('/^(.+?)@\w{8}$/',$auditservice->name,$matches)){
										$auditservice->name = $matches[1];
								}
						}

						$this->service_id_info[$auditservice->id] = $auditservice->name;
				}		
		}

		/* 
		 * 把审计对象ID转换为审计对象名称
		 */
		private function map_audit_site_id($audit_site_id){
				if(array_key_exists($audit_site_id,$this->audit_site_id_info)){
						$auditsite_name = $this->audit_site_id_info[$audit_site_id];
				}else{
						$auditsite_name = $audit_site_id;
				}

				return $auditsite_name;
		}

		/*
		 * 把审计服务ID转换为审计服务名称
		 */
		private function map_service_id($service_id){
				if(array_key_exists($service_id,$this->service_id_info)){
						$service_name = $this->service_id_info[$service_id];
				}else{
						$service_name = $service_id;
				}

				return $service_name;
		}

		/*
		 * 直接从session中读取查询条件，将查询到的数据生成excel文件
		 */
		function export(){					
				include Kohana::find_file('helpers','php-excel.class');

				$this->req->data = $this->session->get('data_session');						

				$this->fetch_type = "export";

				if(!$this->req->data){
						echo file_get_contents(Kohana::find_file('libraries','login_request'));	
						exit;	
				}

				if(isset($this->req->data->id)){
						$return_data = $this->fetch_single($this->req);
				}elseif(isset($this->req->data->sessionId)){
						$return_data = $this->fetch_bysessionid($this->req);
				}else{
						$req_temp = $this->req->data;

						if(isset($this->req->data->force_fetch)){
								unset($req_temp->data->force_fetch);
						}

						$key = md5(serialize($req_temp));

						$id_key = $key . "_id";
						$totalRows_key = $key . "_totalRows";
						$db_info_array_key = $key . "_db_info_array";

						if( xcache_isset($id_key) && xcache_isset($totalRows_key) && xcache_isset($db_info_array_key) ){
								$ids_array = xcache_get($id_key);
								$this->totalRows = xcache_get($totalRows_key);
								$this->db_info_array = xcache_get($db_info_array_key);
								$return_data = $this->fetch_data($ids_array);
						}else{
								$array_TS = $this->strip_db($this->req->data->startTime, $this->req->data->endTime);
								$this->generate_sql($this->req);
								$this->calc_count($array_TS);

								$ids_array = $this->fetch_id();

								$set_id_result = xcache_set($id_key,$ids_array,1800);
								$set_totalRows_result = xcache_set($totalRows_key,$this->totalRows,1800);
								$set_db_info_result = xcache_set($db_info_array_key,$this->db_info_array,1800);

								if(!$set_id_result || !$set_totalRows_result || !$set_db_info_result){
										$this->clear_cache();
										xcache_set($id_key,$ids_array,1800);
										xcache_set($totalRows_key,$this->totalRows,1800);
										xcache_set($db_info_array_key,$this->db_info_array,1800);
								}

								$return_data = $this->fetch_data($ids_array);
						}
				}

				$raw_datas = $return_data[3];

				$this->get_config_info();		

				$data[] = array('事件编号','时间','应用名称','服务名称','服务类型','事件级别','操作者','客户端IP地址','客户端端口','客户端MAC地址','操作内容','操作详细内容');		

				foreach($raw_datas as $raw_data){
						$event_type = $this->map_event_type($raw_data['event_type']);
						$auditsite_name = $this->map_audit_site_id($raw_data['audit_site_id']);
						$service_name = $this->map_service_id($raw_data['service_id']);
						$criticality = $this->map_criticality($raw_data['criticality']);

						$data[] = array($raw_data['id'],$raw_data['occur_time'],$auditsite_name,$service_name,$event_type,$criticality,$raw_data['user_name'],$raw_data['client_ip'],$raw_data['client_port'],$raw_data['client_mac'],$raw_data['operation'],$raw_data['content']);
				}

				$filename = "report";

				$xls = new Excel_XML;
				$xls->addArray($data);
				$output = $xls->generateXML($filename);
		}
}
?>
