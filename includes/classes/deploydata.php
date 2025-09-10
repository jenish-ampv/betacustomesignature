<?php
require_once($GLOBALS['BASE_LINK'].'/'.GetConfig('CLASSES').'/integrations.php');
class CIT_DEPLOYDATA
{
	
	public function __construct()
	{	
		if(isset($_REQUEST['department_id'])){
			$GLOBALS['current_department_id'] = $_REQUEST['department_id'];
		}else{
			$GLOBALS['current_department_id'] = 0;
		}
	}
	
	public function displayPage(){
		
		AddMessageInfo();
		$GLOBALS['success_popup'] =0;
		$GLOBALS['bulkerrorcls'] ='d-none'; 
		$GLOBALS['bulkuploadcls'] = 'd-none'; 
		$GLOBALS['azurestep2'] ='';
		if($_REQUEST['category_id'] == 'deployoutlooksubmit'){
			$this->deployoutlooksubmit();exit();
		}
		if($_REQUEST['action'] =='googledeploy' || $_REQUEST['category_id'] =='googledeploy'){
			$this->deploygmail();exit();
		}
		$conuRow = $GLOBALS['DB']->row("SELECT count(signature_id) as mastersig FROM signature WHERE signature_master =1 AND user_id = ?",array($GLOBALS['USERID']));
		// echo '<pre>'; print_r($conuRow); echo '</pre>'; exit('<br>pre exit');
		if($conuRow['mastersig'] > 0){
			$GLOBALS['bulkuploadcls'] = '';
		}else{
			$GLOBALS['bulkerrorcls'] ='';
			$_SESSION[GetSession('Error')] = '<div class="alert alert-danger">Please select Master Signature for Deploy.</div>';
			GetFrontRedirectUrl(GetUrl(array('module'=>'dashboard'))); exit;
		}
		
		$getattRow = $GLOBALS['DB']->row("SELECT * FROM registerusers_token WHERE token_platform = 0 AND user_id = ?",array($GLOBALS['USERID']));

		// $getgroupsALL =  $GLOBALS['integrations']->getUsergroupGraph('',$getattRow['api_username'],$getattRow['api_password'],$getattRow['api_uniqid']);

		if($GLOBALS['current_department_id'] != '0'){
			$getavailableGroups = $GLOBALS['DB']->query("SELECT * FROM `signature` WHERE `signature_import_email`!='' AND `user_id` = ? AND `department_id` = ? AND `signature_importplatform`='1' ORDER BY `signature_id` DESC",array($GLOBALS['USERID'],$GLOBALS['current_department_id']));

		}else{
			$getavailableGroups = $GLOBALS['DB']->query("SELECT * FROM `signature` WHERE `signature_import_email`!='' AND `user_id` = ? AND `signature_importplatform`='1' ORDER BY `signature_id` DESC",array($GLOBALS['USERID']));
		}

		$checkForDuplicateEmails = [];
		$signatureIdsForLastUpdateData = [];
		foreach ($getavailableGroups as $key => $data) {
			if(!in_array($data['signature_import_email'], $checkForDuplicateEmails)){
				$checkForDuplicateEmails[] = $data['signature_import_email'];
				$signatureIdsForLastUpdateData[] = $data['signature_id'];
			}else{
				unset($getavailableGroups[$key]);
			}
		}

		$signatureIdsForLastUpdateDataSTR = implode(',',$signatureIdsForLastUpdateData);
		$lastUpdatedData = $GLOBALS['DB']->row("SELECT MAX(`last_forced_updated`) as last_updated FROM `signature` WHERE `signature_id` IN (?)",array($signatureIdsForLastUpdateDataSTR));
		$differenceHours = $this->differenceInHours($lastUpdatedData['last_updated'],date('Y-m-d h:i:s'));
		$GLOBALS['outlook_signature_data_last_updated_message'] = "";
		if($lastUpdatedData['last_updated'] == '0000-00-00 00:00:00'){
			$GLOBALS['outlook_signature_data_last_updated_message'] = "You haven't updated data after you imported signatures from outlook.";
		}
		elseif($differenceHours){
			if($differenceHours < 1){
				$GLOBALS['outlook_signature_data_last_updated_message'] = 'The data was last updated less than an hour ago.';
			}else{
				$hours = floor($differenceHours);
				if($hours < 24) {
					$GLOBALS['outlook_signature_data_last_updated_message'] = 'The data was last updated more than '.$hours.' hours ago.';
				}else{
					$days = floor($hours/24);
					if($days < 7){
						$GLOBALS['outlook_signature_data_last_updated_message'] = 'The data was last updated more than '.$days.' days ago.';
					}else{
						$weeks = floor($days/7);
						if($weeks < 5){
							$GLOBALS['outlook_signature_data_last_updated_message'] = 'The data was last updated more than '.$weeks.' weeks ago.';
						}else{
							$months = floor($weeks/4);
							if($months < 13){
								$GLOBALS['outlook_signature_data_last_updated_message'] = 'The data was last updated more than '.$months.' months ago.';
							}else{
								$years = floor($months/12);
								$GLOBALS['outlook_signature_data_last_updated_message'] = 'The data was last updated more than '.$years.' years ago.';
							}
						}
					}
				}
			}
		}
		if($_POST['groupdeployoutlook_submit'] == 1){
			
			$updateSignatureIds = [];
			for ($i=0; $i <= sizeof($_POST); $i++) { 
				if(isset($_POST['members'.$i])){
					$updateSignatureIds[] = $_POST['members'.$i];
				}
			}
			if(!empty($updateSignatureIds)){
				foreach ($updateSignatureIds as $signatureId ) {
					$GLOBALS['DB']->query("UPDATE `signature` SET `is_deploy` = '1' WHERE `signature_id`=?",array($signatureId)); 
				}
				$_SESSION[GetSession('Success')] = '<div class="alert alert-success">Signatures deployed successfully.'.$implodedSTR.'</div><script>jQuery.noConflict();(function($) {$(document).ready(function(){setTimeout(function(){ $(".alert").hide(); }, 2000);});})(jQuery);</script>';
				$redirect = GetUrl(array('module'=>'dashboard'));
				GetFrontRedirectUrl($redirect); exit;
			}
		}
		$getgroups = [];
		if($getgroups['error'] == 0){
			$GLOBALS['group_list'] ='';
			$mem_count = 0;
			
			foreach($getavailableGroups as $group){
				$GLOBALS['group_list'] .='  <div class="accordion-item"><div class="accordion-body">';
				$GLOBALS['search_list'] .='<div class="accordion-body-search" id="swrapper-'.$group['signature_id'].'">';
				if($group['signature_import_email'] != ""){
						$GLOBALS['group_list'] .='<div class="member_list member_deploy"><span class="member_chek"><input class="form-check-input mem_checkbox" type="checkbox" name="members'.$mem_count.'" id="'.$group['signature_id'].'-'.$group['signature_id'].'" value="'.$group['signature_id'].'"  data-wrapper="wrapper-'.$group['signature_id'].'"
	data-master="'.$group['signature_id'].'"><label for="'.$group['signature_id'].'-'.$group['signature_id'].'">'.$group['signature_firstname'].'</label></span><span class="deploy_mail_label" style="margin-left: 10px;">'.$group['signature_import_email'].'</span><div class="deploy_mail" style="display:none;" value="'.$group['signature_import_email'].'"></div>';
	
					$GLOBALS['search_list'] .='<div class="member_list search-container" style="display:none;"><span class="member_chek"><input class="form-check-input mem_search_checkbox" type="checkbox" name="" id="search_'.$group['signature_id'].'-'.$group['signature_id'].'" value=""  data-master="'.$group['signature_id'].'"><label for="search_'.$group['signature_id'].'-'.$group['signature_id'].'">'.$group['signature_firstname'].'</label></span><span class="deploy_mail_label" style="margin-left: 10px;">'.$group['signature_import_email'].'</span>'.$group['mail'].'</div>';
					$mem_count++;
				}
				$GLOBALS['group_list'] .='</div></div>';
				$GLOBALS['search_list'] .='</div>';
				$GLOBALS['memberr_selected']= $mem_count;
			}
		}else{
			$_SESSION[GetSession('Error')] = '<div class="alert alert-danger">'.$getgroups['msg'].'.asdasd</div>';
		}

		if($GLOBALS['current_department_id'] == 0)
			$GLOBALS['backArrowlink'] = GetUrl(array('module'=>'dashboard'));
		else
			$GLOBALS['backArrowlink'] = GetUrl(array('module'=>'dashboard')).'?department_id='.$GLOBALS['current_department_id'];

		$GLOBALS['groupdeployoutlook_form_action'] = GetUrl(array('class'=>'deploydata','category_id'=>'deployoutlooksubmit'));
		$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/deploydata.html');	
		$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');			
		$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');
		$GLOBALS['integrations'] = GetUrl(array('module'=>'integrations'));
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

	function differenceInHours($startdate,$enddate){
		$starttimestamp = strtotime($startdate);
		$endtimestamp = strtotime($enddate);
		$difference = abs($endtimestamp - $starttimestamp)/3600;
		return $difference;
	}

	function deploygmail() {

		$getavailableGroups = $GLOBALS['DB']->query("SELECT * FROM `signature` WHERE `signature_import_email`!='' AND `signature_importplatform`='2' AND user_id = ? ORDER BY `signature_id` DESC",array($GLOBALS['USERID']));

		if($GLOBALS['current_department_id']){
			$getavailableGroups = $GLOBALS['DB']->query("SELECT * FROM `signature` WHERE `signature_import_email`!='' AND `signature_importplatform`='2' AND user_id = ? AND department_id = ? ORDER BY `signature_id` DESC",array($GLOBALS['USERID'],$GLOBALS['current_department_id']));
		}


		$checkForDuplicateEmails = [];
		$signatureIdsForLastUpdateData = [];
		foreach ($getavailableGroups as $key => $data) {
			if(!in_array($data['signature_import_email'], $checkForDuplicateEmails)){
				$checkForDuplicateEmails[] = $data['signature_import_email'];
				$signatureIdsForLastUpdateData[] = $data['signature_id'];
			}else{
				unset($getavailableGroups[$key]);
			}
		}

		$signatureIdsForLastUpdateDataSTR = implode(',',$signatureIdsForLastUpdateData);
		$lastUpdatedData = $GLOBALS['DB']->row("SELECT MAX(`last_forced_updated`) as last_updated FROM `signature` WHERE `signature_id` IN (?)",array($signatureIdsForLastUpdateDataSTR));
		$differenceHours = $this->differenceInHours($lastUpdatedData['last_updated'],date('Y-m-d h:i:s'));
		$GLOBALS['google_signature_data_last_updated_message'] = "";
		if($lastUpdatedData['last_updated'] == '0000-00-00 00:00:00'){
			$GLOBALS['google_signature_data_last_updated_message'] = "You haven't updated data after you imported signatures from gmail.";
		}
		elseif($differenceHours){
			if($differenceHours < 1){
				$GLOBALS['google_signature_data_last_updated_message'] = 'The data was last updated less than an hour ago.';
			}else{
				$hours = floor($differenceHours);
				if($hours < 24) {
					$GLOBALS['google_signature_data_last_updated_message'] = 'The data was last updated more than '.$hours.' hours ago.';
				}else{
					$days = floor($hours/24);
					if($days < 7){
						$GLOBALS['google_signature_data_last_updated_message'] = 'The data was last updated more than '.$days.' days ago.';
					}else{
						$weeks = floor($days/7);
						if($weeks < 5){
							$GLOBALS['google_signature_data_last_updated_message'] = 'The data was last updated more than '.$weeks.' weeks ago.';
						}else{
							$months = floor($weeks/4);
							if($months < 13){
								$GLOBALS['google_signature_data_last_updated_message'] = 'The data was last updated more than '.$months.' months ago.';
							}else{
								$years = floor($months/12);
								$GLOBALS['google_signature_data_last_updated_message'] = 'The data was last updated more than '.$years.' years ago.';
							}
						}
					}
				}
			}
		}
		if($_POST['groupdeploygoogle_submit'] == 1){
			
			$updateSignatureIds = [];
			for ($i=0; $i <= sizeof($_POST); $i++) { 
				if(isset($_POST['members'.$i])){
					$updateSignatureIds[] = $_POST['members'.$i];
				}
			}

			if(!empty($updateSignatureIds)){
				foreach ($updateSignatureIds as $signatureId ) {
					$GLOBALS['DB']->query("UPDATE `signature` SET `is_deploy` = '1' WHERE `signature_id`=?",array($signatureId)); 
					$url = 'https://betaapp.customesignature.com/lib/google-api/index.php?signature_id='.$signatureId;
				    $GLOBALS['ADMINUSEREMAIL'] = $GLOBALS['USEREMAIL'];
				    $GLOBALS['userEmail'] = 'team@customesignature.com';
				    $GLOBALS['signatureHtml'] = 'sabbas beta';
				    $response = file_get_contents($url);
				    // echo "<pre>"; print_r($response); 
				}
				// die;
				$_SESSION[GetSession('Success')] = '<div class="alert alert-success">Signatures deployed successfully.'.$implodedSTR.'</div><script>jQuery.noConflict();(function($) {$(document).ready(function(){setTimeout(function(){ $(".alert").hide(); }, 2000);});})(jQuery);</script>';
				$redirect = GetUrl(array('module'=>'dashboard'));
				GetFrontRedirectUrl($redirect); exit;
			}
		}
		$getgroups = [];
		if($getgroups['error'] == 0){
			$GLOBALS['group_list'] ='';
			$mem_count = 0;
			
			foreach($getavailableGroups as $group){
				$GLOBALS['group_list'] .='  <div class="accordion-item"><div class="accordion-body">';
				$GLOBALS['search_list'] .='<div class="accordion-body-search" id="swrapper-'.$group['signature_id'].'">';
				if($group['signature_import_email'] != ""){
						$GLOBALS['group_list'] .='<div class="member_list member_deploy"><span class="member_chek"><input class="form-check-input mem_checkbox" type="checkbox" name="members'.$mem_count.'" id="'.$group['signature_id'].'-'.$group['signature_id'].'" value="'.$group['signature_id'].'"  data-wrapper="wrapper-'.$group['signature_id'].'" data-master="'.$group['signature_id'].'"><label for="'.$group['signature_id'].'-'.$group['signature_id'].'">'.$group['signature_firstname'].'</label></span><span class="deploy_mail_label" style="margin-left: 10px;">'.$group['signature_import_email'].'</span><div class="deploy_mail" style="display:none;" value="'.$group['signature_import_email'].'"></div>';
	
					$GLOBALS['search_list'] .='<div class="member_list search-container" style="display:none;"><span class="member_chek"><input class="form-check-input mem_search_checkbox" type="checkbox" name="" id="search_'.$group['signature_id'].'-'.$group['signature_id'].'" value=""  data-master="'.$group['signature_id'].'"><label for="search_'.$group['signature_id'].'-'.$group['signature_id'].'">'.$group['signature_firstname'].'</label></span><span class="deploy_mail_label" style="margin-left: 10px;">'.$group['signature_import_email'].'</span>'.$group['mail'].'</div>';
					$mem_count++;
				}
				$GLOBALS['group_list'] .='</div></div>';
				$GLOBALS['search_list'] .='</div>';
				$GLOBALS['memberr_selected']= $mem_count;
			}
		}else{
			$_SESSION[GetSession('Error')] = '<div class="alert alert-danger">'.$getgroups['msg'].'.asdasd</div>';
		}



		$GLOBALS['groupdeploygmail_form_action'] = GetUrl(array('class'=>'deploydata','category_id'=>'deploygmailsubmit'));
		$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/deploydatagmail.html');	
		$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');			
		$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');
		$GLOBALS['integrations'] = GetUrl(array('module'=>'integrations'));
		$GLOBALS['SIDEBAR'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.sidebar.html');
		$GLOBALS['CLA_HTML']->display();
		RemoveMessageInfo();
		exit();	
	}
	
}

?>