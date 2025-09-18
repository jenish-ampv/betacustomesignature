<?php
require_once(GetConfig('SITE_BASE_PATH').'/lib/google-api/vendor/autoload.php');
class CIT_INTEGRATIONS
{
	
	public function __construct()
	{	
		if($GLOBALS['integration_settings'] != "integration_settings"){
			GetFrontRedirectUrl(GetUrl(array('module'=>'dashboard')));
		}
		if(!isset($_SESSION[GetSession('user_id')]) && !isset($_REQUEST['uuid'])){
			GetFrontRedirectUrl(GetUrl(array('module'=>'signin')));
		}

		if($GLOBALS['plan_cancel'] == 1){
			GetFrontRedirectUrl($GLOBALS['renewaccount']);
		}
		
		if($GLOBALS['plan_type'] == 'FREE' && $GLOBALS['freeperiod_dayleft'] == 0){
			$redirect = $GLOBALS['billing'].'?action=freetrial';
			GetFrontRedirectUrl($redirect);
		}
		elseif($GLOBALS['PLAN_STATUS'] == 0){
			if(!isset($_REQUEST['category_id']) && !isset($_REQUEST['uuid'])){
				GetFrontRedirectUrl($GLOBALS['renewaccount']);
			}
		}

		if(isset($_REQUEST['department_id'])){
			$GLOBALS['current_department_id'] = $_REQUEST['department_id'];
		}else{
			$GLOBALS['current_department_id'] = 0;
		}
	}
	
	public function displayPage(){
		AddMessageInfo();
		$GLOBALS['PageView'] = 'integrations';
		$GLOBALS['success_popup'] =0;
		//$refid = $_REQUEST['refid'];

		if($_REQUEST['category_id'] =='installaddin'){
			$this->getPage();
		
			$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/installaddin.html');	
			$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');			
			$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');
			$GLOBALS['SIDEBAR'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.sidebar.html');
			$GLOBALS['CLA_HTML']->display();
			RemoveMessageInfo();
			exit();	
		}
		
		if($_POST['disconnect'] ==1 && isset($_POST['platform'])){ // disconnect
			$GLOBALS['DB']->update("registerusers",array('gmail_authenticated'=>0),array('user_email'=>$GLOBALS['USEREMAIL']));

			$del = $GLOBALS['DB']->query("DELETE FROM `registerusers_token` WHERE `user_id`=? AND `token_platform` = ?",array($GLOBALS['USERID'],$_POST['platform']));
			$GLOBALS['DB']->query("UPDATE `signature` SET `is_deploy` = '0' WHERE `user_id`=?",array($GLOBALS['USERID'])); // for restict deploy to outlook flag(0=nottodeploy)
			if($del){
			 $result = array('error'=>0,'msg'=>'Disconnect Success');
			}else{
				$result = array('error'=>0,'msg'=>'not disconnected try again');
			}
			 echo json_encode($result); exit;
		}
		
		if($_REQUEST['category_id'] =='google' && $_REQUEST['id'] == 'inviteGoogleAdmin'){
			$postdata = $_POST;
			if($postdata['admin_firstname'] !="" && $postdata['admin_lastname'] !="" && $postdata['admin_email'] !="" && $postdata['gsuite_inviteadmin'] == "1"){
				$rowMainUserExist = $GLOBALS['DB']->row("SELECT * FROM `registerusers` WHERE `user_email`= ? LIMIT 0,1",array(strtolower(trim($_POST['admin_email']))));
				if($rowMainUserExist){
					$return_arrs = array('error'=>1,'msg'=>'User already exist with this email id.');
					echo json_encode($return_arrs); exit;
				}
				$rowSubUserExist = $GLOBALS['DB']->row("SELECT * FROM `registerusers_sub_users` WHERE `email`= ? LIMIT 0,1",array(strtolower(trim($_POST['admin_email']))));
				if($rowSubUserExist){
					$result = array('error'=>1,'msg'=>'User already exist with this email id.');
					echo json_encode($result); exit;
				}
				$department_list = $GLOBALS['DB']->row("SELECT GROUP_CONCAT(department_id) AS department_ids FROM `registerusers_departments` WHERE user_id = ?;",array($GLOBALS['USERID']));
				$permission = 'manage_signatures,billing_settings,integration_settings';
				$GLOBALS['encrypt_decrypt_key'] = 'mySuperSecretKeyeSign2025'.time();
				$data = array('parent_user_id'=>$GLOBALS['USERID'],'user_firstname'=>$postdata['admin_firstname'],'user_lastname'=>$postdata['admin_lastname'],'email'=>$postdata['admin_email'],'is_active'=>$user_status,'invite_key'=>$GLOBALS['encrypt_decrypt_key']);
				if($department_list){
					$data['department_list'] = $department_list['department_ids'];
				}
				if($permission){
					$data['permission'] = $permission;
				}
				$subuserid = $GLOBALS['DB']->insert("registerusers_sub_users",$data);
				$key = $GLOBALS['encrypt_decrypt_key'];
				$encodedMail = $this->encryptNoSpecialChars($postdata['admin_email'],$key);
				$expires_at = $this->encryptNoSpecialChars(date("Y-m-d H:i:s", strtotime('+2 hours')),$key);
				$setPasswordLink = GetUrl(array('module'=>'usermanagement','category_id' => 'createpassword','token'=>$expires_at,'user'=>$encodedMail,'user_id'=>$subuserid));
				$GLOBALS['user_name'] = $postdata['admin_firstname']." ".$postdata['admin_lastname'];
				$GLOBALS['parent_user_name'] = trim($GLOBALS['USERNAME']);
				$GLOBALS['linkSetPassword'] = $setPasswordLink;
				$message= _getEmailTemplate('invite_sub_user');
				$send_mail = _SendMail($postdata['admin_email'],'',$GLOBALS['EMAIL_SUBJECT'],$message);
				$return_arrs = array('error'=>0,'msg'=>'Google Workspace Admin Invite Success');
			}else{
				$return_arrs = array('error'=>1,'msg'=>'The Google Workspace Admin has not been invited; something went wrong. Please try again.');
			}
			echo json_encode($return_arrs); exit;
			
		}
		if($_REQUEST['category_id'] =='google' && $_REQUEST['id'] == 'connect'){ // google connect
			$GLOBALS['PageView'] = 'googleconnect';
			$GLOBALS['Gstep1'] ='hidden'; $GLOBALS['Gstep2'] ='hidden'; $GLOBALS['Gstep3'] ='hidden';
			if($_REQUEST['subid'] == 2){
				$GLOBALS['Gstep2'] ='';
				$GLOBALS['contentclsyes'] = $_REQUEST['sid'] == 0 ? 'hidden' : '';
				$GLOBALS['contentclsno'] = $_REQUEST['sid'] == 1 ? 'hidden' : '';
			}else if($_REQUEST['subid'] == 3){
				$GLOBALS['Gstep3'] ='';
			}else{
				$GLOBALS['Gstep1'] ='';
				$GLOBALS['contentclsno'] = 'hidden';
			}
			if($_POST['google_connect'] == 1){  // check connection
				$response = $this->GsuiteConnect('connect',$GLOBALS['USEREMAIL']);
				if($response['error'] == 0){
					$data =array('user_id'=>$GLOBALS['USERID'],'api_username'=>$GLOBALS['USEREMAIL'],'token_platform'=>1,'token_created'=>time(),'auto_update'=>0);
					$add = $GLOBALS['DB']->insert('registerusers_token',$data);
					$result = array('error'=>0,'msg'=>'Google Workspace Connected');
					$GLOBALS['DB']->update("registerusers",array('gmail_authenticated'=>1),array('user_email'=>$GLOBALS['USEREMAIL']));
				}else{
					$result = array('error'=>1,'msg'=>$response['message']);
				}
				echo json_encode($result); exit;
			}
			
			if($_POST['admin_firstname'] != "" && $_POST['admin_lastname'] != "" && $_POST['admin_email'] != "" ){
				$GLOBALS['Gdmin_firstname'] = $_POST['admin_firstname'];
				$GLOBALS['Gdmin_lastanme'] = $_POST['admin_lastname'];
				$GLOBALS['Gdmin_email'] = trim($_POST['admin_email']);
				// $message = $message= _getEmailTemplate('invite_gsuiteadmin');
				// $mailsend = _SendMail($GLOBALS['Gdmin_email'],'',$GLOBALS['EMAIL_SUBJECT'],$message);
				// if($mailsend){
				// 	$result = array('error'=>0,'msg'=>'Google Workspace Admin Invite Success');
				// }else{
				// 	$result = array('error'=>1,'msg'=>'Mail not sent somthing wrong try again');
				// }
				// echo json_encode($result); exit;

				$rowExist = $GLOBALS['DB']->row("SELECT * FROM `registerusers_sub_users` WHERE `email`= ? LIMIT 0,1",array(strtolower(trim($_POST['admin_email']))));
				if($rowExist){
					$result = array('error'=>1,'msg'=>'User already exist with this email id.');
					echo json_encode($result); exit;
				}

				$GLOBALS['encrypt_decrypt_key'] = 'mySuperSecretKeyeSign2025'.time(); 
				$permission = "manage_signatures,billing_settings,integration_settings";
				$departments = $GLOBALS['DB']->query("select department_id FROM `registerusers_departments` WHERE user_id=? ",array($GLOBALS['USERID']));
				$department_list_array = [];
				foreach ($departments as  $department) {
					$department_list_array[] = $department['department_id'];
				}
				$department_list = implode(",",$department_list_array);
				$data = array('parent_user_id'=>$GLOBALS['USERID'],'user_firstname'=>$_POST['admin_firstname'],'user_lastname'=>$_POST['admin_lastname'],'email'=>trim($_POST['admin_email']),'permission'=>$permission,'department_list'=>$department_list,'is_active'=>$user_status,'invite_key'=>$GLOBALS['encrypt_decrypt_key']);
				$subuserid = $GLOBALS['DB']->insert("registerusers_sub_users",$data);


				$key = $GLOBALS['encrypt_decrypt_key'];
				$encodedMail = $this->encryptNoSpecialChars($postdata['email'],$key);
				$expires_at = $this->encryptNoSpecialChars(date("Y-m-d H:i:s", strtotime('+2 hours')),$key);
				$setPasswordLink = GetUrl(array('module'=>'usermanagement','category_id' => 'createpassword','token'=>$expires_at,'user'=>$encodedMail,'user_id'=>$subuserid));
				$GLOBALS['user_name'] = $$_POST['admin_firstname']." ".$_POST['admin_lastname'];
				$GLOBALS['parent_user_name'] = trim($GLOBALS['USERNAME']);
				$GLOBALS['linkSetPassword'] = $setPasswordLink;
				$message= _getEmailTemplate('invite_sub_user');
				$send_mail = _SendMail(trim($_POST['admin_email']),'',$GLOBALS['EMAIL_SUBJECT'],$message);
				if($send_mail){
					$result = array('error'=>0,'msg'=>'Google Workspace Admin Invite Success');
				}else{
					$result = array('error'=>1,'msg'=>'Mail not sent somthing wrong try again');
				}
				echo json_encode($result); exit;
			}
		}
		
		if($_REQUEST['category_id'] =='microsoft' && $_REQUEST['id'] == 'connect'){
			$GLOBALS['PageView'] = 'msconnect';
			
			if($_POST['azuread_connect'] == 1){
				if($_POST['application_id'] !="" && $_POST['application_secret'] !="" && $_POST['application_tenant'] !=""){
					$applicationid = trim($_POST['application_id']); 
					$secret = trim($_POST['application_secret']);
					$tenant = $_POST['application_tenant'];
					$signature_auto_update = (isset($_POST['signature_auto_update'])) ? $_POST['signature_auto_update'] : 0 ;
					$response = $this->getAccessTokenGraph($applicationid,$secret,$tenant);
					if($response['error'] == 1){
						$result = array('error'=>1,'msg'=>$response['msg']);
					}else if($response['error'] == 0 && $response['token'] !=""){
						$data =array('user_id'=>$GLOBALS['USERID'],'api_username'=>$applicationid,'api_password'=>$secret,'api_uniqid'=>$tenant,'api_token'=>$response['token'],'token_platform'=>0,'token_created'=>time(),'token_expire'=>$response['expire'],'auto_update'=>$signature_auto_update);
						$add = $GLOBALS['DB']->insert('registerusers_token',$data);
						$result = array('error'=>0,'msg'=>'Azure AD Connected');
						
					}else{
						$result = array('error'=>1,'msg'=>'not connected try again');
					}
						
				}else{
					$result = array('error'=>1,'msg'=>'enter all required field');
				}
				echo json_encode($result); exit;
			}
				
		}

		
		if($_POST['bulkshare']=="_bulksahre"){

			if($_POST['total_share'] > 1){
				for($i=1; $i < $_POST['total_share']; $i++){				
					$send_mail = false;
					$to = $_POST['share_email'.$i];
					$shareurl = $_POST['share_url'.$i];
					$GLOBALS['SHARE_SIGURL'] = $_POST['share_url'.$i];
					$message= _getEmailTemplate('share_signature'); 	// send mail
					if(filter_var($to, FILTER_VALIDATE_EMAIL)){
						$send_mail = _SendMail($to,'',$GLOBALS['EMAIL_SUBJECT'],$message);
					}
					$result = array('error'=>0,'msg'=>'<div class="gap-8 py-5 px-4 pl-11 border-l-9 border-green-600 rounded-xl relative bg-white bg-gradient-to-r from-[#00B71B]/12 to-[#00B71B]/0 shadow-lg"><strong>Success! </strong>Signature share success</div>');
					$response = json_encode($result);
				}
			}else{
				$result = array('error'=>1,'msg'=>'<div class="alert alert-danger" id="wrong"><strong> Failure! </strong>somthing wrong try again</div>');
				$response =  json_encode($result);
			}
			echo $response; exit;
		}
		
		if($_REQUEST['success'] == "1"){
			if(isset($_SESSION['import_items']) && is_array($_SESSION['import_items'])){
				$GLOBALS['success_popup'] =1; $GLOBALS['signature_sharelists']='';
				$import_list = implode(",",$_SESSION['import_items']);
				$shareLists = $GLOBALS['DB']->query("SELECT S.signature_id,S.signature_firstname,CF.field_value FROM signature S LEFT JOIN signature_customfield CF ON S.signature_id = CF.signature_id AND CF.field_type = 'email' WHERE S.signature_id IN(".$import_list.") GROUP BY S.signature_id");
				$sigcount =1;
				foreach($shareLists as $shareList){
					if($shareList['field_value'] !=""){
				 	$signature_id = $shareList['signature_id'];
				 	$share_link = $GLOBALS['linkModuleUsesignature'].'/install?uuid='.base64_encode($signature_id).'&u='.base64_encode($GLOBALS['USERID']);
					$sharename = $shareList['signature_firstname'];
						$GLOBALS['signature_sharelists'] .='<div class="border_bottom">
							<div class="left"><p>Dhval Hingrajia</p></div>
							<div class="right">
								<div class="form-floating">
									<input type="hidden" class="form-control share_url" id="share_url" name="share_url'.$sigcount.'"  value="'.$share_link.'">
									<input type="text" class="form-control share_email" id="share_email" name="share_email'.$sigcount.'" placeholder="Email Address" required="required" value="'.$shareList['field_value'].'">
									<label for=" ">Email id</label>
								</div>
							   </div>
						</div>';
					$sigcount++; }
				}
				$GLOBALS['signature_sharelists'] .='<input type="hidden" name="total_share" value="'.$sigcount.'">';
			}
		}
		
		
		$intROw = $GLOBALS['DB']->row("SELECT count(*) connected FROM registerusers_token WHERE user_id = ? AND token_platform = 0",array($GLOBALS['USERID']));
		$msconnected = $intROw['connected'];
		if($msconnected > 0){
			$GLOBALS['connectbtncls'] ='hidden';
			$GLOBALS['connectedbtncls'] ='';
		}else{
			$GLOBALS['connectbtncls'] ='';
			$GLOBALS['connectedbtncls'] ='hidden';
		}
		
		$intROw = $GLOBALS['DB']->row("SELECT count(*) glconnected FROM registerusers_token WHERE user_id = ? AND token_platform = 1",array($GLOBALS['USERID']));
		$glconnected = $intROw['glconnected'];
		if($glconnected > 0){
			$GLOBALS['glconnectbtncls'] ='hidden';
			$GLOBALS['glconnectedbtncls'] ='';
		}else{
			$GLOBALS['glconnectbtncls'] ='';
			$GLOBALS['glconnectedbtncls'] ='hidden';
		}
		
		
		$this->getPage();
		$GLOBALS['google_connect'] =GetUrl(array('module'=>$_REQUEST['module'],'action'=>'google','id'=>'connect'));
		$GLOBALS['microsoft_connect'] =GetUrl(array('module'=>$_REQUEST['module'],'action'=>'microsoft','id'=>'connect'));
		$GLOBALS['exchange_connect'] =GetUrl(array('module'=>$_REQUEST['module'],'action'=>'exchange','id'=>'connect'));
	
		$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/integrations.html');	
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
	
	public function getAccessTokenGraph($clientId,$clientSecret,$tenantId){
		
		//$clientId = '2423e1f8-e537-427f-9e58-efec913406d1';
		//$clientSecret = 'DA58Q~A5YAMGojZUhpWlVE3vjBMvUNzekNoyvdfp';
		//$tenantId = '55e7af68-dda6-47eb-ae02-1c96f650d069';
		$scope = 'https://graph.microsoft.com/.default';
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://login.microsoftonline.com/$tenantId/oauth2/token");
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_POSTFIELDS, array(
		'tenant' => $tenantId,
		'client_id' => $clientId,
		'resource' =>'https://graph.microsoft.com/',
		'client_secret' => $clientSecret,
		'grant_type' => 'client_credentials',
		));
		$data = curl_exec($ch);
		curl_close($ch);
		$data_obj = json_decode($data);
		/*stdClass Object ( [token_type] => Bearer [expires_in] => 3599 [ext_expires_in] => 3599 [expires_on] => 1692452448 [not_before] => 1692448548 [resource] => graph.microsoft.com/ [access_token] =>token ) */
		if(isset($data_obj->{"error"})){
			return array('error'=>1,'msg'=>$data_obj->{"error"});
		}else{
			$_SESSION['azure_token'] = $data_obj->{"access_token"};
			return array('error'=>0,'token'=>$data_obj->{"access_token"},'expire'=>$data_obj->{"expires_on"});
		}
	}
	
	public function getUserGraph($id){

		$access_token = $_SESSION['azure_token'];
		$headers = array("Authorization: Bearer $access_token","ConsistencyLevel: eventual");
		//$url = "https://graph.microsoft.com/v1.0/users";
		//$endPoint = rawurlencode('$count=true&$filter=licenseAssignmentState=Active');
		//$url = "https://graph.microsoft.com/beta/groups/".$id."/members?".$endPoint;
		$url = "https://graph.microsoft.com/beta/groups/".$id."/members";
		
		$ch2 = curl_init( $url );
		curl_setopt( $ch2, CURLOPT_URL, $url );
		curl_setopt( $ch2, CURLOPT_HTTPHEADER, $headers );
		curl_setopt($ch2, CURLOPT_CUSTOMREQUEST, "GET"); 
		curl_setopt( $ch2, CURLOPT_RETURNTRANSFER, true);
		curl_setopt( $ch2, CURLOPT_FAILONERROR, true);
		$response = curl_exec( $ch2 );
		if (curl_errno($ch2)) {
			return array('error'=>1,'msg'=>curl_error($ch2));
		}else{
			$users =  json_decode($response,true);
			return array('error'=>0,'users'=>$users['value']);
		}
		curl_close($ch2);
	}
	
	
	public function getUserProfilePhoto($id){
		$access_token = $_SESSION['azure_token'];
		$headers = array("Authorization: Bearer $access_token");
		$value = '$value';
		$url = "https://graph.microsoft.com/beta/users/".$id."/photos/240x240/".$value;
		//$url = "https://graph.microsoft.com/beta/groups/".$id."/photo/".$value;
		
		
		$ch2 = curl_init( $url );
		curl_setopt( $ch2, CURLOPT_URL, $url );
		curl_setopt( $ch2, CURLOPT_HTTPHEADER, $headers );
		curl_setopt($ch2, CURLOPT_CUSTOMREQUEST, "GET"); 
		curl_setopt( $ch2, CURLOPT_RETURNTRANSFER, true);
		curl_setopt( $ch2, CURLOPT_FAILONERROR, true);
		 $response = curl_exec( $ch2 );
		return $response;
		curl_close($ch2);
	}
	
	public function getUsergroupGraph($token='',$clientId='',$clientSecret='',$tenantId=''){
		$gettoken = $this->getAccessTokenGraph($clientId,$clientSecret,$tenantId);
		$access_token = $gettoken['token'];
		$headers = array("Authorization: Bearer $access_token","ConsistencyLevel: eventual"); //"ConsistencyLevel: eventual"
		////$url = "https://graph.microsoft.com/v1.0/groups?$expand=members";
		$url = "https://graph.microsoft.com/v1.0/groups?%24count=true";
		//$endpoint = rawurlencode("$expand=members&$filter=id eq '290c1252-b2ae-40e4-a9c4-7a2ead493aea' OR id eq 'afa27da7-4174-4a2b-b0b7-416ef3024879'");
		//$url = "https://graph.microsoft.com/v1.0/groups?%24expand=members&%24filter=id%20eq%20%27290c1252-b2ae-40e4-a9c4-7a2ead493aea%27%20OR%20id%20eq%20%27afa27da7-4174-4a2b-b0b7-416ef3024879%27".$endpoint; exit;
		$ch2 = curl_init( $url );
		curl_setopt( $ch2, CURLOPT_URL, $url );
		curl_setopt( $ch2, CURLOPT_HTTPHEADER, $headers );
		curl_setopt($ch2, CURLOPT_CUSTOMREQUEST, "GET"); 
		curl_setopt( $ch2, CURLOPT_RETURNTRANSFER, true);
		curl_setopt( $ch2, CURLOPT_FAILONERROR, true);
		$response = curl_exec( $ch2 );
		
		if (curl_errno($ch2)) {
			return array('error'=>1,'msg'=>curl_error($ch2));
		}else{
			$groups =  json_decode($response,true);
			//290c1252-b2ae-40e4-a9c4-7a2ead493aea    All Company 
			//afa27da7-4174-4a2b-b0b7-416ef3024879    IT Support
			// caec7311-5a24-4a8b-9dcd-d2aff65bfddd Custom esignature
			return array('error'=>0,'groups'=>$groups['value']);
		}
		
		curl_close($ch2);
	}
	
	
	public function getGroupUserGraph($filter){

		$access_token = $_SESSION['azure_token'];		
		$headers = array("Authorization: Bearer $access_token","ConsistencyLevel: eventual");
		$filter = rawurlencode($filter);
		$curl = curl_init();
		curl_setopt_array($curl, array(
		 CURLOPT_URL => 'https://graph.microsoft.com/v1.0/groups?%24expand=members&%24filter='.$filter,
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => '',
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 0,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => 'GET',
		  CURLOPT_HTTPHEADER => $headers,
		));
		
		$response = curl_exec($curl);
		
		if (curl_errno($curl)) {
			return array('error'=>1,'msg'=>curl_error($curl));
		}else{
			$users =  json_decode($response,true);
			return array('error'=>0,'users'=>$users['value']);
		}
		curl_close($curl);
	}
	
	public function getLastUpdatedUserData($token='',$clientId='',$clientSecret='',$tenantId=''){
		$gettoken = $this->getAccessTokenGraph($clientId,$clientSecret,$tenantId);
		$access_token = $gettoken['token'];
		$headers = array("Authorization: Bearer $access_token","ConsistencyLevel: eventual");
		$endpoint = rawurlencode("$select=onPremisesLastSyncDateTime,displayname&$filter=onPremisesLastSyncDateTime ge 2024-03-04");
		//echo $url = "https://graph.microsoft.com/beta/users?%24filter=onPremisesLastSyncDateTime ge 2024-03-07";
		$url = "https://graph.microsoft.com/beta/users";
		
		
		$ch2 = curl_init( $url );
		curl_setopt( $ch2, CURLOPT_URL, $url );
		curl_setopt( $ch2, CURLOPT_HTTPHEADER, $headers );
		curl_setopt($ch2, CURLOPT_CUSTOMREQUEST, "GET"); 
		curl_setopt( $ch2, CURLOPT_RETURNTRANSFER, true);
		curl_setopt( $ch2, CURLOPT_FAILONERROR, true);
		$response = curl_exec( $ch2 );
		
		if (curl_errno($ch2)) {
			return array('error'=>1,'msg'=>curl_error($ch2));
		}else{
			$users =  json_decode($response,true);
			return array('error'=>0,'users'=>$users['value']);
		}
		curl_close($ch2);
	}
	
	public function GsuiteConnect($action,$adminEmail){
		$delegatedAdmin = $adminEmail;
		// $delegatedAdmin = 'team@customesignature.com';
		//$delegatedAdmin = 'singualrit.dahval@gmail.com';
		$domainName = substr($delegatedAdmin, strpos($delegatedAdmin, '@') + 1);
		$appName = 'Custome Signature';
		$scopes = array('https://www.googleapis.com/auth/admin.directory.user','https://www.googleapis.com/auth/admin.directory.orgunit.readonly');
		$authJson = GetConfig('SITE_BASE_PATH').'/lib/google-api/customesignature-459811-efe527767b47.json';
		$googleClient = new \Google_Client();
		$googleClient->setApplicationName($appName);
		$googleClient->setAuthConfig($authJson);
		$googleClient->setSubject($delegatedAdmin);
		$googleClient->setScopes($scopes);
		$googleClient->setAccessType('offline');
		$dir = new \Google_Service_Directory($googleClient);
		
		if($action == 'user'){
			try{ 
				$users = $dir->users->listUsers(array('domain'=>$domainName,'query'=>'orgUnitPath=/', 'maxResults' =>500));
				$orgUnits = $dir->orgunits->listOrgunits('my_customer', ['type' => 'all'])->getOrganizationUnits();
				$organization = [];
				foreach ($orgUnits as $ou) {
					$organization[$ou->getOrgUnitPath()] = $ou;
				}
				$response['users'] = $users;
				$response['organization'] = $organization;

			}catch(Exception $e) {
				$response = $e->getMessage();
				$response = json_decode($response,true);
			}
			
		}else if($action == 'orgunit'){
			try{ 
				$response = $dir->orgunits->listOrgunits('my_customer');
			}catch(Exception $e) {
				$response = $e->getMessage();
				$response = json_decode($response,true);
			}
			
		}else{
			try{ 
				$response = $dir->users->get($delegatedAdmin);
			}catch(Exception $e) {
				$response = $e->getMessage();
				$response = json_decode($response,true);
			}
		}
		
		$data = $response;
		if(isset($data['error'])){
			return array('error'=>1,'message'=>$data['error'].', '.$data['error_description']);
		}
		return array('error'=>0,'data'=>$data);
	}

	function encryptNoSpecialChars($data, $key) {
        $ivLength = openssl_cipher_iv_length('aes-256-cbc');
        $iv = openssl_random_pseudo_bytes($ivLength);
        
        $ciphertext = openssl_encrypt($data, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        
        // Calculate HMAC using the key
        $hmac = hash_hmac('sha256', $iv . $ciphertext, $key, true);
        
        // Combine IV + HMAC + ciphertext and encode
        return bin2hex($iv . $hmac . $ciphertext);
    }
	
}

?>