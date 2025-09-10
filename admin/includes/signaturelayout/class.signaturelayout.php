<?php
require_once(GetConfig('SITE_BASE_PATH').'/admin/includes/display/Image.php');
$GLOBALS['user_type']= $_SESSION[GetSession('AdminType')];

class CIT_SIGNATURELAYOUT{
	private $count;
	private $result;
	private $user;
	private $id ='';
	public function __construct(){	
		$GLOBALS['ModuleName'] = 'Signature Layout';	
		
	}
	public function displayPage(){		
		if(isset($_REQUEST['action'])){
			$action = trim($_REQUEST['action']);
		} else {
			$action = '';
		}
		switch($action){
			case "delete":
				$this->deleteSignaturelayout();
				break;
			case "view":
				$this->viewSignaturelayout();
				break;
			case "status":
				$this->statusSignaturelayout();
				break;	
			case "add":
				$this->addSignaturelayout();
				break;
			case "edit":
				$this->editSignaturelayout();
				break;
			case "datatableregdata";
				$this->datatableSignaturelayout();
				break;
			default:
				$this->Signaturelayout();
				break;			
		}
	}


	private function statusSignaturelayout(){ 
		if(isset($_REQUEST['id'])){
			$layout_id = $_REQUEST['id'];
		} else {
			$layout_id =  '';
		}	
		
		if(is_numeric($layout_id)){

			$addResult = $GLOBALS['DB']->update('signature_layout',array('layout_status' => $_REQUEST['status']),array('layout_id'=>$_REQUEST['id']));
			
			if($addResult){
				$_SESSION['Success'] = '<div class="alert alert-success">Status changed successfully</div>';
			} else {
				$_SESSION['Error'] = '<div class="alert alert-danger">An error occurred while you trying to change status, please try again.</div>';
			}
			GetAdminRedirectUrl();
		} else {
			$_SESSION['Error'] = '<div class="alert alert-danger">Record not valid.</div>';
			GetAdminRedirectUrl(GetAdminUrl(array('module'=>$_REQUEST['module']),0));
		}					
	}
	
	private function editSignaturelayout(){
		
		if(isset($_REQUEST['id'])){
			$layout_id = $_REQUEST['id'];
		} else {
			$layout_id =  '';
		}
		
		$row = $GLOBALS['DB']->row("SELECT * FROM `signature_layout` WHERE layout_id= ?",array($layout_id));
		$GLOBALS['layout_name'] = $row['layout_name'];	
		$GLOBALS['layout_desc'] = $row['layout_desc'];
		$GLOBALS['layout_image'] =$GLOBALS['UPLOAD_LINK'].'/layout/'.$row['layout_image'];	
		$GLOBALS['layout_socialstyle'.$row['layout_socialstyle']] ='selected';
		$GLOBALS['layout_custom_with_social'.$row['layout_custom_with_social']] ='selected';
		$GLOBALS['layout_divider_padding_remove'.$row['layout_divider_padding_remove']] ='selected';
		$GLOBALS['profile_image_size'] = $row['profile_image_size'];
		$GLOBALS['layoutindexid']= $row['index_id']; 
		
		if($_POST['submit']){
			if($_POST['layout_name'] != '' && $_POST['layout_desc'] != '' ){	
			
				if($_FILES['layout_image']['name'] != ''){ 
					$imageSourcefile = $_FILES['layout_image'];
					if($imageSourcefile['name'] != ''){
						$objImage = GetClass('Image');
						$imageDestination = GetConfig('SITE_UPLOAD_PATH').'/layout/'.$layout_id;
						$image = $objImage->imageUpload($imageSourcefile,$imageDestination,'image'); 
						$image =$image['name'];
					}
				} else { 
					$image = $row['layout_image'];
				}	
				$layout_desc = $this->sanitize_output($_POST['layout_desc']);
				
				$index_id = $row['index_id'] == $_POST['index_id'] ? $_POST['index_id'] : $row['index_id'];
				  $data = array('index_id'=>$index_id,'layout_name'=>$_POST['layout_name'],'layout_desc'=>$layout_desc,'layout_image'=>$image,'layout_socialstyle'=>$_POST['layout_socialstyle'], 'profile_image_size'=>$_POST['profile_image_size'], 'layout_custom_with_social'=>$_POST['layout_custom_with_social'], 'layout_divider_padding_remove'=>$_POST['layout_divider_padding_remove']);	

				  $where = array('layout_id'=>$layout_id);
				  $addResult = $GLOBALS['DB']->update("signature_layout",$data,$where);
				 if($_POST['index_id']!='0'){
							$countIndex = $GLOBALS['DB']->row("SELECT MAX(`index_id`) AS index_id FROM signature_layout");
							$start = $row['index_id']; $end = $_POST['index_id'];
							if($countIndex['index_id'] >= $_POST['index_id']){	
								if($row['index_id'] != $_POST['index_id']){
									if($row['index_id'] < $_POST['index_id']){
										$GLOBALS['DB']->update("signature_layout",array('index_id'=> 0),array('index_id'=>$start));
										
										$GLOBALS['DB']->query("update `signature_layout` set index_id = index_id - 1 where index_id > ? and index_id <= ?  and index_id != 0",array($start,$end));
										$GLOBALS['DB']->query("update `signature_layout` set index_id = ? where index_id =0",array($end));
									}else{
										$GLOBALS['DB']->query("update `signature_layout` set index_id = 0 where index_id = ?",array($start));	
										$GLOBALS['DB']->query("update `signature_layout` set index_id = index_id + 1 where index_id >= ? and index_id <= ?  and index_id != 0",array($end,$start)); 
										$GLOBALS['DB']->query("update `signature_layout` set index_id = ? where index_id = 0",array($end));  
									}
								}
							}else{
								$_SESSION['Error'] .= sprintf('<div class="alert alert-danger" role="alert">Index id is not valid. Maximum index id is %d and you have entered %d </div>',$countIndex['index_id'],$_POST['index_id']);
							}
						}else{
							$_SESSION['Error'] .= '<div class="alert alert-danger" role="alert">Index id is not valid.</div>';
						}
					if($addResult){
						$_SESSION['Success'] = '<div class="alert alert-success" role="alert">layout Add successfully</div>';					
						GetAdminRedirectUrl(GetAdminUrl(array('module'=>'signaturelayout')));
					} else {
						$_SESSION['Error'] = '<div class="alert alert-danger" role="alert">An error occurred while you trying to add layout, please try again.</div>';	
					}	
			}else{
				$_SESSION['Error'] = '<div class="alert alert-danger" role="alert">Please fill all required Field</div>';	
			}
		}
		
		AddMessageInfo();
	  	$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/'.$GLOBALS['Module'].'/signaturelayout.edit.html');	
		$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');			
		$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');						
		$GLOBALS['LEFT'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.left.html');	
		$GLOBALS['CLA_HTML']->display();
		RemoveMessageInfo();
	}
	
	private function addSignaturelayout(){
		
		
		$GLOBALS['layout_name'] = $_POST['layout_name'];	
		$GLOBALS['layout_desc'] = $_POST['layout_desc'];	
		
		
		
		if($_POST['submit']){
			
			$rowAI = $GLOBALS['DB']->AutoIncrement("signature_layout");
			$objImage = GetClass('Image');
			$image ='';
			$imageSourcefile = $_FILES['layout_image'];	
			if($imageSourcefile['name'] != ''){ 
				 $imageDestination = GetConfig('SITE_UPLOAD_PATH').'/layout/'.$rowAI['Auto_increment']; 
				$image = $objImage->imageUploadResize($imageSourcefile,$imageDestination,'image');	 						
			}
			if($_POST['layout_name'] != '' && $_POST['layout_desc'] != '' ){
				$layout_desc = $this->sanitize_output($_POST['layout_desc']);	
				  $data = array('index_id'=>$rowAI['Auto_increment'],'layout_name'=>$_POST['layout_name'],'layout_desc'=>$layout_desc,'layout_image'=>$image,'layout_socialstyle'=>$_POST['layout_socialstyle'],'profile_image_size'=>$_POST['profile_image_size'],'layout_custom_with_social'=>$_POST['layout_custom_with_social'],'layout_divider_padding_remove'=>$_POST['layout_divider_padding_remove']);	
				  $addResult = $GLOBALS['DB']->insert("signature_layout",$data);
					if($addResult){
						$_SESSION['Success'] = '<div class="alert alert-success" role="alert">layout Add successfully</div>';					
						GetAdminRedirectUrl(GetAdminUrl(array('module'=>'signaturelayout','action'=>'add')));
					} else {
						$_SESSION['Error'] = '<div class="alert alert-danger" role="alert">An error occurred while you trying to add layout, please try again.</div>';	
					}	
			}else{
				$_SESSION['Error'] = '<div class="alert alert-danger" role="alert">Please fill all required Field</div>';	
			}
		}
		
		
		
		AddMessageInfo();
	  	$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/'.$GLOBALS['Module'].'/signaturelayout.add.html');	
		$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');			
		$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');						
		$GLOBALS['LEFT'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.left.html');	
		$GLOBALS['CLA_HTML']->display();
		RemoveMessageInfo();
	}

	private function deleteSignaturelayout(){		
		if(isset($_REQUEST['id'])){
			$id = $_REQUEST['id'];
		} else {
			$id =  '';
		}
		if(is_numeric($id)){		
			$addResult = $GLOBALS['DB']->query("DELETE FROM signature_layout WHERE layout_id = ?", array($id));
			if($addResult){
				$_SESSION['Success'] = '<div class="alert alert-success">Register User deleted successfully</div>';
				GetAdminRedirectUrl(GetAdminUrl(array('module'=>$_REQUEST['module'])));	
			} else {
				$_SESSION['Error'] = '<div class="alert alert-danger">An error occurred while you trying to delete Register User, please try again.</div>';
				GetAdminRedirectUrl(GetAdminUrl(array('module'=>$_REQUEST['module']),0));	
			}
		} else {
			$_SESSION['Error'] = '<div class="alert alert-danger">Record not valid.</div>';
			GetAdminRedirectUrl(GetAdminUrl(array('module'=>$_REQUEST['module']),0));	
		}				
	}

	private function viewSignaturelayout(){
		if(isset($_REQUEST['id'])){
			$id = $_REQUEST['id'];
		} else {
			$id =  '';
		}	
		
		if(is_numeric($id)){ 


			$row = $GLOBALS['DB']->row("SELECT * FROM `signature_layout` WHERE `layout_id` = ?  ORDER BY `layout_id` ASC LIMIT 0,1",array($id));	

		   	$GLOBALS['MFirstname'] = $row['user_firstname']; 
			$GLOBALS['MLastname'] = $row['user_lastname']; 
			$GLOBALS['MEmail'] = $row['user_email'];
			$GLOBALS['MPassword'] = $row['user_password'];
			$GLOBALS['MUsername'] = $row['user_username'];
			$GLOBALS['MPhone'] = $row['user_phone'];
			$GLOBALS['UserId'] = $row['layout_id'];
			$GLOBALS['MRdate'] = GetDateFormat($row['user_created']); 
			if($row['usr_lastlogin'] != 0){ 
					$GLOBALS['GetLastlogin'] = date('d/m/Y h:i:s A',$row['user_lastlogin']);
			}else{
				$GLOBALS['GetLastlogin'] =0;
			}
			$GLOBALS['ADMINREDIRECTURL'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'view','id'=>$id));

			if($row['user_lastlogin'] != 0){ 
				if($row['user_loginby'] == 1){
					$GLOBALS['GetLastlogin'] = date('d/m/Y h:i:s A',$row['user_lastlogin']).' By App';
				}else{
					$GLOBALS['GetLastlogin'] = date('d/m/Y h:i:s A',$row['user_lastlogin']).' By Web';
				}
			}else{
				$reguser[$count]['GetLastlogin'] ='';
			}
			
			if($row['layout_status']==1){
				 $GLOBALS['MStatus'] = 'Activate Member';
			}else if($row['layout_status']==0){
				$GLOBALS['MStatus'] = 'Deactivate Member';
			}

			
			AddMessageInfo();
			
			$GLOBALS['li_ajaxurl'] = GetAdminUrl(array('module' => $_REQUEST['module'],'action' => 'upatememberno','id'=>$row['layout_id']));		
			$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/'.$GLOBALS['Module'].'/signaturelayout.view.html');				
			$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');			
			$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');						
			$GLOBALS['LEFT'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.left.html');			
			$GLOBALS['CLA_HTML']->display();
			RemoveMessageInfo();			
		} else {
			$_SESSION['Error'] = '<div class="alert alert-danger">Record not valid.</div>';
			GetAdminRedirectUrl(GetAdminUrl(array('module'=>$_REQUEST['module']),0));	
		}		
	}

	
	private function datatableSignaturelayout(){
		$draw = $_POST['draw'];
		$row = $_POST['start'];
		$rowperpage = $_POST['length'];
		$columnIndex = $_POST['order'][0]['column'];
		$columnName = $_POST['columns'][$columnIndex]['data'];
		$columnSortOrder = $_POST['order'][0]['dir'];
		$searchValue = $_POST['search']['value'];
		$filter_val = $_POST['filter_val'];
		$searchQuery = " ";
		
		if($searchValue != ''){
			$searchQuery = " AND (layout_id like '%".$searchValue."%' or user_firstname like '%".$searchValue."%' or user_lastname like '%".$searchValue."%' or user_email like '%".$searchValue."%' or user_phone like '%".$searchValue."%' or user_zipcode like '%".$searchValue."%' or user_registercode like '%".$searchValue."%')";
		}
		
			if($_POST['filter_val'] == 1){

				$records = $GLOBALS['DB']->row("select count(*) as allcount FROM `signature_layout` WHERE `layout_status`=0 " .$searchQuery);
				$totalRecordwithFilter = $records['allcount'];

				$registeruserRecords = $GLOBALS['DB']->query("SELECT * FROM `signature_layout` WHERE `layout_status`= 0 ". $searchQuery." order by ".$columnName." ".$columnSortOrder." limit ".$row.",".$rowperpage);
			}else if($_POST['filter_val'] == 2){ 
				$records = $GLOBALS['DB']->row("select count(*) as allcount FROM `signature_layout` WHERE `layout_status`=1 ".$searchQuery);
				$totalRecordwithFilter = $records['allcount'];
				$registeruserRecords = $GLOBALS['DB']->query("SELECT * FROM `signature_layout` WHERE `layout_status`=1 ". $searchQuery." order by ".$columnName." ".$columnSortOrder." limit ".$row.",".$rowperpage);
			}else if($_POST['filter_val'] == 3){ 
				$records = $GLOBALS['DB']->row("select count(*) as allcount FROM `signature_layout` WHERE `user_lastlogin` <= ".time()." AND `user_lastlogin`!=0 ".$searchQuery);
				$totalRecordwithFilter = $records['allcount'];
				$registeruserRecords = $GLOBALS['DB']->query("SELECT * FROM `signature_layout` WHERE `user_lastlogin` <= ".time()." AND `user_lastlogin`!=0  ".$searchQuery." order by ".$columnName." ".$columnSortOrder." limit ".$row.",".$rowperpage);
			}else if($_POST['filter_val'] == 4){ 
				$records = $GLOBALS['DB']->row("select count(*) as allcount FROM `signature_layout` WHERE `user_lastlogin` =0 ".$searchQuery);
				$totalRecordwithFilter = $records['allcount'];
				$registeruserRecords = $GLOBALS['DB']->query("SELECT * FROM `signature_layout` WHERE `user_lastlogin` =0 ". $searchQuery." order by ".$columnName." ".$columnSortOrder." limit ".$row.",".$rowperpage);
			} else{
				$records = $GLOBALS['DB']->row("select count(*) as allcount FROM `signature_layout` WHERE 1" .$searchQuery);
				$totalRecordwithFilter = $records['allcount'];

				$registeruserRecords = $GLOBALS['DB']->query("select * FROM `signature_layout` WHERE 1 ". $searchQuery." order by ".$columnName." ".$columnSortOrder." limit ".$row.",".$rowperpage);

			}

		$data = array();
		$count=1; 

		foreach ($registeruserRecords as $row) {
			$reguser[$count]['GetCreatdDate'] = GetDateFormat($row['layout_created']);
			$layout_image = $GLOBALS['UPLOAD_LINK'].'/layout/'.$row['layout_image'];
			
			if($row['layout_status']){
				$GLOBALS['StatusImage'] = $GLOBALS['ADMIN_LINK'].'/images/status_on.png';	
				$GLOBALS['StatusLink'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'status','id'=>$row['layout_id'],'status'=>'0'));
				$GLOBALS['Statusclass'] = 'c-gr f-22 feather icon-toggle-right';	
			} else {
				$GLOBALS['StatusImage'] =  $GLOBALS['ADMIN_LINK'].'/images/status_off.png';	
				$GLOBALS['StatusLink'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'status','id'=>$row['layout_id'],'status'=>'1'));
				$GLOBALS['Statusclass'] = 'c-rd f-22 feather icon-toggle-left';
			}
			$GLOBALS['li_edit'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'edit','id'=>$row['layout_id']));
			$GLOBALS['li_view'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'view','id'=>$row['layout_id']));
			$GLOBALS['li_delete'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'delete','id'=>$row['layout_id']));
			$GLOBALS['on_delete'] = 'return jsConfirm("","delete")';
			$socialstyle = $row['layout_socialstyle'] == 0 ? 'Horizontal' : 'Vertical';
			$data[] = array(
				"layout_status"=>"<a href='".$GLOBALS['StatusLink']."' class='".$GLOBALS['Statusclass']."'></a>",
				"layout_id"=>$row['layout_id'],
				"index_id"=>'#'.$row['index_id'],
				"layout_name"=>$row['layout_name'],
				"layout_socialstyle"=>$socialstyle,
				"layout_created"=>$reguser[$count]['GetCreatdDate'],
				"action"=>"<a href='".$GLOBALS['li_view']."' class='f-20 feather icon-eye' title='View'>&nbsp;</a><a href='".$GLOBALS['li_edit']."' class='f-20 feather icon-edit' title='Edit'>&nbsp;</a><a href='".$GLOBALS['li_delete']."' onclick='".$GLOBALS['on_delete']."' class='f-20 feather icon-trash' title='Delete'>&nbsp;</a>"
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

	private function Signaturelayout(){
		
		$GLOBALS['li_getuserdata'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'datatableregdata'));
		$GLOBALS['li_export'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'export'));
		$GLOBALS['Totaluser'] = $count-1;	
		// $GLOBALS['DIS_PAGE']->Page('registerusers','site_id='.$GLOBALS['SITE_ID']);
		$GLOBALS['PageNextImage'] = $GLOBALS['DIS_PAGE']->nextImage();
		$GLOBALS['PagePrevImage'] = $GLOBALS['DIS_PAGE']->prevImage();
		$GLOBALS['PagePageLink'] = $GLOBALS['DIS_PAGE']->pageLink();
		$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/'.$GLOBALS['Module'].'/signaturelayout.html');	
		$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');			
		$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');						
		$GLOBALS['LEFT'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.left.html');			
		$GLOBALS['CLA_HTML']->SetLoop('FEEDBACK',$reguser);
		$GLOBALS['CLA_HTML']->display();
		RemoveMessageInfo();		
	}
	
	private function Signaturelayoutextra(){
		if($GLOBALS['SITE_ID'] == 0){
			 $GLOBALS['display']='block';	
			 $GLOBALS['display1']='none';
		}else{
			$GLOBALS['display']='none';	
			$GLOBALS['display1']='block';
		}
		AddMessageInfo();
		if($_POST['submit'] =='search' && ($_POST['email'] !="" || $_POST['name'] !="" || $_POST['phone'] !="" || $_POST['userid'] !="" || $_POST['postalcode'] !="")){
			$GLOBALS['SUserid'] = $_POST['userid'];
			$GLOBALS['SEmail'] = $_POST['email'];
			$GLOBALS['SName'] = $_POST['name'];
			$GLOBALS['SPhone'] = $_POST['phone'];
			$GLOBALS['SPostalcode'] = $_POST['postalcode'];
			$userid = $_POST['userid'];
			if($_POST['name'] !=""){
				$fullname=explode(' ',$_POST['name']);
				$firstname =$fullname[0];
				$lastname =$fullname[1];
			}else{
				$firstname ='Not';
				$lastname ='Not';
			}
			$email = $_POST['email'];
		if($_POST['phone'] !=''){ $phone= $_POST['phone'];}else{ $phone='Not';}
			if($_POST['postalcode'] !=''){ $postalcode= $_POST['postalcode'];}else{ $postalcode=1;}

				$selectResult = $GLOBALS['DB']->query("SELECT * FROM signature_layout WHERE `site_id`= ? AND (`layout_id`=? OR `user_firstname`= ? OR `user_lastname`= ? OR `user_email`= ? OR `user_phone`= ? OR `user_zipcode` = ?",array($GLOBALS['SITE_ID'],$userid,$firstname,$lastname,$email,$phone,$postalcode));	

		}elseif($_POST['filter'] =='Filter' && $_POST['filter_val'] !=""){
			if($_POST['filter_val'] == 1){
				$selectResult = $GLOBALS['DB']->query("SELECT * FROM `signature_layout` WHERE `layout_status`=0 AND `site_id`=?",array($GLOBALS['SITE_ID']));	
				$GLOBALS['SELECT1'] = 'selected="selected"';
			}elseif($_POST['filter_val'] == 2){
				$selectResult = $GLOBALS['DB']->query("SELECT * FROM `signature_layout` WHERE `layout_status`=1 AND `site_id`=?",array($GLOBALS['SITE_ID']));	
				$GLOBALS['SELECT2'] = 'selected="selected"';
			}elseif($_POST['filter_val'] == 3){
				$selectResult = $GLOBALS['DB']->query("SELECT * FROM `signature_layout` WHERE `user_lastlogin` <= ".time()." AND `site_id`=? AND `user_lastlogin`!=0 ORDER BY `user_lastlogin` DESC",array($GLOBALS['SITE_ID']));	
				$GLOBALS['SELECT3'] = 'selected="selected"';
			}elseif($_POST['filter_val'] == 4){
				$selectResult = $GLOBALS['DB']->query("SELECT * FROM `signature_layout` WHERE `user_lastlogin` =0 AND `site_id`=? ORDER BY `user_lastlogin` DESC ",array($GLOBALS['SITE_ID']));	

				$GLOBALS['SELECT4'] = 'selected="selected"';
			}	
		}else{
			$selectResult = $GLOBALS['DB']->query("SELECT * FROM `signature_layout` WHERE `site_id` = ? ORDER BY `layout_id` desC LIMIT ?,?",array($GLOBALS['SITE_ID'],$GLOBALS['PageStart'],$GLOBALS['PerPage']));

	   	}
		$count=1; 

		foreach ($selectResult as $row) {
			$reguser[$count]['GetUserid'] = $row['layout_id'];
			$reguser[$count]['GetfName'] = $row['user_firstname'];
			$reguser[$count]['GetlName'] = $row['user_lastname'];
			$reguser[$count]['GetEmail'] = $row['user_email'];
			$reguser[$count]['GetPhone'] = $row['user_phone']; 
			$reguser[$count]['GetBrn'] = $row['user_brn'];
			$reguser[$count]['GetPostalcode'] = $row['user_zipcode'];
			$reguser[$count]['GetRegisterdate'] = GetOnlyDate($row['user_rdate']);
			if($row['user_lastlogin'] != 0){ 
				$reguser[$count]['GetLastlogin'] = date('d/M/Y h:i:s a',$row['user_lastlogin']);
			}else{
				$reguser[$count]['GetLastlogin'] ='';
			}
			$reguser[$count]['index'] = $count; 
			if($row['layout_status']){
				$reguser[$count]['StatusImage'] = $GLOBALS['ADMIN_LINK'].'/images/status_on.png';	
				$reguser[$count]['StatusLink'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'status','id'=>$row['layout_id'],'status'=>'0'));
				$reguser[$count]['Statusclass'] = 'c-gr f-22 feather icon-toggle-right';	
			} else {
				$reguser[$count]['StatusImage'] =  $GLOBALS['ADMIN_LINK'].'/images/status_off.png';	
				$reguser[$count]['StatusLink'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'status','id'=>$row['layout_id'],'status'=>'1'));
				$reguser[$count]['Statusclass'] = 'c-rd f-22 feather icon-toggle-left';
			}

			if($row['user_member']==1){
				$reguser[$count]['GetMembership'] = 'Gold';
			}else if($row['user_member']==0){
				$reguser[$count]['GetMembership'] = 'Free';
			}	
			$reguser[$count]['li_view'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'view','id'=>$row['layout_id']));
			$reguser[$count]['li_delete'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'delete','id'=>$row['layout_id']));
			$count++;
		}
		$GLOBALS['li_export'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'export'));
		$GLOBALS['Totaluser'] = $count-1;	
		$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/'.$GLOBALS['Module'].'/signaturelayout.html');	
		$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');			
		$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');						
		$GLOBALS['LEFT'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.left.html');			
		$GLOBALS['CLA_HTML']->SetLoop('FEEDBACK',$reguser);
		$GLOBALS['CLA_HTML']->display();
		RemoveMessageInfo();		
	}
	
	private function sanitize_output($buffer) {

		$search = array(
			'/\>[^\S ]+/s',     // strip whitespaces after tags, except space
			'/[^\S ]+\</s',     // strip whitespaces before tags, except space
			'/(\s)+/s',         // shorten multiple whitespace sequences
			'/<!--(.|\s)*?-->/' // Remove HTML comments
		);
	
		$replace = array(
			'>',
			'<',
			'\\1',
			''
		);
	
		$buffer = preg_replace($search, $replace, $buffer);
		return $buffer;
	}
 	 
}