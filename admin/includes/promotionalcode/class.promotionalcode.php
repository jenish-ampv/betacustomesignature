<?php
class CIT_PROMOTIONALCODE{
	private $count;
	private $result;
	private $promotionalcode;
	private $id ='';
	public function __construct(){
		$GLOBALS['ModuleName'] = "Coupon";		
	}
	public function displayPage(){		
		if(isset($_REQUEST['action'])){
			$action = trim($_REQUEST['action']);
		} else {
			$action = '';
		}
		switch($action){
			case "delete":
				$this->deletePromotionalcode();
				break;
			case "edit":
				$this->editPromotionalcode();
				break;
			case "add":
				$this->addPromotionalcode();
				break;
			case "ajax":
				$this->ajaxPromotionalcode();
				break;	
			case "view":
				$this->viewPromotionalcode();
				break;
			case "status":
				$this->statusPromotionalcode();
				break;	
			case "datatablepromodata";
				$this->datatablepromo();
				break;
			default:
				$this->promotionalcode();
				break;			
		}
	}

	private function ajaxPromotionalcode(){
		if(isset($_REQUEST['id'])){
			$id = $_REQUEST['id'];
		} else {
			$id =  '';
		}	
		if($id == 1){
			}elseif($id == 2){
			echo '<li>
                    <div class="lft_txt">Authorised Person<span style="color:red">*</span>:</div>
                    <div class="rgt_ipt"><input type="text" name="ap" id="ap" /></div>
                  </li>
                    <li>
                    <div class="lft_txt">Designation<span style="color:red">*</span>:</div>
                    <div class="rgt_ipt"><input type="text" name="desg" id="desg" /></div>
                  </li>
                    <li>
                    <div class="lft_txt">Contact Number<span style="color:red">*</span>:</div>
                    <div class="rgt_ipt"><input type="text" name="cn" id="cn" /></div>
                  </li>
                    <li>
                    <div class="lft_txt">Address<span style="color:red">*</span>:</div>
                    <div class="rgt_ipt"><input type="text" name="add" id="add" /></div>
                  </li>';	
				}
	}

	private function viewPromotionalcode(){
		if(isset($_REQUEST['id'])){
			$id = $_REQUEST['id'];
		} else {
			$id =  '';
		}				
		if(is_numeric($id)){			
			$row = $GLOBALS['DB']->row("SELECT * FROM `promotionalcode` WHERE `id` = ?  LIMIT 0,1",array($_REQUEST['id']));	;
			$GLOBALS['GetName'] = $row['name'];
			$GLOBALS['GetCode'] = $row['code'];
			$GLOBALS['GetAmount'] = $row['amount'];
			$GLOBALS['GetDate'] = $row['expdate'];
			$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/'.$GLOBALS['Module'].'/promotionalcode.view.html');				
			$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');			
			$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');						
			$GLOBALS['LEFT'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.left.html');			
			$GLOBALS['CLA_HTML']->display();
		} else {
			$_SESSION['Error'] = '<div class="alert alert-danger" role="alert">Record not valid.</div>';
			GetAdminRedirectUrl(GetAdminUrl(array('module'=>$_REQUEST['module']),0));	
		}
	}

	private function statusPromotionalcode(){
		if(isset($_REQUEST['id'])){
			$promotionalcode_id = $_REQUEST['id'];
		} else {
			$promotionalcode_id =  '';
		}				
		if(is_numeric($promotionalcode_id)){
			
			$addResult = $GLOBALS['DB']->update("promotionalcode",array('status'=>$_REQUEST['status']),array('id'=>$_REQUEST['id'])); 
			if($addResult){
				$_SESSION['Success'] = '<div class="alert alert-success" role="alert">Status changed successfully</div>';
			} else {
				$_SESSION['Error'] = '<div class="alert alert-danger" role="alert">An error occurred while you trying to change status, please try again.</div>';
			}
			GetAdminRedirectUrl();
		} else {
			$_SESSION['Error'] = '<div class="alert alert-danger" role="alert">Record not valid.</div>';
			GetAdminRedirectUrl(GetAdminUrl(array('module'=>$_REQUEST['module']),0));
		}						
	}

	private function deletePromotionalcode(){
		if(isset($_REQUEST['id'])){
			$id = $_REQUEST['id'];
		} else {
			$id =  '';
		}
		if(is_numeric($id)){		
			$addResult = $GLOBALS['DB']->query("DELETE FROM `promotionalcode` WHERE `id` = ?",array($id));
			if($addResult){
				$_SESSION['Success'] = '<div class="alert alert-success" role="alert">Promotionalcode deleted successfully</div>';
				GetAdminRedirectUrl(GetAdminUrl(array('module'=>$_REQUEST['module'])));	
			} else {
				$_SESSION['Error'] = '<div class="alert alert-danger" role="alert">An error occurred while you trying to delete promotionalcode, please try again.</div>';
				GetAdminRedirectUrl(GetAdminUrl(array('module'=>$_REQUEST['module']),0));	
			}
		} else {
			$_SESSION['Error'] = '<div class="alert alert-danger" role="alert">Record not valid.</div>';
			GetAdminRedirectUrl(GetAdminUrl(array('module'=>$_REQUEST['module']),0));	
		}				
	}

	private function editPromotionalcode(){
		if(isset($_REQUEST['id'])){
			$id = $_REQUEST['id'];
		} else {
			$id =  '';
		}				
		if(is_numeric($id)){
			if(isset($_POST['code'])){
				if($_POST['name'] !="" && $_POST['code'] !=""){
					if(isset($_POST['website'])){
						$display_website = implode(',',$_POST['website']);
					}else{
						$display_website ='0';
					}
					$expdate = date('Y-m-d', strtotime($_POST['expdate']));
					$data =array('name'=>$_POST['name'],'code'=>$_POST['code'],'amount'=>$_POST['amount'],'amount_type'=>$_POST['amount_type'],'type'=>0,'website'=>$display_website,'expdate'=>$expdate);
					$where = array('id'=>$id);
					$updateResult = $GLOBALS['DB']->update("promotionalcode",$data,$where);
					if($updateResult){
						$_SESSION['Success'] = '<div class="alert alert-success" role="alert">Promotionalcode updated successfully</div>';
					} else {
						
						$_SESSION['Error'] = '<div class="alert alert-danger" role="alert">An error occurred while you trying to update promotionalcode, please try again.</div>';
					}				
					GetAdminRedirectUrl($GLOBALS['CURRENT_URL']); 
				} else {
					$_SESSION['Error'] = '<div class="alert alert-danger" role="alert">Enter Required Field.</div>';
				}
			}
			$row  = $GLOBALS['DB']->row("SELECT * FROM `promotionalcode` WHERE `id` = ? LIMIT 0,1",array($_REQUEST['id']));	
			$GLOBALS['Id'] = $row['id'];	
			$GLOBALS['Name'] = $row['name'];
			$GLOBALS['Code'] = $row['code'];
			$GLOBALS['Amount'] = $row['amount'];
			$GLOBALS['AmountType'.$row['amount_type']] = 'selected="selected"';
			$GLOBALS['Expdate'] =  date('d-m-Y', strtotime($row['expdate']));
			if($row['website'] == 0){
				$GLOBALS['sel_all'] = 'selected="selected"';
			}
			$selectResultsite = $GLOBALS['DB']->query("SELECT * FROM `plan` ORDER BY `plan_id` DESC LIMIT 0,999");
			$display_plan  = explode(",",$row['website']);
			foreach($selectResultsite as $siterow){	
				if(in_array($siterow['plan_id'],$display_plan)){
					$plan_select = 'selected="selected"'; 
				}else{
					$plan_select= '';
				}
				$GLOBALS['SITELIST'].='<option value="'.$siterow['plan_id'].'" '.$plan_select.'>'.$siterow['plan_name'].'</option>';
			}	
			$this->updateErrorMessage();
			AddMessageInfo();			
			$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/'.$GLOBALS['Module'].'/promotionalcode.edit.html');				
			$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');			
			$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');						
			$GLOBALS['LEFT'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.left.html');			
			$GLOBALS['CLA_HTML']->display();
			RemoveMessageInfo();			
		} else {
			$_SESSION['Error'] = '<div class="alert alert-danger" role="alert">Record not valid.</div>';
			GetAdminRedirectUrl(GetAdminUrl(array('module'=>$_REQUEST['module']),0));	
		}
	}

	private function addPromotionalcode(){
		if($_POST['name'] != '' && $_POST['code'] != ''){
			if(isset($_POST['website'])){
				$display_website = implode(',',$_POST['website']);
			}else{
				$display_website ='0';
			}
			$expdate = date('Y-m-d', strtotime($_POST['expdate']));
			$data =array('status'=>1,'name'=>$_POST['name'],'code'=>$_POST['code'],'amount'=>$_POST['amount'],'amount_type'=>$_POST['amount_type'],'type'=>0,'website'=>$display_website,'expdate'=>$expdate);
			$addResult = $GLOBALS['DB']->insert("promotionalcode",$data);
			if($addResult){
				$_SESSION['Success'] = '<div class="alert alert-success" role="alert">Promotionalcode added successfully</div>';
			} else {
				
				$_SESSION['Error'] = '<div class="alert alert-danger" role="alert">An error occurred while you trying to add promotionalcode, please try again.</div>';
			}
		} else {
			$_SESSION['Error'] = '<div class="alert alert-danger" role="alert">Required fields should not blank.</div>';
		}				
		GetAdminRedirectUrl();
	}

	private function datatablepromo(){
		$draw = $_POST['draw'];
		$row = $_POST['start'];
		$rowperpage = $_POST['length'];
		$columnIndex = $_POST['order'][0]['column'];
		$columnName = $_POST['columns'][$columnIndex]['data'];
		$columnSortOrder = $_POST['order'][0]['dir'];
		$searchValue = $_POST['search']['value'];
		$searchQuery = " ";
		if($searchValue != ''){
			$searchQuery = " and (name like '%".$searchValue."%' OR code like '%".$searchValue."%' OR amount like '%".$searchValue."%')";
		}
		$records = $GLOBALS['DB']->row("select count(*) as allcount FROM `promotionalcode` WHERE 1".$searchQuery);
		$totalRecordwithFilter = $records['allcount'];
		$promoRecords = $GLOBALS['DB']->query("select * FROM `promotionalcode` WHERE 1". $searchQuery." order by ".$columnName." ".$columnSortOrder." limit ".$row.",".$rowperpage);
		$data = array();
		$count=1; 
		foreach($promoRecords as $row){
			$promotionalcode[$count]['Id'] = $row['id'];	
			$promotionalcode[$count]['Name'] = $row['name'];
			$promotionalcode[$count]['Code'] = $row['code'];
			$promotionalcode[$count]['Amount'] = $row['amount'];
			$GLOBALS['Expdate'] =  GetOnlyDate($row['expdate']);//date('d-m-Y', strtotime($row['expdate']));
			if($row['amount_type'] == 0){
				$GLOBALS['AmountType'] = '$';
			}else{
				$GLOBALS['AmountType'] = '%';
			}
			if($row['status']){
				$GLOBALS['StatusLink'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'status','id'=>$row['id'],'status'=>'0'));
				$GLOBALS['Statusclass'] = 'c-gr f-22 feather icon-toggle-right';
			} else {
				$GLOBALS['StatusLink'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'status','id'=>$row['id'],'status'=>'1'));
				$GLOBALS['Statusclass'] = 'c-rd f-22 feather icon-toggle-left';
			}				
			$GLOBALS['li_ajax'] = GetAdminUrl(array('module'=>'promotionalcode','action'=>'ajax'));
			$GLOBALS['li_edit'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'edit','id'=>$row['id']));
			$GLOBALS['li_view'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'view','id'=>$row['id']));
			$GLOBALS['li_delete'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'delete','id'=>$row['id']));
			$GLOBALS['on_delete'] = 'return jsConfirm("","delete")';
			$count++;
			$data[] = array(
				"status"=>"<a href='".$GLOBALS['StatusLink']."' class='".$GLOBALS['Statusclass']."'></a>",
				"name"=>'<a href="'.$GLOBALS['li_view'].'" class="view-link" >'.$row['name'].'</a>',
				"code"=>'<a href="'.$GLOBALS['li_view'].'" class="view-link" >'.$row['code'].'</a>',
				"amount"=>'<a href="'.$GLOBALS['li_view'].'" class="view-link" >'.$GLOBALS['AmountType'].$row['amount'].'</a>',
				"expdate"=>'<a href="'.$GLOBALS['li_view'].'" class="view-link" >'.$GLOBALS['Expdate'].'</a>',
				"action"=>"<a href='".$GLOBALS['li_view']."' class='f-20 feather icon-eye' title='View'>&nbsp;</a><a href='".$GLOBALS['li_edit']."' class='f-20 feather icon-edit' title='Edit'>&nbsp;</a><a href='".$GLOBALS['li_delete']."' class='f-20 feather icon-trash' title='Delete' onclick='".$GLOBALS['on_delete']."'>&nbsp;</a>"
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

	private function promotionalcode(){
		$this->updateErrorMessage();
		AddMessageInfo();
		$GLOBALS['li_promodata'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'datatablepromodata'));
		$selectResultsite = $GLOBALS['DB']->query("SELECT * FROM `plan` WHERE plan_status=1 ORDER BY `plan_id` DESC LIMIT 0,999");
		foreach($selectResultsite as $siterow){		
			$GLOBALS['SITELIST'].='<option value="'.$siterow['plan_id'].'">'.$siterow['plan_name'].'</option>';
		}			
		$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/'.$GLOBALS['Module'].'/promotionalcode.html');	
		$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');			
		$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');						
		$GLOBALS['LEFT'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.left.html');			
		$GLOBALS['CLA_HTML']->SetLoop('PROMOTIONALCODE',$promotionalcode);
		$GLOBALS['CLA_HTML']->display();
		RemoveMessageInfo();		
	}	

	private function updateErrorMessage(){
		if(isset($_SESSION['Error'])){
			$_SESSION['Error'] = str_replace("<div class='alert alert-danger' role='alert'> for key 'email'",'. Entered email address already exist. Try again.</div>',$_SESSION['Error']);
		}	
	}
}