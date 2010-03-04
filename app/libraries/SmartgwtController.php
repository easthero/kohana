<?php defined('SYSPATH') or die('No direct script access.');
class SmartgwtController_Core extends Controller {

	protected $req;
	protected $request_uri;
	protected $from_ip;
	protected $user_id;
	protected $data_oldvalues_diff;
	protected $operationType;
	
	function dump($object){
		echo "<pre>";
		print_r($object);
		echo "</pre";
	}

	function __construct(){
		parent::__construct();
		
		include Kohana::find_file('libraries','tableinfo');

		$this->session = Session::instance();

		/**
		 * request_url的格式为 /xaudit/webservice/auditsite/fetch
		 * 其中 operationType 为 fetch
		 * 操作的表为 auditsite
		 */

		$this->request_uri = $_SERVER["REQUEST_URI"];

		$this->from_ip = $_SERVER["REMOTE_ADDR"];

		if(preg_match('/::ffff:(.+)/',$this->from_ip,$matches)){
			$this->from_ip = $matches[1];
		}elseif($this->from_ip == '::1'){
			$this->from_ip = '127.0.0.1';
		}

		preg_match('/([^\/]+)\/([^\/]+)$/',$this->request_uri,$matches);

		$table_info = $matches[1] . "_info";

		$this->operationType = $matches[2];

		/**
		 * 判断用户是否登录
		 */
		if($this->operationType != 'index'){
			if(!$this->session->get('username')){
				echo file_get_contents(Kohana::find_file('libraries','login_request'));	
				exit;
			}
		}

		$this->user_id = $this->session->get('user_id');
		$this->role_id = $this->session->get('role_id');

		$operation_orm = ORM::factory('operation')->where('operation',$this->request_uri)->find();

		/**
		 * 验证当前用户的role_id和当前操作所需要的角色权限是否匹配,如果不匹配则返回登录请求
		 */
		if($operation_orm->loaded){
			$this->operation_id = $operation_orm->id;
			$this->operation_name = $operation_orm->name;

			if($this->operationType != 'index'){
				if (($this->role_id & $operation_orm->roles) === 0){
					echo file_get_contents(Kohana::find_file('libraries','login_request'));	
					exit;	
				}
			}
		}

		$this->req = json_decode(file_get_contents("php://input"));	
				
		/**
		 * 对$this->req进行必要的处理，包括去除为空或者以下划线打头的的成员，初始化部分成员
		 */
		$this->strip_item($this->req);

		$this->trim_item($this->req);

		$this->req = $this->req_init($this->req);

		if(isset($this->req->data) && is_object($this->req->data)){
			$this->req->data_array = get_object_vars($this->req->data);
		}   

		if(isset($this->req->oldValues) && is_object($this->req->oldValues)){
			$this->req->oldValues_array = get_object_vars($this->req->oldValues);
		}

		if(isset($this->$table_info)){
			$this->table_info = $this->$table_info;
			$this->validate();			
		}
	}

	/**
	 * @param: array $data_array $req->data 转换成的数组
	 * @param: array $oldValues_array $req->oldValues_array转换成的数组，如果$req->oldValues为空，则该数组由数据库查询得到
	 * 1.对前台请求更新的数据和老数据进行对比，得到一个新的数组只包含需要的修改的数据 $update_array
	 * 2.再根据$update_array和$oldValues_array生成一个数组，用于写操作日志
	 * 结构如下
	 * array(
	 * "字段名" => array("新数据" => "老数据"),
	 * "字段名" => array("新数据" => "老数据")
	 * )
	 */
	function update_data_diff($data_array,$oldValues_array){		
		$diff = array();

		$update_array = array_diff_assoc($data_array,$oldValues_array);

		foreach($update_array as $key => $newvalue){
			$oldvalue = $oldValues_array[$key];
			$diff[$key] = array($newvalue => $oldvalue);
		}

		return $diff;
	}

	/**
	 * @param: object $object
	 * 递归删除为空或者名称以下划线打头的成员
	 */
	function strip_item($object){
		if(is_object($object)){
			foreach($object as $key => $value){
				if($key[0] == "_"){
					unset($object->$key);
				}elseif(is_object($value)){
					$this->strip_item($object->$key);
				}elseif(($this->operationType != 'update') && ($value !== 0)){
					if(!is_array($object->$key)){
						if(( trim($value) == '' ) || ( is_null($value) )){
							unset($object->$key);
						}
					}
				} 
			}   
		}   
	}

	/**
	 * @param: object $object
	 * 递归删除成员的值前后的空格
	 */
	function trim_item($object){
		if(is_object($object)){
			foreach($object as $key => $value){
				if(is_object($value)){
					$this->trim_item($object->$key);
				}else{
					if(is_string($object->$key)){
						$object->$key = trim($object->$key);
					}
				} 
			}   
		}
	}

	/**
	* @param object $req
	 * 对req对象进行处理
	 * 如果必须的成员为空则对它进行赋值
	 */
	function req_init($req){
		if(!isset($req->startRow)){
			$req->startRow = 0;
		}   

		if(!isset($req->endRow)){
			$req->endRow = $req->startRow + 75;
		}   

		return $req;
	}

	function get_id(){
		if(isset($this->req->oldValues->id)){
			$id = $this->req->oldValues->id;
		}else{
			$id = $this->req->data->id;
		}

		return $id;
	}

	function get_data_array(){
		if(isset($this->req->oldValues_array)){
			$data_array = array_merge($this->req->oldValues_array, $this->req->data_array);
		}else{
			$data_array = $this->req->data_array;
		}

		return $data_array;
	}

	/**
	 * 对请求进行校验
	 * 先校验必填的字段是否填写
	 * 再校验字段是否符合特定的格式
	 * 将没有通过校验的字段放入$validation_errors数组并返回
	 */
	function validate(){
		$validation_errors = array();

		/*
		 * 只有操作类型为add时才验证必填字段是否填写
		 */
		if($this->operationType == 'add' ){
			foreach($this->table_info as $column){
				if(!isset($this->req->data->$column['name']) && $column['notnull'] === 1) {
					$validation_errors[] = $column['name'];
				}
			}
		}

		if($validation_errors){
			$this->respReqError($validation_errors);
			exit;
		}else{
			foreach($this->table_info as $column){
				if(isset($this->req->data->$column['name'])){
					/*
					 * 如果数据为空字符，并且允许为空，则跳过不校验，否则需要进行正则表达式校验
					 */
					if(($column['notnull'] === 0) && ($this->req->data->$column['name'] == '')){
						continue;
					}elseif(isset($column['pattern'])){
						$pattern = $column['pattern'];
						if(!preg_match($pattern, $this->req->data->$column['name'])){
							$validation_errors[] = $column['name'];
						}
					}
				}		
			}
		}

		if($validation_errors){
			$this->respReqError($validation_errors);
			exit;
		}
	}

	function respFetch($startRow,$endRow,$totalRows,$data){
		$json['response']['status'] = 0;
		$json['response']['startRow'] = $startRow;
		$json['response']['endRow'] = $endRow;
		$json['response']['totalRows'] = $totalRows;
		$json['response']['data'] = $data;

		// header('Content-Type: application/json');
		header('Cache-Control: no-cache, no-store, max-age=0, must-revalidate');
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		
		echo json_encode($json);
	}

	function respOk($data){
		$json['response']['status'] = 0;
		$json['response']['data'] = $data;

		// header('Content-Type: application/json');
		header('Cache-Control: no-cache, no-store, max-age=0, must-revalidate');
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		echo json_encode($json);		
	}

	function respFailed($message){
		$json['response']['status'] = -1;
		$json['response']['data']  = $message;

		// header('Content-Type: application/json');
		header('Cache-Control: no-cache, no-store, max-age=0, must-revalidate');
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		echo json_encode($json);
	}

	function respReqError($failed_data){
		$json['response']['status']  = -4;
		$json['response']['message']  = 'req data validate failed';

		foreach($failed_data as $column){
			if(isset($this->table_info[$column]['error'])){
				$error[$column] = array('errorMessage' => $this->table_info[$column]['error']);
			}else{
				$error[$column] = array('errorMessage' => '字段格式错误');
			}
		}

		$json['response']['errors'] = $error;
	
		// header('Content-Type: application/json');
		header('Cache-Control: no-cache, no-store, max-age=0, must-revalidate');
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		echo json_encode($json);
	}

	function respNameError(){
		$json['response']['status']  = -4;
		$json['response']['message']  = 'req data validate failed';

		$json['response']['errors'] = array('name' => array('errorMessage' => '名称必须唯一，请重新输入'));

		// header('Content-Type: application/json');
		header('Cache-Control: no-cache, no-store, max-age=0, must-revalidate');
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		echo json_encode($json);
	}

	function operationlog_add($result,$object_id,$object_name,$data){
		$description = '';
		foreach($data as $key => $value){
			if(isset($this->table_info[$key]['alias'])){
				if($this->table_info[$key]['type'] == 'bool'){
					if($value == 1){
						$value = '是';
					}else{
						$value = '否';
					}
				}
				$alias = $this->table_info[$key]['alias'];

				$description .= "$alias:$value; ";
			}
		}

		/*
		 * 如果$object_id为0，则表示操作失败,result为-1,否则表示操作成功，result为0，result字段和status一致
		 */
		if($result === 0){
			$description = "$this->operation_name $object_name 成功,添加的内容为:\"" . $description . "\"";
		}else{
			$description = "$this->operation_name $object_name 失败,添加的内容为:\"" . $description . "\"";
		}

		$operationlog_orm = ORM::factory('operationlog');
		$operationlog_orm->occur_time = time();
		$operationlog_orm->user_id = $this->user_id;
		$operationlog_orm->from_ip = $this->from_ip;
		$operationlog_orm->operation_id = $this->operation_id;
		$operationlog_orm->object_id = $object_id;
		$operationlog_orm->object_name = $object_name;
		$operationlog_orm->result = $result;
		$operationlog_orm->description = $description;
		$operationlog_orm->save();
	}	

	function operationlog_update($result,$object_id,$object_name,$diff_array){
		$description = '';

		foreach($diff_array as $key => $value){
			foreach($value as $new => $old){
				if($new === ''){
					$new = '空';
				}

				if($old === ''){
					$old = '空';
				}
				if(isset($this->table_info[$key]['alias'])){
					if($this->table_info[$key]['type'] == 'bool'){
						if($new == 1){
							$new = '是';
						}else{
							$new = '否';
						}

						if($old == 1){
							$old = '是';
						}else{
							$old = '否';
						}
					}
					$alias = $this->table_info[$key]['alias'];

					$description .= $alias . "由 " ."'" . $old . "' 修改为 '" . $new ."';";
				}
			}
		}

		/*
		 * 如果$object_id为0，则表示操作失败,result为-1,否则表示操作成功，result为0，result字段和status一致
		 */
		if($result === 0){
			$description = "$this->operation_name $object_name 成功,修改的内容为:" . $description;			
		}else{
			$description = "$this->operation_name $object_name 失败,修改的内容为:" . $description;
		}

		$operationlog_orm = ORM::factory('operationlog');
		$operationlog_orm->occur_time = time();
		$operationlog_orm->user_id = $this->user_id;
		$operationlog_orm->from_ip = $this->from_ip;
		$operationlog_orm->operation_id = $this->operation_id;
		$operationlog_orm->object_id = $object_id;
		$operationlog_orm->object_name = $object_name;
		$operationlog_orm->result = $result;
		$operationlog_orm->description = $description;
		$operationlog_orm->save();		
	}

	function operationlog_remove($result,$object_id,$object_name){		
		if($result === 0){
			$description = "$this->operation_name $object_name 成功";
		}else{
			$description = "$this->operation_name $object_name 失败";
		}

		$operationlog_orm = ORM::factory('operationlog');
		$operationlog_orm->occur_time = time();
		$operationlog_orm->user_id = $this->user_id;
		$operationlog_orm->from_ip = $this->from_ip;
		$operationlog_orm->operation_id = $this->operation_id;
		$operationlog_orm->object_id = $object_id;
		$operationlog_orm->object_name = $object_name;
		$operationlog_orm->result = $result;
		$operationlog_orm->description = $description;
		$operationlog_orm->save();
	}

	function operationlog_misc($result,$object_id,$object_name,$description,$user_id = 0){		
		$operationlog_orm = ORM::factory('operationlog');
		$operationlog_orm->occur_time = time();

		if($user_id){
			$operationlog_orm->user_id = $user_id;
		}else{
			$operationlog_orm->user_id = $this->user_id;
		}

		$operationlog_orm->from_ip = $this->from_ip;
		$operationlog_orm->operation_id = $this->operation_id;
		$operationlog_orm->object_id = $object_id;
		$operationlog_orm->object_name = $object_name;
		$operationlog_orm->result = $result;
		$operationlog_orm->description = $description;
		$operationlog_orm->save();		
	}

	function is_using($type,$id){
		switch ($type) {
			case "appservice":
				$result = $this->is_using_service($id);
			break;
			case "dbservice":
				$result = $this->is_using_service($id);
			break;
			case "ipgroup":
				$result = $this->is_using_ipgroup($id);
			break;
			case "usergroup":
				$result = $this->is_using_usergroup($id);
			break;
			case "responseemail":
				$result = $this->is_using_responseemail($id);
			break;
			case "responsesnmp":
				$result = $this->is_using_responsesnmp($id);
			break;
			case "responsesyslog":
				$result = $this->is_using_responsesyslog($id);
			break;
		}
		return $result;
	}

	function is_using_service($id){
		$policy_orm = ORM::factory('policy')->where(array('service_id' => $id,'deleted' => 0))->find();
		if($policy_orm->loaded){
			return true;
		}else{
			return false;
		}
	}

	function is_using_ipgroup($id){
		$policyapp_orm = ORM::factory('policy')->where(array('ip_group_id' => $id,'deleted' => 0))->find();
		if($policyapp_orm->loaded){
			return true;
		}else{
			return false;
		}
	}

	function is_using_usergroup($id){
		$policyapp_orm = ORM::factory('policy')->where(array('user_group_id' => $id,'deleted' => 0))->find();
		if($policyapp_orm->loaded){
			return true;
		}else{
			return false;
		}	
	}

	function is_using_responsesyslog($id){
		$policyrespsyslog_orm = ORM::factory('policyrespsyslog')->where(array('resp_id' => $id,'deleted' => 0))->find();
		if($policyrespsyslog_orm->loaded){
			return true;
		}else{
			return false;
		}
	}

	function is_using_responseemail($id){
		$policyrespemail_orm = ORM::factory('policyrespemail')->where(array('resp_id' => $id,'deleted' => 0))->find();
		if($policyrespemail_orm->loaded){
			return true;
		}else{
			return false;
		}
	}

	function is_using_responsesnmp($id){
		$policyrespsnmp_orm = ORM::factory('policyrespsnmp')->where(array('resp_id' => $id,'deleted' => 0))->find();
		if($policyrespsnmp_orm->loaded){
			return true;
		}else{
			return false;
		}	
	}
}
?>
