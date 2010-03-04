<?php
defined('SYSPATH') OR die('No direct access allowed.');

class Operationlog_Controller extends SmartgwtController {

	const ALLOW_PRODUCTION = TRUE;

	private $sql_count;
	private $sql_select;
	private $dbfile;

	function __construct(){
		parent::__construct();

		$this->db = Database::instance();

		/*
		 * 请求的时间格式为 YYYY-mm-dd hh:mm:ss
		 * 使用strtotime转换为unix timestamp
		 * 如果没有设置起止时间,则默认结束时间为当前时间,开始时间为当前时间前3小时
		 */
		if (isset($this->req->data->startTime) && isset($this->req->data->endTime)) {
			$this->req->data->startTime = strtotime($this->req->data->startTime);
			$this->req->data->endTime = strtotime($this->req->data->endTime);
		}else{
			$this->req->data->endTime = time();
			$this->req->data->startTime = $this->req->data->endTime-60*60*3;
		}
	}

	function fetch(){		
		/*
		 * 如果开始行大于结束行，则开始行设为0
		 */
		if ($this->req->startRow > $this->req->endRow){
			$this->req->startRow = 0;
		}

		/*
		 * 如果开始时间大于结束时间，则报错退出
		 */
		if($this->req->data->startTime > $this->req->data->endTime){
			$message = '结束时间必须晚于开始时间，请重新选择时间';
			return $this->respFailed($message);
		}

		$operationlog = ORM::factory('operationlog');

		if(isset($this->req->data->user_id)){
			$operationlog = $operationlog->where('user_id',$this->req->data->user_id);
		}

		if(isset($this->req->data->from_ip)){
			$operationlog = $operationlog->where('from_ip',$this->req->data->from_ip);
		}

		if(isset($this->req->data->operation_id)){
			$operationlog = $operationlog->where('operation_id',$this->req->data->operation_id);
		}

		if(isset($this->req->data->keyword)){
			$operationlog = $operationlog->like('description',$this->req->data->keyword);
		}

		$operationlog = $operationlog->find_all();

		$data = array();

		foreach($operationlog as $result){
			$data[] = array(
					'id' => $result->id,
					'occur_time' => date("Y-m-d h:m:s", $result->occur_time),
					'user_id' => $result->user_id,
					'from_ip' => $result->from_ip,
					'object_id' => $result->object_id,
					'object_name' => $result->object_name,
					'result' => $result->result,
					'description' => $result->description		
					);	
		}				

		$totalRows = count($data);

		return $this->respFetch($this->req->startRow,$this->req->endRow,$totalRows,$data);

	}

}
?>
