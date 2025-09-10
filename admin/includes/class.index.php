<?php
class CIT_INDEX
{
	private $action = '';
	public function __construct(){
		
	}
	public function displayPage(){	
	
		if(isset($_REQUEST['action'])){ 
			$action = trim($_REQUEST['action']);
			switch($action){
				case "fp":
					$this->forgotpassword();
					break;
			}
		}
		
		$GLOBALS['MESSAGE'] = '';		
		$this->__link();
		
		if(isset($_SESSION[GetSession('SessionTimout')]) && isset($_REQUEST['module'])){
			$GLOBALS['SessionTime'] = '1';
			$GLOBALS['RefreshStatus'] = 1;
			if($_SESSION[GetSession("SessionTimout")] > time()){
				$GLOBALS['RefreshTime'] =  $_SESSION[GetSession("SessionTimout")] - time();
			} else {
				$GLOBALS['RefreshTime'] =  0;
			}		
		}else{
			$GLOBALS['SessionTime'] = '0';
			$GLOBALS['RefreshStatus'] = 0;
		}
			if(isset($_REQUEST['module'])){		
			$this->validateUser();
			$this->handleModule();			
			exit;
		}
						
		if(isset($_POST['cit_login_submit'])){				
			$this->handleAdmin();
			exit;
		}		
		$this->loginPage();
		exit;
	}
	
	private function forgotpassword(){
		if(isset($_REQUEST['id']))
		{
			$id = $_REQUEST['id'];
		} else {
			$id =  '';
		}	
		
		if(isset($_POST['emailsender_from'])){
			$row = $GLOBALS['DB']->row("SELECT * FROM `admin` WHERE `email` = ? ORDER BY `id` ASC LIMIT 0,1",array($_POST['emailsender_from']));
			
			if($row){ 
				$emailresult = $GLOBALS['CLA_CIPL_emailsender']->fnEmailsender(4);
				$email = $GLOBALS['CLA_DB']->Fetch($emailresult);
			  	
			    $subjectcontactus = 'Forgot Password';
				$message="";
				$message =  $message. "Username		    :  ".$row['username']."   <br />";
				$message =  $message.  "Email 			: ".$row['email']."      <br />";
				$message =  $message.  "Password 		: ".$row['password']." <br />";	
				
				if(GetConfig('mailer')=='smtp'){ 
					if($this->smtpemailsender($row['email'],$email['emailsender_from'] ,$subjectcontactus,$message)){				
						GetFrontRedirectUrl(GetUrl(array('module'=>'thanks')));
						//$GLOBALS['success']="successfully email send";
						//GetFrontRedirectUrl(GetUrl(array('module'=>'thanks'),0));	
					}else { 
						$GLOBALS['errormsg']='Send mail function does not work';	
					}
				}
				if(GetConfig('mailer')=='mail'){ 		
					if($this->__emailsender($email['emailsender_to'],$GLOBALS['email'] ,$subjectcontactus,$message)){				
						GetFrontRedirectUrl(GetUrl(array('module'=>'thanks')));
						//$GLOBALS['success']="successfully email send";
						//GetFrontRedirectUrl(GetUrl(array('module'=>'thanks'),0));	
					}else{
						$GLOBALS['errormsg']='Send mail function does not work';	
					}
				}
			}else{ 
				$GLOBALS['Message']='Email Does not matched';
				$this->loadPage(sprintf('forgotpassword.html'));	
				
			}
		} 
		$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/forgotpassword.html');				
		$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');			
		$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');						
		$GLOBALS['LEFT'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.left.html');			
		$GLOBALS['CLA_HTML']->display();
		exit;
		
	}

	private function smtpemailsender($to, $from, $subject, $message = ''){  
	 
		$to = $to;
		$form = $from;
		
		$mail = new PHPMailer();
		$mail->SetFrom($form);
		$mail->Subject = $subject;
		$msgHtml = $message;
		$mail->MsgHTML($msgHtml);
		$mail->AddReplyTo($form);
		try{
			$mail->AddAddress($to);
			$mail->AddAddress($from);  
			if($mail->Send())
			{ $GLOBALS['Message'] =  'mail sent ';
				$GLOBALS['MessageClass'] = 'success';
				$this->loadPage(sprintf("newsletter.add.html"));		
			} else {
				$GLOBALS['Message'] =  'mail not sent ';
				$GLOBALS['MessageClass'] = 'error';
				$this->loadPage(sprintf("newsletter.add.html"));		
			}
			$mail->ClearAddresses();
		} catch(phpmailerException $e) {
			echo $e->errorMessage();
		} catch(Exception $e) {
			echo $e->getMessage();
		}
	}
	
	//$palyer .= $data[0]['fname'].''.$data[0]['fname'].',';
	
	private function __emailsender($to, $from, $subject, $message = ''){
		$headers  = "From: ".strip_tags($from)."\r\n";
		$headers .= "Reply-To: ".strip_tags($from)."\r\n";
		$headers .= "MIME-Version: 1.0 \r\n";
		$headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";						
		return mail($to, $subject, $message, $headers);
	}

	private function handleAdmin(){				
		$adminCaptcha = substr($_SESSION[GetSession('cit_key')],0,5);
		$row = $GLOBALS['DB']->row("SELECT * FROM `admin` WHERE BINARY `email` = ? AND BINARY `password` = ? AND `status`= 1 ORDER BY `id` ASC LIMIT 0,1",array($_POST['email'],$_POST['password']));	
		
		if($row['id'] == ''){
			$GLOBALS['Message'] = 'The e-mail address or password is incorrect. Please try again.';										
			$GLOBALS['MessageClass'] = 'error';
			$this->loadPage(sprintf("login.html"));					
		} else {		
			$_SESSION[GetSession('AdminEmail')] = $row['email'];	
			$_SESSION[GetSession('AdminId')] = $row['id'];	
			$_SESSION[GetSession('AdminUser')] = $row['username'];	
			$_SESSION[GetSession('AdminType')] = $row['usertype'];	
			$GLOBALS['DB']->update("admin",array('last_login'=>time(),'ip'=>$_SERVER['REMOTE_ADDR']),array('id'=>$row['id']));																	
			$GLOBALS['CLA_SESSION']->adminSession();
			header(sprintf("Location: %s",$GLOBALS['li_dashboard']));										
			JavascriptHeader(sprintf("%s",$GLOBALS['li_dashboard']));	
		}			
		
	}

	private function handleModule(){
		if(isset($_SESSION[GetSession('SessionTimout')]) && isset($_SESSION[GetSession('AdminEmail')])&& $_SESSION[GetSession('AdminType')] == 1){	 	
			$this->action = strtolower($_REQUEST['module']);
			
			if($_REQUEST['module'] == 'reports' && $_REQUEST['action']!= 'view' ){
				$seolink = $_REQUEST['module'].'/'.$_REQUEST['action']; 
			}else if($_REQUEST['module'] == 'reports' && $_REQUEST['action'] == 'view' ){ 
				$seolink = $_REQUEST['module'].'/userreport'; 
			}else{
				$seolink = $_REQUEST['module'];
			} 
			
			$adminValidateRow = $GLOBALS['DB']->row("SELECT * FROM `admin_modules` INNER JOIN `adminuser_permisssion` ON (admin_modules.module_parentid = adminuser_permisssion.module_id || admin_modules.module_parentid = 0 ) WHERE admin_modules.module_permission = 0 AND admin_modules.module_seo = '".$seolink."' AND adminuser_permisssion.user_id = ".$_SESSION[GetSession('AdminId')]);
				
			//$adminValidateRow = $GLOBALS['CLA_DB']->Fetch($adminValidateQuery);
			
			if(is_array($adminValidateRow) ||$_REQUEST['module'] == 'logout' ||$_REQUEST['module'] == 'config' ||$_REQUEST['module'] == 'adminmodules' ||$_REQUEST['module'] == 'errorlog'){
				
				if(is_dir($GLOBALS['CLASSES'].'/'.$this->action)){
				if(is_file(sprintf("%s/%s/class.%s.php",$GLOBALS['CLASSES'],$this->action,$this->action))){					
					require_once(sprintf("%s/%s/class.%s.php",$GLOBALS['CLASSES'],$this->action,$this->action));			
					$GLOBALS['Module'] = $this->action;
					$GLOBALS['ModuleLink'] = GetAdminUrl(array('module'=>$_REQUEST['module']));
					$GLOBALS['CLA_PAGE'] = GetClass(sprintf('CIT_%s',strtoupper($this->action)));					
					$GLOBALS['CLA_PAGE']->displayPage();		
				} else {
					die('file not found!! ');
				}
			} else {
					die('directory not found!! ');
				}
			
			}else{
				
			
				die('Acess denied for this user!! ');
			
			}
			
		}elseif(isset($_SESSION[GetSession('SessionTimout')]) && isset($_SESSION[GetSession('AdminEmail')])&& $_SESSION[GetSession('AdminType')] == 2){	 
			$this->action = strtolower($_REQUEST['module']);
			
			if($_REQUEST['module'] == 'reports' && $_REQUEST['action']!= 'view' ){
				$seolink = $_REQUEST['module'].'/'.$_REQUEST['action']; 
			}else if($_REQUEST['module'] == 'reports' && $_REQUEST['action'] == 'view' ){ 
				$seolink = $_REQUEST['module'].'/userreport'; 
			}else{
				$seolink = $_REQUEST['module'];
			} 
			
			
			$adminValidateRow = $$GLOBALS['DB']->row("SELECT * FROM `admin_modules` INNER JOIN `adminuser_permisssion` ON (admin_modules.module_parentid = adminuser_permisssion.module_id || admin_modules.module_parentid = 0 ) WHERE admin_modules.module_permission = 0 AND admin_modules.module_seo = '".$seolink."' AND adminuser_permisssion.user_id = ".$_SESSION[GetSession('AdminId')]);	
		
			//$adminValidateRow = $GLOBALS['CLA_DB']->Fetch($adminValidateQuery);
			
			if(is_array($adminValidateRow) || $_REQUEST['module'] == 'logout' ){ 
				if(is_dir($GLOBALS['CLASSES'].'/'.$this->action)){
				if(is_file(sprintf("%s/%s/class.%s.php",$GLOBALS['CLASSES'],$this->action,$this->action))){					
					require_once(sprintf("%s/%s/class.%s.php",$GLOBALS['CLASSES'],$this->action,$this->action));			
					$GLOBALS['Module'] = $this->action;
					$GLOBALS['ModuleLink'] = GetAdminUrl(array('module'=>$_REQUEST['module']));
					$GLOBALS['CLA_PAGE'] = GetClass(sprintf('CIT_%s',strtoupper($this->action)));					
					$GLOBALS['CLA_PAGE']->displayPage();		
				} else {
					die('file not found!! ');
				}
			} else {
					die('directory not found!! ');
			}
			
			}else{
				
				die('Acess denied for this user!! ');
			}
						
		} else {			
			header(sprintf("location: %s/admin/",GetConfig('SITE_URL')));
			JavascriptHeader(sprintf("%s/admin/",GetConfig('SITE_URL')));
			exit;
		}
	}

	private function validateUser(){
		$adminValidateRow = $GLOBALS['DB']->row("SELECT * FROM `admin` WHERE `id` = ? ORDER BY `id` ASC LIMIT 0,1",array($_SESSION[GetSession('AdminId') ]));
		if(is_array($adminValidateRow)){
			if($adminValidateRow['email'] == $_SESSION[GetSession('AdminEmail')]){
				$GLOBALS['user']     = $_SESSION[GetSession('AdminUser')];
				$GLOBALS['usertype'] = $_SESSION[GetSession('AdminType')];
				return true;
			}else{				
				$GLOBALS['CLA_SESSION']->logoutAdmin();
				return false;
			}
		}else{
			//$GLOBALS['CLA_DB']->FreeResult($adminValidateQuery);
			$GLOBALS['CLA_SESSION']->logoutAdmin();
			return false;
		}		
	}

	private function loginPage(){
		if(isset($_SESSION[GetSession('SessionExpire')])){
			$GLOBALS['Message'] = $_SESSION[GetSession('SessionExpire')];					
			$GLOBALS['MessageClass'] = 'error';
		}		
		$this->loadPage(sprintf('login.html'));		
	}	

	private function loadPage($pageName){
		if(!isset($GLOBALS['Message'])){
			$GLOBALS['MessageStatus'] = 0;
		}
		$GLOBALS['CLA_HTML']->addMain(sprintf($GLOBALS['WWW_TPL'].'/%s',$GLOBALS['DeviceName'].$pageName));		
		$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub(sprintf($GLOBALS['WWW_TPL'].'/%spage.header.html',$GLOBALS['DeviceName']));			
		$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub(sprintf($GLOBALS['WWW_TPL'].'/%spage.footer.html',$GLOBALS['DeviceName']));			
		$GLOBALS['CLA_HTML']->display();	
		if(isset($_SESSION[GetSession('SessionExpire')])){
			$arrSession = array(GetSession("SessionExpire"));
			$GLOBALS['CLA_SESSION']->unsetSession($arrSession);
		}
		exit;
	}	
		
	private function __link(){
		// manage add, insert, update ,delete in files
		if(isset($_REQUEST['page'])){
			if($GLOBALS['SeoUnabled']){			
				$GLOBALS['PageNo'] = preg_replace("/[^0-9]+/","",$_REQUEST['page']);
				if(isset($GLOBALS['PageNo']) && $GLOBALS['PageNo'] < 1){
					$GLOBALS['PageNo'] = 1;
				}
				$GLOBALS['PageStart'] = abs(($GLOBALS['PageNo']-1)*$GLOBALS['PerPage']);
				$GLOBALS['PageLink'] = '/page'.$GLOBALS['PageNo'];				
			} else {
				$GLOBALS['PageNo'] = $_REQUEST['page'];
				if(isset($GLOBALS['PageNo']) && $GLOBALS['PageNo'] < 1){
					$GLOBALS['PageNo'] = 1;
				}
				$GLOBALS['PageStart'] = abs(($GLOBALS['PageNo']-1)*$GLOBALS['PerPage']);
				$GLOBALS['PageLink'] = '&page='.$GLOBALS['PageNo'];
			}
		} else {
			$GLOBALS['PageLink'] = '';
		}
		if(isset($_REQUEST['module'])){
			$GLOBALS['li_back'] = GetAdminUrl(array('module'=>$_REQUEST['module']));			
			$GLOBALS['li_add']  = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'add')); 
		}		
		if(isset($_REQUEST['id'])){			
			$GLOBALS['li_edit'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'edit','id'=>$_REQUEST['id']));				
		} 
		
		// manage add, insert, update, delete in files
		$GLOBALS['leftMenu'] = $this->Pageleftmenu();
		$GLOBALS['li_fp'] = GetAdminUrl(array('module'=>'login','action'=>'fp'));
		$GLOBALS['li_config'] = GetAdminUrl(array('module'=>'config'),0);
		$GLOBALS['li_dashboard'] = GetAdminUrl(array('module'=>'dashboard'),0);
		$GLOBALS['li_logout'] = GetAdminUrl(array('module'=>'logout'),0);
		$GLOBALS['li_logout_expire'] = GetAdminUrl(array('module'=>'logout','action'=>'expire'),0);
		$GLOBALS['li_mobilesite'] = GetAdminUrl(array('module'=>'mobilesite'),0);
		$GLOBALS['li_importexport'] = GetAdminUrl(array('module'=>'importexport'),0);
		
		
		
	}
	
	private function Pageleftmenu($selCat = '', $selParentCat = ''){
		if($_SESSION[GetSession('AdminId')]!=''){
		$mainCatQuery = $GLOBALS['DB']->query("SELECT * FROM admin_modules AM INNER JOIN adminuser_permisssion AUP ON AUP.module_id = AM.module_id WHERE AM.module_parentid = 0 AND AM.status = 1 AND AM.module_permission=0 AND AUP.user_id =".$_SESSION[GetSession('AdminId')]." ORDER BY AM.module_order ASC"); 
		foreach($mainCatQuery as $mainRow){
		//while($mainRow = $GLOBALS['CLA_DB']->Fetch($mainCatQuery)){	
			$firstLevel  = 1;	
			if($mainRow['module_seo']=='#'){
				$ModuleLink = 'javascript:void(0)';
			}else{
				$ModuleLink = GetAdminUrl(array('module'=>$mainRow['module_seo']),0);	
			}
			$getrow = $GLOBALS['DB']->row("select count(*) as  parentidttl from admin_modules where module_parentid=".$mainRow['module_id']); 
			
			if($getrow['parentidttl']!=0){
				$this->Moduleleftmenu .= sprintf('<li class="nav-item pcoded-hasmenu">');	
			}else{
				$this->Moduleleftmenu .= sprintf('<li class="nav-item">');
			}
			$this->Moduleleftmenu .= sprintf('<a href="'.$ModuleLink.'" title="'.$mainRow['module_name'].'" class="nav-link "><span class="pcoded-micon"><i class="feather icon-'.$mainRow['module_icon'].'"></i></span><span class="pcoded-mtext">'.$mainRow['module_name'].'</span></a>');
			
			$this->subId = '';
			$this->Pageleftsubmenu($mainRow['module_id'],$firstLevel);
			$this->Moduleleftmenu .=sprintf('</li>');
			$seourl = 'li_'.$mainRow['module_seo'];
			$GLOBALS[$seourl] = $ModuleLink;
		}
		return $this->Moduleleftmenu;
		}
	}
	
	private function Pageleftsubmenu($mainCatId,$Level,$selCat = '',$selParentCat = ''){
	 	 $Level = $Level + 1;	
		 $parentList = trim($this->subId,'|');
		 if(end(explode('|',$parentList)) != $mainCatId){
		   $this->subId = $mainCatId;
	     }
		 
		$sql = "SELECT * FROM admin_modules AM INNER JOIN adminuser_permisssion AP ON AP.module_id = AM.module_id WHERE AP.user_id = ".$_SESSION[GetSession('AdminId')]." AND AM.module_parentid= ".$mainCatId." AND AM.module_permission= 0 AND AM.status = 1";
		$subCatQuery = $GLOBALS['DB']->query($sql);
		if(!empty($subCatQuery)){
			$this->Moduleleftmenu .=sprintf('<ul class="pcoded-submenu">');  
			foreach($subCatQuery as $subRow){								
					$this->subId .= '|'.$subRow['module_id'];
					$ModulesubLink = GetAdminUrl(array('module'=>$subRow['module_seo']),0);	
					if($selCat != $subRow['module_id'] && $selCat == $subRow['module_parentid']){
						if($selParentCat == $subRow['module_parentid']){ 
							$this->Moduleleftmenu .= sprintf('<li><a href="'.$ModulesubLink.'">'.$subRow['module_name'].'</a></li>');
						} else {
							$this->Moduleleftmenu .= sprintf('<li><a href="'.$ModulesubLink.'">'.$subRow['module_name'].'</a></li>');						
						}
					} else {				 
						$this->Moduleleftmenu .= sprintf('<li><a href="'.$ModulesubLink.'">'.$subRow['module_name'].'</a></li>');						
					}
					$this->Pageleftsubmenu($subRow['module_id'],$Level, $selCat, $selParentCat);
					if($subRow['add_permission']!=0){
						$seourl = 'adddisplay_'.$subRow['module_seo'];
						$GLOBALS[$seourl] = 'block';
					}else{
						$seourl = 'adddisplay_'.$subRow['module_seo'];
						$GLOBALS[$seourl] = 'none';
					}
					
					 $GLOBALS['ADD_'.$subRow['module_seo']]  = $subRow['add_permission'];
					 $GLOBALS['Edit_'.$subRow['module_seo']] = $subRow['edit_permission'];
					 $GLOBALS['View_'.$subRow['module_seo']] = $subRow['view_permission'];
					 $seourl = 'li_'.$subRow['module_seo'];
					 $GLOBALS[$seourl] = $ModulesubLink;
				} 
			$this->Moduleleftmenu .=sprintf('</ul>');
		}
		
	} 
}