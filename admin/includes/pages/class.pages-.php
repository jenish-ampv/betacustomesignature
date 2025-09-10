<?php
require_once($GLOBALS['BASE_LINK'].GetConfig('CIPL_Business').'/CIPL_pages.php');
require_once(GetConfig('SITE_BASE_PATH').'/admin/includes/display/Image.php');
require_once(GetConfig('SITE_BASE_PATH').'/admin/includes/display/Pagination.php');
class CIT_PAGES
{
	private $count;
	private $result;
	private $user;
	private $id ='';
	private $mainCatQuery,$catContent,$firstLevel;
	public function __construct(){				
		$GLOBALS['CLA_CIPL_BAL'] = GetClass('CIPL_pages');					
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
			case "view":
				$this->viewPage();
				break;
			case "add":
				$this->addPage();
				break;			
			default:
				$this->page();
				break;			
		}
	}
	private function viewPage(){ 
		if(isset($_REQUEST['id'])){
			$id = $_REQUEST['id'];
		} else {
			$id =  '';
		}
		
		if(is_numeric($id)){
				
			$result = $GLOBALS['CLA_CIPL_BAL']->pages(4,$GLOBALS['PageStart'],$GLOBALS['PerPage'],$_REQUEST['id']);		
			$row = $GLOBALS['CLA_DB']->Fetch($result);	
			
			$GLOBALS['GetId'] = $row['id'];
			$GLOBALS['GetName'] = $row['name'];
			$GLOBALS['GetSeoUrl'] = $row['seourl'];
			$GLOBALS['GetMetaTitle'] = $row['metatitle'];
			$GLOBALS['GetMetaKeywords'] = $row['metakeywords'];
			$GLOBALS['GetMetaDescription'] = $row['metadescription'];
			$GLOBALS['GetType']  = $row['type'];	
			$GLOBALS['GetModulename']  = $row['modulename'];
			if(is_null($row['parentname']))
				$GLOBALS['GetParentName'] = '--';
			else							
				$GLOBALS['GetParentName'] = $row['parentname'];
					
			if($GLOBALS['GetType']){
				$GLOBALS['GetModuleTextDisplay']  = 'style="display:none;"';
			} else{
				$GLOBALS['GetModuleTextDisplay']  = '';
			}
			if($row['type'] == '1'){
				$GLOBALS['PageType'] = 'Cms';
			}elseif($row['type'] == '0') {
				$GLOBALS['PageType'] = 'Module';
			} else {
				$GLOBALS['PageType'] = 'Blank(#)';
			}
			if(!isset($_POST['desc'])){ $GLOBALS['GetDesc'] = $row['desc']; } 
			$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/'.$GLOBALS['Module'].'/pages.view.html');				
			$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');			
			$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');						
			$GLOBALS['LEFT'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.left.html');			
			$GLOBALS['CLA_HTML']->display();
		} else {
			$_SESSION['Error'] = 'Record not valid.';
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
			$addResult = $GLOBALS['CLA_CIPL_BAL']->pages(3,'','',$GLOBALS['CLA_DB']->Quote($id));
			if($addResult){
				$_SESSION['Success'] = 'Page deleted successfully';				
			} else {
				$_SESSION['Error'] = 'An error occurred while you trying to delete user, please try again.';				
			}
			GetAdminRedirectUrl(GetAdminUrl(array('module'=>$_REQUEST['module'])));
		} else {
			$_SESSION['Error'] = 'Record not valid.';
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
		
			$result = $GLOBALS['CLA_CIPL_BAL']->pages(4,$GLOBALS['PageStart'],$GLOBALS['PerPage'],$_REQUEST['id']);			
			$row = $GLOBALS['CLA_DB']->Fetch($result);		
			
			if(isset($_POST['name']))
			{	
				if($_POST['name'] != '' && $_POST['name'] != '' && $_POST['type'] != ''){
					if($_POST['type'] == 0 && trim($_POST['modulename']) == ''){
						
						$_SESSION['Error'] = 'Enter module name should not blank.';
						$GLOBALS['GetId'] = $id;
						$GLOBALS['GetName'] = $_POST['name'];
						$GLOBALS['GetSeoUrl'] = $_POST['seourl'];
						$GLOBALS['GetMetaTitle'] = $_POST['metatitle'];
						$GLOBALS['GetMetaKeywords'] = $_POST['metakeywords'];
						$GLOBALS['GetMetaDescription'] = $_POST['metadescription'];	
						$GLOBALS['GetType']  = $_POST['type'];
						$GLOBALS['GetModulename']  = $_POST['modulename'];
							
						// calling fckeditor
						$editor = new FCKeditor('desc');
						$editor->BasePath   = $GLOBALS['EDITOR_LINK'];
						$editor->Width      = "100%";//
						$editor->Height     = "400";//
						$editor->Value      = $_POST['desc'];
						$GLOBALS['FCK_Desc'] = $editor->CreateHtml();	
						// calling fckeditor						
						$GLOBALS['CatContent'] = $this->mainCat($id,$_POST['parentid']);
						
					} else {
						$seourl = preg_replace('/[^a-zA-Z0-9]/i',' ',$_POST['seourl']);
						$seourl = str_replace(' ','-',$seourl);	
						
						$parentId = $GLOBALS['CLA_DB']->Quote($_POST['parentid']);
						if($parentId != 0){
							$reUpdate = $GLOBALS['CLA_CIPL_BAL']->pages(4,$GLOBALS['PageStart'],$GLOBALS['PerPage'],$parentId);			
							$rowUpdate = $GLOBALS['CLA_DB']->Fetch($reUpdate);		
							
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
							$_SESSION['Success'] .= sprintf('Image uploaded successfully<br />');					
						}
						if($image['msg'] == "error"){
							$_SESSION['Error'] .= sprintf('Image not valid<br />');						
						}
					}
				} else {
					$image['name'] = $row['image'];
				}
					$descContent = str_ireplace('<p><span>','<span>',$GLOBALS['CLA_DB']->Quote($_POST['sortdesc']));
					$descContent = str_ireplace('</p></span><br />','</span><br />',$descContent);
					$descContent = str_ireplace('<p>','<span>',$descContent);
					$descContent = str_ireplace('</p>','</span><br />',$descContent);
				
					$updateResult = $GLOBALS['CLA_CIPL_BAL']->pages(2,'','',$id,$GLOBALS['CLA_DB']->Quote($_POST['name']),$GLOBALS['CLA_DB']->Quote($_POST['desc']),$seourl,$GLOBALS['CLA_DB']->Quote($_POST['metatitle']),$GLOBALS['CLA_DB']->Quote($_POST['metakeywords']),$GLOBALS['CLA_DB']->Quote($_POST['metadescription']),$GLOBALS['CLA_DB']->Quote($_POST['type']),$GLOBALS['CLA_DB']->Quote($_POST['modulename']),$parentId, $parentList,$image['name'],$descContent);	
						if($updateResult){
							$_SESSION['Success'] = 'Page updated successfully';
							GetAdminRedirectUrl($GLOBALS['CURRENT_URL']);
						} else {
							$getError = $GLOBALS['CLA_DB']->GetError();
							$_SESSION['Error'] = 'An error occurred while you trying to update page, please try again.<br />'.$getError[0].'<br />';
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
						
						
						// calling fckeditor
						$editor = new FCKeditor('sortdesc');
						$editor->BasePath   = $GLOBALS['EDITOR_LINK'];
						$editor->Width      = "100%";//
						$editor->Height     = "400";//
						$editor->Value      = $_POST['sortdesc'];
						$GLOBALS['FCK_sortdesc'] = $editor->CreateHtml();	
						// calling fckeditor
						
						// calling fckeditor
						$editor = new FCKeditor('desc');
						$editor->BasePath   = $GLOBALS['EDITOR_LINK'];
						$editor->Width      = "100%";//
						$editor->Height     = "400";//
						$editor->Value      = $_POST['desc'];
						$GLOBALS['FCK_Desc'] = $editor->CreateHtml();	
						// calling fckeditor
					}
				} else {
					$_SESSION['Error'] = 'Required fields should not blank.';
					
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
					// calling fckeditor
						$editor = new FCKeditor('sortdesc');
						$editor->BasePath   = $GLOBALS['EDITOR_LINK'];
						$editor->Width      = "100%";//
						$editor->Height     = "400";//
						$editor->Value      = $row['sortdesc'];
						$GLOBALS['FCK_sortdesc'] = $editor->CreateHtml();	
						
					
					// calling fckeditor
					$editor = new FCKeditor('desc');
					$editor->BasePath   = $GLOBALS['EDITOR_LINK'];
					$editor->Width      = "100%";//
					$editor->Height     = "400";//
					$editor->Value      = $row['desc'];
					$GLOBALS['FCK_Desc'] = $editor->CreateHtml();	
					// calling fckeditor
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
				
				
				GetConfig('SITE_UPLOAD_PATH').'/cmspage/'.$row['image']; 
				if(!is_file(GetConfig('SITE_UPLOAD_PATH').'/cmspage/'.$row['image'])){ 
					$GLOBALS['ServiceImage'] = $GLOBALS['UPLOAD_LINK'].'/no-image.jpg';	
				} else { 
					$GLOBALS['ServiceImage'] = $GLOBALS['UPLOAD_LINK'].'/cmspage/'.$row['image'];	
				}
				
				// calling fckeditor
						$editor = new FCKeditor('sortdesc');
						$editor->BasePath   = $GLOBALS['EDITOR_LINK'];
						$editor->Width      = "100%";//
						$editor->Height     = "400";//
						$editor->Value      = $row['sortdesc'];
						$GLOBALS['FCK_sortdesc'] = $editor->CreateHtml();	
						// calling fckeditor
				
				// calling fckeditor
				$editor = new FCKeditor('desc');
				$editor->BasePath   = $GLOBALS['EDITOR_LINK'];
				$editor->Width      = "100%";//
				$editor->Height     = "400";//
				$editor->Value      = $row['desc'];
				$GLOBALS['FCK_Desc'] = $editor->CreateHtml();	
				// calling fckeditor
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
			$_SESSION['Error'] = 'Record not valid.';
			GetAdminRedirectUrl(GetAdminUrl(array('module'=>$_REQUEST['module']),0));
		}
	}
	private function addPage(){	
		$queryAI = $GLOBALS['CLA_DB']->AutoIncrement("pages");
		$rowAI =$GLOBALS['CLA_DB']->Fetch($queryAI);
		
		if(isset($_POST['name'])){ 
			if($_POST['name'] != '' && $_POST['type'] != '')
			{ 
				if($_POST['type'] == 0 && $_POST['modulename'] == ''){ 
					$_SESSION['Error'] = 'Enter module name should not blank.';
					
					
					$GLOBALS['GetName'] = $_POST['name'];
					$GLOBALS['GetSeoUrl'] = $_POST['seourl'];
					$GLOBALS['GetMetaTitle'] = $_POST['metatitle'];
					$GLOBALS['GetMetaKeywords'] = $_POST['metakeywords'];
					$GLOBALS['GetMetaDescription'] = $_POST['metadescription'];	
					$GLOBALS['GetType']  = $_POST['type'];
					$GLOBALS['GetModulename']  = $_POST['modulename'];	
					$GLOBALS['GetDesc'] = $_POST['desc'];
				} else { 
					
					
					$parentId = $GLOBALS['CLA_DB']->Quote($_POST['parentid']);					
					if($parentId != 0){
						$result = $GLOBALS['CLA_CIPL_BAL']->pages(4,$GLOBALS['PageStart'],$GLOBALS['PerPage'],$parentId);			
						$row = $GLOBALS['CLA_DB']->Fetch($result);		
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
									
					if($imageSourcefile['name'] != '')
					{ 
						$imageDestination = GetConfig('SITE_UPLOAD_PATH').'/cmspage/'.$rowAI['Auto_increment']; 
						$image = $objImage->imageUploadResize($imageSourcefile,$imageDestination,'image');	 						
						
						if($image['msg'] == "success")
						{ 
							$_SESSION['Success'] .= sprintf('Image uploaded successfully<br />');					
						}
						if($image['msg'] == "error")
						{
							$_SESSION['Error'] .= sprintf('Image not valid<br />');						
						}
					}
					
					$seourl = preg_replace('/[^a-zA-Z0-9]/i',' ',$_POST['seourl']);
					$seourl = str_replace(' ','-',$seourl);	
					
					$descContent = str_ireplace('<p>','<span>',$GLOBALS['CLA_DB']->Quote($_POST['sortdesc']));
					$descContent = str_ireplace('</p>','</span><br />',$descContent);
					
						
					$addResult = $GLOBALS['CLA_CIPL_BAL']->pages(1,'','','',$GLOBALS['CLA_DB']->Quote($_POST['name']),$GLOBALS['CLA_DB']->Quote($_REQUEST['desc']),$seourl,$GLOBALS['CLA_DB']->Quote($_POST['metatitle']),$GLOBALS['CLA_DB']->Quote($_POST['metakeywords']),$GLOBALS['CLA_DB']->Quote($_POST['metadescription']),$GLOBALS['CLA_DB']->Quote($_POST['type']),$GLOBALS['CLA_DB']->Quote($_POST['modulename']), $parentId, $parentList,$image['name'],$descContent);	
					if($addResult){
						$_SESSION['Success'] = 'Page added successfully';
						GetAdminRedirectUrl($GLOBALS['CURRENT_URL']);
					} else {
						$getError = $GLOBALS['CLA_DB']->GetError();
						$_SESSION['Error'] = 'An error occurred while you trying to add new page, please try again.<br />'.$getError[0].'<br />';
						
					
						$GLOBALS['GetName'] = $_POST['name'];
						$GLOBALS['GetSeoUrl'] = $seourl;
						$GLOBALS['GetMetaTitle'] = $_POST['metatitle'];
						$GLOBALS['GetMetaKeywords'] = $_POST['metakeywords'];
						$GLOBALS['GetMetaDescription'] = $_POST['metadescription'];	
						$GLOBALS['GetType']  = $_POST['type'];
						$GLOBALS['GetModulename']  = $_POST['modulename'];	
						$GLOBALS['GetDesc'] = $_POST['desc'];
					}	
				}
			} else {
				$_SESSION['Error'] = 'Required fields should not blank.';
				
			
				$GLOBALS['GetName'] = $_POST['name'];
				$GLOBALS['GetSeoUrl'] = $_POST['seourl'];
				$GLOBALS['GetMetaTitle'] = $_POST['metatitle'];
				$GLOBALS['GetMetaKeywords'] = $_POST['metakeywords'];
				$GLOBALS['GetMetaDescription'] = $_POST['metadescription'];	
				$GLOBALS['GetType']  = $_POST['type'];
				$GLOBALS['GetModulename']  = $_POST['modulename'];
				$GLOBALS['GetDesc'] = $_POST['desc'];	
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
	  	$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/'.$GLOBALS['Module'].'/pages.add.html');	
		$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');			
		$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');						
		$GLOBALS['LEFT'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.left.html');			
		$GLOBALS['CLA_HTML']->display();
		RemoveMessageInfo();		
	}
	private function page(){		
		AddMessageInfo();		
		
		$selectResult = $GLOBALS['CLA_CIPL_BAL']->pages(4,$GLOBALS['PageStart'],$GLOBALS['PerPage']); 
		$count =0;		
		while($row = $GLOBALS['CLA_DB']->Fetch($selectResult))
		{		
			$page[$count]['Name'] = $row['name'];			
			$page[$count]['Desc'] = $row['desc'];	
			if($row['type'] == '2')
			{
				$page[$count]['SeoUrl'] = '#';
			} else {
				$page[$count]['SeoUrl'] = $row['seourl'];
			}
			
			if($row['type'] == '1'){
				$page[$count]['PageType'] = 'Cms';
			}elseif($row['type'] == '0') {
				$page[$count]['PageType'] = 'Module';
			} else {
				$page[$count]['PageType'] = 'Blank(#)';
			}
			$page[$count]['Gridcolor'] = $count%2 == 0 ? 'dataOdd' : 'dataEven';
			$page[$count]['li_edit'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'edit','id'=>$row['id']));							
			$page[$count]['li_delete'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'delete','id'=>$row['id']));	
			$page[$count]['li_view'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'view','id'=>$row['id']));	
					
			$count++;
		}	
		
		$GLOBALS['DIS_PAGE']->Page('pages');
		$GLOBALS['PageNextImage'] = $GLOBALS['DIS_PAGE']->nextImage();
		$GLOBALS['PagePrevImage'] = $GLOBALS['DIS_PAGE']->prevImage();
		$GLOBALS['PagePageLink'] = $GLOBALS['DIS_PAGE']->pageLink();
		
		
		$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/'.$GLOBALS['Module'].'/pages.html');	
		$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');			
		$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');						
		$GLOBALS['LEFT'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.left.html');			
		$GLOBALS['CLA_HTML']->SetLoop('PAGE',$page);
		$GLOBALS['CLA_HTML']->display();
		RemoveMessageInfo();		
	}
	private function updateErrorMessage(){
		if(isset($_SESSION['Error'])){
			$_SESSION['Error'] = str_replace(" for key 'seourl'",'. Entered seourl address already exist. Try again.',$_SESSION['Error']);
		}	
	}	
	private function mainCat($selCat = '', $selParentCat = ''){   		
		if($_REQUEST['action'] == 'edit'){
			$mainCatQuery = $GLOBALS['CLA_DB']->Query("select id, name from pages where parentid='0' order by id ASC");    
			while($mainRow = $GLOBALS['CLA_DB']->Fetch($mainCatQuery)){			
				$firstLevel  = 1;		
				//if($selCat != $mainRow['id']){					
					if($selParentCat == $mainRow['id']){
						$this->catContent .= sprintf("<option value='%d' %s>%s</option>",$mainRow['id'],'selected="selected"',$mainRow['name']);
					} else {
						$this->catContent .= sprintf("<option value='%d'>%s</option>",$mainRow['id'],$mainRow['name']);
					}
				//} else {
					//$this->catContent .= sprintf("<option value='%d' disabled=\"disabled\">%s</option>",$mainRow['id'],$mainRow['name']);					
				//}
				$this->subId = '';
				$this->subCategories($mainRow['id'],$firstLevel, $selCat, $selParentCat);    				
			}
		} else {			
			$mainCatQuery = $GLOBALS['CLA_DB']->Query("select id, name from pages where parentid='0' order by id DESC");    
			while($mainRow = $GLOBALS['CLA_DB']->Fetch($mainCatQuery)){			
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
        $sql = "select id, name, catparentlist from pages where parentid='".$mainCatId."'  order by id ASC";
        $subCatQuery = $GLOBALS['CLA_DB']->Query($sql) or die(mysql_error());
        $countRows=$GLOBALS['CLA_DB']->CountResult($subCatQuery);		
        if($countRows > 0){
			if($_REQUEST['action'] == 'edit'){				
				while($subRow = $GLOBALS['CLA_DB']->Fetch($subCatQuery)){										
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
				while($subRow = $GLOBALS['CLA_DB']->Fetch($subCatQuery)){										
					$this->subId .= ','.$subRow['id'];
					if($selCat != $subRow['id'] && $selCat == $subRow['parentid']){
						if($selParentCat == $subRow['parentid']){
							//$subRow['name']
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