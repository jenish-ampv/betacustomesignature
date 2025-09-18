<?php
class CIT_INDEX
{
	private $action = '';
	private $id = '';
	public function __construct(){
		
	} 

	public function displayPage(){
		if(isset($_REQUEST['fpr'])){ // set cookie for traking code
			setcookie('fpr',$_REQUEST['fpr'],time()+60*60*24*7,'/');
		}
		$GLOBALS['li_loginRedirect'] =  GetLoginRedirect();
		$GLOBALS['li_ajaxnewsletter'] = GetUrl(array('module'=>'home','action'=>'newsletter'));
		$GLOBALS['li_fpRedirect'] =  GetUrl(array('module'=>''));
		$GLOBALS['li_ajaxlogin'] = GetUrl(array('module'=>'home','action'=>'ajax'));
		$GLOBALS['li_ajaxregister'] = GetUrl(array('module'=>'get-started','action'=>'regajax'));
		$GLOBALS['li_registerRedirect'] =  GetUrl(array('module'=>'thanks','action'=>'getstarted','id'=>0));
		$GLOBALS['li_ajaxforgetpass'] = GetUrl(array('module'=>'home','action'=>'forgetpass'));	
		$GLOBALS['li_ajaxlogout'] = GetUrl(array('module'=>'home','action'=>'logout'));
		$GLOBALS['CurrentDate'] = date('F d Y');
		$GLOBALS['CurrentYear'] = date('Y');

		$GLOBALS['logoImage'] = $GLOBALS['MASTERADMINUPLOAD_LINK'].'/logo/'.$GLOBALS['SITE_ID'].'/'.GetConfig('logo');
		$GLOBALS['logoEmail'] = $GLOBALS['MASTERADMINUPLOAD_LINK'].'/logo/'.$GLOBALS['SITE_ID'].'/'.GetConfig('logoemail');
		if($_SESSION[GetSession('user_status')] == 1){
			$GLOBALS['USERID'] = $_SESSION[GetSession('user_id')];
			$GLOBALS['SUBUSERID'] = $_SESSION[GetSession('sub_user_id')];
			$GLOBALS['USEREMAIL'] = $_SESSION[GetSession('user_email')];
			$GLOBALS['USERNAME'] = $_SESSION[GetSession('user_name')];
			$GLOBALS['USERFIRSTNAME'] = $_SESSION[GetSession('user_firstname')];
			$GLOBALS['USERLASTNAME'] = $_SESSION[GetSession('user_lastname')];
			$GLOBALS['USERORGANIZATION'] = $_SESSION[GetSession('user_organization')];
			$GLOBALS['USERUPLOADLIMIT'] = $_SESSION[GetSession('user_uploadlimit')];
			$GLOBALS['USERTYPE'] = $_SESSION[GetSession('user_type')];
			$GLOBALS['ISSUBUSER'] = $_SESSION[GetSession('is_sub_user')];
			$GLOBALS['ISMAINENTERPRISEUSER'] = '1';
			
			$GLOBALS['USERLOGIN'] = 1;

			$GLOBALS['NLDN'] = "";
			$GLOBALS['LDN'] = "login_dn";
			$GLOBALS['PLAN_STATUS'] = $this->checkPlanStatus();
			$this->GetUserProfilePic();
			$checkuser_staus = $this->checkUserStatus();
			$this->getSignatureLogo();
			if($checkuser_staus == 0){  $this->logoutUser(); }

			// coding for add lottie player js based on logo process status
	
			$signature_logo = $GLOBALS['DB']->row("SELECT * FROM `signature_logo` WHERE user_id = ? ",array($GLOBALS['USERID']));
			if($signature_logo['logo_process'] == '2' && $signature_logo['logo_change_process'] == '2'){
				$GLOBALS['LOTTIE_JS_BASEON_SIGNATURELOGOPROCESSPENDING'] = ' ';
			}
			else{
				$GLOBALS['LOTTIE_JS_BASEON_SIGNATURELOGOPROCESSPENDING'] = '<script src="'.GetConfig('SITE_URL').'/script/lottie-player.js"></script>';
			}
			// coding for add lottie player js based on logo process status
			$GLOBALS['integration_settings'] = "";
			$GLOBALS['billing_settings'] = "";
			$GLOBALS['manage_signatures'] = "";
			if($GLOBALS['ISSUBUSER']){
				$GLOBALS['ISMAINENTERPRISEUSER'] = '0';
				if($_SESSION[GetSession('permission')] != ""){
					$permissionArr = explode(',', $_SESSION[GetSession('permission')]);
					foreach ($permissionArr as $key => $permission) {
						$GLOBALS[$permission] = $permission;
					}
				}
				if($_SESSION[GetSession('department_list')] != ""){
					$departmentArr = explode(',', $_SESSION[GetSession('department_list')]);
					foreach ($departmentArr as $key => $department) {
						$GLOBALS["department_".$department] = $department;
					}
				}
			}else{
				$GLOBALS['manage_signatures'] = "manage_signatures";
				$GLOBALS['integration_settings'] = "integration_settings";
				$GLOBALS['billing_settings'] = "billing_settings";
				$GLOBALS['billing_settings'] = "billing_settings";
			}
			
		}else{ 
			$GLOBALS['USERLOGIN'] = 0;
			$GLOBALS['PLAN_STATUS'] = 0;
			$GLOBALS['LDN'] ="";
			$GLOBALS['NLDN'] = "login_dn";
		}			
		if($_REQUEST['category_id'] == 'forgetpass'){
			ini_set('display_errors', 'On');
			if($_POST['fpwemail']){
				$site_id = $GLOBALS['SITE_ID'];
				$user_email = $_POST['fpwemail'];

				$rowFpw = $GLOBALS['DB']->row("SELECT * FROM `registerusers` WHERE user_email = ?",array($user_email));	
			
				
				if($rowFpw){
					$GLOBALS['FPWEmail']= $rowFpw['user_email'];
					$GLOBALS['UFName'] = $rowFpw['user_firstname'];
					$key = md5($rowFpw['user_password']);
					$id = md5($rowFpw['user_id']);
					$to = $rowFpw['user_email'];
					$message= _getEmailTemplate('forget_password');
					$send_mail = _SendMail($to,'',$GLOBALS['EMAIL_SUBJECT'],$message);
					if($send_mail){
						echo 1;
					}else{
						echo 3;
					}
				}else{
					echo 2;
				}
				exit;
			}
		}

		if($_REQUEST['category_id'] == 'ajax'){
			ini_set('display_errors', 'On');
				if($_POST["user_name"] != "" || $_POST["user_password"] != ""){ 
					$username = $_POST['user_name'];
					//$password = $_POST['user_password'];
					$password = md5($_POST['user_password']);
					$site_id = $GLOBALS['SITE_ID'];
					if($GLOBALS['SUBSITE_IDS']!=""){
						$rowLogin = $GLOBALS['DB']->row("SELECT * FROM `registerusers` WHERE BINARY LOWER(`user_email`)= LOWER(:username) AND `user_password`=:pass AND (`site_id` IN (:subsiteid) OR `site_id`=:siteid) AND `user_logintype`= 0 AND `user_status`= 1 ORDER BY site_id=:siteids",array('username'=>$username,'pass'=>$password,'subsiteid'=>$GLOBALS['SUBSITE_IDS'],'siteid'=>$GLOBALS['SITE_ID'],'siteids'=>$GLOBALS['SITE_ID']));
					} else {
						$rowLogin = $GLOBALS['DB']->row("SELECT * FROM `registerusers` WHERE BINARY LOWER(`user_email`)= LOWER(:username) AND `user_password`= :pass AND `site_id`= :siteid AND `user_logintype`= 0 AND  `user_status`= 1",array('username'=>$username,'pass'=>$password,'siteid'=>$GLOBALS['SITE_ID']));
					}
				if($rowLogin['user_email'] !="" && $rowLogin['user_logintype'] == 0 && $rowLogin['user_status'] == 1){
					if($rowLogin['user_status'] == 1 && $rowLogin['user_logintype'] == 0){
						$_SESSION[GetSession('UserId')] = $rowLogin['user_id'];
						$_SESSION[GetSession('UserEmail')] = $rowLogin['user_email'];
						$_SESSION[GetSession('UserName')] = $rowLogin['user_username'];
					
						//$GLOBALS['CLA_SESSION']->userSession();

						$data = array('user_lastlogin' => time(), 'ip' => $_SERVER['REMOTE_ADDR']);
						$where = array('user_id'=>$rowLogin['user_id']);
						$update_lastlogin = $GLOBALS['DB']->update('registerusers',$data,$where);

						if ($_POST['rememberme'] == 1) {
						/* Set cookie to last 1 year */
							//$cookie_url= parse_url($GLOBALS['ROOT_LINK'], PHP_URL_HOST);
							setcookie('username',$_POST['user_name'], time()+60*60*24*365,'/');
							setcookie('password',$_POST['user_password'], time()+60*60*24*365,'/');
						} else {
							setcookie('username', '', time() - 3600, '/');
							setcookie('password', '', time() - 3600, '/');
						}													
				
					print '1';
					}
				}else{
					print '2';
				}
				
			}else{
				print '3';
			}
			exit;
				
		}
		if($_REQUEST['category_id'] =='logout'){
			$this->logoutUser();
		}

		$this->pageLink();
		$this->main();		
		exit;
		
	}
	/***
	* Main function of site
	* This function check in cofiguration file mod rewrite is on or not.
	* and then call module sent request as per user. 
	* default it will be display home page of site.
	***/
	private function main(){
		$GLOBALS['MetaTitle'] = $GLOBALS['SITE_TITLE'];	
		if (isset($_COOKIE['username']) && isset($_COOKIE['password'])){	
			$GLOBALS['User_Name'] = $_COOKIE['username'];
			$GLOBALS['User_Password'] = $_COOKIE['password'];
			$GLOBALS['RememberMe'] = 'checked="checked"';
		}
		
		if(isset($_REQUEST['module']) && isset($GLOBALS['SeoKeyword'][$_REQUEST['module']]['class'])){
			$GLOBALS['CLASSES'].'/'.sprintf("%s.php",$GLOBALS['SeoKeyword'][$_REQUEST['module']]['class']);	
			if(is_file($GLOBALS['CLASSES'].'/'.sprintf("%s.php",$GLOBALS['SeoKeyword'][$_REQUEST['module']]['class']))){	
				$this->action = $_REQUEST['module'];
				$this->loadMenu();
				require_once(sprintf("%s/%s.php",$GLOBALS['CLASSES'],$GLOBALS['SeoKeyword'][$_REQUEST['module']]['class']));			
				$GLOBALS['CLA_PAGE'] = GetClass(sprintf('CIT_%s',strtoupper($GLOBALS['SeoKeyword'][$_REQUEST['module']]['class'])));
				$GLOBALS['CLA_PAGE']->displayPage();
				exit;
			} else {	
				$this->home();			
			}
		}else if(isset($_REQUEST['module']) && is_string($_REQUEST['module'])){					
			if($_REQUEST['module'] != '' && $_REQUEST['module'] != 'home'){
				$this->action = $_REQUEST['module'];
				$this->loadMenu();					
				$row = $GLOBALS['DB']->row("SELECT * FROM `pages` WHERE `seourl`= ?  LIMIT 0,1",array($_REQUEST['module']));

				// meta information
				if($row['metatitle'] != '')
					$GLOBALS['MetaTitle'] = $row['metatitle'];
				if($row['metakeywords'] != '')	
					$GLOBALS['Metakeywords'] = $row['metakeywords'];
				if($row['metadescription'] != '')	
					$GLOBALS['Metadescription'] = $row['metadescription'];
				// meta information
				if($row['id']){
					$this->cmspage($_REQUEST['module']);	
				}else{
					header("location:".$GLOBALS['ROOT_LINK']);
					JavascriptHeader($GLOBALS['ROOT_LINK']);
					exit;
				}
						
				if(is_array($row)){		
								
					$GLOBALS['PageName'] = $row['name']; 
					$prow = $GLOBALS['DB']->row("SELECT * FROM `pages` WHERE `id` = ?  ORDER BY `id`  ASC LIMIT 0,1",array($row['parentid']));	
				
					$GLOBALS['Parentid'] = $prow['name'];
					$GLOBALS['Link'] = GetUrl(array('module'=>$prow['seourl']));
					
					$GLOBALS['PageId'] = $row['id'];
					
					$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/cmspage.html');	
					$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');	
					$GLOBALS['SIDEBAR'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.sidebar.html');		
					$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');	
					$GLOBALS['PageDesc'] = $GLOBALS['CLA_HTML']->addContent($row['desc']);	
					$GLOBALS['CLA_HTML']->display();			
				} 
			} else {
				$this->home();					
			}
		} else {
			$this->home();						
		}
			
	}

	
	
	/**
	* home page
	*
	**/
	private function home(){
		if(empty($GLOBALS['USERID']) && !isset($_REQUEST['uuid']) && !isset($_REQUEST['customer_id'])){ // Redirect User
			 GetFrontRedirectUrl(GetUrl(array('module'=>'signin'))); 
		}else{
			 GetFrontRedirectUrl(GetUrl(array('module'=>'dashboard'))); 
		}
		
		
		$this->loadMenu();		
		$GLOBALS['header_class'] =1;
		$GLOBALS['LoginUserMenu']=0;
		
		$row = $GLOBALS['DB']->row("SELECT * FROM `pages` WHERE `seourl`= ? LIMIT 0,1",array('home'));	
		if($row['metatitle'] != ''){
			$GLOBALS['MetaTitle'] = $row['metatitle'];
		}
		if($row['name'] == 'Home'){
			  $GLOBALS['Capebility'] = 'inactive'; 	
		}

		$GLOBALS['PAGEHOME']=1;
		$GLOBALS['PageId'] = $row['id'];	
		$GLOBALS['PageName'] = $row['name'];

	   	$GLOBALS['CLA_HTML']->addMain(sprintf($GLOBALS['WWW_TPL'].'/%s','home.html'));
		$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');			
		$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');	
		//$GLOBALS['LEFT'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.left.html');						
		$GLOBALS['PageDesc'] = $GLOBALS['CLA_HTML']->addContent($row['desc']);
		$GLOBALS['CLA_HTML']->display();			
		exit;
	}
		
	/**
	* in this function define all global varibles for active menu
	*
	* for example if i have gallery module at that time gallery link is active so we need
	* to define gallery is active.	
	**/
	private function loadMenu(){
		if($this->action == ''){
			$GLOBALS['PageHome'] = 1;					
		} else {	
			if($this->action == 'cmspage'){
				$this->cmspage();				
			}else if($this->action == 'home' ||  $GLOBALS['SeoKeyword'][$_REQUEST['module']]['class'] == 'home'){
				$GLOBALS['PageHome'] = 1;
			}
		}
	}
	/**
	* in this function define all global varibles for active menu in pages
	*
	* for example if i have about us cmspage at that time about us menu link is active so we need
	* to define about us  is active.	
	**/
	private function cmspage($reqid = ''){
		
		if(isset($_REQUEST['id']) || $reqid != ''){
			if(!isset($_REQUEST['id'])){
				$this->id = $reqid;
			} else {
				$this->id = $_REQUEST['id'];				
			}
			if(is_numeric($this->id)){			
				switch($this->id){					
					case 2:						
						$GLOBALS['PageAboutus'] =1 ;
						break;						
					case 3:
					case 4:
					case 5:
					case 6:
					case 7:
					case 14:
						$GLOBALS['PageServices'] = 1;		
						break;						
					case 7:
						$GLOBALS['PageEvents'] = 1;		
						break;
					case 8:
						$GLOBALS['PageContact'] = 1;		
						break;
					case 10:
						$GLOBALS['PageTestimonial'] = 1;		
						break;
				}
			} else {
				$this->pageName = 'home.html';
			}
		}	
	}	
	/**
	* define all links for seo url and simple url
	* and add seokeyword in $GLOBALS['SeoKeyword'] array
	**/
	private function pageLink(){		
		$this->getModuleCms();
		if($GLOBALS['SeoUnabled']){							
		// reserved seo variable
		// $GLOBALS['SeoKeyword']['signin'] = array('class'=>'login');
		 $GLOBALS['SeoKeyword']['search'] = array('class'=>'search');		
		 $GLOBALS['SeoKeyword']['forgotpassword'] = array('class'=>'forgotpassword');
		 $GLOBALS['SeoKeyword']['billing'] = array('class'=>'billing');	
		 $GLOBALS['SeoKeyword']['import'] = array('class'=>'bulkupload');
		 $GLOBALS['SeoKeyword']['registerdev'] = array('class'=>'registerdev');		
		 $GLOBALS['SeoKeyword']['pricingdev'] = array('class'=>'pricingdev');	
		 $GLOBALS['SeoKeyword']['thanks'] = array('class'=>'thanks');
		 $GLOBALS['SeoKeyword']['renewaccount'] = array('class'=>'renewaccount');
		 $GLOBALS['SeoKeyword']['integrations'] = array('class'=>'integrations');
		 $GLOBALS['SeoKeyword']['azuread'] = array('class'=>'azuread');
		 $GLOBALS['SeoKeyword']['gsuite'] = array('class'=>'gsuite');					
		 $GLOBALS['SeoKeyword']['receivesignature'] = array('class'=>'receivesignature');
		 $GLOBALS['SeoKeyword']['deploydata'] = array('class'=>'deploydata');
		 $GLOBALS['SeoKeyword']['r'] = array('class'=>'r');
		 $GLOBALS['SeoKeyword']['processPendingImportSignature'] = array('class'=>'processPendingImportSignature');
		 $GLOBALS['SeoKeyword']['analyticsurlscript'] = array('class'=>'analyticsurlscript');
		 $GLOBALS['SeoKeyword']['processpayment'] = array('class'=>'processpayment');
		 $GLOBALS['SeoKeyword']['analytics'] = array('class'=>'analytics');
		 $GLOBALS['SeoKeyword']['departmententerprise'] = array('class'=>'departmententerprise');
		 $GLOBALS['SeoKeyword']['newsignatureenterprise'] = array('class'=>'newsignatureenterprise');
		 $GLOBALS['SeoKeyword']['departmentpayment'] = array('class'=>'departmentpayment');
		 $GLOBALS['SeoKeyword']['usermanagement'] = array('class'=>'usermanagement');
		 $GLOBALS['SeoKeyword']['uploadbrandlogo'] = array('class'=>'uploadbrandlogo');
		 $GLOBALS['SeoKeyword']['bannercampaign'] = array('class'=>'bannercampaign');
		 $GLOBALS['SeoKeyword']['cron'] = array('class'=>'cron');
		 $GLOBALS['SeoKeyword']['purchase'] = array('class'=>'purchase');

 
		 $GLOBALS['search'] = GetUrl(array('module'=>'search'));
		 $GLOBALS['thanks'] = GetUrl(array('module'=>'thanks'));
		 $GLOBALS['forgotpassword'] = GetUrl(array('class'=>'forgotpassword'));	
		 $GLOBALS['billing'] = GetUrl(array('class'=>'billing'));	
		 $GLOBALS['bulkupload'] = GetUrl(array('class'=>'import'));
		 $GLOBALS['deployoutlook'] = GetUrl(array('class'=>'deploydata'));
		 $GLOBALS['deploygmail'] = GetUrl(array('class'=>'deploydata'));
		 $GLOBALS['registerdev'] = GetUrl(array('class'=>'registerdev'));
		 $GLOBALS['pricingdev'] = GetUrl(array('class'=>'pricingdev'));	
		 $GLOBALS['thanks'] = GetUrl(array('class'=>'thanks'));	
		 $GLOBALS['renewaccount'] = GetUrl(array('class'=>'renewaccount'));
		 $GLOBALS['integrations'] = GetUrl(array('class'=>'integrations'));
		 $GLOBALS['azuread'] = GetUrl(array('class'=>'azuread'));
		 $GLOBALS['gsuite'] = GetUrl(array('class'=>'gsuite'));
		 $GLOBALS['processPendingImportSignature'] = GetUrl(array('class'=>'processPendingImportSignature'));
		 $GLOBALS['analytics'] = GetUrl(array('class'=>'analytics'));
		 $GLOBALS['departmententerprise'] = GetUrl(array('class'=>'departmententerprise'));
		 $GLOBALS['newsignatureenterprise'] = GetUrl(array('class'=>'newsignatureenterprise'));
		 $GLOBALS['departmentpayment'] = GetUrl(array('class'=>'departmentpayment'));
		 $GLOBALS['usermanagement'] = GetUrl(array('class'=>'usermanagement'));
		 $GLOBALS['uploadbrandlogo'] = GetUrl(array('class'=>'uploadbrandlogo'));
		 $GLOBALS['bannercampaign'] = GetUrl(array('class'=>'bannercampaign'));
		 $GLOBALS['cron'] = GetUrl(array('class'=>'cron'));
		 $GLOBALS['purchase'] = GetUrl(array('class'=>'purchase'));

		
			// reserved seo variable				
		}	
	}
	/**
	* Get cms title from database
	* @param int $cmsid
	* @return strings title of the id if id is available in cmspage
	**/
	private function getModuleCms(){	
		$rowPage = $GLOBALS['DB']->query("SELECT * FROM `pages` ORDER BY `id` DESC LIMIT ?,?",array(0,999));

		foreach($rowPage as $rowCms){						
			if($rowCms['type'] == '0'){							
				$varModuleName  = ucfirst($rowCms['modulename']);	
				$valModuleName = $rowCms['modulename'];
				$GLOBALS['module'.$varModuleName] = $rowCms['seourl'];					
				$GLOBALS['moduleName'.$varModuleName] = $rowCms['name'];
				if($GLOBALS['SeoUnabled'])
					$GLOBALS['moduleLink'.$varModuleName] = GetUrl(array('module'=>$rowCms['seourl'])); 
				else 
				   $GLOBALS['moduleLink'.$varModuleName] = GetUrl(array('module'=>$rowCms['modulename']));						
				$GLOBALS['SeoKeyword'][$GLOBALS['module'.$varModuleName]] = array('class'=>$valModuleName,'id'=>$rowCms['id']);		
			} else {
				if($rowCms['type'] == '2'){			
					$GLOBALS['cmsName'.$rowCms['id']] = $rowCms['name'];					
					$GLOBALS['cmsLink'.$rowCms['id']] = "#";												
				} else{
					$GLOBALS['cmsName'.$rowCms['id']] = $rowCms['name']; 		
					if($GLOBALS['SeoUnabled'])
						$GLOBALS['cmsLink'.$rowCms['id']] = GetUrl(array('seourl'=>$rowCms['seourl']));		
					else 
						$GLOBALS['cmsLink'.$rowCms['id']] = GetUrl(array('module'=>'cmspage','id'=>$rowCms['id']));		
				}
			}	
			$GLOBALS['PageName'][$rowCms['seourl']] = array('id'=>$rowCms['id']);
			
			if($GLOBALS['SeoUnabled']){	
				if($rowCms['type']){					
					$GLOBALS['linkCms'.$rowCms['id']] = GetUrl(array('module'=>$rowCms['seourl']));								
				} else {					
					$varModuleName  = ucfirst($rowCms['modulename']);	
					$valModuleName = $rowCms['modulename'];
					$GLOBALS['getModule'.$varModuleName] = $rowCms['seourl'];					
					$GLOBALS['linkModule'.$varModuleName] = GetUrl(array('module'=>$rowCms['seourl']));
					$GLOBALS['SeoKeyword'][$GLOBALS['getModule'.$varModuleName]] = array('class'=>$valModuleName);							
				}
			}else{		
				if($rowCms['type']){
					$GLOBALS['linkCms'.$rowCms['id']] = GetUrl(array('module'=>'cmspage','id'=>$rowCms['id']));		
				} else {
					$varModuleName  = ucfirst($rowCms['modulename']);	
					$valModuleName = $rowCms['modulename'];
					$GLOBALS['getModule'.$varModuleName] = $rowCms['seourl'];					
					$GLOBALS['linkModule'.$varModuleName] = GetUrl(array('module'=>$rowCms['modulename']));								
				}
			}	
		}		
		$this->getStaticModuleCms();
	}
	
	private function getStaticModuleCms(){
		$master_adminid = 0;
		$rowPage = $GLOBALS['DB']->query("SELECT * FROM `pages` ORDER BY `id` DESC LIMIT ?,?",array(0,999));	
		foreach($rowPage as $rowCms ){						
			if($rowCms['type'] == '0'){							
				$varModuleName  = ucfirst($rowCms['modulename']);	
				$valModuleName = $rowCms['modulename'];
				$GLOBALS['module'.$varModuleName] = $rowCms['seourl'];					
				$GLOBALS['moduleName'.$varModuleName] = $rowCms['name'];
				if($GLOBALS['SeoUnabled'])
					$GLOBALS['moduleLink'.$varModuleName] = GetUrl(array('module'=>$rowCms['seourl'])); 
				else 
				   $GLOBALS['moduleLink'.$varModuleName] = GetUrl(array('module'=>$rowCms['modulename']));						
				$GLOBALS['SeoKeyword'][$GLOBALS['module'.$varModuleName]] = array('class'=>$valModuleName,'id'=>$rowCms['id']);		
			} else {
				if($rowCms['type'] == '2'){			
					$GLOBALS['cmsName'.$rowCms['id']] = $rowCms['name'];					
					$GLOBALS['cmsLink'.$rowCms['id']] = "#";												
				} else{
					$GLOBALS['cmsName'.$rowCms['id']] = $rowCms['name']; 	
					if($GLOBALS['SeoUnabled']){
						$GLOBALS['cmsLink'.$rowCms['id']] = GetUrl(array('seourl'=>$rowCms['seourl']));
							
					}else{ 
						$GLOBALS['cmsLink'.$rowCms['id']] = GetUrl(array('module'=>'cmspage','id'=>$rowCms['id']));	
					}
				}
			}	
			$GLOBALS['PageName'][$rowCms['seourl']] = array('id'=>$rowCms['id']);
			  if($GLOBALS['SeoUnabled']){	
				if($rowCms['type']){					
					$GLOBALS['linkCms'.$rowCms['id']] = GetUrl(array('module'=>$rowCms['seourl']));								
				} else {					
					$varModuleName  = ucfirst($rowCms['modulename']);	
					$valModuleName = $rowCms['modulename'];
					$GLOBALS['getModule'.$varModuleName] = $rowCms['seourl'];					
					$GLOBALS['linkModule'.$varModuleName] = GetUrl(array('module'=>$rowCms['seourl']));
					$GLOBALS['SeoKeyword'][$GLOBALS['getModule'.$varModuleName]] = array('class'=>$valModuleName);							
				}
			} else {
				if($rowCms['type']){
					$GLOBALS['linkCms'.$rowCms['id']] = GetUrl(array('module'=>'cmspage','id'=>$rowCms['id']));		
				} else {
					$varModuleName  = ucfirst($rowCms['modulename']);	
					$valModuleName = $rowCms['modulename'];
					$GLOBALS['getModule'.$varModuleName] = $rowCms['seourl'];					
					$GLOBALS['linkModule'.$varModuleName] = GetUrl(array('module'=>$rowCms['modulename']));									
				}
			}
		}	
	
		
			$key = md5($GLOBALS['UserPassword']);
			$id = md5($GLOBALS['UserId']);
			$GLOBALS['ResetPassLink']=  GetUrl(array('module'=>'login','category_id'=>'resetpass','id'=>$key,'subid'=>$id));	
	}
	 private function logoutUser() {
		$GLOBALS['CLA_SESSION']->userSessionExpire($GLOBALS['ROOT_LINK']);
		exit;
	}
	 
		
	public function checkValidUser() {
		if (!isset($_SESSION[GetSession('UserEmail')])) {
			GetFrontRedirectUrl(sprintf("%s", GetConfig('SITE_URL')));
			exit;
		} 
    }

	private function GetUserProfilePic(){
		if(is_numeric($GLOBALS['USERID'])){
			$usrRes = $GLOBALS['DB']->row("SELECT user_status,user_image FROM `registerusers` WHERE `user_id`=? and user_status=1",array($GLOBALS['USERID']));
			$filepath = $GLOBALS['UPLOAD_LINK'].'/profile/'.$usrRes['user_image']; 
			if(file_exists($filepath) && $usrRes['user_image'] != 'default.png' ){
				$profile_img = $GLOBALS['UPLOAD_LINK'].'/profile/'.$usrRes['user_image'];
				$GLOBALS['USERPROFILEIMG'] = '<img src="'.$profile_img.'" alt="">';
			}else{
				$profile_txt = substr($GLOBALS['USERNAME'],0,1);
				$GLOBALS['USERPROFILEIMG'] = '<span class="profiletxt">'.$profile_txt.'</span>';

			}
			return count($usrRes);
			// return $GLOBALS['DB']->CountResult($usrRes);
		}
		return 0;
	}

	private function checkUserStatus(){
		if(is_numeric($GLOBALS['USERID'])){
			$usrRes = $GLOBALS['DB']->query("SELECT user_status FROM `registerusers` WHERE `user_id`=? and user_status=1",array($GLOBALS['USERID']));	
			return count($usrRes);
		}
		return 0;
	}
	
	private function checkPlanStatus(){
		if(is_numeric($GLOBALS['USERID'])){
			$usrRes = $GLOBALS['DB']->row("SELECT * FROM `registerusers_subscription` WHERE `user_id`=?",array($GLOBALS['USERID']));
			$GLOBALS['plan_start'] = $usrRes['period_start']; 
			$GLOBALS['plan_end'] = $usrRes['period_end']; 
			$GLOBALS['plan_cancel'] = $usrRes['plan_cancel'];
			
			// check free trial left day
			$GLOBALS['plan_type'] = $usrRes['free_trial'] == 1 ? 'FREE' : 'GENEREAL';
			$GLOBALS['FREETRIAL'] = $usrRes['free_trial'] == 1 ? 1 : 0;
			$GLOBALS['FTD'] = $usrRes['free_trial'] == 1 ? '' : 'hidden';
			$GLOBALS['FTDN'] = $usrRes['free_trial'] == 1 ? 'hidden' : '';
			if($GLOBALS['plan_type'] == 'FREE'){
				$now = time();
				
				if($usrRes['period_end'] > $now){
					$day_left = ceil(($usrRes['period_end'] - $now)/86400);
				}else{
					$day_left =0;
				}
				$GLOBALS['freeperiod_dayleft'] = $day_left;
			}

			// check signature limit
			$total_signature = $this->getTotalSignatureCreate();
			if($total_signature >= $usrRes['plan_signaturelimit'] ){
				$GLOBALS['plan_signaturelimit'] = 0;
			    $GLOBALS['total_sigcreated'] =  $usrRes['plan_signaturelimit'];
				$GLOBALS['total_sigleftlimit'] = 0;
			}else{
				$GLOBALS['plan_signaturelimit'] = 1;
				$GLOBALS['total_sigcreated'] =  $total_signature;
				$GLOBALS['total_sigleftlimit'] =  ($usrRes['plan_signaturelimit'] - $GLOBALS['total_sigcreated']);
			}
			if ($usrRes['period_end'] <= time()) { 
				// if (is_dir(GetConfig('SITE_UPLOAD_PATH') ."/signature/complete/".$GLOBALS['USERID'])) {  // expire plan rename directory
					// $current_dir = GetConfig('SITE_UPLOAD_PATH')."/signature/complete/".$GLOBALS['USERID'];
					// $rename_dir =  GetConfig('SITE_UPLOAD_PATH')."/signature/complete/".$GLOBALS['USERID'].'-expire';
					// if(is_dir($current_dir)){
						//// do the code put below try catch here for check local folders
					// }
					// rename($current_dir,$rename_dir);
				// }

				try {
					$bucket = $GLOBALS['BUCKETNAME'];
					$oldPrefix = "upload-beta/signature/complete/".$GLOBALS['USERID'];
					$newPrefix = "upload-beta/signature/complete/".$GLOBALS['USERID']."-expire";
					$s3 = $GLOBALS['S3Client'];

					// 1. List all objects under the old prefix
					$result = $s3->listObjectsV2([
						'Bucket' => $bucket,
						'Prefix' => $oldPrefix,
					]);

					if (!empty($result['Contents'])) {
						foreach ($result['Contents'] as $object) {
							$oldKey = $object['Key'];
							$fileName = substr($oldKey, strlen($oldPrefix));
							$newKey = $newPrefix . $fileName;

							// 2. Copy to the new location
							$s3->copyObject([
								'Bucket'     => $bucket,
								'CopySource' => urlencode("{$bucket}/{$oldKey}"),
								'Key'        => $newKey,
								'ACL'        => 'public-read',
								'StorageClass' => 'REDUCED_REDUNDANCY',
							]);

							// 3. Delete the original object
							$s3->deleteObject([
								'Bucket' => $bucket,
								'Key'    => $oldKey,
							]);
						}

						// Optional: create empty folder marker so it appears in UI
						$s3->putObject([
							'Bucket' => $bucket,
							'Key'    => $newPrefix,
							'Body'   => '',
							'ACL'    => 'public-read'
						]);
					}
					
					$oldPrefixSignatureProfile = "upload-beta/signature/profile/".$GLOBALS['USERID'];
					$newPrefixSignatureProfile = "upload-beta/signature/profile/".$GLOBALS['USERID']."-expire";
					$s3 = $GLOBALS['S3Client'];

					// 1. List all objects under the old prefix
					$result = $s3->listObjectsV2([
						'Bucket' => $bucket,
						'Prefix' => $oldPrefixSignatureProfile,
					]);

					if (!empty($result['Contents'])) {
						foreach ($result['Contents'] as $object) {
							$oldKey = $object['Key'];
							$fileName = substr($oldKey, strlen($oldPrefixSignatureProfile));
							$newKey = $newPrefixSignatureProfile . $fileName;

							// 2. Copy to the new location
							$s3->copyObject([
								'Bucket'     => $bucket,
								'CopySource' => urlencode("{$bucket}/{$oldKey}"),
								'Key'        => $newKey,
								'ACL'        => 'public-read',
								'StorageClass' => 'REDUCED_REDUNDANCY',
							]);

							// 3. Delete the original object
							$s3->deleteObject([
								'Bucket' => $bucket,
								'Key'    => $oldKey,
							]);
						}

						// Optional: create empty folder marker so it appears in UI
						$s3->putObject([
							'Bucket' => $bucket,
							'Key'    => $newPrefixSignatureProfile,
							'Body'   => '',
							'ACL'    => 'public-read'
						]);
					}
				} catch (Exception $e) {
					// ignore renaming in s3bucket as folder not found
				}

				return 0;
			}else{ 
				// if (is_dir(GetConfig('SITE_UPLOAD_PATH') ."/signature/complete/".$GLOBALS['USERID']."-expire")) { // active plan rename directory
				// 	$current_dir = GetConfig('SITE_UPLOAD_PATH') . "/signature/complete/".$GLOBALS['USERID']."-expire";
				// 	$rename_dir =  GetConfig('SITE_UPLOAD_PATH') . "/signature/complete/".$GLOBALS['USERID'];

				// 	if(is_dir($current_dir)){
						//// do the code put below try catch here for check local folders
					// }
					// rename($current_dir,$rename_dir);
				// }
				try {
					$bucket = $GLOBALS['BUCKETNAME'];
					$oldPrefix = "upload-beta/signature/complete/".$GLOBALS['USERID']."-expire";
					$newPrefix = "upload-beta/signature/complete/".$GLOBALS['USERID'];
					$s3 = $GLOBALS['S3Client'];

					// 1. List all objects under the old prefix
					$result = $s3->listObjectsV2([
						'Bucket' => $bucket,
						'Prefix' => $oldPrefix,
					]);

					if (!empty($result['Contents'])) {
						foreach ($result['Contents'] as $object) {
							$oldKey = $object['Key'];
							$fileName = substr($oldKey, strlen($oldPrefix));
							$newKey = $newPrefix . $fileName;
							
							// 2. Copy to the new location
							$s3->copyObject([
								'Bucket'     => $bucket,
								'CopySource' => urlencode("{$bucket}/{$oldKey}"),
								'Key'        => $newKey,
								'ACL'        => 'public-read',
								'StorageClass' => 'REDUCED_REDUNDANCY',
							]);

							// 3. Delete the original object
							$s3->deleteObject([
								'Bucket' => $bucket,
								'Key'    => $oldKey,
							]);
						}
					}

					$oldPrefixSignatureProfile = "upload-beta/signature/profile/".$GLOBALS['USERID']."-expire";
					$newPrefixSignatureProfile = "upload-beta/signature/profile/".$GLOBALS['USERID'];
					$s3 = $GLOBALS['S3Client'];

					// 1. List all objects under the old prefix
					$result = $s3->listObjectsV2([
						'Bucket' => $bucket,
						'Prefix' => $oldPrefixSignatureProfile,
					]);

					if (!empty($result['Contents'])) {
						foreach ($result['Contents'] as $object) {
							$oldKey = $object['Key'];
							$fileName = substr($oldKey, strlen($oldPrefixSignatureProfile));
							$newKey = $newPrefixSignatureProfile . $fileName;

							// 2. Copy to the new location
							$s3->copyObject([
								'Bucket'     => $bucket,
								'CopySource' => urlencode("{$bucket}/{$oldKey}"),
								'Key'        => $newKey,
								'ACL'        => 'public-read',
								'StorageClass' => 'REDUCED_REDUNDANCY',
							]);

							// 3. Delete the original object
							$s3->deleteObject([
								'Bucket' => $bucket,
								'Key'    => $oldKey,
							]);
						}
					}
				} catch (Exception $e) {
					// ignore renaming in s3bucket as folder not found
				}
				return 1; 
			}	
			
		}
		return 0;
	}

	public function getSignatureLogo(){
		if($GLOBALS['USERID']){
			$sigLogo = $GLOBALS['DB']->row("SELECT * FROM `signature_logo` WHERE user_id = ? LIMIT 0,1",array($GLOBALS['USERID']));
			if(isset($_REQUEST['department_id'])){
				$sigLogo = $GLOBALS['DB']->row("SELECT * FROM `signature_logo` WHERE user_id = ? AND department_id = ? LIMIT 0,1",array($GLOBALS['USERID'],$_REQUEST['department_id']));
			} elseif (isset($GLOBALS['current_department_id'])) {
				$sigLogo = $GLOBALS['DB']->row("SELECT * FROM `signature_logo` WHERE user_id = ? AND department_id = ? LIMIT 0,1",array($GLOBALS['USERID'],$GLOBALS['current_department_id']));
			}
			$isDepartmentLogo = true;
			if(!$sigLogo){
				$isDepartmentLogo = false;
				$sigLogo = $GLOBALS['DB']->row("SELECT * FROM `signature_logo` WHERE user_id = ? LIMIT 0,1",array($GLOBALS['USERID']));
			}
			if($sigLogo){
				$GLOBALS['logo_id'] = $sigLogo['id'];
				$GLOBALS['logo_process'] = $sigLogo['logo_process']; 
				$logoName = "";
				if($sigLogo['logo_process'] == 2){ // && $GLOBALS['PLAN_STATUS'] == 1
					$GLOBALS['signature_image'] = $GLOBALS['UPLOAD_LINK'].'/signature/complete/'.$GLOBALS['USERID'].'/'.$sigLogo['logo_animation'];
					$logoName = $sigLogo['logo_animation'];
					// $GLOBALS['signature_image'] = $GLOBALS['ROOT_LINK'].'/r/'.$sigLogo['logo'].'/logo';
				}else if($sigLogo['logo_process'] == 1 && $GLOBALS['PLAN_STATUS'] ==1){
					$GLOBALS['signature_image'] = $GLOBALS['UPLOAD_LINK'].'/signature/complete/'.$GLOBALS['USERID'].'/'.$sigLogo['logo_animation'];
					$logoName = $sigLogo['logo'];
					// $GLOBALS['signature_image'] = $GLOBALS['ROOT_LINK'].'/r/'.$sigLogo['logo'].'/logo';
				}else{
					$GLOBALS['signature_image'] = $GLOBALS['UPLOAD_LINK'].'/signature/'.$GLOBALS['USERID'].'/'.$sigLogo['logo'];
					$logoName = $sigLogo['logo'];
					// $GLOBALS['signature_image'] = $GLOBALS['ROOT_LINK'].'/r/'.$sigLogo['logo'].'/logo';
				}
				$dateToday = date('Y-m-d');
				$userIp = $GLOBALS['CLA_INDEX']->getUserIP();
				$sigLogoAnalytics = $GLOBALS['DB']->row("SELECT * FROM `registerusers_analytics` WHERE url = ? AND user_id = ? AND date = ? AND analytic_type='logo' AND user_ip = ? LIMIT 0,1",array($logoName,$GLOBALS['USERID'],$dateToday,$userIp));
				if($sigLogoAnalytics){
					$GLOBALS['signature_image'] = $GLOBALS['ROOT_LINK'].'/r/'.$sigLogoAnalytics['id'].'/logo';
				}else{
					$existingData = $GLOBALS['DB']->row("SELECT * FROM `registerusers_analytics` WHERE url = ? AND user_id = ? AND analytic_type='logo' LIMIT 0,1",array($logoName,$GLOBALS['USERID']));
					if($existingData){
						$data['user_id'] = $existingData['user_id'];
						$data['signature_id'] = $existingData['signature_id'];
						$data['url'] = $existingData['url'];
						$data['analytic_type'] = $existingData['analytic_type'];
						$data['impressions'] = $data['clicks'] = $data['mobile_clicks'] = $data['desktop_clicks'] = $data['tablet_clicks'] = $data['windows_clicks'] = $data['macos_clicks'] = $data['linux_clicks'] = $data['ios_clicks'] = $data['android_clicks'] =  0;
						$data['date'] = $dateToday;
						$ipInformation = $GLOBALS['CLA_INDEX']->getUserLocation();
						$data['user_ip'] = $ipInformation['ip'];
						$data['location'] = json_encode($ipInformation['location'], true);
						$GLOBALS['DB']->insert('registerusers_analytics', $data);
						$sigLogoAnalytics = $GLOBALS['DB']->row("SELECT * FROM `registerusers_analytics` WHERE url = ? AND user_id = ? AND date = ? AND analytic_type='logo' LIMIT 0,1",array($sigLogo['logo'],$GLOBALS['USERID'],$dateToday));
						if($sigLogoAnalytics){
							$GLOBALS['signature_image'] = $GLOBALS['ROOT_LINK'].'/r/'.$sigLogoAnalytics['id'].'/logo';
						}
					}
				}
				if($sigLogo['changed_logo']){
					if(is_null($sigLogo['changed_logo'])){
						$GLOBALS['signature_change_image'] = $GLOBALS['UPLOAD_LINK'].'/signature/'.$GLOBALS['USERID'].'/'.$sigLogo['logo'];
					}else{
						$GLOBALS['signature_change_image'] = $GLOBALS['UPLOAD_LINK'].'/signature/'.$GLOBALS['USERID'].'/'.$sigLogo['changed_logo'];
					}
				}
				// if($GLOBALS['USERTYPE'] == 'enterprise'){
				// 	if($isDepartmentLogo){
				// 		$GLOBALS['signature_image'] = $GLOBALS['UPLOAD_LINK'].'/signature/'.$GLOBALS['USERID'].'/'.$sigLogo['logo'];
				// 	}
				// }
			}else{
				$GLOBALS['logo_id'] = 0;
				$GLOBALS['logo_process'] = 0; 
				// $GLOBALS['signature_image'] = $GLOBALS['IMAGE_LINK'].'/images/default_siglogo.svg';
				$GLOBALS['signature_image'] = $GLOBALS['IMAGE_LINK'].'/images/default_siglogo.svg';
			}
		}

	}

	public function getTotalSignatureCreate(){
		$user = $GLOBALS['DB']->row("SELECT * FROM `registerusers` WHERE user_id = ? ",array($GLOBALS['USERID']));
		$totalRow = $GLOBALS['DB']->row("SELECT count(`signature_id`) as totalsignature FROM `signature` WHERE `user_id` = ?",array($GLOBALS['USERID']));
		return $totalRow['totalsignature'];
	}

	public function __destruct(){
		if(isset($_SESSION['Error'])){			
			unset($_SESSION['Error']);			
		}	
		if(isset($_SESSION['Success'])){
			unset($_SESSION['Success']);			
		}	
	}

	public function getUserLocation($ip = null) {
        // Use given IP or detect automatically
        if (!$ip) {
            if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
            } else {
                $ip = $_SERVER['REMOTE_ADDR'];
            }
        }

        // TEMP: Override for local testing
        if ($ip === '127.0.0.1' || $ip === '::1') {
            $ip = '164.92.90.31'; // Replace with your own IP if needed
        }

        $response = @file_get_contents("http://ip-api.com/json/{$ip}?fields=status,country,regionName,city");

        if ($response !== false) {
            $data = json_decode($response, true);
            if ($data['status'] === 'success') {
                return ['ip'=>$ip, 'location' => [
							'country' => $data['country'],
							'region' => $data['regionName'],
							'city' => $data['city']
						]
					];
            }
        }
		return ['ip'=>$ip, 'location' => [
				'country' => 'Unknown',
				'region' => 'Unknown',
				'city' => 'Unknown'
				]
			];
    }

	public function getUserIP($ip = null) {
        // Use given IP or detect automatically
        if (!$ip) {
            if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
            } else {
                $ip = $_SERVER['REMOTE_ADDR'];
            }
        }

        // TEMP: Override for local testing
        if ($ip === '127.0.0.1' || $ip === '::1') {
            $ip = '164.92.90.31'; // Replace with your own IP if needed
        }
        
		return $ip;
    }

}