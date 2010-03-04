<?php
defined('SYSPATH') OR die('No direct access allowed.');

class Report_Controller extends SmartgwtController {
	const ALLOW_PRODUCTION = TRUE;

	function __construct(){
		parent::__construct();

		if (isset($this->req->data->startTime)){
			$this->startTime = strtotime($this->req->data->startTime);
		}

		if (isset($this->req->data->endTime)) {
			$this->endTime = strtotime($this->req->data->endTime);
		}

		if (isset($this->req->data->time_type)) {
			$this->time_type = $this->req->data->time_type;
		}

		if (isset($this->req->data->service_id)) {
			$this->service_id = $this->req->data->service_id;
		}

		if( isset($this->req->data->criticality) ){
			$this->criticality = $this->req->data->criticality;
		}else{
			$this->criticality = FALSE;
		}

		if ( isset($this->req->data->client_ip) ){
			$this->client_ip = $this->req->data->client_ip;
		}else{
			$this->client_ip = FALSE;
		}

		if ( isset($this->req->data->user_name) ){
			$this->user_name = $this->req->data->user_name; 
		}else{
			$this->user_name = FALSE;
		}

		if ( isset($this->req->data->operation) ){
			$this->operation = $this->req->data->operation; 
		}else{
			$this->operation = FALSE;
		}

		if ( isset($this->req->data->service_id) ){
			$this->service_id = $this->req->data->service_id;
		}else{
			$this->service_id = FALSE;
		}

	}

	/**
	 * 计算时间轴
	 */
	function get_time_axis($startTime, $endTime, $time_type){
		switch($time_type){
		case "day":
			foreach(range(0,23) as $i){
				$time_axis[] = $i;
			}
			break;

		case "week":
			$startTime = mktime(0, 0, 0, date("m",$startTime), date("d", $startTime), date("Y", $startTime) );
			$endTime = mktime(0, 0, 0, date("m",$endTime), date("d", $endTime), date("Y", $endTime) );

			for($current = $startTime; $current <= $endTime; $current = $current + 86400){
				$time_axis[] = date("j", $current);
			}
			break;

		case "month":
			foreach(range(1, date("t", $startTime)) as $i){
				$time_axis[] = $i;
			}
			break;

		case "year":
			foreach(range(1, 12) as $i){
				$time_axis[] = $i;
			}
			break;
		}

		return $time_axis;
	}

	/**
	 * 计算应该从哪个统计数据库取数据
	 * @param $startTime string 开始时间
	 * @param $endTime string 结束时间
	 * @param $time_type string 时间类型
	 */
	function get_dbfiles($startTime, $endTime, $time_type){
		switch($time_type){
		case "day":
			$dbfiles_temp = array(Kohana::config('core.datapath') . "/" . date("Y", $startTime). "/" . date("m", $startTime) . "/" . date("d", $startTime) . "_stat.db");			
			break;

		case "week":			
			if ( date("Y", $startTime) != date("Y", $endTime) || date("m", $startTime) != date("m", $endTime) ){
				$dbfiles_temp = array(
					Kohana::config('core.datapath') . "/" . date("Y", $startTime). "/" . "stat_m" . date("m", $startTime) . ".db",			  	
					Kohana::config('core.datapath') . "/" . date("Y", $endTime). "/" . "stat_m" . date("m", $endTime) . ".db"
				);
			}else{
				$dbfiles_temp = array(
					Kohana::config('core.datapath') . "/" . date("Y", $startTime). "/" . "stat_m" . date("m", $startTime) . ".db"
				);
			}
			break;

		case "month":
			$dbfiles_temp = array(
				Kohana::config('core.datapath') . "/" . date("Y", $startTime). "/" . "stat_m" . date("m", $startTime) . ".db"
			);				
			break;

		case "year":
			$dbfiles_temp = array(
				Kohana::config('core.datapath') . "/" . date("Y", $startTime). "/" . "stat.db"
			);		 	
			break;
		}

		$dbfiles = array();

		foreach($dbfiles_temp as $dbfile){
			if(file_exists($dbfile) ){
				$dbfiles[] = $dbfile;
			}
		}

		return $dbfiles;
	}

	/**
	 * 计算指定service_id的相关信息
	 * @param $service_id int 服务ID
	 * 返回格式如下
	 *Array
	 *(
	 *	[$service_id 1] => Array
	 *	(
	 *		[name] => oracle_server
	 *		[from_table] => stat_oracle_event
	 *	)
	 *
	 *	[$service_id 2] => Array
	 *	(
	 *		[name] => telnet_server
	 *		[from_table] => stat_telnet_event
	 *	)
	 *)
	 */	
	function get_service_info($service_id){
		if ( $service_id ){		
			$auditservices = ORM::factory('auditservice')->where(array('id' => $service_id, 'deleted' => 0))->find_all();
		}else{
			$auditservices = ORM::factory('auditservice')->where('deleted',0)->find_all();
		}

		foreach($auditservices as $auditservice){
			switch ($auditservice->service_type) {
			case "17":
				$from_table = "stat_oracle_event";
				break;
			case "18":
				$from_table = "stat_sqlserver_event";
				break;
			case "33":
				$from_table = "stat_web_event";
				break;
			case "34":
				$from_table = "stat_telnet_event";
				break;
			case "35":
				$from_table = "stat_ftp_event";
				break;
			}

			$services_info[$auditservice->id] = array('name' => $auditservice->name, 'from_table' => $from_table);
		}

		return $services_info;
	}

	/**
	 * 生成报表条件页面
	 */
	function index(){
		$this->report_view = new View('report');
		$this->report_view->render(TRUE);	
	}

	function chartrender(){	
		$this->chartrender_view = new View('chartrender');
		$this->report_model = new Report_Model;

		$time_axis = $this->get_time_axis($this->startTime, $this->endTime, $this->time_type);
		$dbfiles = $this->get_dbfiles($this->startTime, $this->endTime, $this->time_type);
		$services_info = $this->get_service_info($this->service_id);

		$stacked_chart_data = $this->report_model->get_stacked_chart_data($dbfiles, $time_axis, $this->startTime, $this->endTime,  $this->time_type, $services_info, $this->client_ip, $this->user_name, $this->criticality, $this->operation);

		$top10_types = array('client_ip', 'user_name', 'operation');	
		$top10_chart_data = $this->report_model->get_top10_chartdata($top10_types, $dbfiles, $time_axis, $this->startTime, $this->endTime,  $this->time_type, $services_info, $this->client_ip, $this->user_name, $this->criticality, $this->operation);

		$this->chartrender_view->set('stacked_chart_data', $stacked_chart_data);
		$this->chartrender_view->set('startTime', $this->startTime);
		$this->chartrender_view->set('endTime', $this->endTime);
		$this->chartrender_view->set('client_ip', $this->client_ip);
		$this->chartrender_view->set('user_name', $this->user_name);
		$this->chartrender_view->set('operation', $this->operation);
		$this->chartrender_view->set('criticality', $this->criticality);
		$this->chartrender_view->set('services_info', $services_info);
		$this->chartrender_view->set('time_axis', $time_axis);
		$this->chartrender_view->set('time_type', $this->time_type);
		$this->chartrender_view->set('top10_chart_data', $top10_chart_data);

		$this->chartrender_view->render(TRUE);
	}

	function topdf(){
		$this->service_id = $_POST['service_id'];
		$this->startTime = strtotime($_POST['startTime']);
		$this->endTime = strtotime($_POST['endTime']);
		$this->time_type = $_POST['time_type'];

		require_once Kohana::find_file('helpers', 'tcpdf/config/lang/eng');
		require_once Kohana::find_file('helpers', 'tcpdf/mypdf');

		$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

		$pdf->SetCreator("uooly");
		$pdf->SetAuthor('上海雍立信息科技有限公司');
		$pdf->SetTitle('雍立ASM应用安全审计报表');
		$pdf->SetSubject('雍立ASM应用安全审计报表');

		$logo_img = '../../../../assets/images/logo.png';

		$logo_width = 15;
		$header_title = "雍立ASM应用安全审计";
		$header_string = "2010年度报表";

		$pdf->SetHeaderData($logo_img, $logo_width, $header_title, $header_string);

		$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
		$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

		$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
		$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

		$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO); 

		$pdf->setLanguageArray($l); 

		$pdf->AddPage();

		$pdf->SetFont('stsongstdlight', '', 10);

		if (! $this->client_ip) $client_ip = "所有IP地址";
		if (! $this->user_name) $user_name = "所有用户";
		if (! $this->criticality) $criticality = "所有严重级别";
		if (! $this->operation) $operation = "所有操作";

		$startTime = date("Y-m-d H:i:s", $this->startTime);
		$endTime = date("Y-m-d H:i:s", $this->endTime);

		switch($this->time_type){
		case "day":
			$chart_type = "日报";
			break;
		case "week":
			$chart_type = "周报";
			break;
		case "month":
			$chart_type = "月报";
			break;
		case "year":
			$chart_type = "年报";
			break;
		}

		$tbl = "<h1>hello world</h1>";
		$tbl = "<div>";
		$tbl .= '<table border="1" id="summary_table">';
		$tbl .= '<tr>';
		$tbl .= '<th colspan=2>统计条件</th>';
		$tbl .= '</tr>';
		$tbl .= '<tr>';
		$tbl .= '<td>时间段包含</td>';
		$tbl .= "<td>$startTime 至 $endTime</td>";
		$tbl .= '</tr>';
		$tbl .= '<tr>';
		$tbl .= '<td>客户端包括</td>';
		$tbl .= "<td>$client_ip</td>";
		$tbl .= '</tr>';
		$tbl .= '<tr>';
		$tbl .= '<td><font color="red">用户名包括</font></td>';
		$tbl .= "<td>$user_name</td>";
		$tbl .= '</tr>';
		$tbl .= '<tr>';
		$tbl .= '<td>操作包括</td>';
		$tbl .= "<td>$operation</td>";
		$tbl .= '</tr>';
		$tbl .= '<tr>';
		$tbl .= '<td>紧急程度</td>';
		$tbl .= "<td>$criticality</td>";
		$tbl .= '</tr>';
		$tbl .= '</table>';
		$tbl .= "</div>";
		$tbl .= "<div>";
		$tbl .= '<img src="assets/export/stacked.jpg" width="500" height="310">';
		$tbl .= "</div>";
		$tbl .= "<div>";
		$tbl .= '<img src="assets/export/1_clientip_column2d.jpg" width="500" height="310">';
		$tbl .= "</div>";
		$tbl .= "<div>";
		$tbl .= '<img src="assets/export/1_username_column2d.jpg" width="500" height="310">';
		$tbl .= "</div>";
		$tbl .= "<div>";
		$tbl .= '<img src="assets/export/1_operation_column2d.jpg" width="500" height="310">';
		$tbl .= "</div>";
		$pdf->writeHTML($tbl, true, false, false, false, '');

		$pdf->Output('example_038.pdf', 'I');

	}
}
?>
