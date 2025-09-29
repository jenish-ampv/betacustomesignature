<?php
class CIT_FORGOTPASSWORD
{
	
	public function __construct()
	{	

	}
	
	public function displayPage(){
		
		AddMessageInfo();	
		if(isset($_REQUEST['category_id'])){
			$action = trim($_REQUEST['category_id']);
		} else {
			$action = '';
		}
		if($_REQUEST['category_id'] == 'otp' && isset($_SESSION[GetSession('fp_otp')])){
			$GLOBALS['fp_step'] =2;
		}else if($_REQUEST['category_id'] == 'resetpassword' && isset($_SESSION[GetSession('fp_otp')])  && isset($_SESSION[GetSession('fp_otpverify')])){
			$GLOBALS['fp_step'] =3;
		}else{
			$GLOBALS['fp_step'] =1;
		}
		
		// password change
		if($_POST['fpw_password'] != "" && $_POST['fpw_cpassword'] !="" && $_SESSION[GetSession('fp_otp')] == $_SESSION[GetSession('fp_otpverify')] ){
			
			if($_POST['fpw_password'] == $_POST['fpw_cpassword']){
				$user_id = $_SESSION[GetSession('fp_userid')]; $newpassword = md5($_POST['fpw_password']);
				$change_pass = $GLOBALS['DB']->update("registerusers",array('user_password'=>$newpassword),array('user_id' =>$user_id));
				if($change_pass){
					unset($_SESSION[GetSession('fp_otp')]);
					unset($_SESSION[GetSession('fp_otpverify')]);
					unset($_SESSION[GetSession('fp_userid')]);
					$_SESSION[GetSession('Error')]='<div class="success-error-message gap-8 py-5 px-4 pl-11 border-l-9 border-green-600 rounded-xl relative bg-white bg-gradient-to-r from-[#00B71B]/12 to-[#00B71B]/0 shadow-lg"><strong>Success!</strong> password change success.</div>';
					GetFrontRedirectUrl(GetUrl(array('module'=>'signin')));
				}
			}else{
				$_SESSION[GetSession('Error')]='<div class="alert alert-danger"><strong>Fail!</strong> password and confirm password not match.</div>';
				GetFrontRedirectUrl(GetUrl(array('module'=>$_REQUEST['module'],'id'=>'resetpassword')));
			}
		}
		
		// otp verify
		if(isset($_SESSION[GetSession('fp_otp')]) && $_POST['digit-1'] !="" && $_POST['digit-2'] !="" && $_POST['digit-3'] !="" && $_POST['digit-4'] !="" && $_POST['digit-5'] !="" && $_POST['digit-6'] !=""){
			$otp = $_POST['digit-1'].$_POST['digit-2'].$_POST['digit-3'].$_POST['digit-4'].$_POST['digit-5'].$_POST['digit-6'];
			if($otp == $_SESSION[GetSession('fp_otp')]){
				$_SESSION[GetSession('fp_otpverify')] = $otp;
				GetFrontRedirectUrl(GetUrl(array('module'=>$_REQUEST['module'],'id'=>'resetpassword')));
			}else{
				$_SESSION[GetSession('Error')]='<div class="alert alert-danger"><strong>Fail!</strong> code not match.</div>';
				GetFrontRedirectUrl(GetUrl(array('module'=>$_REQUEST['module'],'id'=>'otp')));
			}
		}
		
		// resend otp
		if($_REQUEST['category_id'] == 'resendotp' && isset($_SESSION[GetSession('fp_email')])){
			
			$GLOBALS['FPWEmail']= $_SESSION[GetSession('fp_email')];
			$GLOBALS['UFName'] = $_SESSION[GetSession('fp_username')]; 
			$GLOBALS['fpotp'] = $_SESSION[GetSession('fp_otp')];
			$to = $_SESSION[GetSession('fp_email')];
			$message= _getEmailTemplate('forget_password');
			$send_mail = _SendMail($to,'',$GLOBALS['EMAIL_SUBJECT'],$message);
			if($send_mail){
				$_SESSION[GetSession('Success')]='<div class="success-error-message fixed top-0 right-0 p-3"><div class="success-error-message gap-8 py-5 px-4 pl-11 border-l-9 border-green-600 rounded-xl relative bg-white bg-gradient-to-r from-[#00B71B]/12 to-[#00B71B]/0 shadow-lg" id="success"><strong>Success!</strong> OTP sent to your email account.</div></div>';
				GetFrontRedirectUrl(GetUrl(array('module'=>$_REQUEST['module'],'id'=>'otp')));
			}else{
				$_SESSION[GetSession('Error')]='<div class="alert alert-danger"><strong>Fail!</strong> mail not sent try again.</div>';
				GetFrontRedirectUrl(GetUrl(array('module'=>$_REQUEST['module'],'id'=>'otp')));
			}
		}
		
		// send otp
		if($_POST['fpw_email']){
				$site_id = $GLOBALS['SITE_ID'];
				$user_email = $_POST['fpw_email'];

				$rowFpw = $GLOBALS['DB']->row("SELECT * FROM `registerusers` WHERE user_email = ?",array($user_email));	
				$rowFpwSubUser = $GLOBALS['DB']->row("SELECT * FROM `registerusers_sub_users` WHERE email = ?",array($user_email));
				
				if($rowFpw){
					$GLOBALS['FPWEmail']= $rowFpw['user_email'];
					$GLOBALS['UFName'] = $rowFpw['user_fullname']; 
					$GLOBALS['fpotp'] = rand(100000,999999);
					$_SESSION[GetSession('fp_otp')] = $GLOBALS['fpotp'];
					$_SESSION[GetSession('fp_userid')] = $rowFpw['user_id'];
					$_SESSION[GetSession('fp_email')] = $rowFpw['user_email'];
					$_SESSION[GetSession('fp_username')] = $rowFpw['user_fullname'];
					$to = $rowFpw['user_email'];
					$message= _getEmailTemplate('forget_password');
					$send_mail = _SendMail($to,'',$GLOBALS['EMAIL_SUBJECT'],$message);
					if($send_mail){
						$_SESSION[GetSession('Success')]='<div class="success-error-message fixed top-0 right-0 p-3"><div class="success-error-message gap-8 py-5 px-4 pl-11 border-l-9 border-green-600 rounded-xl relative bg-white bg-gradient-to-r from-[#00B71B]/12 to-[#00B71B]/0 shadow-lg" id="success"><strong>Success!</strong> OTP sent to your email account.</div></div>';
						GetFrontRedirectUrl(GetUrl(array('module'=>$_REQUEST['module'],'id'=>'otp')));
					}else{
						$_SESSION[GetSession('Error')]='<div class="alert alert-danger"><strong>Fail!</strong> mail not sent try again.</div>';
						GetFrontRedirectUrl(GetUrl(array('module'=>$_REQUEST['module'])));
					}
				}
				else if($rowFpwSubUser){
					$GLOBALS['FPWEmail']= $rowFpwSubUser['email'];
					$GLOBALS['UFName'] = $rowFpwSubUser['user_firstname'] ." ".$rowFpwSubUser['user_lastname']; 
					$GLOBALS['fpotp'] = rand(100000,999999);
					$_SESSION[GetSession('fp_otp')] = $GLOBALS['fpotp'];
					$_SESSION[GetSession('fp_userid')] = $rowFpwSubUser['id'];
					$_SESSION[GetSession('fp_email')] = $rowFpwSubUser['email'];
					$_SESSION[GetSession('fp_username')] = $rowFpwSubUser['user_firstname'] ." ".$rowFpwSubUser['user_lastname']; 
					$to = $rowFpwSubUser['email'];
					$message= _getEmailTemplate('forget_password');
					$send_mail = _SendMail($to,'',$GLOBALS['EMAIL_SUBJECT'],$message);
					if($send_mail){
						$_SESSION[GetSession('Success')]='<div class="success-error-message fixed top-0 right-0 p-3"><div class="success-error-message gap-8 py-5 px-4 pl-11 border-l-9 border-green-600 rounded-xl relative bg-white bg-gradient-to-r from-[#00B71B]/12 to-[#00B71B]/0 shadow-lg" id="success"><strong>Success!</strong> OTP sent to your email account.</div></div>';
						GetFrontRedirectUrl(GetUrl(array('module'=>$_REQUEST['module'],'id'=>'otp')));
				}else{
						$_SESSION[GetSession('Error')]='<div class="alert alert-danger"><strong>Fail!</strong> mail not sent try again.</div>';
						GetFrontRedirectUrl(GetUrl(array('module'=>$_REQUEST['module'])));
					}

				}
				else{
					$_SESSION[GetSession('Error')]='<div class="alert alert-danger"><strong>Fail!</strong> No account found with this email. Please check or sign up.</div>';
				    GetFrontRedirectUrl(GetUrl(array('module'=>$_REQUEST['module'])));
				}
			}
		
		
		//$this->getPage();
		$GLOBALS['li_resendotp'] = GetUrl(array('module'=>$_REQUEST['module'],'id'=>'resendotp'));
		$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/forgotpassword.html');	
		$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');			
		$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');
		$GLOBALS['SIDEBAR'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.sidebar.html');
		$GLOBALS['CLA_HTML']->display();
		RemoveMessageInfo();
		exit();	
		
	}
	
	public function getPage(){
		$row = $GLOBALS['DB']->row("SELECT * FROM `pages` WHERE `seourl`= ? LIMIT 0,1",array($_REQUEST['module'],0));
		$GLOBALS['MetaTitle'] =  $row['metatitle'] !="" ? $row['metatitle'] : $GLOBALS['SITE_TITLE'];
		$GLOBALS['Metakeywords'] = $row['metakeywords'];
		$GLOBALS['Metadescription'] =  $row['metadescription'];
		$GLOBALS['PageId'] = $row['id'];
		$GLOBALS['PageName'] = $row['name'];
		$GLOBALS['PageDesc'] = $GLOBALS['CLA_HTML']->addContent($row['desc']);
	}
	
	
}

?>