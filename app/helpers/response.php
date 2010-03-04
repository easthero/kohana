<?php
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
	header('Content-type: application/json');
	echo json_encode($json);		
}

function respFailed($message){
	$json['response']['status'] = -1;
	$json['response']['data']  = $message;

	// header('Content-Type: application/json');
	header('Cache-Control: no-cache, no-store, max-age=0, must-revalidate');
	header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
	header('Content-type: application/json');
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
?>
