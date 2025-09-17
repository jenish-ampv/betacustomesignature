<?php
require_once($GLOBALS['BASE_LINK'].'/'.GetConfig('CLASSES').'/integrations.php');
require_once($GLOBALS['BASE_LINK'].'/'.GetConfig('CLASSES').'/dashboard.php');
class CIT_AZUREAD
{
	
	public function __construct()
	{	
		if($GLOBALS['integration_settings'] != "integration_settings" || $GLOBALS['manage_signatures'] != "manage_signatures"){
			GetFrontRedirectUrl(GetUrl(array('module'=>'dashboard')));
		}
		$GLOBALS['integrations'] = GetClass('CIT_INTEGRATIONS');
		$GLOBALS['SIGNATURE'] = GetClass('CIT_DASHBOARD');
		if(isset($_REQUEST['department_id'])){
			$GLOBALS['current_department_id'] = $_REQUEST['department_id'];
		}else{
			$GLOBALS['current_department_id'] = 0;
		}

	}
	
	public function displayPage(){
		
		AddMessageInfo();
		$GLOBALS['success_popup'] =0;
		$GLOBALS['bulkerrorcls'] ='hidden'; 
		$GLOBALS['bulkuploadcls'] = 'hidden'; 

		if(isset($_REQUEST['category_id']) && $_REQUEST['category_id'] == 'copyCtaButton'){
			$this->copyCtaButton();
			exit();
		}

		if(isset($_REQUEST['category_id']) && $_REQUEST['category_id'] == 'saveSignatureHtml'){
			$this->saveSignatureHtml();
			exit();
		}
		
		if($_REQUEST['category_id'] == 'thanks'){
			$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/thanks-import.html');	
			$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');			
			$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');
			// $GLOBALS['SIDEBAR'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.sidebar.html');
			$GLOBALS['CLA_HTML']->display();
			exit;
		}
		
		$getattRow = $GLOBALS['DB']->row("SELECT * FROM registerusers_token WHERE token_platform = 0 AND user_id = ?",array($GLOBALS['USERID']));
		if($getattRow['user_id'] == "" && $getattRow['api_username'] ==""){
			GetFrontRedirectUrl(GetUrl(array('module'=>'integrations'))); exit;
		}
	/*$getgroups =  $GLOBALS['integrations']->getLastUpdatedUserData('',$getattRow['api_username'],$getattRow['api_password'],$getattRow['api_uniqid']);
		echo '<pre>';
		
		echo count($getgroups['value']);
		print_r($getgroups['value']); exit;*/
		
		$conuRow = $GLOBALS['DB']->row("SELECT count(signature_id) as mastersig FROM signature WHERE signature_master =1 AND user_id = ?",array($GLOBALS['USERID']));

		if(isset($GLOBALS['current_department_id']) &&  $GLOBALS['current_department_id'] != '0'){
			$conuRow = $GLOBALS['DB']->row("SELECT count(signature_id) as mastersig FROM signature WHERE signature_master =1 AND user_id = ? AND department_id =? " ,array($GLOBALS['USERID'],$GLOBALS['current_department_id']));
		}
		
		if($conuRow['mastersig'] > 0){
			$GLOBALS['bulkuploadcls'] = '';
		}else{
			$GLOBALS['bulkerrorcls'] ='';
			$_SESSION[GetSession('Error')] = '<div id="error-msg" class="alert alert-danger">Please select Master Signature for Import Signature.</div>';
			GetFrontRedirectUrl(GetUrl(array('module'=>'dashboard'))."?department_id=".$GLOBALS['current_department_id']); exit;
		}

		$GLOBALS['azurestep1'] ='hidden';
		$GLOBALS['azurestep2'] ='hidden';
		$GLOBALS['azurestep3'] ='hidden';
		
			if($_POST['forcetoupdate'] == 1){
				$members =  $GLOBALS['integrations']->getLastUpdatedUserData('',$getattRow['api_username'],$getattRow['api_password'],$getattRow['api_uniqid']);
				if($members['error'] == 0){
					$successCount = 0;
					foreach($members['users'] as $member){
						$refSigRow = $GLOBALS['DB']->row("SELECT * FROM signature WHERE signature_importid=? AND user_id= ?",array($member['id'],$GLOBALS['USERID']));
						if($refSigRow['signature_importid']== $member['id'] && $member['id'] != ""){
							$maping_fields = unserialize($getattRow['maping_field']);
							$mapper_field = $maping_fields['personal_field']; 
							$mapcustom_field = $maping_fields['contact_field']; 
							$import_profile = $maping_fields['import_profile'];

							foreach($mapper_field as $key => $perfield){ // check mapping field
								if($perfield == 'businessPhones'){
									${$key} = $member[$perfield][0];
								}else{
									${$key} =  $perfield == 'master' ? $refSigRow[$key] : $member[$perfield]; 
								}
							}
							
							if($import_profile == 1){
									$res = $GLOBALS['integrations']->getUserProfilePhoto($member['id']);
									if($res){ 
										$filename = time().'-'.$GLOBALS['USERID'].rand(1111,9999).'.jpg';
										$location =  GetConfig('SITE_UPLOAD_PATH').'/signature/profile/'.$filename ;
										file_put_contents($location,$res);
										$signature_profile = $filename;
									}else{
										$signature_profile = $refSigRow['signature_profile'];
									}
								}
							
							$data = array('signature_profile'=>$signature_profile,'signature_firstname'=>$signature_firstname,'signature_company'=>$signature_company,'signature_jobtitle'=>$signature_jobtitle,'last_forced_updated'=>date('Y-m-d h:i:s'));
							$where = array('signature_importid'=>$refSigRow['signature_importid'], 'user_id' => $GLOBALS['USERID']);
							$updateSignature = $GLOBALS['DB']->update("signature",$data,$where);
							if($updateSignature > 0){
								 $customfieldRows = $GLOBALS['DB']->query("SELECT * FROM `signature_customfield` WHERE `signature_id` = ?",array($refSigRow['signature_id']));
								$fcount = 0; $fieldtype_arr =array();
								$emailc=1; $phonec=1; $textc=1; $faxc=1; $websitec=1; $addressc=1; $hyperlinkc=1; $disclaimerc=1;
								 foreach($customfieldRows as $key =>$fieldvalue){
									 if(in_array($fieldvalue['field_type'],$fieldtype_arr)){
										 ${$fieldvalue['field_type'].'c'}++;
									 }
									 
									 $mapfield = $fieldvalue['field_type'].${$fieldvalue['field_type'].'c'};
									 if($mapcustom_field[$mapfield] == 'businessPhones'){
										 $fieldval = $mapcustom_field[$mapfield] == 'master' ? $fieldvalue['field_value'] : $member[$mapcustom_field[$mapfield]][0];
										}else{
									 $fieldval = $mapcustom_field[$mapfield] == 'master' ? $fieldvalue['field_value'] : $member[$mapcustom_field[$mapfield]];
										}

										$fielddata = array("field_value"=>$fieldval);
										$cfwhere = array('field_id'=>$fieldvalue['field_id']);
										$GLOBALS['DB']->update("signature_customfield",$fielddata,$cfwhere);

									 $fieldtype_arr[] = $fieldvalue['field_type'];
								 }
							}
							$successCount++;	
						}
							
					}
					if ($successCount > 0) {
						echo json_encode(array('error'=>0,'msg'=>'Signatures has been sync successfully.')); exit;
					}
				}else{
					echo json_encode(array('error'=>0,'msg'=>'Azure ad not connect try again')); exit;
				}
				
				echo json_encode(array('error'=>0,'msg'=>'No record found')); exit;
				  
			}
			
			  // STEP2
			 if($_REQUEST['step'] == 2){
				  if($_POST['azuread_group'] == 1){
						$members = $_POST['members'];
						$memberemails = $_POST['mail'];
						if(count($members)  > $GLOBALS['total_sigleftlimit']){  // check signature limit
						$_SESSION[GetSession('Error')] = '<div class="alert alert-danger">you have exceeded signature limit please upgrade your plan.</div>';
							 $redurl = GetUrl(array('module'=>$_REQUEST['module'])).'?step=2&department_id='.$GLOBALS['current_department_id'];
							 GetFrontRedirectUrl($redurl); exit;
						}
						if(count($members) > 0){
							
							$totalimport_count =0;
							$maping_fields = unserialize($getattRow['maping_field']);
							$mapper_field = $maping_fields['personal_field']; 
							$mapcustom_field = $maping_fields['contact_field']; 
							$import_profile = $maping_fields['import_profile']; 
							
							// get master signature data
							$refSigRow = $GLOBALS['DB']->row("SELECT * FROM signature WHERE signature_master=1 AND user_id= ?",array($GLOBALS['USERID']));
							$user_id = $refSigRow['user_id'];
							$layout_id = $refSigRow['layout_id'];
							$signature_style = $refSigRow['signature_style'];
							$signature_btndesign = $refSigRow['signature_btndesign'];
							$signature_marketbtndesign = $refSigRow['signature_marketbtndesign'];
							$signature_socialanimation = $refSigRow['signature_socialanimation'];
							$signature_custombtnanimation = $refSigRow['signature_custombtnanimation'];
							$signature_marketbtnanimation = $refSigRow['signature_marketbtnanimation'];
							$signature_status = $refSigRow['signature_status'];
							$signature_custombtn = $refSigRow['signature_custombtn'];
							$signature_custombtnlink = $refSigRow['signature_custombtnlink'];
							$signature_custombtntext = $refSigRow['signature_custombtntext'];
							$signature_link = $refSigRow['signature_link'];
							
							foreach($members as $pkey=>$member){
								
								if($totalimport_count  > $GLOBALS['total_sigleftlimit']){  // check signature limit
									$reachlimitmsg = 'but some signature not import you reach signautre limit.';
									break;
								}
								
								foreach($mapper_field as $key => $perfield){ // check mapping field
									${$key} =  $perfield == 'master' ? $refSigRow[$key] : $_POST[$perfield][$pkey]; 
								}
								
								// upload profile picture
								if($import_profile == 1){
									$res = $GLOBALS['integrations']->getUserProfilePhoto($member);
									if($res){ 
										$filename = time().'-'.$GLOBALS['USERID'].rand(1111,9999).'.jpg';
										$location =  GetConfig('SITE_UPLOAD_PATH').'/signature/profile/'.$filename ;
										file_put_contents($location,$res);
										$signature_profile = $filename;
										$result = $GLOBALS['S3Client']->putObject(array( // upload image s3bucket
											'Bucket'=>$GLOBALS['BUCKETNAME'],
											'Key' =>  'upload-beta/signature/profile/'.$signature_profile,
											'SourceFile' => $location,
											'StorageClass' => 'REDUCED_REDUNDANCY',
											'ACL'   => 'public-read'
										));
									}else{
										$signature_profile = '';
									}
								}
								
								$data =array('user_id'=>$GLOBALS['USERID'],'layout_id'=>$refSigRow['layout_id'],'signature_profile'=>$signature_profile,'signature_firstname'=>$signature_firstname,'signature_company'=>$signature_company,'signature_jobtitle'=>$signature_jobtitle,'signature_socialdesign'=>$refSigRow['signature_socialdesign'],'signature_btndesign'=>$refSigRow['signature_btndesign'],'signature_custombtn'=>$refSigRow['signature_custombtn'],'signature_custombtntext'=>$refSigRow['signature_custombtntext'], 'signature_custombtnlink'=>$refSigRow['signature_custombtnlink'],'signature_web'=>$refSigRow['signature_web'],'signature_facebook'=>$refSigRow['signature_facebook'], 'signature_insta'=>$refSigRow['signature_insta'],'signature_google'=>$refSigRow['signature_google'],'signature_youtube'=>$refSigRow['signature_youtube'],'signature_linkedin'=>$refSigRow['signature_linkedin'],'signature_pintrest'=>$refSigRow['signature_pintrest'],'signature_twitter'=>$refSigRow['signature_twitter'],'signature_clendly'=>$refSigRow['signature_clendly'],'signature_ebay'=>$refSigRow['signature_ebay'],'signature_imbd'=>$refSigRow['signature_imbd'],'signature_tiktok'=>$refSigRow['signature_tiktok'],'signature_vimeo'=>$refSigRow['signature_vimeo'],'signature_yelp'=>$refSigRow['signature_yelp'],'signature_zillow'=>$refSigRow['signature_zillow'],'signature_snapchat'=>$refSigRow['signature_snapchat'],'signature_reddit'=>$refSigRow['signature_reddit'],'signature_wechat'=>$refSigRow['signature_wechat'],'signature_airbnb'=>$refSigRow['signature_airbnb'],'signature_amazon'=>$refSigRow['signature_amazon'],'signature_discord'=>$refSigRow['signature_discord'],'signature_spotify'=>$refSigRow['signature_spotify'],'signature_apple'=>$refSigRow['signature_apple'],'signature_whatsapp'=>$refSigRow['signature_whatsapp'],'signature_shopify'=>$refSigRow['signature_shopify'],'signature_threads'=>$refSigRow['signature_threads'],'signature_venmo'=>$refSigRow['signature_venmo'],'signature_zelle'=>$refSigRow['signature_zelle'],'signature_link'=>$refSigRow['signature_link'],'signature_banner'=>$refSigRow['signature_banner'],'signature_bannerlink'=>$refSigRow['signature_bannerlink'],'signature_ctabtnname1'=>$refSigRow['signature_ctabtnname1'],'signature_ctabtnlink1'=>$refSigRow['signature_ctabtnlink1'],'signature_ctabtnname2'=>$refSigRow['signature_ctabtnname2'],'signature_ctabtnlink2'=>$refSigRow['signature_ctabtnlink2'],'signature_ctabtnname3'=>$refSigRow['signature_ctabtnname3'],'signature_ctabtnlink3'=>$refSigRow['signature_ctabtnlink3'],'signature_style'=>$signature_style,'signature_appstorebtn'=>$refSigRow['signature_appstorebtn'],'signature_playstorebtn'=>$refSigRow['signature_playstorebtn'],'signature_amazonbtn'=>$refSigRow['signature_amazonbtn'],'signature_ebaybtn'=>$refSigRow['signature_ebaybtn'],'signature_socialanimation'=>$refSigRow['signature_socialanimation'],'signature_custombtnanimation'=>$refSigRow['signature_custombtnanimation'],'signature_marketbtnanimation'=>$refSigRow['signature_marketbtnanimation'],'signature_importid'=>$member,'signature_importplatform'=>1,'signature_import_email'=>$memberemails[$pkey]);
								if($_REQUEST['department_id']){
									$data['department_id'] = $_REQUEST['department_id'];
								}
								 $addSignature = $GLOBALS['DB']->insert("signature",$data);
								  if($addSignature > 0){
									   $customfieldRows = $GLOBALS['DB']->query("SELECT * FROM `signature_customfield` WHERE `signature_id` = ?",array($refSigRow['signature_id']));
										$fcount = 0; $fieldtype_arr =array();
										$emailc=1; $phonec=1; $textc=1; $faxc=1; $websitec=1; $addressc=1; $hyperlinkc=1; $disclaimerc=1;
										 foreach($customfieldRows as $key =>$fieldvalue){
											 if(in_array($fieldvalue['field_type'],$fieldtype_arr)){
												 ${$fieldvalue['field_type'].'c'}++;
											 }
											 
											 $mapfield = $fieldvalue['field_type'].${$fieldvalue['field_type'].'c'};
											 $fieldval = $mapcustom_field[$mapfield] == 'master' ? $fieldvalue['field_value'] : $_POST[$mapcustom_field[$mapfield]][$pkey];
											 if(trim($fieldval) != ""){
												$fielddata = array("signature_id"=>$addSignature,"field_type"=>$fieldvalue['field_type'],"field_label"=>$fieldvalue['field_label'],"field_value"=>$fieldval,"field_fontsize"=>$fieldvalue['field_fontsize'],"field_fontweight"=>$fieldvalue['field_fontweight'],"field_fontstyle"=>$fieldvalue['field_fontstyle'],"field_color"=>$fieldvalue['field_color'],"field_order"=>$fieldvalue['field_order']);
								$GLOBALS['DB']->insert("signature_customfield",$fielddata);
											 }
											 $fieldtype_arr[] = $fieldvalue['field_type'];
										 }
										 $import_item[] = $addSignature; 
								  }
								$totalimport_count++;
							}
							
							$_SESSION['import_items'] =  $import_item;

							

							if(isset($_SESSION['import_items'])){

								// save signature html only if outlook integration connected and auto save enabled
								$msconnectedRow = $GLOBALS['DB']->row("SELECT * FROM registerusers_token WHERE user_id = ? AND token_platform = 0",array($GLOBALS['USERID']));
								if($msconnectedRow['auto_update']){
									// manage import process queue with DB
									foreach ($_SESSION['import_items'] as $key => $signature_id) {
										$data = array('user_id' => $GLOBALS['USERID'], 'signature_id' => $signature_id, 'platform' => '0', 'status' => '0'); // platform 0 = ms outlook & status 0 = pending
										$addSignature = $GLOBALS['DB']->insert("signature_import_process_data",$data);
									}
									// manage import process queue with DB
									// $this->previewImportedSignatureToSaveHtml();
									$_SESSION[GetSession('Success')] = '<div class="alert alert-success">The signature has been created successfully.</div>';
									$redirect = GetUrl(array('module'=>'dashboard'))."?department_id=".$GLOBALS['current_department_id'];
									GetFrontRedirectUrl($redirect); exit;
								}
								else{
									$_SESSION[GetSession('Success')] = '<div class="alert alert-success">The signature has been created successfully.</div>';
									//$redirect = GetUrl(array('module'=>'import')).'?success=1';
									$redirect = GetUrl(array('module'=>'azuread','category_id'=>'thanks'));
									GetFrontRedirectUrl($redirect); exit;
								}
							}else{
								$_SESSION[GetSession('Error')] = '<div class="alert alert-danger">user not found</div>';
								$redirect = GetUrl(array('module'=>$_REQUEST['module'])).'?step=2&department_id='.$GLOBALS['current_department_id'];
								GetFrontRedirectUrl($redirect); exit;
							}
						}else{
							 $_SESSION[GetSession('Error')] = '<div class="alert alert-danger">Please select group or member.</div>';
							 $redurl = GetUrl(array('module'=>$_REQUEST['module'])).'?step=2&department_id='.$GLOBALS['current_department_id'];
							 GetFrontRedirectUrl($redurl); exit;
					    }
						
				  }
				$GLOBALS['azurestep2'] ='';
				$getgroups =  $GLOBALS['integrations']->getUsergroupGraph('',$getattRow['api_username'],$getattRow['api_password'],$getattRow['api_uniqid']);
				if($getgroups['error'] == 0){
					$GLOBALS['group_list'] ='';
					 $mem_count = 0;
					foreach($getgroups['groups'] as $group){
						$GLOBALS['group_list'] .='  <div data-kt-accordion-item="true" class="kt-accordion-item group relative" aria-expanded="false">
							<div class="flex items-center gap-2 p-3 group-[.active]:bg-primary/10">
								<input class="kt-checkbox master_checkbox" type="checkbox" name="adgroups[]" id="'.$group['id'].'" value="'.$group['id'].'" data-wrapper ="wrapper-'.$group['id'].'">
								<label class="kt-label" for="'.$group['id'].'">'.$group['displayName'].'</label>
							</div>
							<div class="size-[45px] cursor-pointer hover:bg-gray-100 flex items-center justify-center absolute right-0 top-0" data-kt-accordion-toggle="true" aria-controls="accordion_content_'.$group['id'].'" id="accordion_toggle_'.$group['id'].'">
								<i  class="hgi hgi-stroke hgi-arrow-right-01 text-xl"></i>
							</div>

							<div class="kt-accordion-content hidden" aria-labelledby="accordion_toggle_'.$group['id'].'" id="accordion_content_'.$group['id'].'">
							<div class="py-3 pl-5 pb-3 space-y-2" id="wrapper-'.$group['id'].'">';
							$GLOBALS['search_list'] .='<div class="accordion-body-search" id="swrapper-'.$group['id'].'">';
							  	$azureadusers = $GLOBALS['integrations']->getUserGraph($group['id']);
								if(!count($azureadusers['users'])){
									$GLOBALS['group_list'] .='<p class="text-gray-400">No User Found</p>';
								}
								foreach($azureadusers['users'] as  $members){
									if($members['mail'] != ""){
										$GLOBALS['group_list'] .='
										<div class="member_list">
											<div class="member_chek flex flex-wrap items-center gap-2">
												<input class="kt-checkbox mem_checkbox" type="checkbox" name="members['.$mem_count.']" id="'.$members['id'].'-'.$group['id'].'" value="'.$members['id'].'"  data-wrapper="wrapper-'.$group['id'].'" data-master="'.$group['id'].'">
												<label class="kt-label" for="'.$members['id'].'-'.$group['id'].'">'.$members['displayName'].'<span class="ml-2 text-gray-500 font-normal">('.$members['mail'].')</span></label>
											</div>
											<input type="hidden" name="displayName[]" value="'.$members['displayName'].'" >
											<input type="hidden" name="givenName[]" value="'.$members['givenName'].'" >
											<input type="hidden" name="surname[]" value="'.$members['surname'].'" >
											<input type="hidden" name="jobTitle[]" value="'.$members['jobTitle'].'" >
											<input type="hidden" name="mail[]" value="'.$members['mail'].'" >
											<input type="hidden" name="mobilePhone[]" value="'.$members['mobilePhone'].'" >
											<input type="hidden" name="officeLocation[]" value="'.$members['officeLocation'].'" >
											<input type="hidden" name="businessPhones[]" value="'.$members['businessPhones'][0].'" >
											<input type="hidden" name="faxNumber[]" value="'.$members['faxNumber'].'" >
											<input type="hidden" name="department[]" value="'.$members['department'].'" >
											<input type="hidden" name="companyName[]" value="'.$members['companyName'].'" >
											<input type="hidden" name="streetAddress[]" value="'.$members['streetAddress'].'" >
											<input type="hidden" name="city[]" value="'.$members['city'].'" >
											<input type="hidden" name="state[]" value="'.$members['state'].'" >
											<input type="hidden" name="country[]" value="'.$members['country'].'" >
											<input type="hidden" name="postalCode[]" value="'.$members['postalCode'].'">
										</div>';
					
										$GLOBALS['search_list'] .='
										<div class="member_list search-container" style="display:none;">
											<div class="flex items-center gap-2">
												<input class="kt-checkbox mem_search_checkbox" type="checkbox" name="" id="search_'.$members['id'].'-'.$group['id'].'" value=""  data-master="'.$group['id'].'">
												<label class="kt-label" for="search_'.$members['id'].'-'.$group['id'].'">'.$members['displayName'].'('.$members['mail'].')</label>
											</div>
										</div>';
										$mem_count++;
								 	}
								} 
	  						$GLOBALS['group_list'] .='</div></div></div>';
							$GLOBALS['search_list'] .='</div>';
							$GLOBALS['memberr_selected']= $mem_count;
					}
				}else{
						$_SESSION[GetSession('Error')] = '<div class="alert alert-danger">'.$getgroups['msg'].'.</div>';
				}
				 
				 
			 }else if($_REQUEST['step'] == 3){
				 $GLOBALS['azurestep3'] ='';
			 }else{
				 $GLOBALS['azurestep1'] ='';
			 }
			
			//STEP1 FIELD MAPIING 
			if($_POST['azuread_savefield'] == 1 && isset($_POST['fieldp']) && isset($_POST['dirattrp'])){
				
				$personal_field = $_POST['fieldp'];
				$contact_field = $_POST['fieldc'];
				$import_profile = $_POST['import_profile'] == 1 ? 1 : 0;
				
				
				$fc =0;
				foreach($personal_field as $fieldp){
					$newfieldp[$fieldp] =  $_POST['dirattrp'][$fc]; 
				$fc++; }
			
				
				$fc =0; $emailc =1; $phonec=1; $textc=1; $faxc=1; $websitec=1; $addressc =1; $hyperlinkc=1; $disclaimerc =1; $newfieldc=array();
				foreach($contact_field as $fieldc){
					$chk = $fieldc.${$fieldc.'c'};
					if(array_key_exists($chk,$newfieldc)){
							${$fieldc.'c'} ++;
						}
					$newfieldc[$fieldc.${$fieldc.'c'}] =  $_POST['dirattrc'][$fc]; 
				$fc++; }
				
				
				$fielddata = array('personal_field'=>$newfieldp,'contact_field'=>$newfieldc,'import_profile'=>$import_profile);
				$fielddata = serialize($fielddata);
				$update = $GLOBALS['DB']->update('registerusers_token',array('maping_field'=>$fielddata),array('user_id'=>$GLOBALS['USERID'],'token_platform'=>0));
				$redurl = GetUrl(array('module'=>$_REQUEST['module'])).'?step=2&department_id='.$GLOBALS['current_department_id'];
				GetFrontRedirectUrl($redurl); exit;
			}
			
			
			$data = $GLOBALS['DB']->row("SELECT count(signature_id) as no,signature_id,signature_firstname,signature_company,signature_jobtitle FROM signature WHERE signature_master= 1 AND user_id =?",array($GLOBALS['USERID']));
			$GLOBALS['master_fieldp']= ''; $GLOBALS['master_fieldc']= '';  $GLOBALS['dirattr_fieldp']= ''; $GLOBALS['dirattr_fieldc']= '';
			
			$signature_firstnamedisplayName = 'selected';
			$signature_jobtitlejobTitle ='selected';
			$signature_companycompanyName ='selected';
			$email1mail = 'selected';
			$phone1mobilePhone = 'selected';
			$phone2businessPhones = 'selected';
			$fax1faxNumber = 'selected';
			$address1streetAddress = 'selected';
			
			if($getattRow['maping_field']){
				$selattfield = unserialize($getattRow['maping_field']);
				foreach($selattfield['personal_field'] as $key=>$selperatt){
					${$key.$selperatt} = 'selected';
				}
				
				foreach($selattfield['contact_field'] as $key=>$selperatt){
					${$key.$selperatt} = 'selected';
				}
				$GLOBALS['import_profilesel'] = $selattfield['import_profile'] == 1 ? 'checked' : '';
			}else{
				$GLOBALS['import_profilesel'] = 'checked';
			}
			$mfield = 0;
			if($data['signature_firstname'] != ""){
				$GLOBALS['master_fieldp'] .='<div>
						<label class="kt-label">Full Name</label>
						<input type="text" class="kt-input"  placeholder=""  value="'.$data['signature_firstname'].'" disabled="disabled">
					</div>';
				$GLOBALS['dirattr_fieldp'] .='<div>
					<label class="kt-label">Full Name</label>
					<select class="kt-select" name="dirattrp[]">
						<option value="master" '.$signature_firstnamemaster.'>Take from Master Signature </option>
						<option value="displayName" '.$signature_firstnamedisplayName.'>Display Name</option>
						<option value="givenName" '.$signature_firstnamegivenName.'>Given Name</option>
						<option value="surname" '.$signature_firstnamesurname.'>Surname</option>
						<option value="jobTitle" '.$signature_firstnamejobTitle.'>job Title</option>
						<option value="mail" '.$signature_firstnamemail.'>Mail</option>
						<option value="mobilePhone" '.$signature_firstnamemobilePhone.'>Mobile Phone</option>
						<option value="businessPhones" '.$signature_firstnamebusinessPhones.'>Office Phones</option>
						<option value="faxNumber" '.$signature_firstnamefaxNumber.'>Fax Number</option>
						<option value="companyName" '.$signature_firstnamecompanyName.'>Company Name</option>
						<option value="department" '.$signature_firstnamedepartment.'>Department</option>
						<option value="officeLocation" '.$signature_firstnameofficeLocation.'>Office Location</option>
						<option value="streetAddress" '.$signature_firstnamestreetAddress.'>Street Address</option>
						<option value="postalCode" '.$signature_firstnamepostalCode.'>Zipcode</option>
						<option value="city" '.$signature_firstnamecity.'>City</option>
						<option value="state" '.$signature_firstnamestate.'>State</option>
						<option value="country" '.$signature_firstnamecountry.'>Country</option>
					</select>
					<input type="hidden" name="fieldp[]" value="signature_firstname" />
				</div>';
				$mfield++;
			}
			if($data['signature_jobtitle'] != ""){
				$GLOBALS['master_fieldp'] .='<div>
							<label class>Title / Sub Title</label>
  							<input type="text" class="kt-input" placeholder="" value="'.$data['signature_jobtitle'].'" disabled="disabled">
						</div>';
				$GLOBALS['dirattr_fieldp'] .='<div>
								<label class="kt-label">Title / Sub Title</label>
                                <select class="kt-select" name="dirattrp[]">
                                    <option value="master" '.$signature_jobtitlemaster.'>Take from Master Signature </option>
                                    <option value="displayName" '.$signature_jobtitledisplayName.'>Display Name</option>
                                    <option value="givenName" '.$signature_jobtitlegivenName.'>Given Name</option>
									<option value="surname" '.$signature_jobtitlesurname.'>Surname</option>
                                    <option value="jobTitle" '.$signature_jobtitlejobTitle.'>Job Title</option>
                                    <option value="mail" '.$signature_jobtitlemail.'>Mail</option>
                                    <option value="mobilePhone" '.$signature_jobtitlemobilePhone.'>Mobile Phone</option>
									<option value="businessPhones" '.$signature_jobtitlebusinessPhones.'>Office Phones</option>
								    <option value="faxNumber" '.$signature_jobtitlefaxNumber.'>Fax Number</option>
								    <option value="companyName" '.$signature_jobtitlecompanyName.'>Company Name</option>
								    <option value="department" '.$signature_jobtitledepartment.'>Department</option>
								    <option value="officeLocation" '.$signature_jobtitleofficeLocation.'>Office Location</option>
								    <option value="streetAddress" '.$signature_jobtitlestreetAddress.'>Street Address</option>
									<option value="postalCode" '.$signature_jobtitlepostalCode.'>Zipcode</option>
								    <option value="city" '.$signature_jobtitlecity.'>City</option>
								    <option value="state" '.$signature_jobtitlestate.'>State</option>
								    <option value="country" '.$signature_jobtitlecountry.'>Country</option>
                                </select>
                                <input type="hidden" name="fieldp[]" value="signature_jobtitle" />
                            </div>';
				$mfield++;
			}
			if($data['signature_company'] !=""){
				$mfield++;
				$GLOBALS['master_fieldp'] .='<div>
							<label class="kt-label">Company Name</label>
							<input type="text" class="kt-input"  placeholder=""  value="'.$data['signature_company'].'" disabled="disabled">
						</div>';
				$GLOBALS['dirattr_fieldp'] .='<div>
								<label class="kt-label">Company Name</label>
                                <select class="kt-select" name="dirattrp[]">
                                   <option value="master" '.$signature_companymaster.'>Take from Master Signature </option>
                                    <option value="displayName" '.$signature_companydisplayName.'>Display Name</option>
                                    <option value="givenName" '.$signature_companygivenName.'>Given Name</option>
									<option value="surname" '.$signature_companysurname.'>Surname</option>
                                    <option value="jobTitle" '.$signature_companyjobTitle.'>job Title</option>
                                    <option value="mail" '.$signature_companymail.'>Mail</option>
                                    <option value="mobilePhone" '.$signature_companymobilePhone.'>Mobile Phone</option>
									<option value="businessPhones" '.$signature_companybusinessPhones.'>Office Phones</option>
								    <option value="faxNumber" '.$signature_companyfaxNumber.'>Fax Number</option>
								    <option value="companyName" '.$signature_companycompanyName.'>Company Name</option>
								    <option value="department" '.$signature_companydepartment.'>Department</option>
								    <option value="officeLocation" '.$signature_companyofficeLocation.'>Office Location</option>
								    <option value="streetAddress" '.$signature_companystreetAddress.'>Street Address</option>
									<option value="postalCode" '.$signature_companypostalCode.'>Zipcode</option>
								    <option value="city" '.$signature_companycity.'>City</option>
								    <option value="state" '.$signature_companystate.'>State</option>
								    <option value="country" '.$signature_companycountry.'>Country</option>
                                </select>
                                <input type="hidden" name="fieldp[]" value="signature_company" />
                            </div>';
			$mfield++;
			}
			
			
			if($data['no'] != ""){
				
				$customfields = $GLOBALS['DB']->query("SELECT `field_type`,`field_value` FROM `signature_customfield` WHERE `signature_id`=?",array($data['signature_id']));
				if(count($customfields) > 0){
					$fc =0; $emailc =1; $phonec=1; $textc=1; $faxc=1; $websitec=1; $addressc =1; $hyperlinkc=1; $disclaimerc =1; $newfieldc =array();
					foreach($customfields as $customfield){
						$field_label = ucwords($customfield['field_type']);
						$field_value = $customfield['field_value'];
						
						// check key exist than plus plus
						$fieldc = $customfield['field_type'];
						$chk = $fieldc.${$fieldc.'c'};
						if(array_key_exists($chk,$newfieldc)){
							${$fieldc.'c'} ++;
						}
						$newfieldc[$fieldc.${$fieldc.'c'}] = 1;
						$fieldname =    $fieldc.${$fieldc.'c'};
						
						$GLOBALS['master_fieldc'] .='<div>
						<label class="kt-label">'.$field_label.'</label>
						<input type="text" class="kt-input"  placeholder=""  value="'.$field_value.'" disabled="disabled">
						</div>';
						
						$GLOBALS['dirattr_fieldc'] .='<div>
								<label class="kt-label">'.$field_label.'</label>
                                <select class="kt-select" name="dirattrc[]">
                                    <option value="master" '.${$fieldname.'master'}.'>Take from Master Signature </option>
                                    <option value="displayName" '.${$fieldname.'displayName'}.'>Display Name</option>
									 <option value="surname" '.${$fieldname.'surname'}.'>Surname</option>
                                    <option value="givenName" '.${$fieldname.'givenName'}.'>Given Name</option>
                                    <option value="jobTitle" '.${$fieldname.'jobTitle'}.'>job Title</option>
                                    <option value="mail" '.${$fieldname.'mail'}.'>Mail</option>
                                    <option value="mobilePhone" '.${$fieldname.'mobilePhone'}.'>Mobile Phone</option>
									<option value="businessPhones" '.${$fieldname.'businessPhones'}.'>Office Phones</option>
								    <option value="faxNumber" '.${$fieldname.'faxNumber'}.'>Fax Number</option>
								    <option value="companyName" '.${$fieldname.'companyName'}.'>Company Name</option>
								    <option value="department" '.${$fieldname.'department'}.'>Department</option>
								    <option value="officeLocation" '.${$fieldname.'officeLocation'}.'>Office Location</option>
								    <option value="streetAddress" '.${$fieldname.'streetAddress'}.'>Street Address</option>
									<option value="postalCode" '.${$fieldname.'postalCode'}.'>Zipcode</option>
								    <option value="city" '.${$fieldname.'city'}.'>City</option>
								    <option value="state" '.${$fieldname.'state'}.'>State</option>
								    <option value="country" '.${$fieldname.'country'}.'>Country</option>
                                </select>
                                <input type="hidden" name="fieldc[]" value="'.$customfield['field_type'].'" />
                            </div>'; 
					 $fieldno++; $mfield++; }
				}

				unset($data['signature_id']); unset($data['no']); // remove from csv file

			
			}
			
		//$this->getPage();
		$GLOBALS['samplecsv_download'] =GetUrl(array('module'=>$_REQUEST['module'])).'?download=1';
		$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/azuread.html');	
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

	
	public function previewImportedSignatureToSaveHtml(){
		// $import_items = $_SESSION['import_items'];

		// take only pending signature from import process queue from db
		$import_items = $GLOBALS['DB']->query("SELECT * FROM signature_import_process_data WHERE `status`=0 AND user_id = ?",array($GLOBALS['USERID'])); //status 0 = pending
		$GLOBALS['total_imported_signature'] = sizeof($import_items);
		foreach ($import_items as $key => $import_item) {
			$signature_id = $import_item['signature_id'];
			$GLOBALS['signature_outlook_imported'] .= '<div class="signature_preview_container" data-signature-id="'.$signature_id.'">';
			$GLOBALS['signature_outlook_imported'] .= $GLOBALS['SIGNATURE']->getUserSignature($signature_id);
			$sigSaveHtmlUrl = GetUrl(array('module'=>$_REQUEST['module'],'category_id'=>'saveSignatureHtml','id'=>$signature_id));
			$GLOBALS['signature_outlook_imported'] .= '</div>';
			$GLOBALS['signature_outlook_imported'] .= '<input type="hidden" id="signature_save_html_url_'.$signature_id.'" value="'.$sigSaveHtmlUrl.'" />';
		}

		$GLOBALS['link_redirect'] = GetUrl(array('module'=>'dashboard'))."?department_id=".$GLOBALS['current_department_id'];
		$GLOBALS['link_copy_cta_btn_url'] = GetUrl(array('module'=>$_REQUEST['module'],'category_id'=>'copyCtaButton'));   // code is for saving signature html(to use in deploy) 
		$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/signaturepreview.html');
		$GLOBALS['CLA_HTML']->display();
	}

	public function saveSignatureHtml() {
		if($_POST['html']){
			$html = $_POST['html'];
			$signature_id = $_REQUEST['id'];
			if($signature_id){
				$data = array("outlook_html" => $html);
				$where = array('signature_id'=>$signature_id);
				$addSignature = $GLOBALS['DB']->update("signature",$data,$where);
				if($addSignature){
					// manage import process queue with DB
					$data = array('status' => 1); // status 1 = completed
					$where = array('signature_id'=>$signature_id);
					$updateImportQueueStatus = $GLOBALS['DB']->update("signature_import_process_data",$data,$where);
					// manage import process queue with DB

					$return_arrs = array('error'=>1,'msg'=>'Signature saved successfully');				
					// echo json_encode($return_arrs); exit;
				}else{
					$return_arrs = array('error'=>1,'msg'=>'Something went wrong while saving signature html');
					// echo json_encode($return_arrs); exit;
				}

			}
			else{
				$return_arrs = array('error'=>1,'msg'=>'Something went wrong with signature, please try again');
				// echo json_encode($return_arrs); exit;
			}
		}else{
			$return_arrs = array('error'=>1,'msg'=>'Something went wrong, please try again');
			// echo json_encode($return_arrs); exit;
		}
	}

	public function copyCtaButton(){

		if($_POST['copy_from_signature_id'] && $_POST['copy_to_signature_id']){
			$copy_from_signature_id = $_POST['copy_from_signature_id'];
			$copy_to_signature_id = $_POST['copy_to_signature_id'];

			// echo("--------------------------------");
			// echo $copy_from_signature_id;
			// echo("--------------------------------");
			// echo $copy_to_signature_id;
			// echo("--------------------------------");
			$userId = $GLOBALS['USERID'];
			$source = $GLOBALS['UPLOAD_LINK']."/htmltoimage/".$userId."/".$copy_from_signature_id."/";
			$destination = $GLOBALS['UPLOAD_LINK']."/htmltoimage/".$userId."/".$copy_to_signature_id."/";
			if (!is_dir($destination)) {
				mkdir($destination, 0755, true);
			}
			$files = scandir($source);
			foreach ($files as $file) {
				if ($file !== '.' && $file !== '..') {
					$sourceFile = $source . '/' . $file;
					$destinationFile = $destination . '/' . $file;
					if (is_dir($sourceFile)) {
						copyDirectory($sourceFile, $destinationFile);
					} else {
						copy($sourceFile, $destinationFile);
					}
				}
			}
			 
			$return_arrs = array('error'=>0,'signature_id'=>$copy_to_signature_id,'msg'=>'Cta Buttons copied successfully');
			echo json_encode($return_arrs);
		}
		
	}

}

?>