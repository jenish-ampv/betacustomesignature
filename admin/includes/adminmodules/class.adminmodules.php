<?php

require_once(GetConfig('SITE_BASE_PATH').'/admin/includes/display/Image.php');
require_once(GetConfig('SITE_BASE_PATH').'/admin/includes/display/Pagination.php');
class CIT_ADMINMODULES{
	private $count;
	private $result;
	private $user;
	private $id ='';
	private $mainCatQuery,$catContent,$firstLevel;
	public function __construct(){		
		$GLOBALS['ModuleName'] = 'Adminmodules';	
						
	}
	public function displayPage(){
		if(isset($_REQUEST['action'])){
			$action = trim($_REQUEST['action']);
		} else {
			$action = '';
		}
		switch($action){
			case "status":
				$this->statusModule();
				break;	
			case "delete":
				$this->deletePage();
				break;
			case "edit":
				$this->editmodule();
				break;
			case "add":
				$this->addadminmodules();
				break;
			case "datatableadminmodules";
				$this->datatableModules();
				break;	
			default:
				$this->adminmodules();
				break;			
		}
	}

	private function statusModule(){
		if(isset($_REQUEST['id'])){
			$id = $_REQUEST['id'];
		} else {
			$id =  '';
		}	
		if(is_numeric($id)){
			
			$status = $_REQUEST['status']; 
			
			$UpdateResult = $GLOBALS['DB']->update("admin_modules" ,array('status' => $status),array('module_id' => $id));
			if($UpdateResult){
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
	
	private function deletePage(){
		if(isset($_REQUEST['id'])){
			$id = $_REQUEST['id'];
		} else {
			$id =  '';
		}
		if(is_numeric($id)){		
			$addResult = $GLOBALS['DB']->query("DELETE FROM admin_modules WHERE module_id =?", array($id));
			if($addResult){
				$_SESSION['Success'] = '<div class="gap-8 py-5 px-4 pl-11 border-l-9 border-green-600 rounded-xl relative bg-white bg-gradient-to-r from-[#00B71B]/12 to-[#00B71B]/0 shadow-lg">Page deleted successfully</div>';				
			} else {
				$_SESSION['Error'] = '<div class="alert alert-danger">An error occurred while you trying to delete user, please try again.</div>';				
			}
			GetAdminRedirectUrl(GetAdminUrl(array('module'=>$_REQUEST['module'])));
		} else {
			$_SESSION['Error'] = '<div class="alert alert-danger">Record not valid.</div>';
			GetAdminRedirectUrl(GetAdminUrl(array('module'=>$_REQUEST['module']),0));
		}			
	}

	private function editmodule(){
		if(isset($_REQUEST['id'])){
			$id = $_REQUEST['id'];
		} else { 
			$id =  '';
		}
		if(is_numeric($id)){	

			$row = $GLOBALS['DB']->row("select * FROM `admin_modules` WHERE module_id=?",array($id));	

			$GLOBALS['name'] = $row['module_name'];
			$GLOBALS['seo'] = $row['module_seo'];
			$GLOBALS['icon'] = $row['module_icon'];
			$GLOBALS['moduleOrder'] = $row['module_order'];
			if($row['module_order']!=0){$GLOBALS['indexdisplay']='flex';}else{$GLOBALS['indexdisplay']='none';}
			if($row['module_permission'] =='1') {
				$GLOBALS['permission'] = "<input type='checkbox' name='mpermission' class='form-check-input' id='gridCheck1' value='1' checked='checked'>";
			} else {
				$GLOBALS['permission'] = "<input type='checkbox' name='mpermission' class='form-check-input' id='gridCheck1' value='1'>";
			}
			if($_POST['seourl']!='#'){
				if ($_POST['seourl'] == ''){
					$seourl = preg_replace('/[^a-zA-Z0-9]/i',' ',$_POST['name']);
					$seourl = str_replace(' ','-',$seourl);
				} else {
					$seourl = preg_replace('/[^a-zA-Z0-9]/i',' ',$_POST['seourl']);
					$seourl = str_replace(' ','-',$seourl);	
				}
			}else{
				$seourl = $_POST['seourl'];
			}
			if($_POST['mname'] != ''){

				$parentId = $_POST['parentid'];	

				if($parentId != 0){

					$row = $GLOBALS['DB']->row("SELECT * FROM admin_modules WHERE `module_parentid` =?",array($parentId));

					if($row['module_parentlist'] != ''){
						$parentList = $row['module_parentlist'].'|'.$id;
					} else {
						$parentList = $parentId;

					}

				} else {
					$parentList = $parentId;
				}

				$data = array('module_parentid' => $_POST['parentid'],'module_parentlist' => $parentList,'module_name' => $_POST['mname'],'module_seo' => $seourl,'module_icon' => $_POST['micon'],'module_permission' => $_POST['mpermission']);
				$where = array('module_id'=>$id);
				$updateResult = $GLOBALS['DB']->update('admin_modules',$data,$where);
				
				if($_POST['module_index']!='0'){
					$countIndex = $GLOBALS['DB']->row("SELECT MAX(`module_order`) AS index_id FROM admin_modules");	

					$start = $row['module_order']; $end = $_POST['module_index'];
					if($countIndex['index_id'] >= $_POST['module_index']){  	
						if($row['module_order'] != $_POST['module_index']){
							if($row['module_order'] < $_POST['module_index']){

								$GLOBALS['DB']->update("admin_modules",array('module_order'=>0),array('module_parentid'=>0,'module_order'=>$start)); 

								$GLOBALS['DB']->query("update `admin_modules` set module_order = module_order - 1 where module_order > ".$start." and module_order <= ".$end."  and module_order != 0 AND `module_parentid` = 0");

								$GLOBALS['DB']->update("admin_modules",array('module_order'=>$end),array('module_parentid'=>0,'module_order'=>0)); 

							}else{
								$GLOBALS['DB']->update("admin_modules",array('module_order'=>0),array('module_parentid'=>0,'module_order'=>$start,'module_parentid'=>0)); 

								$GLOBALS['DB']->query("update `admin_modules` set module_order = module_order + 1 where module_order >= ".$end." and module_order <= ".$start."  and module_order != 0 AND `module_parentid` = 0"); 

								$GLOBALS['DB']->update("admin_modules",array('module_order'=>$end),array('module_order'=>0,'module_order'=>0,'module_parentid'=>0)); 
							}
						}
					}else{ 
						$_SESSION['Error'] .= sprintf('<div class="alert alert-danger" role="alert">Index id is not valid. Maximum index id is %d and you have entered %d </div>',$countIndex['index_id'],$_POST['module_index']);
					}
				}else{
					$_SESSION['Error'] .= '<div class="alert alert-danger" role="alert">Index id is not valid.</div>';
				}
				if($updateResult){
					$_SESSION['Success'] = '<div class="gap-8 py-5 px-4 pl-11 border-l-9 border-green-600 rounded-xl relative bg-white bg-gradient-to-r from-[#00B71B]/12 to-[#00B71B]/0 shadow-lg">Page updated successfully</div>';
					GetAdminRedirectUrl($GLOBALS['CURRENT_URL']);
				} else {
					
					$_SESSION['Error'] = '<div class="alert alert-danger">An error occurred while you trying to update page, please try again.</div>';
				}	
			}
			$this->updateErrorMessage();
			AddMessageInfo();	
			$GLOBALS['CatContent'] = $this->mainCat($row['module_id'],$row['module_parentid']); 
			$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/'.$GLOBALS['Module'].'/modules.edit.html');				
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

	private function addadminmodules(){	
		$rowAI =$GLOBALS['DB']->AutoIncrement('admin_modules');
		$rowAI =  $rowAI['Auto_increment'];
		if($_POST['parentid'] != ''){
			if($_POST['mname'] != ''){				 
				$parentId = $_POST['parentid'];	

				if($parentId != 0){
					$row = $GLOBALS['DB']->row("SELECT * FROM admin_modules WHERE `module_parentid` =?",array($parentId));	

					if($row['module_parentlist'] != ''){
						$parentList = $row['module_parentlist'].'|'.$rowAI['Auto_increment'];
					} else {
						$parentList = $parentId;
					}
				} else {
					$parentList = $rowAI['Auto_increment']; 
				}
				if ($_POST['seourl'] != '#'){
					if ($_POST['seourl'] == ''){
						$seourl = preg_replace('/[^a-zA-Z0-9]/i',' ',$_POST['name']);
						$seourl = str_replace(' ','-',$seourl);
					} else {
						$seourl = preg_replace('/[^a-zA-Z0-9]/i',' ',$_POST['seourl']);
						$seourl = str_replace(' ','-',$seourl);	
					}
				}else{
					$seourl = $_POST['seourl'];	
				}
				if ($_POST['mpermission'] != ''){
					$mpermission = $_POST['mpermission'];
				} else {
					$mpermission = '0';
				}
			
				$countIndex = $GLOBALS['DB']->row("SELECT MAX(`module_order`) AS index_id FROM admin_modules");	

				if($_POST['parentid']!=0){
					$maxindexid = 0;
				}else{
					$maxindexid = $countIndex['index_id'] + 1 ;	
				}
				 
				$data = array("module_parentid"=>$_POST['parentid'],"module_parentlist"=>$parentList,"module_name"=>$_POST['mname'],"module_seo"=>$seourl,"module_icon"=>$_POST['micon'],"module_permission"=>$mpermission,"module_order"=>$maxindexid,"status"=>1);
				$addresult = $GLOBALS['DB']->insert("admin_modules",$data);
				
				if($addresult){
					$_SESSION['Success'] = '<div class="gap-8 py-5 px-4 pl-11 border-l-9 border-green-600 rounded-xl relative bg-white bg-gradient-to-r from-[#00B71B]/12 to-[#00B71B]/0 shadow-lg">Module added successfully</div>';
					GetAdminRedirectUrl($GLOBALS['CURRENT_URL']);
				} else {
					
					$_SESSION['Error'] = '<div class="alert alert-danger">An error occurred while you trying to add new Module, please try again.</div>';
					$GLOBALS['GetSeoUrl'] 	   = $seourl;
					$GLOBALS['GetModuleName']  = $_POST['mname'];
				}
			} else {
				$_SESSION['Error'] = '<div class="alert alert-danger">Required fields should not blank.</div>';
				$GLOBALS['GetModulename']  = $_POST['mname'];	
				$GLOBALS['GetSeoUrl'] = $seourl;
			}
		}
		$this->updateErrorMessage();
		$GLOBALS['GetId'] = $rowAI['Auto_increment'];
		AddMessageInfo();
		$GLOBALS['CatContent'] = $this->mainCat(); 
		$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/'.$GLOBALS['Module'].'/module.add.html');	
		$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');
		$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');
		$GLOBALS['LEFT'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.left.html');			
		$GLOBALS['CLA_HTML']->display();
		RemoveMessageInfo();		
	}

	private function adminmodules(){
		AddMessageInfo();		

		$GLOBALS['li_modulesdata'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'datatableadminmodules'));

		//$GLOBALS['DIS_PAGE']->Page('admin_modules');
		$GLOBALS['PageNextImage'] = $GLOBALS['DIS_PAGE']->nextImage();
		$GLOBALS['PagePrevImage'] = $GLOBALS['DIS_PAGE']->prevImage();
		$GLOBALS['PagePageLink'] = $GLOBALS['DIS_PAGE']->pageLink();
		$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/'.$GLOBALS['Module'].'/modules.html');	
		$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');			
		$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');		
		$GLOBALS['LEFT'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.left.html');			
		$GLOBALS['CLA_HTML']->SetLoop('PAGE',$page);
		$GLOBALS['CLA_HTML']->display();
		RemoveMessageInfo();		
	}

	private function datatableModules(){
		$draw = $_POST['draw'];
		$row = $_POST['start'];
		$rowperpage = $_POST['length'];
		$columnIndex = $_POST['order'][0]['column'];
		$columnName = $_POST['columns'][$columnIndex]['data'];
		$columnSortOrder = $_POST['order'][0]['dir'];
		$searchValue = $_POST['search']['value'];
		$searchQuery = " ";
		if($searchValue != ''){
			$searchQuery = " and (module_name like '%".$searchValue."%')";
		}

		$records = $GLOBALS['DB']->row("select count(*) as allcount FROM `admin_modules` WHERE 1".$searchQuery);
		$totalRecordwithFilter = $records['allcount'];
		$modulesRecords = $GLOBALS['DB']->query("select * FROM `admin_modules` WHERE 1". $searchQuery." order by ".$columnName." ".$columnSortOrder." limit ".$row.",".$rowperpage);
		$data = array();
		$count=1; 
		
		foreach($modulesRecords as $row) {
			$GLOBALS['ModuleName'] = $row['module_name'];
			if($row['module_seo'] =='-'){
				$GLOBALS['Moduleseo'] = '#';
			}else{
				$GLOBALS['Moduleseo'] = $row['module_seo'];
			}
			if ($row['module_parentid']!='0') {
				$mrow = $GLOBALS['DB']->row("select module_name FROM `admin_modules` WHERE `module_id` = ?",array($row['module_parentid']));
				$GLOBALS['Parentmoduleid'] = $mrow['module_name']; 
			} else {
				$GLOBALS['Parentmoduleid'] = '-';
			}
			$GLOBALS['li_edit'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'edit','id'=>$row['module_id']));		
			$GLOBALS['li_view'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'view','id'=>$row['module_id']));	
			$GLOBALS['li_delete'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'delete','id'=>$row['module_id']));
			$GLOBALS['on_delete'] = 'return jsConfirm("","delete")';
			
			if($row['status']){
				$ecards[$count]['StatusImage'] = $GLOBALS['ADMIN_LINK'].'/images/status_on.png';	
				$ecards[$count]['StatusLink'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'status','id'=>$row['module_id'],'status'=>'0'));
				$ecards[$count]['Statusclass'] = 'c-gr f-22 feather icon-toggle-right';
			}else{
				$ecards[$count]['StatusImage'] = $GLOBALS['ADMIN_LINK'].'/images/status_off.png';	
				$ecards[$count]['StatusLink'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'status','id'=>$row['module_id'],'status'=>'1'));
				$ecards[$count]['Statusclass'] = 'c-rd f-22 feather icon-toggle-left';
			}
			
			$data[] = array(
				"status"=>"<a href='".$ecards[$count]['StatusLink']."' class='".$ecards[$count]['Statusclass']."'></a>",
				"name" =>$row['module_name'],
				"seourl"=>$GLOBALS['Moduleseo'],
				"parentmodulename"=>$GLOBALS['Parentmoduleid'],
				"action"=>"<a href='".$GLOBALS['li_edit']."' class='f-20 feather icon-edit' title='Edit'>&nbsp;</a><a href='".$GLOBALS['li_delete']."' class='f-20 feather icon-trash' title='Delete' onclick='".$GLOBALS['on_delete']."'>&nbsp;</a>"
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
	
	private function updateErrorMessage(){
		if(isset($_SESSION['Error'])){
			$_SESSION['Error'] = str_replace("<div class='alert alert-danger'>for key 'seourl'",'. Entered seourl address already exist. Try again.</div>',$_SESSION['Error']);
		}	
	}	

	private function mainCat($selCat = '', $selParentCat = ''){  
		if($_REQUEST['action'] == 'edit'){

			$mainCatQuery = $GLOBALS['DB']->query("select module_id , module_name from admin_modules where module_parentid='0' ORDER BY module_order ASC");

			foreach ($mainCatQuery as $mainRow) {
				$firstLevel  = 1;						
				if($selParentCat == $mainRow['module_id']){
					$this->catContent .= sprintf("<option value='%d' %s>%s</option>",$mainRow['module_id'],'selected="selected"',$mainRow['module_name']);
				} else {
					$this->catContent .= sprintf("<option value='%d'>%s</option>",$mainRow['module_id'],$mainRow['module_name']);
				}
				$this->subCategories($mainRow['module_id'],$firstLevel, $selCat, $selParentCat);    				
			}
		} else {		
			$mainCatQuery = $GLOBALS['DB']->query("select module_id , module_name from admin_modules where module_parentid='0' ORDER BY module_order ASC"); 

			foreach ($mainCatQuery as $mainRow) {		
				$firstLevel  = 1;		
				$this->catContent .= sprintf("<option value='%d'>%s</option>",$mainRow['module_id'],$mainRow['module_name']);
				$this->subId = '';
			    $this->catparentlist = '';
				$this->subCategories($mainRow['module_id'],$firstLevel);    
			}			
		}
		return $this->catContent;
	}
	
    private function subCategories($mainCatId,$Level,$selCat = '',$selParentCat = ''){		
		$Level = $Level + 1;	   
		$parentList = trim($this->subId,',');
		if(end(explode('|',$parentList)) != $mainCatId){
		   $this->subId = $mainCatId;
	    }		
        
		$subCatQuery = $GLOBALS['DB']->query("select module_id, module_name,module_parentlist from admin_modules where module_parentid=? order by module_id ASC",array($mainCatId)); 
		$countRows=count($subCatQuery);

        if($countRows > 0){
			if($_REQUEST['action'] == 'edit'){		
				
				foreach ($subCatQuery as $subRow) {
					$this->subId .= ',';			
					if($selCat == $subRow['module_id']){
						$this->catparentlist = $subRow['module_parentlist'];
						$this->catContent .= sprintf("<option value='%d' style='padding-left:%dpx' disabled=\"disabled\">- %s</option>",$subRow['module_id'],$this->needSpace($Level*12),$subRow['module_name']);						
					} else if(strstr($subRow['module_parentlist'],$this->catparentlist) && $selCat == $subRow['module_id']){
						$this->catparentlist = $subRow['module_parentlist'];
						$this->catContent .= sprintf("<option value='%d' style='padding-left:%dpx' disabled=\"disabled\">- %s</option>",$subRow['module_id'],$this->needSpace($Level*12),$subRow['module_name']);						
					} else {	
						if($selParentCat == $subRow['module_id']){
							$this->catContent .= sprintf("<option value='%d' style='padding-left:%dpx' %s>- %s</option>",$subRow['module_id'],$this->needSpace($Level*12),'selected="selected"',$subRow['module_name']);
						} else {
							$this->catContent .= sprintf("<option value='%d' style='padding-left:%dpx'>- %s</option>",$subRow['module_id'],$this->needSpace($Level*12),$subRow['module_name']);						
						}
					}		
										
					$this->subCategories($subRow['module_id'],$Level, $selCat, $selParentCat);				
				}
			} else {		
				foreach ($subCatQuery as $subRow) {									
					$this->subId .= ','.$subRow['module_id'];
					if($selCat != $subRow['module_id'] && $selCat == $subRow['module_parentid']){
						if($selParentCat == $subRow['module_parentid']){ 
							$this->catContent .= sprintf("<option value='%s' style='padding-left:%dpx'>- %s</option>",$subRow['module_id'],$this->needSpace($Level*12),$subRow['module_name']);
						} else {
							$this->catContent .= sprintf("<option value='%s' style='padding-left:%dpx'>- %s</option>",$subRow['module_id'],$this->needSpace($Level*12),$subRow['module_name']);						
						}
					} else {				 
						$this->catContent .= sprintf("<option value='%s' style='padding-left:%dpx' disabled=\"disabled\">- %s</option>",$subRow['module_id'],$this->needSpace($Level*12),$subRow['module_name']);						
					}
					$this->subCategories($subRow['module_id'],$Level, $selCat, $selParentCat);				
				}				
			}
		}        
	}  
	                                     
    private function needSpace($setSpace){    
        $getSpace = $setSpace * 2;        
		
        return $getSpace;
    }	
}