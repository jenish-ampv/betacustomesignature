<?php
class CIT_CONFIG{
	private $count;
	private $sitestatus = 0;
	private $result;
	private $category;
	private $config_id ='';
	public function __construct(){					

	}	
	public function displayPage(){
		if(isset($_POST['submit'])){										
			$row = $GLOBALS['DB']->AutoIncrement("config");
			$site_id	=	$_POST['site_id'];
			$GLOBALS['contitle']	=	$_POST['title'];
			$GLOBALS['consorttitle']	=	$_POST['sort_title'];
			$GLOBALS['conurl']	=	$_POST['url'];
			$GLOBALS['consorturl']	=	$_POST['sorturl'];
			$GLOBALS['conappurl']	=	$_POST['sorturl'];
			$GLOBALS['consession']	=	$_POST['session'];
			$GLOBALS['conusersession']	=	$_POST['usersession'];
			$GLOBALS['condebug']	=	$_POST['debug'];
			$GLOBALS['conseourl']	=	$_POST['seourl'];
			$GLOBALS['conmailer']	= $_POST['mailer'];
			$GLOBALS['consendmail']	= $_POST['sendmail'];
			$GLOBALS['consmtpauth']	= $_POST['smtpauth'];
			$GLOBALS['consmtpsecure']	= $_POST['smtpsecure'];
			$GLOBALS['consmtpport']	= $_POST['smtpport'];
			$GLOBALS['consmtpuser']	= $_POST['smtpuser'];
			$GLOBALS['consmtppass']	= $_POST['smtppass'];
			$GLOBALS['consmtphost']	= $_POST['smtphost'];
			
		
			$GLOBALS['SITE_PHONE_NUMBER'] = $_POST['SITE_PHONE_NUMBER'];
			$GLOBALS['SITE_EMAIL_ADDRESS'] = $_POST['SITE_EMAIL_ADDRESS'];
			$GLOBALS['SITE_ADDRESS'] = $_POST['SITE_ADDRESS'];
			$GLOBALS['EMAIL_ADDRESS'] = $_POST['EMAIL_ADDRESS'];
			$GLOBALS['POSTAL_CODE'] = $_POST['POSTAL_CODE'];
			
			$GLOBALS['SITE_COLOR'] = $_POST['SITE_COLOR'];
			$GLOBALS['SITE_BGCOLOR'] = $_POST['SITE_BGCOLOR'];
			$GLOBALS['SITE_GOOGLEANALYTICS'] = $_POST['SITE_GOOGLEANALYTICS'];

			$GLOBALS['CSS_VERSION'] = $_POST['CSS_VERSION'];
			$GLOBALS['JS_VERSION'] = $_POST['JS_VERSION'];
			
			$GLOBALS['SITE_RGBCOLOR'] = $_POST['SITE_RGBCOLOR'];
			
			
			if($_POST['SITE_CURRENCYSYMBOL'] !=""){
				$GLOBALS['SITE_CURRENCYSYMBOL'] = $_POST['SITE_CURRENCYSYMBOL'];
			}else{
				$GLOBALS['SITE_CURRENCYSYMBOL'] = '$';
			}
			
			$fileName = $_FILES['conlogo']['name'];
			if($fileName != ''){
				if(!is_dir(GetConfig('SITE_UPLOAD_PATH').'/logo/'.$GLOBALS['SITE_ID'])){
					mkdir(GetConfig('SITE_UPLOAD_PATH').'/logo/'.$GLOBALS['SITE_ID']);
				}	
				$logoExtension = end(explode(".",$fileName));
				copy($_FILES['conlogo']['tmp_name'],GetConfig('SITE_UPLOAD_PATH').'/logo/'.$GLOBALS['SITE_ID'].'/logo.'.$logoExtension);
				$uploadfilename = 'logo.'.$logoExtension;
			} else {
				$uploadfilename = GetConfig('logo');
			}
			
			$emaillogofilename = $_FILES['conlogoemail']['name'];
			
			if($emaillogofilename != ''){
				if(!is_dir(GetConfig('SITE_UPLOAD_PATH').'/logo/'.$GLOBALS['SITE_ID'])){
					mkdir(GetConfig('SITE_UPLOAD_PATH').'/logo/'.$GLOBALS['SITE_ID']);
				}	
				$logoExtension = end(explode(".",$emaillogofilename));
				copy($_FILES['conlogoemail']['tmp_name'],GetConfig('SITE_UPLOAD_PATH').'/logo/'.$GLOBALS['SITE_ID'].'/logo-email.'.$logoExtension);
				$uploademailfilename = 'logo-email.'.$logoExtension;
			} else {
				$uploademailfilename = GetConfig('logoemail');
			}
			
			//App Logo
			$applogofilename = $_FILES['conlogoapp']['name'];
			
			if($applogofilename != ''){
				if(!is_dir(GetConfig('SITE_UPLOAD_PATH').'/logo/'.$GLOBALS['SITE_ID'])){
					mkdir(GetConfig('SITE_UPLOAD_PATH').'/logo/'.$GLOBALS['SITE_ID']);
				}	
				$logoExtension = end(explode(".",$applogofilename));
				copy($_FILES['conlogoapp']['tmp_name'],GetConfig('SITE_UPLOAD_PATH').'/logo/'.$GLOBALS['SITE_ID'].'/logo-app.'.$logoExtension);
				$uploadappfilename = 'logo-app.'.$logoExtension;
			} else {
				$uploadappfilename = GetConfig('logoapp');
			}
				
			if($_POST['sorturl'] ==""){
				$_POST['sorturl'] = shortenURL($_POST['url']);
			}
			$name = array('SITE_TITLE'=>$_POST['title'],'SITE_SORTTITLE'=>$_POST['sort_title'],'SITE_URL'=>$_POST['url'],'SITE_SORTURL'=>$_POST['sorturl'], 'AdminSessionTimeout'=>$_POST['session'], 'UserSessionTimeout'=>$_POST['usersession'], 'cit_dbdebug'=>$_POST['debug'], 'mod_rewrite'=>$_POST['seourl'], 'mailer'=>$_POST['mailer'], 'sendmail'=>$_POST['sendmail'], 'smtpauth'=>$_POST['smtpauth'], 'smtpsecure'=>$_POST['smtpsecure'], 'smtpport'=>$_POST['smtpport'], 'smtpuser'=>$_POST['smtpuser'], 'smtppass'=>$_POST['smtppass'], 'smtphost'=>$_POST['smtphost'],'smtpdomain'=>$_POST['smtpdomain'],'logo'=>$uploadfilename,'PAYPAL_EMAILID'=>$_POST['PAYPAL_EMAILID'],'SITE_PHONE_NUMBER'=>$_POST['SITE_PHONE_NUMBER'],'SITE_EMAIL_ADDRESS'=>$_POST['SITE_EMAIL_ADDRESS'],'SITE_ADDRESS'=>$_POST['SITE_ADDRESS'],'SITE_COLOR'=>$_POST['SITE_COLOR'],'SITE_BGCOLOR'=>$_POST['SITE_BGCOLOR'],'POSTAL_CODE'=>$_POST['POSTAL_CODE'],'SITE_GOOGLEANALYTICS'=>$_POST['SITE_GOOGLEANALYTICS'],'SUPPORT_TEXT'=>$_POST['SUPPORT_TEXT'],'REDIRECT_TEXT'=>$_POST['REDIRECT_TEXT'],'CSS_VERSION'=>$_POST['CSS_VERSION'],'JS_VERSION'=>$_POST['JS_VERSION'],'SITE_COUNTRY'=>$_POST['SITE_COUNTRY'],'SITE_CURRENCYSYMBOL'=>$GLOBALS['SITE_CURRENCYSYMBOL'],'logoemail'=>$uploademailfilename,'logoapp'=>$uploadappfilename,'SITE_RGBCOLOR'=>$GLOBALS['SITE_RGBCOLOR'],'STRIPE_SECRET_KEY'=>$_POST['STRIPE_SECRET_KEY'],'STRIPE_PUBLISHABLE_KEY'=>$_POST['STRIPE_PUBLISHABLE_KEY'],'STRIPE_WEBHOOK_SECRET'=>$_POST['STRIPE_WEBHOOK_SECRET'],'ADMIN_LOGINKEY'=>$_POST['ADMIN_LOGINKEY'],'S3BUCKET'=>$_POST['S3BUCKET']);
			$configName =  serialize($name);
			    $update_config = $GLOBALS['DB']->update('config',array('name' => $configName),array('id' =>1));
				if($update_config){
					$_SESSION['Success'] .= '<div class="alert alert-success">Configuration updated successfully</div>';
				} else {
					$_SESSION['Error'] .= '<div class="alert alert-danger">An error occurred while you trying to update configuration, please try again.</div>';
				}
				GetAdminRedirectUrl();			
			exit;
		}
		$this->config();
	}

	private function config(){					
		AddMessageInfo();		
			
		$row = $GLOBALS['DB']->row("SELECT * FROM `config` WHERE `id` = 1 LIMIT 0,1");	
		
		if(is_array($row)){
			if($row['name'] != ''){
				$configName = unserialize($row['name']);
				foreach($configName as $conKey=>$conValue){
					if($conKey == 'logo'){
						if(is_file(GetConfig('SITE_UPLOAD_PATH').'/logo/'.$GLOBALS['SITE_ID'].'/'.$conValue)){ 
							list($conlogowidth,$conlogoheight) = getimagesize(GetConfig('SITE_UPLOAD_PATH').'/logo/'.$GLOBALS['SITE_ID'].'/'.$conValue);
							$GLOBALS['conlogodetails'] = sprintf('<br /><span class="admnotes">Logo\'s size %d width and %d height</span>',$conlogowidth,$conlogoheight);
							$GLOBALS['con'.$conKey] = sprintf('<br /><img src="%s" style="width:250px;"/>',$GLOBALS['UPLOAD_LINK'].'/logo/'.$GLOBALS['SITE_ID'].'/'.$conValue);
						}
					}elseif($conKey == 'logoemail'){
							if(is_file(GetConfig('SITE_UPLOAD_PATH').'/logo/'.$GLOBALS['SITE_ID'].'/'.$conValue)){ 
							list($conlogowidth,$conlogoheight) = getimagesize(GetConfig('SITE_UPLOAD_PATH').'/logo/'.$GLOBALS['SITE_ID'].'/'.$conValue);
							$GLOBALS['conemaillogodetails'] = sprintf('<br /><span class="admnotes">Logo\'s size %d width and %d height</span>',$conlogowidth,$conlogoheight);
							$GLOBALS['con'.$conKey] = sprintf('<br /><img src="%s" style="width:250px;"/>',$GLOBALS['UPLOAD_LINK'].'/logo/'.$GLOBALS['SITE_ID'].'/'.$conValue);
							}
					}elseif($conKey == 'logoapp'){
							if(is_file(GetConfig('SITE_UPLOAD_PATH').'/logo/'.$GLOBALS['SITE_ID'].'/'.$conValue)){ 
							list($conlogowidth,$conlogoheight) = getimagesize(GetConfig('SITE_UPLOAD_PATH').'/logo/'.$GLOBALS['SITE_ID'].'/'.$conValue);
							$GLOBALS['conapplogodetails'] = sprintf('<br /><span class="admnotes">Logo\'s size %d width and %d height</span>',$conlogowidth,$conlogoheight);
							$GLOBALS['con'.$conKey] = sprintf('<br /><img src="%s" style="width:250px;"/>',$GLOBALS['UPLOAD_LINK'].'/logo/'.$GLOBALS['SITE_ID'].'/'.$conValue);
							}
					}else{
						$GLOBALS['con'.$conKey] = $conValue;
					}
				} 	
			}
		} else {
			$GLOBALS['consession'] = '3600';
			$GLOBALS['consmtpsecure'] = 'none';
			$GLOBALS['condebug'] = '0';
			$GLOBALS['conseourl'] = '1';
			$GLOBALS['conmailer'] = 'mail';
			$GLOBALS['consendmail'] = '/usr/sbin/sendmail';
			$GLOBALS['consmtpauth'] = '0';
			$GLOBALS['consmtpsecure'] = 'none';
			$GLOBALS['consmtpport'] = '25';
			$GLOBALS['consmtpuser'] = '';
			$GLOBALS['consmtppass'] = '';
			$GLOBALS['consmtphost'] = 'localhost';			
		}
		$count = 0;				
		$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/'.$GLOBALS['Module'].'/config.html');	
		$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');			
		$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');						
		$GLOBALS['LEFT'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.left.html');			
		$GLOBALS['CLA_HTML']->display();
		RemoveMessageInfo();	
	}			
}