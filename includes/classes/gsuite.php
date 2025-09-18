<?php
// ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);

require_once($GLOBALS['BASE_LINK'].'/'.GetConfig('CLASSES').'/integrations.php');
class CIT_GSUITE
{
	
	public function __construct()
	{	
		if($GLOBALS['integration_settings'] != "integration_settings" || $GLOBALS['manage_signatures'] != "manage_signatures"){
			GetFrontRedirectUrl(GetUrl(array('module'=>'dashboard')));
		}
		$GLOBALS['integrations'] = GetClass('CIT_INTEGRATIONS');

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
		
		$getattRow = $GLOBALS['DB']->row("SELECT * FROM registerusers_token WHERE token_platform = 1 AND user_id = ?",array($GLOBALS['USERID']));
		if($getattRow['user_id'] == "" && $getattRow['api_username'] ==""){
			GetFrontRedirectUrl(GetUrl(array('module'=>'integrations'))); exit;
		}
		$conuRow = $GLOBALS['DB']->row("SELECT count(signature_id) as mastersig FROM signature WHERE signature_master =1 AND user_id = ?",array($GLOBALS['USERID']));
		if($GLOBALS['current_department_id']){
			$conuRow = $GLOBALS['DB']->row("SELECT count(signature_id) as mastersig FROM signature WHERE signature_master =1 AND user_id = ? AND department_id = ?",array($GLOBALS['USERID'],$GLOBALS['current_department_id']));
		}
		if($conuRow['mastersig'] ==1){
			$GLOBALS['bulkuploadcls'] = '';
		}else{
			$GLOBALS['bulkerrorcls'] ='';
			$_SESSION[GetSession('Error')] = '<div id="error-msg" class="alert alert-danger">Please select Master Signature for Import Signature.</div>';
			GetFrontRedirectUrl(GetUrl(array('module'=>'dashboard'))."?department_id=".$GLOBALS['current_department_id']); exit;
		}

		$GLOBALS['azurestep1'] ='hidden';
		$GLOBALS['azurestep2'] ='hidden';
		$GLOBALS['azurestep3'] ='hidden';
			
			  // STEP2
			 if($_REQUEST['step'] == 2){
				 
				 if($_POST['gsuite_group'] == 1 && isset($_POST['addUsers'])){
					 if($_POST['addUsers'] !=""){
						 $addUsers = $_POST['addUsers'];
						 if(is_array($addUsers)){
							$maping_fields = unserialize($getattRow['maping_field']);
							$mapper_field = $maping_fields['personal_field']; 
							$mapcustom_field = $maping_fields['contact_field']; 
							$gsuiteusers = $GLOBALS['integrations']->GsuiteConnect('user',$GLOBALS['USEREMAIL']);
							if($azureadusers['error'] == 0 && is_array($gsuiteusers['data']['users']['users'])){
								
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
								
								foreach($gsuiteusers['data']['users']['users'] as $key =>$gsuiteuser){
										if(!in_array($gsuiteuser->primaryEmail,$addUsers)){ continue; } // check unit select or not
										//mapp personal field
										foreach($mapper_field as $key => $perfield){
											if(in_array($perfield,array('displayName','familyName','fullName','givenName'))){
												${$key} = $gsuiteuser->name->$perfield;
											}else{
												 ${$key} =  $perfield == 'master' ? $refSigRow[$key] : $gsuiteuser->$perfield; 
											}
										}
										$data =array('user_id'=>$GLOBALS['USERID'],'layout_id'=>$refSigRow['layout_id'],'signature_profile'=>$refSigRow['signature_profile'],'signature_firstname'=>$signature_firstname,'signature_company'=>$signature_company,'signature_jobtitle'=>$signature_jobtitle,'signature_socialdesign'=>$refSigRow['signature_socialdesign'],'signature_btndesign'=>$refSigRow['signature_btndesign'],'signature_custombtn'=>$refSigRow['signature_custombtn'],'signature_custombtntext'=>$refSigRow['signature_custombtntext'], 'signature_custombtnlink'=>$refSigRow['signature_custombtnlink'],'signature_web'=>$refSigRow['signature_web'],'signature_facebook'=>$refSigRow['signature_facebook'], 'signature_insta'=>$refSigRow['signature_insta'],'signature_google'=>$refSigRow['signature_google'],'signature_youtube'=>$refSigRow['signature_youtube'],'signature_linkedin'=>$refSigRow['signature_linkedin'],'signature_pintrest'=>$refSigRow['signature_pintrest'],'signature_twitter'=>$refSigRow['signature_twitter'],'signature_clendly'=>$refSigRow['signature_clendly'],'signature_ebay'=>$refSigRow['signature_ebay'],'signature_imbd'=>$refSigRow['signature_imbd'],'signature_tiktok'=>$refSigRow['signature_tiktok'],'signature_vimeo'=>$refSigRow['signature_vimeo'],'signature_yelp'=>$refSigRow['signature_yelp'],'signature_zillow'=>$refSigRow['signature_zillow'],'signature_snapchat'=>$refSigRow['signature_snapchat'],'signature_reddit'=>$refSigRow['signature_reddit'],'signature_wechat'=>$refSigRow['signature_wechat'],'signature_airbnb'=>$refSigRow['signature_airbnb'],'signature_amazon'=>$refSigRow['signature_amazon'],'signature_discord'=>$refSigRow['signature_discord'],'signature_spotify'=>$refSigRow['signature_spotify'],'signature_apple'=>$refSigRow['signature_apple'],'signature_whatsapp'=>$refSigRow['signature_whatsapp'],'signature_shopify'=>$refSigRow['signature_shopify'],'signature_threads'=>$refSigRow['signature_threads'],'signature_venmo'=>$refSigRow['signature_venmo'],'signature_zelle'=>$refSigRow['signature_zelle'],'signature_link'=>$refSigRow['signature_link'],'signature_banner'=>$refSigRow['signature_banner'],'signature_bannerlink'=>$refSigRow['signature_bannerlink'],'signature_ctabtnname1'=>$refSigRow['signature_ctabtnname1'],'signature_ctabtnlink1'=>$refSigRow['signature_ctabtnlink1'],'signature_ctabtnname2'=>$refSigRow['signature_ctabtnname2'],'signature_ctabtnlink2'=>$refSigRow['signature_ctabtnlink2'],'signature_ctabtnname3'=>$refSigRow['signature_ctabtnname3'],'signature_ctabtnlink3'=>$refSigRow['signature_ctabtnlink3'],'signature_style'=>$signature_style,'signature_appstorebtn'=>$refSigRow['signature_appstorebtn'],'signature_playstorebtn'=>$refSigRow['signature_playstorebtn'],'signature_amazonbtn'=>$refSigRow['signature_amazonbtn'],'signature_ebaybtn'=>$refSigRow['signature_ebaybtn'],'signature_socialanimation'=>$refSigRow['signature_socialanimation'],'signature_custombtnanimation'=>$refSigRow['signature_custombtnanimation'],'signature_marketbtnanimation'=>$refSigRow['signature_marketbtnanimation'],'signature_importplatform'=>'2','signature_import_email'=>$gsuiteuser->primaryEmail);
										if($GLOBALS['current_department_id']){
											$data['department_id'] = $GLOBALS['current_department_id'];
										}
										 $addSignature = $GLOBALS['DB']->insert("signature",$data);
										 $data = array('user_id' => $GLOBALS['USERID'], 'signature_id' => $addSignature, 'platform' => '0', 'status' => '0'); // platform 0 = ms outlook & status 0 = pending
										$addSignatureImportProcess = $GLOBALS['DB']->insert("signature_import_process_data",$data);
										  if($addSignature > 0){
											   $customfieldRows = $GLOBALS['DB']->query("SELECT * FROM `signature_customfield` WHERE `signature_id` = ?",array($refSigRow['signature_id']));
								  				$fcount = 0; $fieldtype_arr =array();
												$emailc=1; $phonec=1; $textc=1; $faxc=1; $websitec=1; $addressc=1; $hyperlinkc=1; $disclaimerc=1;
												 foreach($customfieldRows as $key =>$fieldvalue){
													 if(in_array($fieldvalue['field_type'],$fieldtype_arr)){
														 ${$fieldvalue['field_type'].'c'}++;
													 }
													 
													 $mapfield = $fieldvalue['field_type'].${$fieldvalue['field_type'].'c'};
													 if($mapcustom_field[$mapfield] == 'master'){
													 	$fieldval =  $fieldvalue['field_value'];
													 }else{
														  $objkey = $mapcustom_field[$mapfield];
														 if(in_array($mapcustom_field[$mapfield],array('displayName','familyName','fullName','givenName'))){
															 $fieldval = $gsuiteuser->name->$objkey;
														 }else{
														 	$fieldval = $gsuiteuser->$objkey;
														 }
													 }
													 if(is_array($fieldval) || is_null($fieldval)){
													 }
													 else if(trim($fieldval) != ""){
													 	$fielddata = array("signature_id"=>$addSignature,"field_type"=>$fieldvalue['field_type'],"field_label"=>$fieldvalue['field_label'],"field_value"=>$fieldval,"field_fontsize"=>$fieldvalue['field_fontsize'],"field_fontweight"=>$fieldvalue['field_fontweight'],"field_fontstyle"=>$fieldvalue['field_fontstyle'],"field_color"=>$fieldvalue['field_color'],"field_order"=>$fieldvalue['field_order']);
									 					$GLOBALS['DB']->insert("signature_customfield",$fielddata);
													 }
													 $fieldtype_arr[] = $fieldvalue['field_type'];
												 }
												 $import_item[] = $addSignature; 
										  }
								}
								
								$_SESSION['import_items'] =  $import_item;
								$_SESSION[GetSession('Success')] = '<div class="alert alert-success">File has been imported successfully.</div>';
					 			$redirect = GetUrl(array('module'=>'import')).'?success=1';
							    GetFrontRedirectUrl($redirect); exit;
							}else{
								$_SESSION[GetSession('Error')] = '<div class="alert alert-danger">user not found</div>';
						 		$redurl = GetUrl(array('module'=>$_REQUEST['module'])).'?step=2';
								if($GLOBALS['current_department_id']){
						 			$redurl = GetUrl(array('module'=>$_REQUEST['module'])).'?step=2&department_id='.$GLOBALS['current_department_id'];
								}
								GetFrontRedirectUrl($redurl); exit;
							}
							
						 }
					 }else{
						$_SESSION[GetSession('Error')] = '<div class="alert alert-danger">pleae select group</div>';
						$redurl = GetUrl(array('module'=>$_REQUEST['module'])).'?step=2';
						if($GLOBALS['current_department_id']){
							$redurl = GetUrl(array('module'=>$_REQUEST['module'])).'?step=2&department_id='.$GLOBALS['current_department_id'];
						}
						GetFrontRedirectUrl($redurl); exit;
					 }
					 
				 }
				$GLOBALS['azurestep2'] ='';
				// $getgroups =  $GLOBALS['integrations']->GsuiteConnect('orgunit',$GLOBALS['USEREMAIL']);
				// if($getgroups['error'] == 0){
				// 	$GLOBALS['group_list'] ='';
				// 	foreach($getgroups['data']['organizationUnits'] as $k => $orgunit){
				// 		$GLOBALS['group_list'] .='<li>
                //               <input class="form-check-input groupcbox" type="checkbox" name="adgroups[]" id="'.$group['id'].'" value="'.$orgunit->orgUnitPath.'" checked="checked"><label for="'.$group['id'].'">'.$orgunit->name.'</label></li>';
				// 	}
				// }
				
				$getUsers =  $GLOBALS['integrations']->GsuiteConnect('user',$GLOBALS['USEREMAIL']);
				if($getUsers['error'] == 0){
					$GLOBALS['group_list'] ='';
					$groupedUsers = [];
					foreach($getUsers['data']['users']['users'] as $k => $user){
						$orgPath = $user->getOrgUnitPath();
						$orgName = $getUsers['data']['organization'][$orgPath]->name ?? 'Uncategorized';
						$grouped[$orgName][] = $user;
					}
					foreach ($grouped as $groupName => $users) {
    					$GLOBALS['group_list'] .= "<strong>" . htmlspecialchars($groupName) . "</strong>
						<div class='flex items-center gap-2 mb-4'>
							<label class='kt-label' for='select_all_".strtolower(htmlspecialchars($groupName))."'>Select All</label>
							<input type='checkbox' class='select_all_department kt-checkbox' id='select_all_".strtolower(htmlspecialchars($groupName))."' data-department-id='".strtolower(htmlspecialchars($groupName))."' >
						</div>";
						foreach ($users as $user) {
							$GLOBALS['group_list'] .='<div class="flex items-center gap-2 mb-4">
                              <input class="kt-checkbox groupcbox checkbox-'.strtolower(htmlspecialchars($groupName)).'" type="checkbox" name="addUsers[]" id="'.$group['id'].'" value="'.$user->primaryEmail.'" data-department-id="'.strtolower(htmlspecialchars($groupName)).'">
							  <label for="'.$group['id'].'">'.$user->name->fullName.'<span class="text-gray-400">('.$user->primaryEmail.')</span></label>
							</div>';
						}
					}
				}else{
						$_SESSION[GetSession('Error')] = '<div class="alert alert-danger">'.$getUsers['msg'].'.</div>';
				}
				 
				 
			 }else if($_REQUEST['step'] == 3){
				 $GLOBALS['azurestep3'] ='';
			 }else{
				 $GLOBALS['azurestep1'] ='';
			 }
			
			//STEP1 FIELD MAPIING 
			if($_POST['gsuite_savefield'] == 1 && isset($_POST['fieldp']) && isset($_POST['dirattrp'])){
				$personal_field = $_POST['fieldp'];
				$contact_field = $_POST['fieldc'];
				$fc =0;
				foreach($personal_field as $fieldp){
					$newfieldp[$fieldp] =  $_POST['dirattrp'][$fc]; 
				$fc++; }
			
				
				$fc =0; $emailc =1; $phonec=1; $textc=1; $faxc=1; $websitec=1; $addressc =1; $hyperlinkc=1; $disclaimerc =1;
				$newfieldc = [];
				foreach($contact_field as $fieldc){
					$chk = $fieldc.${$fieldc.'c'};
					var_dump($chk);
					if(array_key_exists($chk,$newfieldc)){
							${$fieldc.'c'} ++;
						}
					$newfieldc[$fieldc.${$fieldc.'c'}] =  $_POST['dirattrc'][$fc]; 
				$fc++; }
				
				$fielddata = array('personal_field'=>$newfieldp,'contact_field'=>$newfieldc);
				$fielddata = serialize($fielddata);
				$update = $GLOBALS['DB']->update('registerusers_token',array('maping_field'=>$fielddata),array('user_id'=>$GLOBALS['USERID'],'token_platform'=>1));
				$redurl = GetUrl(array('module'=>$_REQUEST['module'])).'?step=2';
				if($GLOBALS['current_department_id']){
					$redurl = GetUrl(array('module'=>$_REQUEST['module'])).'?step=2&department_id='.$GLOBALS['current_department_id'];
				}
				GetFrontRedirectUrl($redurl); exit;
			}
			
			
			$data = $GLOBALS['DB']->row("SELECT count(signature_id) as no,signature_id,signature_firstname,signature_company,signature_jobtitle FROM signature WHERE signature_master= 1 AND user_id =?",array($GLOBALS['USERID']));
			$GLOBALS['master_fieldp']= ''; $GLOBALS['master_fieldc']= '';  $GLOBALS['dirattr_fieldp']= ''; $GLOBALS['dirattr_fieldc']= '';
			
			if($getattRow['maping_field']){
				$selattfield = unserialize($getattRow['maping_field']);
				foreach($selattfield['personal_field'] as $key=>$selperatt){
					${$key.$selperatt} = 'selected';
				}
				
				foreach($selattfield['contact_field'] as $key=>$selperatt){
					${$key.$selperatt} = 'selected';
				}
			}
			$mfield = 0;
			if($data['signature_firstname'] != ""){
				$GLOBALS['master_fieldp'] .='<div>
							<label class="kt-label mb-3">Full Name</label>
							<input type="text" class="kt-input"  placeholder=""  value="'.$data['signature_firstname'].'" disabled="disabled">
							</div>';
				$GLOBALS['dirattr_fieldp'] .='<div>
                                <select  class="kt-select" name="dirattrp[]">
                                    <option value="master" '.$signature_firstnamemaster.'>Take From Master </option>
                                    <option value="displayName" '.$signature_firstnamedisplayName.'>Display Name</option>
                                    <option value="familyName" '.$signature_firstnamefamilyName.'>family Name</option>
									<option value="fullName" '.$signature_firstnamefullName.'>Full Name</option>
									<option value="givenName" '.$signature_firstnamegivenName.'>Given Name</option>
                                    <option value="primaryEmail" '.$signature_firstnameprimaryEmail.'>primaryEmail</option>
                                    <option value="phones" '.$signature_firstnamephones.'>Phones</option>
                                    <option value="organizations" '.$signature_firstnameorganizations.'>Organizations</option>
                                    <option value="websites" '.$signature_firstnamewebsites.'>Websites</option>
									 <option value="addresses" '.$signature_firstnameaddresses.'>Address</option>
                                    <option value="locations" '.$signature_firstnamelocations.'>Locations</option>
                                </select>
                                <input type="hidden" name="fieldp[]" value="signature_firstname" />
                            </div>';
				$mfield++;
			}
			if($data['signature_jobtitle'] != ""){
				$GLOBALS['master_fieldp'] .='<div>
							<label class="kt-label mb-3">Title / Sub Title</label>
  							<input type="text" class="kt-input" placeholder="" value="'.$data['signature_jobtitle'].'" disabled="disabled">
						</div>';
				$GLOBALS['dirattr_fieldp'] .='<div>
                                <select  class="kt-select" name="dirattrp[]">
                                    <option value="master" '.$signature_jobtitlemaster.'>Take From Master </option>
                                    <option value="displayName" '.$signature_jobtitledisplayName.'>Display Name</option>
                                    <option value="familyName" '.$signature_jobtitlefamilyName.'>family Name</option>
									<option value="fullName" '.$signature_jobtitlefullName.'>Full Name</option>
									<option value="givenName" '.$signature_jobtitlegivenName.'>Given Name</option>
                                    <option value="primaryEmail" '.$signature_jobtitleprimaryEmail.'>primaryEmail</option>
                                    <option value="phones" '.$signature_jobtitlephones.'>Phones</option>
                                    <option value="organizations" '.$signature_jobtitleorganizations.'>Organizations</option>
                                    <option value="websites" '.$signature_jobtitlewebsites.'>Websites</option>
									 <option value="addresses" '.$signature_jobtitleaddresses.'>Address</option>
                                    <option value="locations" '.$signature_jobtitlelocations.'>Locations</option>
                                </select>
                                <input type="hidden" name="fieldp[]" value="signature_jobtitle" />
                            </div>';
				$mfield++;
			}
			if($data['signature_company'] !=""){
				$mfield++;
				$GLOBALS['master_fieldp'] .='<div>
							<label class="kt-label mb-3">Company Name</label>
							<input type="text" class="kt-input"  placeholder=""  value="'.$data['signature_company'].'" disabled="disabled">
						</div>';
				$GLOBALS['dirattr_fieldp'] .='<div>
						<select class="kt-select" name="dirattrp[]">
							<option value="master" '.$signature_companymaster.'>Take From Master </option>
							<option value="displayName" '.$signature_companydisplayName.'>Display Name</option>
							<option value="familyName" '.$signature_companyfamilyName.'>Family Name</option>
							<option value="fullName" '.$signature_companyfullName.'>Full Name</option>
							<option value="givenName" '.$signature_companygivenName.'>Given Name</option>
							<option value="primaryEmail" '.$signature_companyprimaryEmail.'>primaryEmail</option>
							<option value="phones" '.$signature_companyphones.'>Phones</option>
							<option value="organizations" '.$signature_companyorganizations.'>Organizations</option>
							<option value="websites" '.$signature_companywebsites.'>Websites</option>
								<option value="addresses" '.$signature_companyaddresses.'>Address</option>
							<option value="locations" '.$signature_companylocations.'>Locations</option>
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
							<label class="kt-label mb-3">'.$field_label.'</label>
							<input type="text" class="kt-input"  placeholder=""  value="'.$field_value.'" disabled="disabled">
						</div>';
						
						$GLOBALS['dirattr_fieldc'] .='<div>
                                <select class="kt-select" name="dirattrc[]">
                                    <option value="master" '.${$fieldname.'master'}.'>Take From Master </option>
									 <option value="displayName" '.${$fieldname.'displayName'}.'>Display Name</option>
                                    <option value="familyName" '.${$fieldname.'familyName'}.'>Family Name</option>
									<option value="fullName" '.${$fieldname.'fullName'}.'>Full Name</option>
									<option value="givenName" '.${$fieldname.'givenName'}.'>Given Name</option>
                                    <option value="primaryEmail" '.${$fieldname.'primaryEmail'}.'>Primary Email</option>
                                    <option value="phones" '.${$fieldname.'phones'}.'>Phones</option>
                                    <option value="organizations" '.${$fieldname.'organizations'}.'>Organizations</option>
                                    <option value="websites" '.${$fieldname.'websites'}.'>Websites</option>
									 <option value="addresses" '.${$fieldname.'addresses'}.'>Address</option>
                                    <option value="locations" '.${$fieldname.'locations'}.'>Locations</option>
                                </select>
                                <input type="hidden" name="fieldc[]" value="'.$customfield['field_type'].'" />
                            </div>'; 
					 $fieldno++; $mfield++; }
				}
				unset($data['signature_id']); unset($data['no']); // remove from csv file
			
			}
		
		//$this->getPage();
		$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/gsuite.html');	
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

	
	
}

?>