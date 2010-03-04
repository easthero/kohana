<?php
defined('SYSPATH') OR die('No direct access allowed.');

class Test_Controller extends SmartgwtController {

	const ALLOW_PRODUCTION = TRUE;

	function index(){

//		define('FPDF_FONTPATH','/Users/easthero/Sites/pdf/font/');


		include Kohana::find_file('helpers','fpdf/chinese-unicode');

		$pdf = new PDF_Unicode(); 

		$pdf->Open ();
		$pdf->AddPage ();

		$pdf->AddUniGBhwFont('msyh','微软雅黑'); 
		$pdf->SetFont('msyh','',20); 
		
		$operationlog_orm = ORM::factory('operationlog')->find_all();
		
		foreach($operationlog_orm as $object){
			$time = $object->occur_time;
			$time = date("Y-m-d H:i:s",$time);
			
			$pdf->Write(12,$time);
			$pdf->Write(12,$object->user_id);
			$pdf->Write(12,$object->from_ip);
			$pdf->Write(12,$object->description);
			
			
			$pdf->Ln();
		}

		$pdf -> Output();

	}
}
?>
