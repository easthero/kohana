<?php defined('SYSPATH') OR die('No direct access allowed.');

class Report_Model extends Model {
	/**
	 * 生成按时间分布统计的SQL语句
	 */
	function generate_sql_groupbytime($service_id, $from_table, $startTime, $endTime, $time_type, $client_ip, $user_name, $criticality, $operation){
		if ($time_type == "day"){
			$sql = "select (stat_time - stat_time % 3600) as stattime,";
		}else{
			$sql = "select stat_time as stattime,";
		}

		$sql .= " sum(event_count) as event_count from $from_table where service_id = $service_id";

		if ( $client_ip ){
			$sql .= " and client_ip = '" . $client_ip . "' ";
		}

		if ( $user_name ){
			$sql .= " and user_name = '" . $user_name . "' ";
		}

		if ( $criticality ){
			$sql .= " and criticality = $criticality";
		}

		if ( $operation ){
			$sql .= " and operation = '" . $operation . "' ";
		}

		if ($time_type == "week"){
			$sql .= " and stat_time >= $startTime and stat_time <= $endTime";
		}

		$sql .= " group by stattime order by stat_time";

		return $sql;
	}

	/**
	 * 生成按类型统计top10的SQL语句
	 */
	function generate_sql_top10($top_type, $db_type, $service_id, $from_table, $startTime, $endTime, $time_type, $client_ip, $user_name, $criticality, $operation){
		$sql = "select $top_type, sum(event_count) as eventcount";

		if ($db_type == "singledb"){
			$sql .= " from $from_table";
		}else{
			$sql .= " from (select * from $from_table union select * from db02.$from_table)";
		}

		$sql .= " where service_id = $service_id";

		if ( $client_ip ){
			$sql .= " and client_ip = '" . $client_ip . "' ";
		}

		if ( $user_name ){
			$sql .= " and user_name = '" . $user_name . "' ";
		}

		if ( $criticality ){
			$sql .= " and criticality = $criticality";
		}

		if ( $operation ){
			$sql .= " and operation = '" . $operation . "' ";
		}

		if ($time_type == "week"){
			$sql .= " and stat_time >= $startTime and stat_time <= $endTime";
		}

		$sql .= " group by $top_type order by eventcount desc limit 10";

		return $sql;		
	}

	/**
	 * 获取按时间分布的统计数据
	 */
	function get_stats_data($chart_type, $dbfiles, $sql, $time_axis, $time_type){
		$result_array = array();

		if(count($dbfiles) != 0){
			foreach($dbfiles as $dbfile){
				$db = new PDO("sqlite:$dbfile");
				$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				try{
					$sth = $db->prepare($sql);
					$sth->execute();
					$result = $sth->fetchAll(PDO::FETCH_ASSOC);
					$result_array = array_merge($result_array,$result);
				}catch(PDOException $e){
					Kohana::log('error', "在对 $dbfile 进行分时摘要统计时发生数据库查询错误，原因: " . $e->getMessage());
					continue;
				}
			}
		}else{
			Kohana::log('error', "在进行分时摘要统计时发生数据库查询错误，原因: 数据库不存在");
		}

		if ( count($result_array) == 0 ){
			foreach($time_axis as $time){
				$statsdata[$time] = 0;
			}
		}else{
			foreach($result_array as $item){
				switch($time_type){
				case "day":
					$statsdata_temp[ date("G", $item['stattime']) ] = $item['event_count'];;
					break;
				case "week":
					$statsdata_temp[ date("j", $item['stattime']) ] = $item['event_count'];;
					break;
				case "month":
					$statsdata_temp[ date("j", $item['stattime']) ] = $item['event_count'];;
					break;
				case "year":
					$statsdata_temp[ date("n", $item['stattime']) ] = $item['event_count'];;
					break;
				}
			}

			/**
			 * 对数据进行补全，如果某个时间点内无数据，则该时间点的数据为0
			 */
			foreach($time_axis as $time){
				if (array_key_exists($time, $statsdata_temp)){
					switch ($chart_type){
					case "stacked":
						$statsdata[$time] = $statsdata_temp[$time];
						break;
					case "line":
						$statsdata[] = array($time,$statsdata_temp[$time]);
						break;
					}
				}else{
					switch ($chart_type){
					case "stacked":
						$statsdata[$time] = 0; 
						break;
					case "line":
						$statsdata[] = array($time,0);
						break;
					}
				}
			}
		}

		return $statsdata;		
	}

	/**
	 * 生成分时堆叠图数据
	 * 返回格式如下
	 *Array
	 *(
	 *	[$service_id 1] => Array
	 *	(
	 *		[0] => $service_name oracle9.2
	 *		[1] => 
	 *		[2] => 26655
	 *			...
	 *			...
	 *		[25] => 31435
	 *	)
	 *
	 *	[$service_id 2 ] => Array
	 *	(
	 *		[0] => $service_name telnet
	 *		[1] => 
	 *		[3] => 31060
	 *			...
	 *			...
	 *		[25] => 30439
	 *	)
	 *)
	 */
	function get_stacked_chart_data($dbfiles, $time_axis, $startTime, $endTime,  $time_type, $services_info, $client_ip, $user_name, $criticality, $operation){
		foreach($services_info as $service_id => $service_info){
			$from_table = $service_info['from_table'];
			$service_name = $service_info['name'];

			$sql = $this->generate_sql_groupbytime($service_id, $from_table, $startTime, $endTime, $time_type, $client_ip, $user_name, $criticality, $operation);

			$stacked_chartdata[$service_id] = array($service_name,"");

			$stacked_chartdata[$service_id] = array_merge($stacked_chartdata[$service_id], $this->get_stats_data("stacked", $dbfiles, $sql, $time_axis, $time_type));
		}

		return $stacked_chartdata;
	}

	/**
	 * 生成每个service的top10数据
	 * 返回格式如下
	 *Array
	 *(
	 * ['client_ip'] => Array
	 * 	(
	 *		[$service_id 1] => Array
	 *		(
	 *			[0] => Array
	 *			(
	 *				[0] => $client_ip 192.168.0.7
	 *				[1] => 81217
	 *			)
	 * 
	 *			[9] => Array
	 *			(
	 *				[0] => $client_ip 192.168.0.10
	 *				[1] => 46821
	 *			)
	 * 		)
	 *	)
	 *)
	 */
	function get_top10_chartdata($top10_types, $dbfiles, $time_axis, $startTime, $endTime,  $time_type, $services_info, $client_ip, $user_name, $criticality, $operation){
		foreach($top10_types as $stats_type){
			foreach($services_info as $service_id => $service_info){
				$from_table = $service_info['from_table'];
				$service_name = $service_info['name'];

				/**
				 * 如果有两个库则需要跨库查询，进行union操作
				 */
				$singledb_sql = $this->generate_sql_top10($stats_type, "singledb", $service_id, $from_table, $startTime, $endTime, $time_type, $client_ip, $user_name, $criticality, $operation);
				$twodb_sql = $this->generate_sql_top10($stats_type, "twodb", $service_id, $from_table, $startTime, $endTime, $time_type, $client_ip, $user_name, $criticality, $operation);

				$result = array();

				if (count($dbfiles) == 0){
					Kohana::log('error', "在进行top10 $stats_type 统计时发生数据库查询错误，原因: 数据库文件不存在");
				} elseif (count($dbfiles) == 1) {
					try{
						$db = new PDO("sqlite:$dbfiles[0]");
						$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
						$sth = $db->prepare($singledb_sql);
						$sth->execute();
						$result = $sth->fetchAll(PDO::FETCH_ASSOC);
					}catch(PDOException $e){
						Kohana::log('error', "在对 $dbfiles[0] 进行top10 $stats_type 统计时发生数据库查询错误，原因: " . $e->getMessage());				
					}
				}else{
					try{
						$db = new PDO("sqlite:$dbfiles[0]");
						$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
						$attach_sql = "attach '$dbfiles[1]' as db02";
						$db->exec($attach_sql);
						$sth = $db->prepare($twodb_sql);			
						$sth->execute();
						$result = $sth->fetchAll(PDO::FETCH_ASSOC);					
						$db->exec("detach db02");					
					}catch(PDOException $e){
						$errormsg = "在对 $dbfiles[0] 和 $dbfiles[1] 进行top10 $stats_type 统计时发生数据库查询错误，失败原因: " . $e->getMessage() . ", 将继续对 $dbfiles[0] 进行查询";		
						Kohana::log('error', $errormsg);	
						try{
							$db = new PDO("sqlite:$dbfiles[0]");
							$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
							$sth = $db->prepare($singledb_sql);
							$sth->execute();
							$result = $sth->fetchAll(PDO::FETCH_ASSOC);
						}catch(PDOException $e){
							Kohana::log('error', "在对 $dbfiles[0] 进行top10 $stats_type 统计时发生数据库查询错误，原因: " . $e->getMessage());				
							try{
								$db = new PDO("sqlite:$dbfiles[1]");
								$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
								$sth = $db->prepare($singledb_sql);
								$sth->execute();
								$result = $sth->fetchAll(PDO::FETCH_ASSOC);	
							}catch(PDOException $e){
								Kohana::log('error', "在对 $dbfiles[1] 进行top10 $stats_type 统计时发生数据库查询错误，原因: " . $e->getMessage());
							}
						}
					}
				}

				if($result){
					foreach($result as $item){
						$data[$stats_type][$service_id][] = array($item[$stats_type], $item['eventcount']);
					}
				}else{
					$data[$stats_type][$service_id] = array();
				}

			}		
		}
		return $data;
	}
}
?>
