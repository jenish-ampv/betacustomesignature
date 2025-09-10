<?php
require_once(GetConfig('SITE_BASE_PATH').'/admin/includes/display/Image.php');
class CIT_PAGES{
	private $count;
	private $result;
	private $user;
	private $id ='';
	private $mainCatQuery,$catContent,$firstLevel;
	public function __construct(){		
		$GLOBALS['ModuleName'] = 'Pages';						
		$GLOBALS['DeviceModuleName'] = 'Pages';	
	}
	public function displayPage(){
		if(isset($_REQUEST['action'])){
			$action = trim($_REQUEST['action']);
		} else {
			$action = '';
		}
		switch($action){
			case "delete":
				$this->deletePage();
				break;
			case "edit":
				$this->editPage();
				break;
			case "add":
				$this->addPage();
				break;	
			case "datatablepages";
				$this->datatablePages();
				break;		
			default:
				$this->page();
				break;			
		}
	}
	
	private function deletePage(){
		if(isset($_REQUEST['id'])){
			$id = $_REQUEST['id'];
		} else {
			$id =  '';
		}
		if(is_numeric($id)){		
			$addResult = $GLOBALS['DB']->query("DELETE FROM `pages` WHERE id = ?",array($id));
			if($addResult){
				$_SESSION['Success'] = '<div class="alert alert-success">Page deleted successfully</div>';				
			} else {
				$_SESSION['Error'] = '<div class="alert alert-danger">An error occurred while you trying to delete user, please try again.</div>';				
			}
			GetAdminRedirectUrl(GetAdminUrl(array('module'=>$_REQUEST['module'])));
		} else {
			$_SESSION['Error'] = '<div class="alert alert-danger">Record not valid.</div>';
			GetAdminRedirectUrl(GetAdminUrl(array('module'=>$_REQUEST['module']),0));
		}			
	}

	private function editPage(){
		if(isset($_REQUEST['id'])){
			$id = $_REQUEST['id'];
		} else { 
			$id =  '';
		}
		if(is_numeric($id)){	
			$row  = $GLOBALS['DB']->row("SELECT * FROM `pages` WHERE id = ? LIMIT 0,1",array($id));			
			if(isset($_POST['name'])){	
				if($_POST['name'] != '' && $_POST['name'] != '' && $_POST['type'] != ''){
					if($_POST['type'] == 0 && trim($_POST['modulename']) == ''){
						$_SESSION['Error'] = '<div class="alert alert-danger">Enter module name should not blank.</div>';
						$GLOBALS['GetId'] = $id;
						$GLOBALS['GetName'] = $_POST['name'];
						$GLOBALS['GetSeoUrl'] = $_POST['seourl'];
						$GLOBALS['GetMetaTitle'] = $_POST['metatitle'];
						$GLOBALS['GetMetaKeywords'] = $_POST['metakeywords'];
						$GLOBALS['GetMetaDescription'] = $_POST['metadescription'];	
						$GLOBALS['GetType']  = $_POST['type'];
						$GLOBALS['GetModulename']  = $_POST['modulename'];	
						if(!is_file(GetConfig('SITE_UPLOAD_PATH').'/cmspage/'.$row['image'])){ 
					$GLOBALS['ServiceImage'] = $GLOBALS['UPLOAD_LINK'].'/no-image.jpg';	
				} else { 
					$GLOBALS['ServiceImage'] = $GLOBALS['UPLOAD_LINK'].'/cmspage/'.$row['image'];	
				}
					$GLOBALS['CatContent'] = $this->mainCat($id,$_POST['parentid']);				
					} else {
						$seourl = preg_replace('/[^a-zA-Z0-9]/i',' ',$_POST['seourl']);
						$seourl = str_replace(' ','-',$seourl);	
						$parentId = $_POST['parentid'];
						if($parentId != 0){
							$rowUpdate = $GLOBALS['DB']->row("SELECT * FROM `pages` WHERE id = ? LIMIT 0,1",array($parentId));					
							if($rowUpdate['catparentlist'] != ''){
								$parentList = $rowUpdate['catparentlist'].','.$id;
							} else {
								$parentList = $parentId;
							}
						} else {
							$parentList = $id;							
						}	
						$objImage = GetClass('Image');
						$smallimage ='';
				if($_FILES['cms_image']['name'] != ''){ 
					$imageSourcefile = $_FILES['cms_image'];
					if($imageSourcefile['name'] != ''){
						$imageDestination = GetConfig('SITE_UPLOAD_PATH').'/cmspage/'.$id;
						$image = $objImage->imageUpload($imageSourcefile,$imageDestination,'image'); 
						if($image['msg'] == "success"){
							$_SESSION['Success'] .= sprintf('<div class="alert alert-success">Image uploaded successfully<br /></div>');
						}
						if($image['msg'] == "error"){
							$_SESSION['Error'] .= sprintf('<div class="alert alert-danger">Image not valid<br /></div>');						
						}
					}
				} else { 
					$image['name'] = $row['image'];
				}
					$descContent = str_ireplace('<p><span>','<span>',$_POST['sortdesc']);
					$descContent = str_ireplace('</p></span><br />','</span><br />',$descContent);
					$descContent = str_ireplace('<p>','<span>',$descContent);
					$descContent = str_ireplace('</p>','</span><br />',$descContent);
						
					$updateResult = $GLOBALS['DB']->query("UPDATE `pages` SET `site_id`=".$GLOBALS['SITE_ID'].",`name`='".$_POST['name']."',`desc`='".$_POST['desc']."',`seourl`='".$seourl."',`type`=".$_POST['type'].",`modulename`='".$_POST['modulename']."',`metatitle`='".$_POST['metatitle']."',`metakeywords`='".$_POST['metakeywords']."',`metadescription`='".$_POST['metadescription']."',`image`='".$imagename."',`sortdesc`='".$descContent."' WHERE id=$id");	
										
						if($updateResult){
							$_SESSION['Success'] = '<div class="alert alert-success">Page updated successfully</div>';
							GetAdminRedirectUrl($GLOBALS['CURRENT_URL']);
						} else {
							$_SESSION['Error'] = '<div class="alert alert-danger">An error occurred while you trying to update page, please try again.</div>';
						}								
						$GLOBALS['GetId'] = $id;
						$GLOBALS['GetName'] = $_POST['name'];
						$GLOBALS['GetSeoUrl'] = $seourl;
						$GLOBALS['GetMetaTitle'] = $_POST['metatitle'];
						$GLOBALS['GetMetaKeywords'] = $_POST['metakeywords'];
						$GLOBALS['GetMetaDescription'] = $_POST['metadescription'];	
						$GLOBALS['GetType']  = $_POST['type'];
						$GLOBALS['GetModulename']  = $_POST['modulename'];
						$GLOBALS['CatContent'] = $this->mainCat($id,$_POST['parentid']);
						if(!is_file(GetConfig('SITE_UPLOAD_PATH').'/cmspage/'.$row['image'])){ 
							$GLOBALS['ServiceImage'] = $GLOBALS['UPLOAD_LINK'].'/no-image.jpg';	
						} else { 
							$GLOBALS['ServiceImage'] = $GLOBALS['UPLOAD_LINK'].'/cmspage/'.$row['image'];	
						}
					}
				} else {
					$_SESSION['Error'] = '<div class="alert alert-danger">Required fields should not blank.</div>';
					$GLOBALS['GetId'] = $row['id'];
					$GLOBALS['GetName'] = $row['name'];
					$GLOBALS['GetSeoUrl'] = $row['seourl'];
					$GLOBALS['GetMetaTitle'] = $row['metatitle'];
					$GLOBALS['GetMetaKeywords'] = $row['metakeywords'];
					$GLOBALS['GetMetaDescription'] = $row['metadescription'];
					$GLOBALS['GetType']  = $row['type'];	
					$GLOBALS['GetModulename']  = $row['modulename'];	
					$GLOBALS['CatContent'] = $this->mainCat($row['id'],$row['parentid']);
				
				if(!is_file(GetConfig('SITE_UPLOAD_PATH').'/cmspage/'.$row['image'])){ 
				} else { 
					$GLOBALS['ServiceImage'] = $GLOBALS['UPLOAD_LINK'].'/cmspage/'.$row['image'];	
				}
			}
			} else {				
				$GLOBALS['GetId'] = $row['id'];
				$GLOBALS['GetName'] = $row['name'];
				$GLOBALS['GetSeoUrl'] = $row['seourl'];
				$GLOBALS['GetMetaTitle'] = $row['metatitle'];
				$GLOBALS['GetMetaKeywords'] = $row['metakeywords'];
				$GLOBALS['GetMetaDescription'] = $row['metadescription'];
				$GLOBALS['GetType']  = $row['type'];	
				$GLOBALS['GetModulename']  = $row['modulename'];
				$GLOBALS['CatContent'] = $this->mainCat($row['id'],$row['parentid']);				
				if(!is_file(GetConfig('SITE_UPLOAD_PATH').'/cmspage/'.$row['image'])){ 
					$GLOBALS['ServiceImage'] = $GLOBALS['UPLOAD_LINK'].'/no-image.jpg';	
				} else { 
					$GLOBALS['ServiceImage'] = $GLOBALS['UPLOAD_LINK'].'/cmspage/'.$row['image'];	
				}
			}	
			if($GLOBALS['GetType']){
				$GLOBALS['GetModuleTextDisplay']  = 'style="display:none;"';
			} else{
				$GLOBALS['GetModuleTextDisplay']  = '';
			}
			if(isset($_POST['desc'])){ $GLOBALS['GetDesc'] = $_POST['desc']; } 
			if(!isset($_POST['desc'])){ $GLOBALS['GetDesc'] = $row['desc']; } 
			$this->updateErrorMessage();
			AddMessageInfo();			
			$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/'.$GLOBALS['Module'].'/pages.edit.html');				
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

	private function addPage(){	
		$rowAI = $GLOBALS['DB']->AutoIncrement("pages");
		if(isset($_POST['name'])){ 
			// if($_POST['name'] != '' && $_POST['name'] != '' && $_POST['type'] != ''){
			// if($_POST['type'] == 0 && $_POST['modulename'] == ''){					
			if($_POST['name'] != ''){				
				if($_POST['type'] != '' && $_POST['modulename'] == '' && $_POST['seourl'] == '' ){
					$_SESSION['Error'] = '<div class="alert alert-danger">Enter module name should not blank.</div>';
					$GLOBALS['GetName'] = $_POST['name'];
					$GLOBALS['GetSeoUrl'] = $_POST['seourl'];
					$GLOBALS['GetMetaTitle'] = $_POST['metatitle'];
					$GLOBALS['GetMetaKeywords'] = $_POST['metakeywords'];
					$GLOBALS['GetMetaDescription'] = $_POST['metadescription'];	
					$GLOBALS['GetType']  = $_POST['type'];
					$GLOBALS['GetModulename']  = $_POST['modulename'];
				} else { 
					$parentId = $_POST['parentid'];					
					if($parentId != 0){		
						$row = $rowUpdate = $GLOBALS['DB']->row("SELECT * FROM `pages` WHERE id = ? LIMIT 0,1",array($parentId));		
						if($row['catparentlist'] != ''){
							$parentList = $row['catparentlist'].','.$rowAI['Auto_increment'];
						} else {
							$parentList = $parentId;
						}
					} else {
						$parentList = $rowAI['Auto_increment'];
					}	
					$objImage = GetClass('Image');
					$image ='';
					$imageSourcefile = $_FILES['cms_image'];		
					if($imageSourcefile['name'] != ''){ 
						$imageDestination = GetConfig('SITE_UPLOAD_PATH').'/cmspage/'.$rowAI['Auto_increment']; 
						$image = $objImage->imageUploadResize($imageSourcefile,$imageDestination,'image');	 						
						if($image['msg'] == "success"){ 
							$_SESSION['Success'] .= sprintf('<div class="alert alert-success">Image uploaded successfully<br /></div>');		
						}
						if($image['msg'] == "error"){
							$_SESSION['Error'] .= sprintf('<div class="alert alert-danger">Image not valid<br /></div>');						
						}
					}
					if ($_POST['seourl'] == ''){
						$seourl = preg_replace('/[^a-zA-Z0-9]/i',' ',$_POST['name']);
						$seourl = str_replace(' ','-',$seourl);
					} else {
						$seourl = preg_replace('/[^a-zA-Z0-9]/i',' ',$_POST['seourl']);
						$seourl = str_replace(' ','-',$seourl);	
					}
					$descContent = str_ireplace('<p>','<span>',$_POST['sortdesc']);
					$descContent = str_ireplace('</p>','</span><br />',$descContent);

					$data =array('site_id'=>$GLOBALS['SITE_ID'],'name'=>$_POST['name'],'seourl'=>$seourl,'desc'=>$_POST['desc'], 'metatitle'=>$_POST['metatitle'], 'metakeywords'=>$_POST['metakeywords'], 'metadescription'=>$_POST['metadescription'],'type'=>$_POST['type'], 'modulename'=> $_POST['modulename'], 'parentid'=>$parentId,'catparentlist'=>$parentList,'image'=>$image['name'], 'sortdesc'=>$descContent);

					$addResult = $GLOBALS['DB']->insert("pages",$data);	
					
					if($addResult){
						$_SESSION['Success'] = '<div class="alert alert-success">Page added successfully</div>';
						GetAdminRedirectUrl($GLOBALS['CURRENT_URL']);
					} else {
						$_SESSION['Error'] = '<div class="alert alert-danger">An error occurred while you trying to add new page, please try again.</div>';
						$GLOBALS['GetName'] = $_POST['name'];
						$GLOBALS['GetSeoUrl'] = $seourl;
						$GLOBALS['GetMetaTitle'] = $_POST['metatitle'];
						$GLOBALS['GetMetaKeywords'] = $_POST['metakeywords'];
						$GLOBALS['GetMetaDescription'] = $_POST['metadescription'];	
						$GLOBALS['GetType']  = $_POST['type'];
						$GLOBALS['GetModulename']  = $_POST['modulename'];	
					}	
				}
			} else {
				$_SESSION['Error'] = '<div class="alert alert-danger">Required fields should not blank.</div>';
				$GLOBALS['GetName'] = $_POST['name'];
				$GLOBALS['GetSeoUrl'] = $_POST['seourl'];
				$GLOBALS['GetMetaTitle'] = $_POST['metatitle'];
				$GLOBALS['GetMetaKeywords'] = $_POST['metakeywords'];
				$GLOBALS['GetMetaDescription'] = $_POST['metadescription'];	
				$GLOBALS['GetType']  = $_POST['type'];
				$GLOBALS['GetModulename']  = $_POST['modulename'];	
			}
			if($GLOBALS['GetType']){
				$GLOBALS['GetModuleTextDisplay']  = 'style="display:none;"';
			} else{
				$GLOBALS['GetModuleTextDisplay']  = '';
			}
		} else { 		
			$GLOBALS['GetModuleTextDisplay']  = 'style="display:none;"';
		}
		$this->updateErrorMessage();
		$GLOBALS['GetId'] = $rowAI['Auto_increment'];
		AddMessageInfo();
		$GLOBALS['CatContent'] = $this->mainCat();
		$GLOBALS['CLA_HTML']->addMain(sprintf($GLOBALS['WWW_TPL'].'/'.$GLOBALS['Module'].'/%spages.add.html',$GLOBALS['DeviceName']));	
		$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub(sprintf($GLOBALS['WWW_TPL'].'/%spage.header.html',$GLOBALS['DeviceName']));
		$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub(sprintf($GLOBALS['WWW_TPL'].'/%spage.footer.html',$GLOBALS['DeviceName']));
		$GLOBALS['LEFT'] = $GLOBALS['CLA_HTML']->addSub(sprintf($GLOBALS['WWW_TPL'].'/%spage.left.html',$GLOBALS['DeviceName']));			
		$GLOBALS['CLA_HTML']->display();
		RemoveMessageInfo();		
	}

	private function page(){
		$GLOBALS['li_pagesdata'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'datatablepages'));
		AddMessageInfo();		
		$GLOBALS['CLA_HTML']->addMain(sprintf($GLOBALS['WWW_TPL'].'/'.$GLOBALS['Module'].'/%spages.html',$GLOBALS['DeviceName']));	
		$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub(sprintf($GLOBALS['WWW_TPL'].'/%spage.header.html',$GLOBALS['DeviceName']));			
		$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub(sprintf($GLOBALS['WWW_TPL'].'/%spage.footer.html',$GLOBALS['DeviceName']));		
		$GLOBALS['LEFT'] = $GLOBALS['CLA_HTML']->addSub(sprintf($GLOBALS['WWW_TPL'].'/%spage.left.html',$GLOBALS['DeviceName']));			
		$GLOBALS['CLA_HTML']->SetLoop('PAGE',$page);
		$GLOBALS['CLA_HTML']->display();
		RemoveMessageInfo();		
	}

	private function datatablePages(){
		$draw = $_POST['draw'];
		$row = $_POST['start'];
		$rowperpage = $_POST['length'];
		$columnIndex = $_POST['order'][0]['column'];
		$columnName = $_POST['columns'][$columnIndex]['data'];
		$columnSortOrder = $_POST['order'][0]['dir'];
		$searchValue = $_POST['search']['value'];
		$searchQuery = " ";
		if($searchValue != ''){
			$searchQuery = " and (name like '%".$searchValue."%' OR seourl like '%".$searchValue."%')";
		}
		$records = $GLOBALS['DB']->row("select count(*) as allcount FROM `pages` WHERE type = 1 AND `site_id` = ".$GLOBALS['SITE_ID'].$searchQuery);
		$totalRecordwithFilter = $records['allcount'];
		$pagesRecords = $GLOBALS['DB']->query("select * FROM `pages` WHERE type = 1 AND `site_id` = ".$GLOBALS['SITE_ID']." ". $searchQuery." order by ".$columnName." ".$columnSortOrder." limit ".$row.",".$rowperpage);
		$data = array();
		$count=1; 
		foreach($pagesRecords as $row){			
			$GLOBALS['Desc'] = $row['desc'];	
			if($row['type'] == '2'){
				$GLOBALS['SeoUrl'] = '#';
			} else {
				$GLOBALS['SeoUrl'] = $row['seourl'];
			}
			if($row['type'] == '1'){
				$GLOBALS['PageType'] = 'Cms';
			}elseif($row['type'] == '0') {
				$GLOBALS['PageType'] = 'Module';
			} else {
				$GLOBALS['PageType'] = 'Blank(#)';
			}
			$page[$count]['Gridcolor'] = $count%2 == 0 ? 'dataOdd' : 'dataEven';
			$GLOBALS['li_edit'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'edit','id'=>$row['id']));		
			$GLOBALS['li_view'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'view','id'=>$row['id']));	
			$GLOBALS['li_delete'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'delete','id'=>$row['id']));
			$GLOBALS['on_delete'] = 'return jsConfirm("","delete")';
			$data[] = array(
				"id"=>$count,
				"name"=>$row['name'],
				"seourl"=>$GLOBALS['SeoUrl'],
				"type"=>$GLOBALS['PageType'],
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
	}

	private function updateErrorMessage(){
		if(isset($_SESSION['Error'])){
			$_SESSION['Error'] = str_replace("<div class='alert alert-danger'>for key 'seourl'",'. Entered seourl address already exist. Try again.</div>',$_SESSION['Error']);
		}	
	}	

	private function mainCat($selCat = '', $selParentCat = ''){
		if($_REQUEST['action'] == 'edit'){
			$mainCatQuery = $GLOBALS['DB']->query("select id, name from pages where parentid='0' order by id ASC");    
			foreach($mainCatQuery as $mainRow){			
				$firstLevel  = 1;						
				if($selParentCat == $mainRow['id']){
					$this->catContent .= sprintf("<option value='%d' %s>%s</option>",$mainRow['id'],'selected="selected"',$mainRow['name']);
				} else {
					$this->catContent .= sprintf("<option value='%d'>%s</option>",$mainRow['id'],$mainRow['name']);
				}
				$this->subId = '';
				$this->subCategories($mainRow['id'],$firstLevel, $selCat, $selParentCat);    				
			}
		} else {			
			$mainCatQuery = $GLOBALS['DB']->query("select id, name from pages where parentid='0' order by id DESC");    
			foreach($mainCatQuery as $mainRow){			
				$firstLevel  = 1;		
				$this->catContent .= sprintf("<option value='%d'>%s</option>",$mainRow['id'],$mainRow['name']);
				$this->subId = '';
			    $this->catparentlist = '';
				$this->subCategories($mainRow['id'],$firstLevel);    
			}			
		}
		return $this->catContent;
	}
	
    private function subCategories($mainCatId,$Level,$selCat = '',$selParentCat = ''){
        $Level = $Level + 1;		
		$parentList = trim($this->subId,',');
		if(end(explode(',',$parentList)) != $mainCatId){
		   $this->subId = $mainCatId;
	    }		
        $subCatQuery = $GLOBALS['DB']->query("select id, name, catparentlist from pages where parentid= ? order by id ASC",array($mainCatId));
        $countRows=count($subCatQuery);		
        if($countRows > 0){
			if($_REQUEST['action'] == 'edit'){				
				foreach($subCatQuery as $subRow){										
					$this->subId .= ',';			
					if($selCat == $subRow['id']){
						$this->catparentlist = $subRow['catparentlist'];
						$this->catContent .= sprintf("<option value='%d' style='padding-left:%dpx' disabled=\"disabled\">- %s</option>",$subRow['id'],$this->needSpace($Level*12),$subRow['name']);						
					} else if(strstr($subRow['catparentlist'],$this->catparentlist) && $selCat == $subRow['id']){
						$this->catparentlist = $subRow['catparentlist'];
						$this->catContent .= sprintf("<option value='%d' style='padding-left:%dpx' disabled=\"disabled\">- %s</option>",$subRow['id'],$this->needSpace($Level*12),$subRow['name']);						
					} else {	
						if($selParentCat == $subRow['id']){
							$this->catContent .= sprintf("<option value='%d' style='padding-left:%dpx' %s>- %s</option>",$subRow['id'],$this->needSpace($Level*12),'selected="selected"',$subRow['name']);
						} else {
							$this->catContent .= sprintf("<option value='%d' style='padding-left:%dpx'>- %s</option>",$subRow['id'],$this->needSpace($Level*12),$subRow['name']);						
						}
					}								
					$this->subCategories($subRow['id'],$Level, $selCat, $selParentCat);				
				}
			} else {				
				foreach($subCatQuery as $subRow){										
					$this->subId .= ','.$subRow['id'];
					if($selCat != $subRow['id'] && $selCat == $subRow['parentid']){
						if($selParentCat == $subRow['parentid']){
							$this->catContent .= sprintf("<option value='%s' style='padding-left:%dpx'>- %s</option>",$subRow['id'],$this->needSpace($Level*12),$subRow['name']);
						} else {
							$this->catContent .= sprintf("<option value='%s' style='padding-left:%dpx'>- %s</option>",$subRow['id'],$this->needSpace($Level*12),$subRow['name']);						
						}
					} else {					 
						$this->catContent .= sprintf("<option value='%s' style='padding-left:%dpx' disabled=\"disabled\">- %s</option>",$subRow['id'],$this->needSpace($Level*12),$subRow['name']);						
					}
					$this->subCategories($subRow['id'],$Level, $selCat, $selParentCat);				
				}				
			}
		}        
	}  
	                                     
    private function needSpace($setSpace){    
        $getSpace = $setSpace * 2;        
        return $getSpace;
    }	
}