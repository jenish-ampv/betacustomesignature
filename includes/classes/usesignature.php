<?php
require_once($GLOBALS['BASE_LINK'].'/'.GetConfig('CLASSES').'/dashboard.php');
class CIT_USESIGNATURE
{
	
	public function __construct()
	{	
		if(!isset($_SESSION[GetSession('user_id')]) && !isset($_REQUEST['uuid'])){
			GetFrontRedirectUrl(GetUrl(array('module'=>'signin')));
		}
		$GLOBALS['SIGNATURE'] = GetClass('CIT_DASHBOARD');
		if(isset($_REQUEST['department_id'])){
			$GLOBALS['current_department_id'] = $_REQUEST['department_id'];
		}else{
			$GLOBALS['current_department_id'] = 0;
		}
	}
	
	public function displayPage(){
		AddMessageInfo();	
		if(isset($_REQUEST['category_id'])){
			$action = trim($_REQUEST['category_id']);
		} else {
			$action = '';
		}
		if($_REQUEST['category_id'] =="install" && isset($_REQUEST['uuid'])){
			$share_id  = base64_decode($_REQUEST['uuid']);
			
			$result = $GLOBALS['DB']->row("SELECT * FROM signature  WHERE signature_id= ? LIMIT 0,1",array($share_id));
			//$GLOBALS['USERID'] = base64_decode($_REQUEST['u']);
			$GLOBALS['USERID'] = $result['user_id'];
			$this->getSignatureLogo();
			$dateToday = date('Y-m-d');
			$userIp = $GLOBALS['CLA_INDEX']->getUserIP();
			$signature_logo = $GLOBALS['DB']->row("SELECT * FROM `signature_logo` WHERE user_id = ? ",array($GLOBALS['USERID']));
			if($signature_logo['logo_process'] == 2){
				$signature_logo_name = $signature_logo['logo_animation'];
			}else if($signature_logo['logo_process'] == 1 && $GLOBALS['PLAN_STATUS'] ==1){
				$signature_logo_name = $signature_logo['logo_animation'];
			}else{
				$signature_logo_name = $signature_logo['logo'];
			}
			$sigLogoAnalytics = $GLOBALS['DB']->row("SELECT * FROM `registerusers_analytics` WHERE url=? AND signature_id = ? AND user_id = ? AND date = ? AND analytic_type='logo' AND user_ip = ? LIMIT 0,1",array($signature_logo_name,$share_id,$GLOBALS['USERID'],$dateToday,$userIp));
			if($sigLogoAnalytics){
				$GLOBALS['signature_image'] = $GLOBALS['ROOT_LINK'].'/r/'.$sigLogoAnalytics['id'].'/logo';
			}else{
				$existingData = $GLOBALS['DB']->row("SELECT * FROM `registerusers_analytics` WHERE url=? AND signature_id = ? AND user_id = ? AND analytic_type='logo' LIMIT 0,1",array($signature_logo_name,$share_id,$GLOBALS['USERID']));
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
					$sigLogoAnalytics = $GLOBALS['DB']->row("SELECT * FROM `registerusers_analytics` WHERE url=? AND signature_id = ? AND user_id = ? AND date = ? AND analytic_type='logo' LIMIT 0,1",array($signature_logo_name,$share_id,$GLOBALS['USERID'],$dateToday));
					if($sigLogoAnalytics){
						$GLOBALS['signature_image'] = $GLOBALS['ROOT_LINK'].'/r/'.$sigLogoAnalytics['id'].'/logo';
					}
				}
			}
			$GLOBALS['signature_source'] = $GLOBALS['SIGNATURE']->getUserSignature($share_id);
			
		}else{
			$dateToday = date('Y-m-d');
			$userIp = $GLOBALS['CLA_INDEX']->getUserIP();
			$signature_logo = $GLOBALS['DB']->row("SELECT * FROM `signature_logo` WHERE user_id = ? ",array($GLOBALS['USERID']));
			if($signature_logo['logo_process'] == 2){
				$signature_logo_name = $signature_logo['logo_animation'];
			}else if($signature_logo['logo_process'] == 1 && $GLOBALS['PLAN_STATUS'] ==1){
				$signature_logo_name = $signature_logo['logo_animation'];
			}else{
				$signature_logo_name = $signature_logo['logo'];
			}
			$sigLogoAnalytics = $GLOBALS['DB']->row("SELECT * FROM `registerusers_analytics` WHERE url=? AND signature_id = ? AND user_id = ? AND date = ? AND analytic_type='logo' AND user_ip = ? LIMIT 0,1",array($signature_logo_name,$_REQUEST['id'],$GLOBALS['USERID'],$dateToday,$userIp));
			if($sigLogoAnalytics){
				$GLOBALS['signature_image'] = $GLOBALS['ROOT_LINK'].'/r/'.$sigLogoAnalytics['id'].'/logo';
			}else{
				$existingData = $GLOBALS['DB']->row("SELECT * FROM `registerusers_analytics` WHERE url=? AND signature_id = ? AND user_id = ? AND analytic_type='logo' LIMIT 0,1",array($signature_logo_name,$_REQUEST['id'],$GLOBALS['USERID']));
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
					$sigLogoAnalytics = $GLOBALS['DB']->row("SELECT * FROM `registerusers_analytics` WHERE url=? AND signature_id = ? AND user_id = ? AND date = ? AND analytic_type='logo' LIMIT 0,1",array($signature_logo_name,$_REQUEST['id'],$GLOBALS['USERID'],$dateToday));
					if($sigLogoAnalytics){
						$GLOBALS['signature_image'] = $GLOBALS['ROOT_LINK'].'/r/'.$sigLogoAnalytics['id'].'/logo';
					}
				}
			}
			$GLOBALS['signature_source'] = $GLOBALS['SIGNATURE']->getUserSignature($_REQUEST['id']);
		}

		$signatureSubscription = $GLOBALS['DB']->row("SELECT subscription_id FROM `registerusers_subscription` WHERE user_id = ? ",array($GLOBALS['USERID']));
		$GLOBALS['use_signature_buttons'] = '<a class="kt-btn kt-btn-primary w-full sm:w-auto" href="javascript:void(0);" onclick="CopyToClipboard("signature_source");"><img src="'.$GLOBALS['IMAGE_LINK'].'/images/copysignature.svg" alt="">Copy signature</a>
        		<a href="javascript:void(0);" id="copy_sorcecode" class="kt-btn border border-primary bg-white text-gray-500 w-full sm:w-auto"><img src="'.$GLOBALS['IMAGE_LINK'].'/images/copysourcecode.svg" alt="">Copy source code</a>';
		if($signatureSubscription){
			if($signatureSubscription['subscription_id'] == ""){
				$GLOBALS['use_signature_buttons'] = '<a class="kt-btn kt-btn-primary w-full sm:w-auto" href="'.$GLOBALS['ROOT_LINK'].'/pricing">Install Signature</a>';
			}
		}

		$this->getPage();
		$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/usesignature.html');	
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