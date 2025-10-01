<?php


class CIT_USER
{
	private $count;
	private $result;
	private $user;
	private $id ='';
	public function __construct(){	
		$GLOBALS['ModuleName'] = "User";	
			
	}
	
	public function displayPage(){		
		if(isset($_REQUEST['action'])){
			$action = trim($_REQUEST['action']);
		} else {
			$action = '';
		}
		switch($action){
			case "delete":
				$this->deleteUser();
				break;
			case "edit":
				$this->editUser();
				break;
			case "add":
				$this->addUser();
				break;
			case "ajax":
				$this->ajaxUser();
				break;	
			case "view":
				$this->viewUser();
				break;
			case "status":
				$this->statusUser();
				break;
			case "usergetdata";
				$this->getuserdata();
				break; 
			default:
				$this->user(); 
				break;			
		}
	}

	private function ajaxUser(){ 
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

	private function viewUser(){
		if(isset($_REQUEST['id'])){
			$id = $_REQUEST['id'];
		} else {
			$id =  '';
		}				
		if(is_numeric($id)){		

			$row = $GLOBALS['DB']->row("SELECT * FROM `admin` WHERE `id` = ? ORDER BY `id` ASC LIMIT 0,1",array($_REQUEST['id']));	

			$GLOBALS['GetEmail'] = $row['email'];
			$GLOBALS['GetPassword'] = $row['password'];							
			$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/'.$GLOBALS['Module'].'/user.view.html');				
			$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');			
			$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');						
			$GLOBALS['LEFT'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.left.html');			
			$GLOBALS['CLA_HTML']->display();
		}else{
			$_SESSION['Error'] = '<div class="alert alert-danger" role="alert">Record not valid.</div>';
			GetAdminRedirectUrl(GetAdminUrl(array('module'=>$_REQUEST['module']),0));	
		}
	}
	
	private function statusUser(){
		if(isset($_REQUEST['id'])){
			
			$user_id = $_REQUEST['id'];
		} else {
			$user_id =  '';
		}				
		if(is_numeric($user_id)){
			$addResult = $GLOBALS['DB']->update('admin',array('status' =>$_REQUEST['status']),array('id'=>$_REQUEST['id']));

			if($addResult){
				$_SESSION['Success'] = '<div class="gap-8 py-5 px-4 pl-11 border-l-9 border-green-600 rounded-xl relative bg-white bg-gradient-to-r from-[#00B71B]/12 to-[#00B71B]/0 shadow-lg" role="alert">Status changed successfully</div>';
			} else {
				$_SESSION['Error'] = '<div class="alert alert-danger" role="alert">An error occurred while you trying to change status, please try again.</div>';
			}
			GetAdminRedirectUrl();
		} else {
			$_SESSION['Error'] = '<div class="alert alert-danger" role="alert">Record not valid.</div>';
			GetAdminRedirectUrl(GetAdminUrl(array('module'=>$_REQUEST['module']),0));
		}						
	}

	private function deleteUser(){
		if(isset($_REQUEST['id'])){
			$id = $_REQUEST['id'];
		} else {
			$id =  '';
		}
		if(is_numeric($id)){		
			
			$addResult = $GLOBALS['DB']->query("DELETE FROM admin WHERE id = ?", array($id));

			if($addResult){
				$_SESSION['Success'] = '<div class="gap-8 py-5 px-4 pl-11 border-l-9 border-green-600 rounded-xl relative bg-white bg-gradient-to-r from-[#00B71B]/12 to-[#00B71B]/0 shadow-lg" role="alert">User deleted successfully</div>';
				GetAdminRedirectUrl(GetAdminUrl(array('module'=>$_REQUEST['module'])));	
			} else {
				$_SESSION['Error'] = '<div class="alert alert-danger" role="alert">An error occurred while you trying to delete user, please try again.</div>';
				GetAdminRedirectUrl(GetAdminUrl(array('module'=>$_REQUEST['module']),0));	
			}
		} else {
			$_SESSION['Error'] = '<div class="alert alert-danger" role="alert">Record not valid.</div>';
			GetAdminRedirectUrl(GetAdminUrl(array('module'=>$_REQUEST['module']),0));	
		}				
	}

	private function editUser(){
		if(isset($_REQUEST['id'])){
			$id = $_REQUEST['id'];
		} else {
			$id =  '';
		}				
		if(is_numeric($id)){
			if(isset($_POST['email']))
			{	
				
				
			    if($_POST['password'] == $_POST['cpassword']){
				if($_POST['email'] != '' && $_POST['password'] != ''){
					if(is_email_address($_POST['email'])){
						if($id == $_SESSION[GetSession('AdminId')]){
							$_SESSION[GetSession('AdminEmail')] = $_POST['email'];
						}
						$data = array('username' => $_POST['uname'],'email' => $_POST['email'],'password' => $_POST['password'],'phone' => $_POST['phone'],'usertype' => $_POST['usertype']);
						$where = array('id'=>$id);					
						$updateResult = $GLOBALS['DB']->update('admin',$data,$where);

						if($updateResult){
							$modulename = $_POST['modulename']; 
							$add_p 		= $_POST['add_permission'];
							$edit_p 	= $_POST['edit_permission'];
							$view_p		= $_POST['view_permission']; //echo '<pre>';
							
							$deletepermission = $GLOBALS['DB']->query("DELETE FROM `adminuser_permisssion` WHERE `user_id`=?",array($id));
							
							for ($i=0; $i<sizeof($modulename); $i++){ 
								if($modulename[$i]!=0){ 

									$rowid = $GLOBALS['DB']->query('SELECT module_parentid FROM `admin_modules` WHERE module_id = '.$modulename[$i].' order BY `module_id` DESC LIMIT 0,1');
									
									if($rowpid['module_parentid']!=0 ){
										$addpermission = $add_p[$i];
										$editpermission = $edit_p[$i];
										$viewpermission = $view_p[$i]; 
									}else{
										$addpermission = 0;
										$editpermission = 0;
										$viewpermission = 0;	 
									}
									
									$data = array("site_id"=>$GLOBALS['SITE_ID'],"user_id"=>$id,"module_id"=>$modulename[$i],"permission"=>1,"add_permission"=>$addpermission,"edit_permission"=>$editpermission,"view_permission"=>$viewpermission);
    								$addresult = $GLOBALS['DB']->insert("adminuser_permisssion",$data);

								}
							} 
							$_SESSION['Success'] = '<div class="gap-8 py-5 px-4 pl-11 border-l-9 border-green-600 rounded-xl relative bg-white bg-gradient-to-r from-[#00B71B]/12 to-[#00B71B]/0 shadow-lg" role="alert">User updated successfully</div>';
						} else {
							
							$_SESSION['Error'] = '<div class="alert alert-danger" role="alert">An error occurred while you trying to update user, please try again.</div>';
						}	
						GetAdminRedirectUrl($GLOBALS['CURRENT_URL']);
					} else {
						$_SESSION['Error'] = '<div class="alert alert-danger" role="alert">Enter valid email address.</div>';
					}
				} else {
					$_SESSION['Error'] = '<div class="alert alert-danger" role="alert">Required fields should not blank.</div>';
				}	
				} else {
				$_SESSION['Error'] = '<div class="alert alert-danger" role="alert">Password And Confirm password Do Not Matched.</div>';
			}
			}
			
			
			$row = $GLOBALS['DB']->row("SELECT * FROM `admin` WHERE `id` = ? ORDER BY `id` ASC LIMIT 0,1",array($_REQUEST['id']));

			
				$GLOBALS['GetUname'] = $row['username'];
				$GLOBALS['GetEmail'] = $row['email'];
				$GLOBALS['GetPassword'] = $row['password'];	
			 	$GLOBALS['GetPhone'] = $row['phone'];	
				
				$this->getmodules('edit');
				
				if($row['usertype']==1){
					$GLOBALS['Getutype'].='<option value="1" selected="selected">Administrator</option>
                    <option value="2">Admin Sub User</option>';
				}else{
					$GLOBALS['Getutype'].='	<option value="1">Master Admin</option>
                    <option value="2" selected="selected">Admin Sub User</option>';
				}
				
			$this->updateErrorMessage(); 
			AddMessageInfo();			
			$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/'.$GLOBALS['Module'].'/user.edit.html');				
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
	
	private function addUser(){	
		AddMessageInfo();	
		if($_POST['email'] != '' && $_POST['password'] != ''){
			if($_POST['password'] == $_POST['cpassword']){
				if(is_email_address($_POST['email'])){
					$data = array("username"=>$_POST['uname'],"password"=>$_POST['password'],"email"=>$_POST['email'],"phone"=>$_POST['phone'],"usertype"=>$_POST['usertype']);

					$addResult = $GLOBALS['DB']->insert("admin",$data);

					$addResult =1;
					if($addResult){
						$rowlastid = $GLOBALS['DB']->row("SELECT * FROM `admin` order BY `id` DESC LIMIT 0,1");	
						$GLOBALS['Last_id'] = $rowlastid['id'];
						$modulename = $_POST['modulename'];
						$add_p 		= $_POST['add_permission'];
						$edit_p 	= $_POST['edit_permission'];
						$view_p		= $_POST['view_permission'];
							
						for ($i=0; $i<sizeof($modulename); $i++){ 							
							if($modulename[$i]!=0){
								$rowpid = $GLOBALS['DB']->row("SELECT module_parentid FROM `admin_modules` WHERE module_id = ? order BY `module_id` DESC LIMIT 0,1",array($modulename[$i]));
								if($rowpid['module_parentid']!=0){
									$addpermission = $add_p[$i];
									$editpermission = $edit_p[$i];
									$viewpermission = $view_p[$i]; 
								}else{
									$addpermission = 0;
									$editpermission = 0;
									$viewpermission = 0;	 
								}
								$data = array("site_id"=>$GLOBALS['SITE_ID'],"user_id"=>$GLOBALS['Last_id'],"module_id"=>$modulename[$i],"permission"=>1,"add_permission"=>$addpermission,"edit_permission"=>$editpermission,"view_permission"=>$viewpermission);
								$addresult = $GLOBALS['DB']->insert("adminuser_permisssion",$data);
							}
						}
						$_SESSION['Success'] = '<div class="gap-8 py-5 px-4 pl-11 border-l-9 border-green-600 rounded-xl relative bg-white bg-gradient-to-r from-[#00B71B]/12 to-[#00B71B]/0 shadow-lg" role="alert">User added successfully</div>';
					} else {
						
						$_SESSION['Error'] = '<div class="alert alert-danger" role="alert">An error occurred while you trying to add user, please try again.</div>';
					}
				} else {
					$_SESSION['Error'] = '<div class="alert alert-danger" role="alert">Enter valid email address.</div>';
				}
			} else {
				$_SESSION['Error'] = '<div class="alert alert-danger" role="alert">Password And Confirm password Do Not Matched.</div>';
			}
		} else {
			$_SESSION['Error'] = '<div class="alert alert-danger" role="alert">Required fields should not blank.</div>';
		}				
		GetAdminRedirectUrl();
	}

	private function getuserdata(){
		$draw = $_POST['draw'];
		$row = $_POST['start'];
		$rowperpage = $_POST['length'];
		$columnIndex = $_POST['order'][0]['column'];
		$columnName = $_POST['columns'][$columnIndex]['data'];
		$columnSortOrder = $_POST['order'][0]['dir'];
		$searchValue = $_POST['search']['value'];
		$searchQuery = " ";
		if($searchValue != ''){
			$searchQuery = " and (email like '%".$searchValue."%')";
		}

		$records = $GLOBALS['DB']->row("select count(*) as allcount FROM `admin` WHERE 1".$searchQuery);

		$totalRecordwithFilter = $records['allcount'];
		$userRecords = $GLOBALS['DB']->query("select * FROM `admin` WHERE 1 ". $searchQuery." order by ".$columnName." ".$columnSortOrder." limit ".$row.",".$rowperpage);
		$data = array();
		foreach ($userRecords as $row) {

			$user[$count]['SiteName'] = 'Master Admin';
			$user[$count]['LastLoginTime'] =date('d/m/Y h:i:s a',$row['last_login']);	
			if($row['usertype']==1){
				$user[$count]['UserType'] = 'Administrator';
			}else{
				$user[$count]['UserType'] = 'Admin Sub User';
			}	
			if($row['status']){	
				$GLOBALS['StatusLink'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'status','id'=>$row['id'],'status'=>'0'));
				$GLOBALS['Statusclass'] = 'c-gr f-22 feather icon-toggle-right';	
			} else {
				$GLOBALS['StatusLink'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'status','id'=>$row['id'],'status'=>'1'));
				$GLOBALS['Statusclass'] = 'c-rd f-22 feather icon-toggle-left';	
			}		
			$GLOBALS['li_edit'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'edit','id'=>$row['id']));
			$GLOBALS['li_view'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'view','id'=>$row['id']));
			$GLOBALS['li_delete'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'delete','id'=>$row['id']));
			$GLOBALS['on_delete'] = 'return jsConfirm("","delete")';
			
			$data[] = array(
				"status"=>"<a href='".$GLOBALS['StatusLink']."' class='".$GLOBALS['Statusclass']."'></a>",
				"usertype"=>'<a href="'.$GLOBALS['li_view'].'" class="view-link" >'.$user[$count]['UserType'].'</a>',
				"email"=>'<a href="'.$GLOBALS['li_view'].'" class="view-link" >'.$row['email'].'</a>',
				"last_login"=>'<a href="'.$GLOBALS['li_view'].'" class="view-link" >'.$user[$count]['LastLoginTime'].'</a>',
				"action"=>"<a href='".$GLOBALS['li_view']."' class='f-20 feather icon-eye' title='View'>&nbsp;</a><a href='".$GLOBALS['li_edit']."' class='f-20 feather icon-edit' title='Edit'>&nbsp;</a><a href='".$GLOBALS['li_delete']."' onclick='".$GLOBALS['on_delete']."' class='f-20 feather icon-trash' title='Delete'>&nbsp;</a>"
			);
		}
		$response = array(
			"draw" => intval($draw),
			"iTotalDisplayRecords" => $totalRecordwithFilter,
			"aaData" => $data
		);
		echo json_encode($response);
	}

	private function user(){
		AddMessageInfo();	
		$GLOBALS['li_getuserdata'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'usergetdata'));
		$this->getmodules();
		$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/'.$GLOBALS['Module'].'/user.html');	
		$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');			
		$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');						
		$GLOBALS['LEFT'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.left.html');			
		$GLOBALS['CLA_HTML']->SetLoop('USERS',$user);
		$GLOBALS['CLA_HTML']->SetLoop('MODULES',$modules);
		$GLOBALS['CLA_HTML']->display();
		RemoveMessageInfo();		
	}	
	
	private function getmodules(){
		
		if($_REQUEST['action'] == 'edit'){
			$sqlpermission = $GLOBALS['DB']->query("SELECT * FROM `adminuser_permisssion` WHERE `user_id`= ?",array($_REQUEST['id']));	
			$count=0;
			foreach ($sqlpermission as $rowpermisiion) {
				$permissionmodulelist .= $rowpermisiion['module_id'].',';
				$viewpermission .= $rowpermisiion['view_permission'].',';
				$editpermission .= $rowpermisiion['edit_permission'].',';
				$addpermission .= $rowpermisiion['add_permission'].',';	
				$count++;
			}
		
			$permissionmodulelist = rtrim($permissionmodulelist, ",");
			$GLOBALS['PmoduleList'] = explode(',',$permissionmodulelist);
			
			$addpermission = rtrim($addpermission, ",");
			$GLOBALS['Addplist'] = explode(',',$addpermission);
			
			$editpermission = rtrim($editpermission, ",");
			$GLOBALS['EditPlist'] = explode(',',$editpermission);
			
			$viewpermission = rtrim($viewpermission, ",");
			$GLOBALS['viewPlist'] = explode(',',$viewpermission);
			
			$sqladminmodules = $GLOBALS['DB']->query("SELECT * FROM admin_modules WHERE module_parentid = 0 AND status = 1 ORDER BY module_order ASC");
			$count_module=0;

			foreach ($sqladminmodules as $getmodulelist) {
				if (in_array($getmodulelist['module_id'], $GLOBALS['PmoduleList'])){
					$GLOBALS['ParentModules'] .='<div class="col-sm-4"><div class="form-check"><input class="form-check-input" type="checkbox" name="modulename[]" id="modulename'.$getmodulelist['module_id'].'" value="'.$getmodulelist['module_id'].'" data-toggle="modal" data-target="#myModal'.$count_module.'" checked><label class="form-check-label" for="gridCheck1">'.$getmodulelist['module_name'].'</label><span style="display:none;"><input class="form-check-input" type="checkbox" name="add_permission[]" id="add_permission'.$getmodulelist['module_id'].'" value="0" checked ><input class="form-check-input" type="checkbox" name="edit_permission[]" id="edit_permission'.$getmodulelist['module_id'].'" value="0" checked><input class="form-check-input" type="checkbox" name="view_permission[]" id="view_permission'.$getmodulelist['module_id'].'" value="0" checked ></span></div></div>';
				}else{ 
					$GLOBALS['ParentModules'] .='<div class="col-sm-4"><div class="form-check"><input class="form-check-input" type="checkbox" name="modulename[]" id="modulename'.$getmodulelist['module_id'].'" value="'.$getmodulelist['module_id'].'" data-toggle="modal" data-target="#myModal'.$count_module.'" ><label class="form-check-label" for="gridCheck1">'.$getmodulelist['module_name'].'</label><span style="display:none;"><input class="form-check-input" type="checkbox" name="add_permission[]" id="add_permission'.$getmodulelist['module_id'].'" value="0" checked ><input class="form-check-input" type="checkbox" name="edit_permission[]" id="edit_permission'.$getmodulelist['module_id'].'" value="0" checked><input class="form-check-input" type="checkbox" name="view_permission[]" id="view_permission'.$getmodulelist['module_id'].'" value="0" checked ></span></div></div>';
				}
				$this->Getsubmodules($getmodulelist['module_id'],$firstLevel,$count_module);
				$count_module++	;
			}
		}else{
			$firstLevel  = 1;
			$modulesRecords = $GLOBALS['DB']->query("SELECT * FROM admin_modules WHERE module_parentid = 0 AND status = 1  ORDER BY module_order ASC");
			$count=0; 
			foreach ($modulesRecords as $row) {
				$firstLevel  = 1;
				$GLOBALS['ParentModules'] .='<div class="col-sm-4"><div class="form-check"><input class="form-check-input" type="checkbox" name="modulename[]" id="modulename'.$row['module_id'].'" value="'.$row['module_id'].'" ><label class="form-check-label" for="gridCheck1" data-toggle="modal" data-target="#myModal'.$count.'">'.$row['module_name'].'</label><span style="display:none;"><input class="form-check-input" type="checkbox" name="add_permission[]" id="add_permission'.$row['module_id'].'" value="0" checked ><input class="form-check-input" type="checkbox" name="edit_permission[]" id="edit_permission'.$row['module_id'].'" value="0" checked><input class="form-check-input" type="checkbox" name="view_permission[]" id="view_permission'.$row['module_id'].'" value="0" checked ></span></div></div>';
				$this->Getsubmodules($row['module_id'],$firstLevel,$count);
				$modules[$count]['Moduleid'] = $row['module_id']; 
				$modules[$count]['ModuleName'] = $row['module_name']; 
				$count++;
			}
		}
	}
	
	private function Getsubmodules($parentmoduleid='',$level='',$count){
		if($_REQUEST['action'] == 'edit'){ 
			$Level = $Level + 1;	

			$subCatQuery = $GLOBALS['DB']->query("select module_id, module_name,module_seo,module_parentlist from admin_modules where status = 1 AND module_parentid=? order by module_id ASC",array($parentmoduleid));	
			$countRows=count($subCatQuery);
			if($countRows > 0){
				$GLOBALS['ParentModules'] .= '<div id="myModal'.$count.'" class="modal fade" role="dialog"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button></div><div class="modal-body"><div class="col-sm-9"><div class="row">';
				
				$GLOBALS['ParentModules'] .= '<div class="col-sm-5"><div class="form-check"><label class="form-check-label" for="gridCheck1"><strong>Modules</strong></label></div></div><div class="col-sm-2"><label class="form-check-label" for="gridCheck1"><strong>Add</strong></label></div><div class="col-sm-2"><label class="form-check-label" for="gridCheck1"><strong>Edit</strong></label></div><div class="col-sm-2"><label class="form-check-label" for="gridCheck1"><strong>View</strong></label></div>';
				foreach ($subCatQuery as $subRow) {					
					if (in_array($subRow['module_id'], $GLOBALS['PmoduleList'])){
						if (in_array($subRow['module_id'], $GLOBALS['Addplist'])){$add = 'checked';}else{ $add = '';}	
						if (in_array($subRow['module_id'], $GLOBALS['EditPlist'])){$edit = 'checked';}else{ $edit = '';}	
						if (in_array($subRow['module_id'], $GLOBALS['viewPlist'])){$view = 'checked';}else{ $view = '';}	
						
					$GLOBALS['ParentModules'] .='<div class="col-sm-6"><div class="form-check"><input class="form-check-input" type="checkbox" name="modulename[]" id="modulename'.$subRow['module_id'].'" value="'.$subRow['module_id'].'" checked><label class="form-check-label" for="gridCheck1">'.$subRow['module_name'].'</label></div></div><div class="col-sm-2"><input class="form-check-input" type="checkbox" name="add_permission[]" id="add_permission'.$subRow['module_id'].'" value="'.$subRow['module_id'].'" '.$add.'></div><div class="col-sm-2"><input class="form-check-input" type="checkbox" name="edit_permission[]" id="edit_permission'.$subRow['module_id'].'" value="'.$subRow['module_id'].'" '.$edit.'></div><div class="col-sm-2"><input class="form-check-input" type="checkbox" name="view_permission[]" id="view_permission'.$subRow['module_id'].'" value="'.$subRow['module_id'].'" '.$view.'></div>';
					}else{
					$GLOBALS['ParentModules'] .='<div class="col-sm-6"><div class="form-check"><input class="form-check-input" type="checkbox" name="modulename[]" id="modulename'.$subRow['module_id'].'" value="'.$subRow['module_id'].'"><label class="form-check-label" for="gridCheck1">'.$subRow['module_name'].'</label></div></div><div class="col-sm-2"><input class="form-check-input" type="checkbox" name="add_permission[]" id="add_permission'.$subRow['module_id'].'" value="'.$subRow['module_id'].'"></div><div class="col-sm-2"><input class="form-check-input" type="checkbox" name="edit_permission[]" id="edit_permission'.$subRow['module_id'].'" value="'.$subRow['module_id'].'"></div><div class="col-sm-2"><input class="form-check-input" type="checkbox" name="view_permission[]" id="view_permission'.$subRow['module_id'].'" value="'.$subRow['module_id'].'"></div>';
					}
				}
				$GLOBALS['ParentModules'] .= '</div></div></div><div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Close</button></div></div></div></div>'; 
			}
		}else{
			$Level = $Level + 1;	
			
			$subCatQuery = $GLOBALS['DB']->query("select module_id, module_name,module_seo,module_parentlist from admin_modules where status = 1 AND module_parentid=?  order by module_id ASC",array($parentmoduleid));	
			$countRows=count($subCatQuery);
			if($countRows > 0){
				$GLOBALS['ParentModules'] .= '<div id="myModal'.$count.'" class="modal fade" role="dialog"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button></div><div class="modal-body"><div class="col-sm-9"><div class="row">';
				
				$GLOBALS['ParentModules'] .= '<div class="col-sm-5"><div class="form-check"><label class="form-check-label" for="gridCheck1"><strong>Modules</strong></label></div></div><div class="col-sm-2"><label class="form-check-label" for="gridCheck1"><strong>Add</strong></label></div><div class="col-sm-2"><label class="form-check-label" for="gridCheck1"><strong>Edit</strong></label></div><div class="col-sm-2"><label class="form-check-label" for="gridCheck1"><strong>View</strong></label></div>' ;

				foreach ($subCatQuery as $subRow) {
			  		$GLOBALS['ParentModules'] .='<div class="col-sm-6"><div class="form-check"><input class="form-check-input" type="checkbox" name="modulename[]" id="modulename'.$subRow['module_id'].'" value="'.$subRow['module_id'].'"><label class="form-check-label" for="gridCheck1">'.$subRow['module_name'].'</label></div></div><div class="col-sm-2"><input class="form-check-input" type="checkbox" name="add_permission[]" id="add_permission'.$subRow['module_id'].'" value="'.$subRow['module_id'].'"></div><div class="col-sm-2"><input class="form-check-input" type="checkbox" name="edit_permission[]" id="edit_permission'.$subRow['module_id'].'" value="'.$subRow['module_id'].'"></div><div class="col-sm-2"><input class="form-check-input" type="checkbox" name="view_permission[]" id="view_permission'.$subRow['module_id'].'" value="'.$subRow['module_id'].'"></div>';
				} 
				$GLOBALS['ParentModules'] .= '</div></div></div><div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Close</button></div></div></div></div>'; 
			}
		}
	} 
	
	private function updateErrorMessage(){
		if(isset($_SESSION['Error'])){
			$_SESSION['Error'] = str_replace("<div class='alert alert-danger' role='alert'> for key 'email'",'. Entered email address already exist. Try again.</div>',$_SESSION['Error']);
		}	
	}  
}