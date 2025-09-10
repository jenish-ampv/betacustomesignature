<?php
class CIT_ERRORLOG{
	private $count;
	private $result;
	private $user;
	private $id ='';
	public function __construct(){	
		$GLOBALS['ModuleName'] = "SQL Error Log (Today)";	
				
	}
	
	public function displayPage(){
		if(isset($_REQUEST['action'])){
			$action = trim($_REQUEST['action']);
		} else {
			$action = '';
		}
		switch($action){
			case "datatableerrorlog";
				$this->datatableErrorLog();
				break;
			default:
				$this->errorlog();
				break;			
		}
	}
	

	
	
	public function errorlog(){
		AddMessageInfo();
		$GLOBALS['li_errorlogdata'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'datatableerrorlog'));
		
		$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/'.$GLOBALS['Module'].'/errorlog.html');	
		$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');			
		$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');						
		$GLOBALS['LEFT'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.left.html');			
		$GLOBALS['CLA_HTML']->SetLoop('CONTACTUS',$contactus);
		$GLOBALS['CLA_HTML']->display();
		RemoveMessageInfo();		
	}
	
	private function datatableErrorLog(){
		$draw = $_POST['draw'];
		$row = $_POST['start'];
		$rowperpage = $_POST['length'];
		$columnIndex = $_POST['order'][0]['column'];

		$loglines = array();
		$date = new DateTime();
		$fileSalt = DBName. md5(DBPassword);
		$filename =  $date->format('Y-m-d') . "-" . md5($date->format('Y-m-d') . $fileSalt) . ".txt";
		$filepath =  GetConfig('SITE_BASE_PATH')."\lib\pdo\logs/".$filename;
		if (file_exists($filepath)) {
			$myfile = fopen($filepath, "r") or die("Unable to open file!");
			$logsdata =  fread($myfile,filesize($filepath));
			$loglines = explode('Time :',$logsdata);
			fclose($myfile);
			unset($loglines[0]);
		}
	
		$totalRecordwithFilter = count($loglines);
		
		$data = array();
		$count=1; 
		foreach($loglines as $errorlog){
			$log = explode('SQLSTATE',$errorlog);
			$data[] = array(
				"id"=>$count,
				"time"=>$log[0],
				"error"=>'SQLSTATE'.$log[1],
			);
			$count++;
		}			
		$response = array(
			"draw" => intval($draw),
			"iTotalDisplayRecords" => $totalRecordwithFilter,
			"aaData" => $data
		);
		echo json_encode($response);
	}

}