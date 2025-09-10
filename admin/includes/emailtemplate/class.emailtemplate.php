<?php
class CIT_EMAILTEMPLATE
{
	private $count;
	private $result;
	private $user;
	private $id ='';
	private $emailtemplate = '';
	public function __construct(){	
		$GLOBALS['ModuleName'] = "Email Template";	
		$GLOBALS['DeviceModuleName'] = 'Emailtemplate';
	}
	public function displayPage(){
		if(isset($_REQUEST['action'])){
			$action = trim($_REQUEST['action']);
		}else{
			$action = '';
		}
		switch($action){
			case "delete":
				$this->deleteEmailtemplate();
				break;
			case "add":
				$this->addEmailtemplate();
				break;		
			case "edit":
				$this->editEmailtemplate();
				break;
			case "view":
				$this->viewEmailtemplate();
				break;
			case "status":
				$this->statusEmailtemplate();
				break;
			case "datatabletemplate";
				$this->datatableEmailtemplate();
				break;
			default:
				$this->emailtemplate(); 
				break;			
		} 
	}
	
	private function viewEmailtemplate(){
		if(isset($_REQUEST['id'])){
			$template_id = $_REQUEST['id'];
		} else {
			$template_id =  '';
		}
		
		if($_POST['test_mail'] !="" && $_POST['testmailcontent'] !=""){
			
			$message_send = $_POST['testmailcontent'];
			$email_to = $_POST['test_mail']; $subject = $_POST['email_subject'];
			  _SendMail($email_to,'',$subject,$message_send);	
			  GetAdminRedirectUrl(GetAdminUrl(array('module'=>'emailtemplate','action'=>'view','id'=>$template_id)));
		}
		
		if(is_numeric($template_id)){
			
			$configRow = $GLOBALS['DB']->row("SELECT * FROM `config` WHERE site_id = ?  LIMIT 0,1",array($GLOBALS['SITE_ID']));
			 
			$ConfigInfo = unserialize($configRow['name']);
			$GLOBALS['ROOT_LINK'] = $ConfigInfo['SITE_URL'];
			$GLOBALS['SITE_BGCOLOR'] = $ConfigInfo['SITE_COLOR'];

			$editRow = $GLOBALS['DB']->row("SELECT * FROM `emailtemplate` WHERE template_id = ? LIMIT 0,1",array($template_id));	
			
		   // Get Header Footer
           $TemplateRow = $GLOBALS['DB']->row("SELECT * FROM `emailtemplate` WHERE site_id = ? LIMIT 0,1",array($GLOBALS['SITE_ID']));
		    if($TemplateRow['template_header'] !="" && $TemplateRow['template_footer'] !=""){
			   $GLOBALS['TemplateHeader'] =  $GLOBALS['CLA_HTML']->addContent($TemplateRow['template_header']); 
			   $GLOBALS['TemplateFooter'] =  $GLOBALS['CLA_HTML']->addContent($TemplateRow['template_footer']);
		    }else{
			   $TemplateRow = $GLOBALS['DB']->row("SELECT * FROM `emailtemplate` WHERE site_id = 0 LIMIT 0,1");
			   $GLOBALS['TemplateHeader'] =  $GLOBALS['CLA_HTML']->addContent($TemplateRow['template_header']); 
			   $GLOBALS['TemplateFooter'] =  $GLOBALS['CLA_HTML']->addContent($TemplateRow['template_footer']);
		    }
		    $GLOBALS['TemplateSubject'] = $editRow['template_subject'];
		    $GLOBALS['template_content'] = $GLOBALS['CLA_HTML']->addContent($editRow['template_content']);
			
			AddMessageInfo();
			
			$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/'.$GLOBALS['Module'].'/emailtemplate.view.html');	
			$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');			
			$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');						
			$GLOBALS['LEFT'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.left.html');	
			$GLOBALS['CLA_HTML']->display();
			RemoveMessageInfo();
		}else{
			$_SESSION['Error'] = '<div class="alert alert-danger" role="alert">Emailtemplate not valid.</div>';
		}
	}
	
	private function editEmailtemplate(){
		if(isset($_REQUEST['id'])){
			$template_id = $_REQUEST['id'];
		} else {
			$template_id =  '';
		}
		if(is_numeric($template_id)){
			$editRow = $GLOBALS['DB']->row("SELECT * FROM `emailtemplate` WHERE template_id = ? LIMIT 0,1",array($template_id));
			$GLOBALS['template_id'] = $editRow['template_id'];
			$GLOBALS['template_name'] = $editRow['template_name'];	
			$GLOBALS['template_url'] = $editRow['template_url'];	
			$GLOBALS['template_subject'] = $editRow['template_subject'];
			$GLOBALS['template_content'] = $editRow['template_content']; 
			$GLOBALS['template_note'] = $editRow['templatenote']; 
			if($_POST['template_subject'] != '' ){	
					//echo $_POST['template_content']; exit;
				$data =array('template_name'=>$_POST['template_name'],'template_url'=>$_POST['template_url'],'template_subject'=> $_POST['template_subject'],'template_content'=>$_POST['template_content'],'templatenote'=>$_POST['tempnote'],'site_id'=>$GLOBALS['SITE_ID']);
				$where = array('template_id'=>$template_id);
				$updateResult = $GLOBALS['DB']->update("emailtemplate",$data,$where); 
				if($updateResult){	 	
					$_SESSION['Success'] .= '<div class="alert alert-success" role="alert">Record updated successfully<br /></div>';
					GetAdminRedirectUrl($GLOBALS['CURRENT_URL']);
				}else{
											
					$_SESSION['Error'] .= sprintf('<div class="alert alert-danger" role="alert">An error occurred while you trying to update image, please try again.</div>',$i);
				}	
			} 
			
		// $editor = new FCKeditor('template_content');
		// $editor->BasePath   = $GLOBALS['EDITOR_LINK'];
		// $editor->Width      = "100%";//
		// $editor->Height     = "600";//
		// $editor->Value      = $GLOBALS['template_content'];
		// $GLOBALS['FCK_Content'] = $editor->CreateHtml();
			
		AddMessageInfo();
	  	$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/'.$GLOBALS['Module'].'/emailtemplate.edit.html');	
		$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');			
		$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');						
		$GLOBALS['LEFT'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.left.html');	
		$GLOBALS['CLA_HTML']->display();
		RemoveMessageInfo();
		} else {
			$_SESSION['Error'] = '<div class="alert alert-danger" role="alert">Emailtemplate not valid.</div>';
		}	
	}
	
	private function addEmailtemplate(){
		
		$GLOBALS['template_name'] = $_POST['template_name'];	
		$GLOBALS['template_url'] = $_POST['template_url'];	
		$GLOBALS['template_subject'] = $_POST['template_subject'];
		$GLOBALS['template_content'] = $_POST['template_content']; 
		
		if($_POST['submit']){
			
			if($_POST['template_name'] != '' && $_POST['template_url'] != '' && $_POST['template_subject'] != '' && $_POST['template_content'] != '' ){	
				  $data = array('site_id'=>$GLOBALS['SITE_ID'],'template_name'=>$_POST['template_name'],'template_url'=>$_POST['template_url'],'template_subject'=>$_POST['template_subject'],'template_content'=>$_POST['template_content'],'templatenote'=>$_POST['tempnote']);	
				  $addResult = $GLOBALS['DB']->insert("emailtemplate",$data);
					if($addResult){
						$_SESSION['Success'] = '<div class="alert alert-success" role="alert">Template Add successfully</div>';					
						GetAdminRedirectUrl(GetAdminUrl(array('module'=>'emailtemplate','action'=>'add')));
					} else {
						$_SESSION['Error'] = '<div class="alert alert-danger" role="alert">An error occurred while you trying to add template, please try again.</div>';	
					}	
			}else{
				$_SESSION['Error'] = '<div class="alert alert-danger" role="alert">Please fill all required Field</div>';	
			}
		}
		
		
		
		AddMessageInfo();
	  	$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/'.$GLOBALS['Module'].'/emailtemplate.add.html');	
		$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');			
		$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');						
		$GLOBALS['LEFT'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.left.html');	
		$GLOBALS['CLA_HTML']->display();
		RemoveMessageInfo();
	}

	private function deleteEmailtemplate(){
		if(isset($_REQUEST['id'])){
			$template_id = $_REQUEST['id'];
		} else {
			$template_id =  '';
		}
		if(is_numeric($template_id)){
			$addResult = $GLOBALS['DB']->query("DELETE FROM `emailtemplate` WHERE `template_id` = ?",array($template_id));
			if($addResult== true){
				$_SESSION['Success'] = '<div class="alert alert-success" role="alert">Template deleted successfully</div>';
			} else {
				$_SESSION['Error'] = '<div class="alert alert-danger" role="alert">An error occurred while you trying to delete emailtemplate, please try again.</div>';
			}
			GetAdminRedirectUrl();
		} else {
			$_SESSION['Error'] = '<div class="alert alert-danger" role="alert">Record not valid.</div>';
			GetAdminRedirectUrl(GetAdminUrl(array('module'=>$_REQUEST['module']),0));	
		}			
	}		
	
	private function datatableEmailtemplate(){
		$draw = $_POST['draw'];
		$row = $_POST['start'];
		$rowperpage = $_POST['length'];
		$columnIndex = $_POST['order'][0]['column'];
		$columnName = $_POST['columns'][$columnIndex]['data'];
		$columnSortOrder = $_POST['order'][0]['dir'];
		$searchValue = $_POST['search']['value'];
		$searchQuery = " ";
		if($searchValue != ''){
			$searchQuery = " and (template_name like '%".$searchValue."%' OR template_url like '%".$searchValue."%' OR template_subject like '%".$searchValue."%')";
		}
		$records = $GLOBALS['DB']->row("select count(*) as allcount FROM `emailtemplate` WHERE `site_id` =".$GLOBALS['SITE_ID'].$searchQuery);
		$totalRecordwithFilter = $records['allcount'];
		$templateRecords = $GLOBALS['DB']->query("select * FROM `emailtemplate` WHERE `site_id` = ".$GLOBALS['SITE_ID']." ". $searchQuery." order by ".$columnName." ".$columnSortOrder." limit ".$row.",".$rowperpage);
		$data = array();
		$count=1;
		foreach($templateRecords as $row){
			
			$GLOBALS['li_edit'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'edit','id'=>$row['template_id']));
			$GLOBALS['li_view'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'view','id'=>$row['template_id']));		
			$GLOBALS['li_delete'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'delete','id'=>$row['template_id']));	
			$GLOBALS['on_delete'] = 'return jsConfirm("","delete")';
			$data[] = array(
				"template_id"=>$count,
				"template_name"=>'<a href="'.$GLOBALS['li_view'].'" class="view-link" >'.$row['template_name'].'</a>',
				"template_url"=>'<a href="'.$GLOBALS['li_view'].'" class="view-link" >'.$row['template_url'].'</a>',
				"template_subject"=>'<a href="'.$GLOBALS['li_view'].'" class="view-link" >'.$row['template_subject'].'</a>',
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

	private function emailtemplate(){
		if(isset($_POST['template_header']) && isset($_POST['template_footer'])){
			$result = $this->SaveTemplateDetail();
			if($result){
				$_SESSION['Success'] = '<div class="alert alert-success" role="alert">Template Header and Footer Save  successfully</div>';
			} else {
				$_SESSION['Error'] = '<div class="alert alert-danger" role="alert">somthing wrong not save header and footer, please try again.</div>';
			}
		}
		if(isset($_POST['template_adminheader']) && isset($_POST['template_adminfooter'])){
			$result = $this->SaveAdminTemplateDetail();
			if($result){
				$_SESSION['Success'] = '<div class="alert alert-success" role="alert">Admin Template Header and Footer Save  successfully</div>';
			} else {
				$_SESSION['Error'] = '<div class="alert alert-danger" role="alert">somthing wrong not save Admin header and footer, please try again.</div>';
			}
		}	
		$GLOBALS['li_gettemplatedata'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'datatabletemplate'));
		$this->GetTemplateDetali();
		AddMessageInfo();
		$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/'.$GLOBALS['Module'].'/emailtemplate.html');	
		$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');			
		$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');						
		$GLOBALS['LEFT'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.left.html');					
		$GLOBALS['CLA_HTML']->display();
		RemoveMessageInfo();		
	}

	private function SaveTemplateDetail(){
		$TemplateRow = $GLOBALS['DB']->row("SELECT * FROM `emailtemplate_detail` WHERE site_id = ? LIMIT 0,1",array($GLOBALS['SITE_ID']));
		if($TemplateRow['detail_id']){
			$data =array('site_id'=>$GLOBALS['SITE_ID'],'template_header'=>$_POST['template_header'],'template_footer'=>$_POST['template_footer']);
			$where = array('detail_id'=>$TemplateRow['detail_id']);
			$addResult = $GLOBALS['DB']->update("emailtemplate_detail",$data,$where);
		}else{
			$data =array('site_id'=>$GLOBALS['SITE_ID'],'template_header'=>$_POST['template_header'],'template_footer'=>$_POST['template_footer']);
			$addResult = $GLOBALS['DB']->insert("emailtemplate_detail",$data);
		}
		return $addResult;
	}
	
	private function SaveAdminTemplateDetail(){
		$TemplateRow = $GLOBALS['DB']->row("SELECT * FROM `emailtemplate_detail` WHERE site_id = ? LIMIT 0,1",array($GLOBALS['SITE_ID']));
		if(isset($TemplateRow['site_id'])){
			$data = array('template_adminheader'=>$_POST['template_adminheader'],'template_adminfooter'=>$_POST['template_adminfooter'],'site_id'=>$GLOBALS['SITE_ID']);
			$where = array('detail_id'=>$TemplateRow['detail_id']);
			$addResult = $GLOBALS['DB']->update("emailtemplate_detail",$data,$where);
		}else{
			$data = array('template_adminheader'=>$_POST['template_adminheader'],'template_adminfooter'=>$_POST['template_adminfooter'],'site_id'=>$GLOBALS['SITE_ID']);
			$addResult = $GLOBALS['DB']->insert("emailtemplate_detail",$data);
		}
		return $addResult;
	}
	
	private function GetTemplateDetali(){
	   $TemplateRow= $GLOBALS['DB']->row("SELECT * FROM `emailtemplate_detail` WHERE `site_id` = ? LIMIT 0,1",array($GLOBALS['SITE_ID']));
	   $GLOBALS['TemplateHeader'] =  $TemplateRow['template_header']; 
	   $GLOBALS['TemplateFooter'] =  $TemplateRow['template_footer'];
	   $GLOBALS['TemplateAdminHeader'] =  $TemplateRow['template_adminheader']; 
	   $GLOBALS['TemplateAdminFooter'] =  $TemplateRow['template_adminfooter'];
	}
	
}