<?php

class CIT_CONTACTUS{
	private $count;
	private $result;
	private $user;
	private $id ='';
	public function __construct(){	
		$GLOBALS['ModuleName'] = "Contact Us";	
				
	}
	
	public function displayPage(){
		if(isset($_REQUEST['action'])){
			$action = trim($_REQUEST['action']);
		} else {
			$action = '';
		}
		switch($action){
			case "delete":
				$this->deleteContactUs();
				break;
			case "view":
				$this->viewContactUs();
				break;	
			case "datatablecontactus";
				$this->datatableContactUs();
				break;
			default:
				$this->contactus();
				break;			
		}
	}
	
	public function deleteContactUs(){
		if(isset($_REQUEST['id'])){
			$id = $_REQUEST['id'];
		} else {
			$id =  '';
		}
		if(is_numeric($id)){		
			$addResult = $GLOBALS['DB']->query("DELETE FROM `contactus` WHERE `id` = ?", array($id));		
			$altResult = $GLOBALS['DB']->query("ALTER TABLE `contactus` AUTO_INCREMENT = 1");
			if($addResult){
				$_SESSION['Success'] = '<div class="gap-8 py-5 px-4 pl-11 border-l-9 border-green-600 rounded-xl relative bg-white bg-gradient-to-r from-[#00B71B]/12 to-[#00B71B]/0 shadow-lg" role="alert">Contact us deleted successfully</div>';
			} else {
				$_SESSION['Error'] = '<div class="alert alert-danger" role="alert">An error occurred while you trying to delete auto locator, please try again.</div>';
			}
		} else {
			$_SESSION['Error'] = '<div class="alert alert-danger" role="alert">Contact us not valid.</div>';
		}	
		GetAdminRedirectUrl();
	}	
	
	public function viewContactUs(){
		if(isset($_REQUEST['id'])){
			$id = $_REQUEST['id'];
		} else {
			$id =  '';
		}
		if($GLOBALS['View_contactus']!=0){
			if(is_numeric($id)){
				$row = $GLOBALS['DB']->row("SELECT * FROM `contactus` WHERE `id`= ? ORDER BY `id` ASC LIMIT 0,1",array($id));
				$GLOBALS['Name'] = $row['name'];	
				$GLOBALS['Phone'] = $row['phone'];			
				$GLOBALS['Email'] = $row['email'];
				$GLOBALS['Address'] = $row['address'];
				$GLOBALS['Msg'] = $row['message'];	
				foreach($row as $rowkey=>$rowval){
					$GLOBALS['get'.$rowkey] = $rowval;						   
				}
				AddMessageInfo();			
				$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/'.$GLOBALS['Module'].'/contactus.view.html');				
				$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');			
				$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');						
				$GLOBALS['LEFT'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.left.html');			
				$GLOBALS['CLA_HTML']->display();
				RemoveMessageInfo();			
			}else{
				$_SESSION['Error'] = 'Page not valid.';
				GetAdminRedirectUrl();
			}	
		}else{
			$_SESSION['Error'] = '<div class="alert alert-danger" role="alert">Acess denied for this user!!.</div>';
			GetAdminRedirectUrl(GetAdminUrl(array('module'=>$_REQUEST['module']),0));	
		}
	}
	
	public function contactus(){
		AddMessageInfo();
		$GLOBALS['li_contactusdata'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'datatablecontactus'));
		$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/'.$GLOBALS['Module'].'/contactus.html');	
		$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');			
		$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');						
		$GLOBALS['LEFT'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.left.html');			
		$GLOBALS['CLA_HTML']->SetLoop('CONTACTUS',$contactus);
		$GLOBALS['CLA_HTML']->display();
		RemoveMessageInfo();		
	}
	
	private function datatableContactUs(){
		$draw = $_POST['draw'];
		$row = $_POST['start'];
		$rowperpage = $_POST['length'];
		$columnIndex = $_POST['order'][0]['column'];
		$columnName = $_POST['columns'][$columnIndex]['data'];
		$columnSortOrder = $_POST['order'][0]['dir'];
		$searchValue = $_POST['search']['value'];
		$searchQuery = " ";
		if($searchValue != ''){
			$searchQuery = " and (name like '%".$searchValue."%' OR email like '%".$searchValue."%')";
		}
		$records = $GLOBALS['DB']->row("select count(*) as allcount FROM `contactus` WHERE `site_id` = ".$GLOBALS['SITE_ID'].$searchQuery);
		$totalRecordwithFilter = $records['allcount'];

		$contactusRecords = $GLOBALS['DB']->query("select * FROM `contactus` WHERE `site_id` = ".$GLOBALS['SITE_ID']." ". $searchQuery." order by ".$columnName." ".$columnSortOrder." limit ".$row.",".$rowperpage);

		$data = array();
		$count=1; 
		foreach ($contactusRecords as $row) {
			// $GLOBALS['Date'] = GetDateFormat($row['date']);
			$GLOBALS['Date'] = GetDateTimeFormat($row['date']);
			$GLOBALS['li_view'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'view','id'=>$row['id']));		
			$GLOBALS['li_delete'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'delete','id'=>$row['id']));	
			$GLOBALS['on_delete'] = 'return jsConfirm("","delete")';
			$li_view = "<a href='".$GLOBALS['li_view']."' class='f-20 feather icon-eye' title='View'>&nbsp;</a>";
		
			if($GLOBALS['usertype'] == 1){
				$li_delete = "<a href='".$GLOBALS['li_delete']."' class='f-20 feather icon-trash' title='Delete' onclick='".$GLOBALS['on_delete']."'>&nbsp;</a>";
			}
			$data[] = array(
				"id"=>$count,
				"name"=>$row['name'],
				"email"=>$row['email'],
				"date"=>$GLOBALS['Date'],
				"action"=>$li_view.' '.$li_delete
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