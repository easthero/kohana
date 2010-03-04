<?php
defined('SYSPATH') OR die('No direct access allowed.');

class Hostinfo_Controller extends SmartgwtController {

	const ALLOW_PRODUCTION = TRUE;
	private $year;
	private $month;
	private $mday;

	function __construct(){
		parent::__construct();

		/*
		 * 请求的时间格式为 YYYY-mm-dd hh:mm:ss
		 * 使用strtotime转换为unix timestamp
		 * 如果没有设置开始时间,则默认为当天
		 */
		if (isset($this->req->data->time)) {
			$this->req->data->time = strtotime($this->req->data->time);
		}else{
			$this->req->data->time = time();
		}
		$this->year = date("Y",$this->req->data->time);
		$this->mon = date("m",$this->req->data->time);
		$this->mday = date("d",$this->req->data->time);
	}

	function fetch(){
		$sql_select = "select id,ip,mac,datetime(login_time,'unixepoch','localtime') as login_time,ip_group_id,user_group_id,host_name,login_name from hostinfo where ip = '" . $this->req->data->ip . "' and login_time <= " . $this->req->data->time . " order by login_time desc limit 1";

		$dbfile = Kohana::config('core.datapath') . "/$this->year/$this->mon/$this->mday.db";

		$db = new PDO("sqlite:$dbfile");
		$query = $db->query($sql_select);

		$result = array();

		if($query){
			$result = $query->fetch(PDO::FETCH_ASSOC);
		}

		$return_data = array(1,$result);
		return $this->respFetch($this->req->startRow,$this->req->endRow,$return_data[0],$return_data[1]);
	}
}
?>
