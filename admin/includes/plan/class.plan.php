<?php
class CIT_PLAN
{
	private $count;
	private $result;
	private $plan;
	private $id ='';
	public function __construct(){	
		$GLOBALS['ModuleName'] = 'Plan';
	}

	public function displayPage(){		
		if(isset($_REQUEST['action'])){
			$action = trim($_REQUEST['action']);
		} else {
			$action = '';
		}
		switch($action){
			case "delete":
				$this->deletePlan();
				break;
			case "edit":
				$this->editPlan();
				break;
			case "add":
				$this->addPlan();
				break;
			case "ajax":
				$this->ajaxPlan();
				break;	
			case "view":
				$this->viewPlan();
				break;
			case "status":
				$this->statusPlan();
				break;
			case "popular":
				$this->popularPlan();
				break;
			case "datatableplan";
				$this->datatableplan();
				break;	
			default:
				$this->plan();
				break;			
		}
	}
	

	private function statusPlan(){
		if(isset($_REQUEST['id'])){			
			$plan_id = $_REQUEST['id'];
		} else {
			$plan_id =  '';
		}				
		if(is_numeric($plan_id)){
			$addResult = $GLOBALS['DB']->update("plan",array('plan_status'=>$_REQUEST['status']),array('plan_id'=>$_REQUEST['id']));
			if($addResult){
				$_SESSION['Success'] = '<div class="alert alert-success">Status Changed successfully!</div>';
			} else {
				$_SESSION['Error'] = '<div class="alert alert-danger">An error occurred while you trying to change status,please try again!</div>';
			}
			GetAdminRedirectUrl();
		} else {
			$_SESSION['Error'] = 'Record not valid!';
			GetAdminRedirectUrl(GetAdminUrl(array('module'=>$_REQUEST['module']),0));
		}						
	}
	
	private function popularPlan(){
		if(isset($_REQUEST['id'])){			
			$plan_id = $_REQUEST['id'];
		} else {
			$plan_id =  '';
		}				
		if(is_numeric($plan_id)){
			$addResult = $GLOBALS['DB']->update("plan",array('plan_popular'=>$_REQUEST['status']),array('plan_id'=>$_REQUEST['id']));
			if($addResult){
				$_SESSION['Success'] = '<div class="alert alert-success">Status Changed successfully!</div>';
			} else {
				$_SESSION['Error'] = '<div class="alert alert-danger">An error occurred while you trying to change status,please try again!</div>';
			}
			GetAdminRedirectUrl();
		} else {
			$_SESSION['Error'] = 'Record not valid!';
			GetAdminRedirectUrl(GetAdminUrl(array('module'=>$_REQUEST['module']),0));
		}						
	}
	
	

	private function deletePlan(){		
		if(isset($_REQUEST['id'])){
			$id = $_REQUEST['id'];
		} else {
			$id =  '';
		}
		if(is_numeric($id)){		
			$addResult = $GLOBALS['DB']->query("DELETE FROM `plan` WHERE `plan_id` = ?",array($id));
			if($addResult){
				$_SESSION['Success'] = '<div class="alert alert-success">Plan Deleted Successfully!</div>';
				GetAdminRedirectUrl(GetAdminUrl(array('module'=>$_REQUEST['module'])));	
			} else {
				$_SESSION['Error'] = '<div class="alert alert-danger">An error occurred while you trying to delete plan,Please try again!';
				GetAdminRedirectUrl(GetAdminUrl(array('module'=>$_REQUEST['module']),0));	
			}
		} else {
			$_SESSION['Error'] = '<div class="alert alert-danger">Record not valid.';
			GetAdminRedirectUrl(GetAdminUrl(array('module'=>$_REQUEST['module']),0));	
		}				
	}

	private function editPlan(){				
		if(isset($_REQUEST['id'])){
			$id = $_REQUEST['id'];
		} else {
			$id =  '';
		}				
		if(is_numeric($id)){
			$row = $GLOBALS['DB']->row("SELECT * FROM `plan` WHERE `plan_id` = ? LIMIT 0,1",array($_REQUEST['id']));
			$GLOBALS['plan_id'] = $row['plan_id'];	
			$GLOBALS['plan_name'] = $row['plan_name'];
			$GLOBALS['plan_feature'] = $row['plan_feature'];
			$GLOBALS['plan_type'] = $row['plan_type'];
			$GLOBALS['plan_typesel'.$row['plan_type']] = 'selected="selected"';
			$GLOBALS['plan_price'] = $row['plan_price'];
			$GLOBALS['plan_priceid'] = $row['plan_priceid'];
			$GLOBALS['plan_popular'] = $row['plan_popular'] == 1 ? 'checked' : '';
			$GLOBALS['plan_status'] = $row['plan_status'] ==1 ? 'Active' : 'DeActive';
			$GLOBALS['plan_created'] = GetDateFormat($row['plan_created']);
			$GLOBALS['plan_updated'] = GetDateFormat($row['plan_updated']);
			
			if(isset($_POST['plan_name']) && isset($_POST['plan_price']) && isset($_POST['plan_priceid'])){
				if($_POST['plan_name'] !="" && $_POST['plan_price'] !="" && $_POST['plan_priceid'] !=""){
					
						$data = array('plan_name'=>$_POST['plan_name'],'plan_feature'=>$_POST['plan_feature'],'plan_type'=>$_POST['plan_type'],'plan_price'=>$_POST['plan_price'],'plan_priceid'=>$_POST['plan_priceid'],'plan_popular'=>$_POST['plan_popular']);
						$where =array('plan_id'=>$id);
						$updateResult = $GLOBALS['DB']->update("plan",$data,$where);
						if($updateResult){
							$GLOBALS['DB']->query("DELETE FROM plan_unit WHERE plan_id = ?",array($id));
							$unit_arr = $_POST['plan_unit'];
							$u=0;
							foreach($unit_arr as $value){
								$GLOBALS['DB']->insert("plan_unit",array("plan_id"=>$id,"plan_unit"=>$value,"plan_unitprice"=>$_POST['plan_unitprice'][$u],"plan_unitsplprice"=>$_POST['plan_unitsplprice'][$u]));
							$u++; }
							$_SESSION['Success'] = '<div class="alert alert-success">Plan updated Successfully</div>';
							GetAdminRedirectUrl(GetAdminUrl(array('module'=>$_REQUEST['module']),0));	
						} else {
							$_SESSION['Error'] = '<div class="alert alert-success">An error occurred while you trying to update plan, please try again.</div>';
						}				
				} else {
					$_SESSION['Error'] = '<div class="alert alert-success">Required field shoud not be blank!</div>';
				}					
			}

			$unit_rows = $GLOBALS['DB']->query("SELECT * FROM plan_unit WHERE plan_id= ?",array($id));
			if ($unit_rows) {
				foreach ($unit_rows as $unit_row) {
					$GLOBALS['Plan_units'] .= '<div class="form-group row"><label for="inputEmail3" class="col-sm-3 col-form-label">&nbsp;</label><div class="col-sm-3"><input type="text" name="plan_unit[]" class="form-control" id="plan_unit" value="' . $unit_row['plan_unit'] . '" placeholder="No of Employee" required="required"></div><div class="col-sm-3"><input type="text" name="plan_unitprice[]" class="form-control" id="plan_unitprice" value="' . $unit_row['plan_unitprice'] . '" placeholder="Unit Price" required="required"></div><div class="col-sm-3"><input type="text" name="plan_unitsplprice[]" class="form-control" id="plan_unitsplprice" value="' . $unit_row['plan_unitsplprice'] . '" placeholder="Unit Price Displa" required="required"></div><div class="col-sm-3"><a href="javascript:void(0);" class="remove_button"><i class="f-20 feather icon-trash"></i></a></div></div>';
				}
			}else{
				$GLOBALS['Plan_units'] .= '<div class="form-group row"><label for="inputEmail3" class="col-sm-3 col-form-label">&nbsp;</label><div class="col-sm-3"><input type="text" name="plan_unit[]" class="form-control" id="plan_unit" value="" placeholder="No of Employee" required="required"></div><div class="col-sm-3"><input type="text" name="plan_unitprice[]" class="form-control" id="plan_unitprice" value="" placeholder="Unit Price" required="required"></div><div class="col-sm-3"><input type="text" name="plan_unitsplprice[]" class="form-control" id="plan_unitsplprice" value="" placeholder="Unit Price Display" required="required"></div><div class="col-sm-3">&nbsp;</div></div>';
			}

			AddMessageInfo();			
			$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/'.$GLOBALS['Module'].'/plan.edit.html');				
			$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');			
			$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');						
			$GLOBALS['LEFT'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.left.html');			
			$GLOBALS['CLA_HTML']->display();
			RemoveMessageInfo();			
		} else {
			$_SESSION['Error'] = 'Record not valid.';
			GetAdminRedirectUrl(GetAdminUrl(array('module'=>$_REQUEST['module']),0));	
		}
	}

	private function addPlan(){		
		if($_POST){	
			if($_POST['plan_name'] !="" && $_POST['plan_price'] !="" && $_POST['plan_priceid'] !=""){
					$data = array('plan_name'=>$_POST['plan_name'],'plan_feature'=>$_POST['plan_feature'],'plan_type'=>$_POST['plan_type'],'plan_price'=>$_POST['plan_price'],'plan_priceid'=>$_POST['plan_priceid'],'plan_popular'=>$_POST['plan_popular']);
					$addResult =  $GLOBALS['DB']->insert("plan",$data);				
					if($addResult){
						$_SESSION['Success'] = '<div class="alert alert-success">Plan added Successfully</div>';
						GetAdminRedirectUrl(GetAdminUrl(array('module'=>$_REQUEST['module']),0));	
					} else {
						$_SESSION['Error'] = '<div class="alert alert-danger">An error occurred while you trying to add plan, please try again.</div>';
					}
					GetAdminUrl(array('module'=>$_REQUEST['module']));
			} else {
				$_SESSION['Error'] = '<div class="alert alert-danger">Required field should not blank.</div>';
			}
		}
		
		AddMessageInfo();
	  	$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/'.$GLOBALS['Module'].'/plan.add.html');	
		$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');			
		$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');						
		$GLOBALS['LEFT'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.left.html');	
		$GLOBALS['CLA_HTML']->display();
		RemoveMessageInfo();
	}

	private function plan(){
		 AddMessageInfo();	
		$GLOBALS['li_getplandata'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'datatableplan'));
		$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/'.$GLOBALS['Module'].'/plan.html');	
		$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');			
		$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');						
		$GLOBALS['LEFT'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.left.html');			
		$GLOBALS['CLA_HTML']->display();
		RemoveMessageInfo();		
	}	

	private function datatableplan(){
		$draw = $_POST['draw'];
		$row = $_POST['start'];
		$rowperpage = $_POST['length'];
		$columnIndex = $_POST['order'][0]['column'];
		$columnName = $_POST['columns'][$columnIndex]['data'];
		$columnSortOrder = $_POST['order'][0]['dir'];
		$searchValue = $_POST['search']['value'];
		$searchQuery = " ";
		if($searchValue != ''){
			$searchQuery = " and (plan_name like '%".$searchValue."%')";
		}
		$records = $GLOBALS['DB']->row("select count(*) as allcount FROM `plan` WHERE 1 ".$searchQuery);
		$totalRecordwithFilter = $records['allcount'];
		$salRecords = $GLOBALS['DB']->query("select * FROM `plan` WHERE 1 ".$searchQuery." order by ".$columnName." ".$columnSortOrder." limit ".$row.",".$rowperpage);
		$data = array();
		$count=1; 
		foreach($salRecords as $row){
			if($row['plan_status']){
				$GLOBALS['StatusImage'] = $GLOBALS['ADMIN_LINK'].'/images/status_on.png';	
				$GLOBALS['StatusLink'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'status','id'=>$row['plan_id'],'status'=>'0'));
				$GLOBALS['Statusclass'] = 'c-gr f-22 feather icon-toggle-right';
			} else {
				$GLOBALS['StatusImage'] = $GLOBALS['ADMIN_LINK'].'/images/status_off.png';	
				$GLOBALS['StatusLink'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'status','id'=>$row['plan_id'],'status'=>'1'));
				$GLOBALS['Statusclass'] = 'c-rd f-22 feather icon-toggle-left';
			}
			
			if($row['plan_popular']){
				$GLOBALS['PStatusImage'] = $GLOBALS['ADMIN_LINK'].'/images/status_on.png';	
				$GLOBALS['PStatusLink'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'popular','id'=>$row['plan_id'],'status'=>'0'));
				$GLOBALS['PStatusclass'] = 'c-gr f-22 feather icon-toggle-right';
			} else {
				$GLOBALS['PStatusImage'] = $GLOBALS['ADMIN_LINK'].'/images/status_off.png';	
				$GLOBALS['PStatusLink'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'popular','id'=>$row['plan_id'],'status'=>'1'));
				$GLOBALS['PStatusclass'] = 'c-rd f-22 feather icon-toggle-left';
			}
			$GLOBALS['li_edit'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'edit','id'=>$row['plan_id']));		
			$GLOBALS['li_view'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'view','id'=>$row['plan_id']));		
			$GLOBALS['li_delete'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'delete','id'=>$row['plan_id']));	
			$GLOBALS['on_delete'] = 'return jsConfirm("","delete")';
			$data[] = array(
				"plan_id"=>$row['plan_id'],
				"plan_status"=>"<a href='".$GLOBALS['StatusLink']."' class='".$GLOBALS['Statusclass']."'></a>",
				"plan_popular"=>"<a href='".$GLOBALS['PStatusLink']."' class='".$GLOBALS['PStatusclass']."'></a>",
				"plan_name"=>$row['plan_name'],
				"plan_price"=>$row['plan_price'],
				"plan_created"=>GetDateFormat($row['plan_created']),
				"action"=>"	<a href='".$GLOBALS['li_edit']."' class='f-20 feather icon-edit' title='Edit'>&nbsp;</a><a href='".$GLOBALS['li_delete']."' class='f-20 feather icon-trash' title='Delete' onclick='".$GLOBALS['on_delete']."'>&nbsp;</a>"
			);
			$count++;
		}	
		$response = array(
			"draw" => intval($draw),
			"iTotalDisplayRecords" => $totalRecordwithFilter,
			"aaData" => $data
		);
		echo json_encode($response);
		exit;
	}

}