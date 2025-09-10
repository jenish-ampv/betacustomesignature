<?php
require_once($GLOBALS['BASE_LINK'].'/'.GetConfig('CLASSES').'/receivedashboard.php');
class CIT_RECEIVESIGNATURE
{
	
	public function __construct()
	{	
		// if(!isset($_SESSION[GetSession('user_id')]) && !isset($_REQUEST['uuid'])){
		// 	GetFrontRedirectUrl(GetUrl(array('module'=>'signin')));
		// }
		$GLOBALS['SIGNATURE'] = GetClass('CIT_RECEIVEDASHBOARD');
	}
	
	public function displayPage(){
		AddMessageInfo();	
		if(isset($_REQUEST['category_id'])){
			$action = trim($_REQUEST['category_id']);
		} else {
			$action = '';
		}
		if(isset($_REQUEST['email'])){
			$signatureId = $GLOBALS['DB']->row("SELECT * FROM `signature` SG  WHERE `signature_import_email`=? AND `is_deploy`='1' ",array($_REQUEST['email']));
			$user = $GLOBALS['DB']->row("SELECT * FROM `registerusers`  WHERE `user_id`=? ",array($signatureId['user_id']));
			if(is_array($user) && $user['user_planactive'] == '1' && $user['user_status'] == '1'){
				$GLOBALS['signature_source'] = $signatureId['outlook_html'];
			}else{
				$GLOBALS['signature_source'] = '';
			}
			// session_start();
			// $GLOBALS["USERID"] = $signatureId['user_id'];
			// $GLOBALS["SIGNATUREID"] = $signatureId['signature_id'];
			// $_SESSION['receive_signature_id'] = $signatureId['signature_id'];
			// $this->getSignatureLogo();
			// $GLOBALS['signature_source'] = $GLOBALS['SIGNATURE']->getUserSignature($signatureId['signature_id']);
			
		}
		else{
			$GLOBALS['signature_source'] = "";
		}
		$this->getPage();
		header("Access-Control-Allow-Origin: *");
		$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/receivesignature.html');	
		$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');			
		$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');
		$GLOBALS['SIDEBAR'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.sidebar.html');
		$GLOBALS['CLA_HTML']->display();
		RemoveMessageInfo();
		exit();	
		
	}
	
	public function getSignatureLogo(){
		if($GLOBALS['USERID']){
			$sigLogo = $GLOBALS['DB']->row("SELECT * FROM `signature_logo`   WHERE user_id = ? LIMIT 0,1",array($GLOBALS['USERID']));
			if($sigLogo){
				$GLOBALS['logo_id'] = $sigLogo['id'];
				$GLOBALS['logo_process'] = $sigLogo['logo_process']; 
				if($sigLogo['logo_process'] == 2){ // && $GLOBALS['PLAN_STATUS'] == 1
					$GLOBALS['signature_image'] = $GLOBALS['UPLOAD_LINK'].'/signature/complete/'.$GLOBALS['USERID'].'/'.$sigLogo['logo_animation'];
				}else if($sigLogo['logo_process'] == 1 && $GLOBALS['PLAN_STATUS'] ==1){
					$GLOBALS['signature_image'] = $GLOBALS['UPLOAD_LINK'].'/signature/complete/'.$GLOBALS['USERID'].'/'.$sigLogo['logo_animation'];
				}else{
					$GLOBALS['signature_image'] = $GLOBALS['UPLOAD_LINK'].'/signature/'.$GLOBALS['USERID'].'/'.$sigLogo['logo'];
				}
			}else{
				$GLOBALS['logo_id'] = 0;
				$GLOBALS['logo_process'] = 0; 
				$GLOBALS['signature_image'] = $GLOBALS['IMAGE_LINK'].'/images/default_siglogo.svg';
			}
		}

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