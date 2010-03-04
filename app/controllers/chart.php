<?php
defined('SYSPATH') OR die('No direct access allowed.');

class Chart_Controller extends SmartgwtController {

	const ALLOW_PRODUCTION = TRUE;

	function __construct(){
		parent::__construct();
		include Kohana::find_file('helpers','ofc/open-flash-chart');
	}

	function disk(){
		$disk_used = trim(shell_exec("df -h|grep /usr/data |awk '{print $3}'"));
		$disk_available = trim(shell_exec("df -h|grep /usr/data |awk '{print $4}'"));

		$title = new title( "磁盘使用情况\n当前已使用 $disk_used ，剩余 $disk_available");
		$title->set_style( "{font-size: 12px; font-family: Times New Roman; font-weight: bold; color: #A2ACBA; text-align: center;}" );

		$used_percent = trim(shell_exec("df -h|grep /usr/data |awk '{print $5}'"));
		$used_percent = substr($used_percent,0,strlen($used_percent)-1);
		settype($used_percent,'float');

		$available_percent = 100-$used_percent;

		$pie = new pie();
		$pie->set_alpha(0.7);
		$pie->set_start_angle(0);
		$pie->add_animation( new pie_fade() );

		$pie->set_tooltip( '#val#%' );

		$pie->set_colours( array('#FF368D','#1C9E05') );

		$d = array();
		$d[] = new pie_value($used_percent,$used_percent . "%" );
		$d[] = new pie_value($available_percent,$available_percent . "%");

		$pie->set_values($d);

		$chart = new open_flash_chart();
		$chart->set_title( $title );
		$chart->add_element( $pie );
		$chart->set_bg_colour( '#FFFFFF' );

		$chart->x_axis = null;

		header("Cache-Control: cache, must-revalidate");
		header("Pragma: public");

		echo $chart->toPrettyString();
	}

	function loadaverage(){
		sleep(1);
		$uptime = trim(shell_exec("cat /proc/uptime  |awk '{print $1}' |cut -d '.' -f 1"));
		$load_average_1min = trim(shell_exec("cat /proc/loadavg |awk '{print $1}'"));
		$load_average_5min = trim(shell_exec("cat /proc/loadavg |awk '{print $2}'"));
		$load_average_15min = trim(shell_exec("cat /proc/loadavg |awk '{print $3}'"));	

		if($load_average_1min <= 1){
			$stats = '当前系统空闲';
		}elseif($load_average_1min <= 4){
			$stats = '当前系统正常';
		}else{
			$stats = '当前系统繁忙';
		}

		$days = floor($uptime/86400);
		$hours = floor(($uptime%86400)/3600);
		$minutes = floor(($uptime%3600)/60);

		$title = new title( "系统平均负载\n$stats");
		$title->set_style( "{font-size: 12px; font-family: Times New Roman; font-weight: bold; color: #A2ACBA; text-align: center;}" );

		settype($load_average_1min,"float");
		settype($load_average_5min,"float");
		settype($load_average_15min,"float");

		$load_average = array($load_average_1min,$load_average_5min,$load_average_15min);

		function colour($value){
			if($value <= 1){
				$colour = "#339900";
			}elseif($value <= 4){
				$colour = "#FFFF00";
			}else{
				$colour = "#FF0033";
			}

			return $colour;
		}

		if(max($load_average) <= 0.5){
			$y_axis_max = 0.5;
		}elseif(max($load_average) <= 1){
			$y_axis_max = 1;
		}elseif(max($load_average) <= 5){
			$y_axis_max = 5;
		}elseif(max($load_average) <= 10){
			$y_axis_max = 10;
		}elseif(max($load_average) <= 50){
			$y_axis_max = 50;
		}else{
			$y_axis_max = 100;
		}

		$y_axis_step = $y_axis_max/5;

		if($load_average_1min == 0){
			$data[0] = new bar_value($y_axis_max/100);
			$data[0]->set_colour(colour($load_average_1min));
			$data[0]->set_tooltip(0);
		}else{
			$data[0] = new bar_value($load_average_1min);
			$data[0]->set_colour(colour($load_average_1min));
			$data[0]->set_tooltip( '#val#' );
		}

		if($load_average_5min == 0){
			$data[1] = new bar_value($y_axis_max/100);
			$data[1]->set_colour(colour($load_average_5min));
			$data[1]->set_tooltip(0);
		}else{
			$data[1] = new bar_value($load_average_5min);
			$data[1]->set_colour(colour($load_average_5min));
			$data[1]->set_tooltip( '#val#' );
		}

		if($load_average_15min == 0){
			$data[2] = new bar_value($y_axis_max/100);
			$data[2]->set_colour(colour($load_average_15min));
			$data[2]->set_tooltip(0);
		}else{
			$data[2] = new bar_value($load_average_15min);
			$data[2]->set_colour(colour($load_average_15min));
			$data[2]->set_tooltip( '#val#' );
		}

		$bar = new bar_glass();
		$bar->set_values( $data );

		$x = new x_axis();
		$x->set_labels_from_array(array('1分钟','5分钟','15分钟')); 

		$y = new y_axis();
		$y->set_range(0, $y_axis_max, $y_axis_step);

		$chart = new open_flash_chart();
		$chart->set_title( $title );
		$chart->add_element( $bar );
		$chart->set_bg_colour( '#FFFFFF' );
		$chart->set_x_axis($x);
		$chart->set_y_axis( $y );

		$x_legend = new x_legend( "系统当前已运行 $days 天 $hours 小时 $minutes 分钟");
		$x_legend->set_style( '{font-size: 12px; color: #778877}' );
		$chart->set_x_legend($x_legend);

		header("Cache-Control: cache, must-revalidate");
		header("Pragma: public");

		echo $chart->toString();
	}

	function netio(){
		function bit_to_kb($n){
			return round(($n/1000),2);
		}

		function bit_to_mb($n){
			return round(($n/1000000),2);
		}

		function get_data(){
			$recv_l =  trim(shell_exec("cat /sys/class/net/eth0/statistics/rx_bytes"))/8;
			sleep(1);
			$recv_n =  trim(shell_exec("cat /sys/class/net/eth0/statistics/rx_bytes"))/8;
			return  $recv_n - $recv_l;
		}

		if (array_key_exists('netio', $_SESSION) && array_key_exists('recv_l', $_SESSION)) {
			if(count($_SESSION['netio']) == 10){
				array_shift($_SESSION['netio']);
				$_SESSION['netio'][] = get_data();
			}else{
				$_SESSION['netio'][] = get_data();
				$_SESSION['recv_l'] = end($_SESSION['netio']);
			}
		}else{
			$_SESSION['netio'] = array(0,0,0,0,0,0,0,0,0);
			$_SESSION['netio'][] = get_data();
			$_SESSION['recv_l'] = end($_SESSION['netio']);
		}

		$data = $_SESSION['netio'];
		/*
		   $data = array();

		   for($i=0;$i<40;$i++){
		   $data[] = rand(1000000,10000000);
		   }
		 */

		foreach(range(1,10) as $i){
			settype($i,'string');
			$second[] = $i;
		}

		if(max($data) <= 1000){
			$data = array_map("bit_to_kb",$data);
			$y_axis_max = 1;
			$y_axis_key_text = " KB/s";
		}elseif(max($data) <= 10000){
			$data = array_map("bit_to_kb",$data);
			$y_axis_max = 10;
			$y_axis_key_text = " KB/s";
		}elseif(max($data) <= 100000){
			$data = array_map("bit_to_kb",$data);
			$y_axis_max = 100;
			$y_axis_key_text = " KB/s";
		}elseif(max($data) <= 1000000){
			$data = array_map("bit_to_kb",$data);
			$y_axis_max = 1000;
			$y_axis_key_text = " KB/s";
		}elseif(max($data) <= 10000000){
			$data = array_map("bit_to_mb",$data);
			$y_axis_max = 10;
			$y_axis_key_text = " MB/s";
		}else{
			$data = array_map("bit_to_mb",$data);
			$y_axis_max = 100;
			$y_axis_key_text = " MB/s";
		}

		$y_axis_step = $y_axis_max/5;

		$chart = new open_flash_chart();

		$title = new title("实时流量显示");
		$title->set_style( "{font-size: 12px; color: #A2ACBA; text-align: center;}" );

		$chart->set_title($title);

#点是指曲线图上的顶点
#		$d = new dot();
#		$d->colour('#9C0E57')->size(3);

		$area = new area();

#width是指曲线的宽度
#		$area->set_width(3);
#		$area->set_default_dot_style($d);
		$area->set_colour( '#5B56B6' );
#value即曲线顶的值
		$area->set_values($data);
#左上角的文字
		$area->set_key($y_axis_key_text,10);

		$area->set_fill_colour('#CCCAAA');

#设透明度
		$area->set_fill_alpha(0.3);


#area设置结束，使用add_element方法把area加进来
		$chart->add_element($area);
		$chart->set_bg_colour( '#FFFFFF' );

#设置label
		$x_labels = new x_axis_labels();
		$x_labels->set_steps(1);
		$x_labels->set_colour('#A2ACBA');

		$x_labels->set_labels( $second );

#设置X轴
		$x_axis = new x_axis();
		$x_axis->set_colour('#A2ACBA');
		$x_axis->set_grid_colour( '#D7E4A3' );
		$x_axis->set_offset( false );
		$x_axis->set_steps(1);
		$x_axis->set_labels($x_labels);

		$chart->set_x_axis($x_axis);

#设置X轴的文件说明，即x_legend

		$legend_text = "当前网络流量 " . end($data) . $y_axis_key_text;
		$x_legend = new x_legend( $legend_text );
		$x_legend->set_style( '{font-size: 12px; color: #778877}' );
		$chart->set_x_legend($x_legend);
#设置轴
		$y_axis = new y_axis();
		$y_axis->set_range(0, $y_axis_max, $y_axis_step);

		$y_axis->labels = null;
		$y_axis->set_offset(false);
		$chart->add_y_axis($y_axis);

		header("Cache-Control: cache, must-revalidate");
		header("Pragma: public");

		echo $chart->toPrettyString();
	}
}
?>
