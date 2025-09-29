<?php
// ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
require_once(GetConfig('SITE_BASE_PATH').'/lib/stripe-php/stripe-config.php');	  // stripe
require_once(GetConfig('SITE_BASE_PATH').'/lib/stripe-php/init.php');	  // bpoint  // stripe
// require_once(GetConfig('SITE_BASE_PATH').'/google-login/vendor/autoload.php');	// signup/signin With Google
// require_once(GetConfig('SITE_BASE_PATH').'/x-login/autoload.php');	// signup/signin With X(twitter)
// use Abraham\TwitterOAuth\TwitterOAuth;  // signup/signin With X(twitter)
class CIT_REGISTER
{
	
	public function __construct()
	{	
		$GLOBALS['plan_selunit'] =1;
	
		if(isset($_SESSION['FREE_TRIAL_SIGNATURE_ID'])){
			$freeTrialSignatureDetails = json_decode($this->receiveFreeTrialSavedSignature($_SESSION['FREE_TRIAL_SIGNATURE_ID']));
			// $_REQUEST['id'] = $_SESSION['FREE_TRIAL_SIGNATURE_ID'];
			if(isset($_REQUEST['id'])){
			$GLOBALS['FREE_TRIAL_SIGNATURE_PREVIEW'] = $freeTrialSignatureDetails->signature_html;
			$GLOBALS['user_firstname'] = $freeTrialSignatureDetails->signature_firstname;
			$GLOBALS['user_lastname'] = $freeTrialSignatureDetails->signature_lastname;
			$GLOBALS['user_organization'] = $freeTrialSignatureDetails->signature_company;
			$GLOBALS['user_email'] = $freeTrialSignatureDetails->signature_email;
			}else{
				unset($_SESSION['FREE_TRIAL_SIGNATURE_ID']);
			}
			
		}

		// // signup With Google
		// $clientID = '700717033122-rb8hgml0u3rva45eblm90559d8tt6359.apps.googleusercontent.com';
		// $clientSecret = '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';
		// $redirectUri = GetUrl(array('module'=>'signup','category_id'=>'signupwithgoogle'));

		// // create Client Request to access Google API
		// $client = new Google_Client();
		// $client->setClientId($clientID);
		// $client->setClientSecret($clientSecret);
		// $client->setRedirectUri($redirectUri);
		// $client->addScope("email");
		// $client->addScope("profile");
		// $GLOBALS['GOOGLE_SIGNUP_AUTHURL'] = $client->createAuthUrl();
		// $GLOBALS['GOOGLE_CLIENT_SIGNUP_OBJECT'] = $client;
		// // signup With Google

		// // signup With Facebook
		// unset($_SESSION['FACEBOOK_SIGNIN_FAIL_URL']);
		// unset($_SESSION['FACEBOOK_SIGNIN_SUCCESS_URL']);
		// unset($_SESSION['FACEBOOK_SIGNIN']);
		// $GLOBALS['FACEBOOK_SIGNUP_AUTHURL'] = $GLOBALS['ROOT_LINK'].'/facebook-login/facebook-oauth.php';
		// $_SESSION['FACEBOOK_SIGNUP_FAIL_URL']  = GetUrl(array('module'=>'signup'));
		// $_SESSION['FACEBOOK_SIGNUP_SUCCESS_URL'] = GetUrl(array('module'=>'signup','category_id'=>'signupwithfacebook'));
		// $_SESSION['FACEBOOK_SIGNUP'] = TRUE;
		// // signup With Facebook

		// // signup With X(twitter)
		// if(!isset($_REQUEST['oauth_verifier'], $_REQUEST['oauth_token'])){
		// 	$redirectUriX = GetUrl(array('module'=>'signup','category_id'=>'signupwithx'));
		// 	$GLOBALS['X_CONSUMER_KEY'] = '3k5COeQodclpbzNHM0dy7zsD7'; // Replace with your API key
		// 	$GLOBALS['X_CONSUMER_SECRET'] = '6avwSk2AHXJSZcrJ88mUZin26eaa2F3A5JACQVA3NVzt0Yxy13'; // Replace with your API secret key
		// 	$GLOBALS['X_OAUTH_CALLBACK'] = $redirectUriX; // Replace with your callback URL
		// 	$connection = new TwitterOAuth('3k5COeQodclpbzNHM0dy7zsD7', '6avwSk2AHXJSZcrJ88mUZin26eaa2F3A5JACQVA3NVzt0Yxy13');
		// 	$request_token = $connection->oauth('oauth/request_token', array('oauth_callback' => $GLOBALS['X_OAUTH_CALLBACK']));
		// 	$_SESSION['x_oauth_token'] = $request_token['oauth_token'];
		// 	$_SESSION['x_oauth_token_secret'] = $request_token['oauth_token_secret'];
		// 	$GLOBALS['X_SIGNUP_AUTHURL'] = $connection->url('oauth/authorize', array('oauth_token' => $request_token['oauth_token']));
		// }

		// // signup With X(twitter)

	}
	
	public function displayPage(){
		AddMessageInfo();	
		if($_REQUEST['3Dsecuresuccess'] == true){
			$this->createuserforsuccess3D(); exit;
		}
		if($_REQUEST['category_id'] =='stripe' && $_REQUEST['id'] =='web-hook'){ // STRIPE WEBHOOK
			$this->StripeWebhook(); exit;
		}
		if(!$_REQUEST['category_id']){
			GetFrontRedirectUrl(GetUrl(array('module'=>'signup','category_id'=>'verifyemail')));
		}

		if($_REQUEST['category_id'] =='verifyemail'){ // verifyemail 
			$this->verifyEmail(); exit;
		}
		
		if($_REQUEST['category_id'] =='userdetails'){ // userDetails 
			$this->userDetails(); exit;
		}

		if($_REQUEST['category_id'] =='registerFormSubmit'){ // registerFormSubmit 
			$this->registerFormSubmit(); exit;
		}
		
		if($_POST['coupon_code'] !="" && $_POST['coupon_apply'] ==1){
			$coupon_code = trim($_POST['coupon_code']);
			echo $this->isCouponValid($coupon_code); exit;
		}

		if($_REQUEST['category_id'] == 'plan'){
			$_SESSION['plan_id'] = $_REQUEST['id'];
			$_SESSION['plan_unit'] = $_REQUEST['subid'];
			$_SESSION['free_trial'] = isset($_REQUEST['free_trial']) ? $_REQUEST['free_trial'] : 0;
			$_SESSION['plan_from_pricing'] = 'true';
			GetFrontRedirectUrl(GetUrl(array('module'=>'signup')));
		}else{
			$GLOBALS['plan_from_pricing'] = 'false';
		}

		if(isset($_REQUEST['category_id']) && $_REQUEST['category_id'] == 'signupwithgoogle' && $_POST['registersubmit'] != 1){
			if (isset($_GET['code'])) {
				$googleClient = $GLOBALS['GOOGLE_CLIENT_SIGNUP_OBJECT'];
				$token = $googleClient->fetchAccessTokenWithAuthCode($_GET['code']);
				$googleClient->setAccessToken($token['access_token']);
			  
				// get profile info
				$google_oauth = new Google_Service_Oauth2($googleClient);
				$google_account_info = $google_oauth->userinfo->get();
				$userinfo = [
				  'email' => $google_account_info['email'],
				  'first_name' => $google_account_info['givenName'],
				  'last_name' => $google_account_info['familyName'],
				  'gender' => $google_account_info['gender'],
				  'full_name' => $google_account_info['name'],
				  'picture' => $google_account_info['picture'],
				  'verifiedEmail' => $google_account_info['verifiedEmail'],
				  'token' => $google_account_info['id'],
				];

				$GLOBALS['user_firstname'] = $userinfo['first_name'];
				$GLOBALS['user_lastname'] = $userinfo['last_name'];
				$GLOBALS['user_email'] = $userinfo['email'];
				$GLOBALS['signup_google_user_password_display_hide'] = 'style="display:none;"';
				$GLOBALS['user_password'] = "CustomEsign#2024@googleSigup";
				$GLOBALS['user_googleid'] = $userinfo['token'];
				$GLOBALS['user_outhprovider'] = "1";
			} else {
				$GLOBALS['Message'] ='<div class="alert alert-danger" id="wrong"><strong> Failure! </strong>invalid token please try again!</div>';
				GetFrontRedirectUrl(GetUrl(array('module'=>$_REQUEST['module'])));
			}
		}
		if(isset($_REQUEST['category_id']) && $_REQUEST['category_id'] == 'signupwithfacebook' && $_POST['registersubmit'] != 1){
			$nameFromFacebookArray = explode(" ", $_SESSION['facebook_name']);
			$GLOBALS['user_firstname'] = isset($nameFromFacebookArray[0]) ? $nameFromFacebookArray[0] : "";
			$GLOBALS['user_lastname'] = isset($nameFromFacebookArray[1]) ? $nameFromFacebookArray[1] : "";
			$GLOBALS['user_email'] = $_SESSION['facebook_email'];
			$GLOBALS['signup_google_user_password_display_hide'] = 'style="display:none;"';
			$GLOBALS['user_password'] = "CustomEsign#2024@fbSigup";
			$GLOBALS['user_facebookid'] = $_SESSION['facebook_loggedin'];
			$GLOBALS['user_outhprovider'] = "2";

		}
		if(isset($_REQUEST['category_id']) && $_REQUEST['category_id'] == 'signupwithx' && $_POST['registersubmit'] != 1){
			
			// Sign in with X START
			
			if(isset($_SESSION['X_SIGNIN_AUTHURL']) && $_SESSION['X_SIGNIN_AUTHURL'] != ''){
				unset($_SESSION['X_SIGNIN_AUTHURL']);
				if (isset($_REQUEST['oauth_verifier'], $_REQUEST['oauth_token']) && $_REQUEST['oauth_token'] == $_SESSION['x_oauth_token_signin']) {
					$request_token = [];
					$request_token['oauth_token'] = $_SESSION['x_oauth_token_signin'];
					$request_token['oauth_token_secret'] = $_SESSION['x_oauth_token_secret_signin'];
					$connection = new TwitterOAuth('3k5COeQodclpbzNHM0dy7zsD7', '6avwSk2AHXJSZcrJ88mUZin26eaa2F3A5JACQVA3NVzt0Yxy13', $request_token['oauth_token'], $request_token['oauth_token_secret']);
					$access_token = $connection->oauth("oauth/access_token", array("oauth_verifier" => $_REQUEST['oauth_verifier']));
					$connection = new TwitterOAuth('3k5COeQodclpbzNHM0dy7zsD7','6avwSk2AHXJSZcrJ88mUZin26eaa2F3A5JACQVA3NVzt0Yxy13', $access_token['oauth_token'], $access_token['oauth_token_secret']);
					$user = $connection->get("account/verify_credentials", ['include_email' => 'true']);
					$user_email = $user->email;
					$token = $user->id;
					$rowLogin = $GLOBALS['DB']->row("SELECT * FROM `registerusers` WHERE user_email = ?  AND user_x_id= ? AND user_outhprovider = 3  LIMIT 0,1",array($user_email,$token));
					if($rowLogin){
						if($rowLogin['user_status'] == 1){
							$_SESSION[GetSession('user_id')] = $rowLogin['user_id'];
							$_SESSION[GetSession('user_email')] = $rowLogin['user_email'];
							$_SESSION[GetSession('user_name')] = $rowLogin['user_firstname'].' '.$rowLogin['user_lastname'];
							$_SESSION[GetSession('user_uploadlimit')] = $rowLogin['user_uploadlimit'];
							$_SESSION[GetSession('user_status')] = $rowLogin['user_status'];
							$_SESSION[GetSession('user_planactive')] =  $rowLogin['user_planactive'];
							GetFrontRedirectUrl(GetUrl(array('module'=>'dashboard')));
							exit();
						}else{
							$_SESSION[GetSession('Error')] ='<div class="alert alert-danger" id="wrong"><strong> Failure! </strong>your account is temporarily disabled!</div>';
							GetFrontRedirectUrl(GetUrl(array('module'=>$_REQUEST['module'])));
						}
					}else{
						$existRow = $GLOBALS['DB']->row("SELECT * FROM registerusers WHERE user_email = ? LIMIT 0,1",array($user_email));
						if(isset($existRow['user_outhprovider']) && $existRow['user_outhprovider'] == 1){
							$_SESSION[GetSession('Error')] ='<div class="alert alert-danger" id="wrong"><strong> Fail! </strong>Oops! Email address already used in google!</div>';
						}
						else if(isset($existRow['user_outhprovider']) && $existRow['user_outhprovider'] == 2){
							$_SESSION[GetSession('Error')] ='<div class="alert alert-danger" id="wrong"><strong> Fail! </strong>Oops! Email address already used in facebook!</div>';
						}
						else{
							$_SESSION[GetSession('Error')] ='<div class="alert alert-danger" id="wrong"><strong> Fail! </strong>not found any user associate this email address!</div>';
						}
						GetFrontRedirectUrl(GetUrl(array('module'=>$_REQUEST['module'])));
					}
				}else{
					$_SESSION['x_oauth_token'] = '';
					$GLOBALS['Message'] ='<div class="alert alert-danger" id="wrong"><strong> Failure! </strong>invalid token please try again!</div>';
					GetFrontRedirectUrl(GetUrl(array('module'=>$_REQUEST['module'])));
				}
			}

			// Sign in with X END

			
			if (isset($_REQUEST['oauth_verifier'], $_REQUEST['oauth_token']) && $_REQUEST['oauth_token'] == $_SESSION['x_oauth_token']) {
				$request_token = [];
				$request_token['oauth_token'] = $_SESSION['x_oauth_token'];
				$request_token['oauth_token_secret'] = $_SESSION['x_oauth_token_secret'];
				$connection = new TwitterOAuth('3k5COeQodclpbzNHM0dy7zsD7', '6avwSk2AHXJSZcrJ88mUZin26eaa2F3A5JACQVA3NVzt0Yxy13', $request_token['oauth_token'], $request_token['oauth_token_secret']);
				$access_token = $connection->oauth("oauth/access_token", array("oauth_verifier" => $_REQUEST['oauth_verifier']));
				$_SESSION['access_token'] = $access_token;
				$connection = new TwitterOAuth('3k5COeQodclpbzNHM0dy7zsD7','6avwSk2AHXJSZcrJ88mUZin26eaa2F3A5JACQVA3NVzt0Yxy13', $access_token['oauth_token'], $access_token['oauth_token_secret']);
				$user = $connection->get("account/verify_credentials", ['include_email' => 'true']);
				$nameFromXArray = explode(" ", $user->name);
				$GLOBALS['user_firstname'] = isset($nameFromXArray[0]) ? $nameFromXArray[0] : "";
				$GLOBALS['user_lastname'] = isset($nameFromXArray[1]) ? $nameFromXArray[1] : "";
				$GLOBALS['user_email'] = $user->email;
				$GLOBALS['signup_google_user_password_display_hide'] = 'style="display:none;"';
				$GLOBALS['user_password'] = "CustomEsign#2024@xSigup";
				$GLOBALS['user_x_id'] = $user->id;
				$GLOBALS['user_outhprovider'] = "3";
				$_SESSION['x_oauth_token'] = FALSE;
			}else{
				$_SESSION['x_oauth_token'] = FALSE;
				$GLOBALS['Message'] ='<div class="alert alert-danger" id="wrong"><strong> Failure! </strong>invalid token please try again!</div>';
				GetFrontRedirectUrl(GetUrl(array('module'=>$_REQUEST['module'])));
			}
		}
		if(isset($_REQUEST['id']) && $_REQUEST['category_id'] != 'signupwithgoogle' && $_REQUEST['category_id'] != 'signupwithfacebook' && $_REQUEST['category_id'] != 'signupwithx' && $_POST['registersubmit'] != 1){
			$_SESSION['FREE_TRIAL_SIGNATURE_ID'] = $_REQUEST['id'];
			$freeTrialSignatureDetails = json_decode($this->receiveFreeTrialSavedSignature($_REQUEST['id']));
			$GLOBALS['FREE_TRIAL_SIGNATURE_PREVIEW'] = $freeTrialSignatureDetails->signature_html;
			$GLOBALS['user_firstname'] = $freeTrialSignatureDetails->signature_firstname;
			$GLOBALS['user_lastname'] = $freeTrialSignatureDetails->signature_lastname;
			$GLOBALS['user_organization'] = $freeTrialSignatureDetails->signature_company;
			$GLOBALS['user_email'] = $freeTrialSignatureDetails->signature_email;
		}
		else if(isset($_REQUEST['category_id']) && $_REQUEST['category_id'] != 'signupwithgoogle' && $_REQUEST['category_id'] != 'signupwithfacebook' && $_REQUEST['category_id'] != 'signupwithx' && $_POST['registersubmit'] != 1){
			$_SESSION['FREE_TRIAL_SIGNATURE_ID'] = $_REQUEST['id'];
			$freeTrialSignatureDetails = json_decode($this->receiveFreeTrialSavedSignature($_REQUEST['category_id']));
			$GLOBALS['FREE_TRIAL_SIGNATURE_PREVIEW'] = $freeTrialSignatureDetails->signature_html;
			$GLOBALS['user_firstname'] = $freeTrialSignatureDetails->signature_firstname;
			$GLOBALS['user_lastname'] = $freeTrialSignatureDetails->signature_lastname;
			$GLOBALS['user_organization'] = $freeTrialSignatureDetails->signature_company;
			$GLOBALS['user_email'] = $freeTrialSignatureDetails->signature_email;
		}
		
		if(isset($_REQUEST['category_id'])){
			$action = trim($_REQUEST['category_id']);
		} else {
			$action = '';
		}
		
		if($_REQUEST['category_id'] == 'forgotpassword'){
			
			if(isset($_POST['forgotpass'])){
				$user_email = trim($_POST['user_email']);
				$stauts =1;
				$rowFpw = $GLOBALS['DB']->row("SELECT * FROM `registerusers` WHERE BINARY LOWER(`user_email`) = LOWER(?) AND `user_status`=?",array($user_email,$stauts));	
				if($rowFpw){
					$GLOBALS['FPWEmail']= $rowFpw['user_email'];
					$GLOBALS['UFName'] = $rowFpw['user_firstname'];
					$key = md5($rowFpw['user_password']);
					$id = md5($rowFpw['user_id']);
					$GLOBALS['FPWLink']=  GetUrl(array('module'=>'signin','category_id'=>'resetpass','id'=>$key,'subid'=>$id));
					$to = $rowFpw['user_email'];
					$message= _getEmailTemplate('forget_password');
					$send_mail = _SendMail($to,'',$GLOBALS['EMAIL_SUBJECT'],$message);
					if($send_mail){
						$_SESSION[GetSession('Error')] ='<div class="gap-8 py-5 px-4 pl-11 border-l-9 border-green-600 rounded-xl relative bg-white bg-gradient-to-r from-[#00B71B]/12 to-[#00B71B]/0 shadow-lg"><strong> Success! </strong>password reset link sent to your register email address!</div>';
					}else{
						$_SESSION[GetSession('Error')] ='<div class="alert alert-danger" id="wrong"><strong> Failure! </strong>please enter email address associate with your account!</div>';
					}
				}
			}


			$GLOBALS['PageName'] = 'Reset Password';
			$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/forgotpass.html');	
			$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');			
			$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');
			$GLOBALS['SIDEBAR'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.sidebar.html');
			$GLOBALS['CLA_HTML']->display();
			RemoveMessageInfo();
			exit();	
			
		}
		
		if($_POST['registersubmit'] == 1){  // SUBMIT REGISTER 

			//$_SESSION['plan_id'] = $_POST['plan_id'];
			//$_SESSION['plan_unit'] = $_POST['plan_unit'];
			foreach($_POST as $key => $value){ $GLOBALS[$key] = $value; }
					
			 if($_POST['stripeToken']!="" && $_POST['user_email'] !="" && $_POST['user_firstname'] !="" && $_POST['plan_id'] !="" && $_POST['plan_unit'] !=""){
				if($this->emailExist($_POST['user_email']) == false){
					$Subscription = $this->createSubscription($_POST['stripeToken'],$_POST['plan_id'], $_POST['plan_unit']);
					if($Subscription){	
							if(isset($_POST['user_googleid']) && $_POST['user_googleid'] !=""){ // google sign in
								$user_googleid  = $_POST['user_googleid']; 
								$user_password='';
								$user_outhprovider = $_POST['user_outhprovider']; 
							}else if(isset($_POST['user_facebookid']) && $_POST['user_facebookid'] !=""){ // facebook sign in
								$user_facebookid  = $_POST['user_facebookid']; 
								$user_password='';
								$user_outhprovider = $_POST['user_outhprovider']; 
							}else if(isset($_POST['user_x_id']) && $_POST['user_x_id'] !=""){ // X(twitter) sign in
								$user_x_id  = $_POST['user_x_id']; 
								$user_password='';
								$user_outhprovider = $_POST['user_outhprovider']; 
							}else{
								$user_googleid='';
								$user_facebookid='';
								$user_x_id='';
								$user_password = md5($_POST['user_password']);
								$user_image='';
								$user_outhprovider = 0;
							}
							
							$data =array('user_googleid'=>$user_googleid,'user_facebookid'=>$user_facebookid,'user_x_id'=>$user_x_id,'user_outhprovider'=>$user_outhprovider,'user_firstname'=>trim($_POST['user_firstname']),'user_lastname'=>trim($_POST['user_lastname']),'user_email'=>trim(strtolower($_POST['user_email'])),'user_password'=>$user_password,'user_organization'=>trim($_POST['user_organization']),'user_image'=>$user_image,'user_ip'=>$_SERVER['REMOTE_ADDR'],'user_planactive'=>1);
							$insert_id = $GLOBALS['DB']->insert("registerusers",$data);
							if($insert_id){
								if (!is_dir(GetConfig('SITE_UPLOAD_PATH') . "/signature/".$insert_id)) {
									if (!mkdir(GetConfig('SITE_UPLOAD_PATH')."/signature/".$insert_id)) {
										die("\"temp\" folder not created. Permission problem.......");
									}
								}
								if (!is_dir(GetConfig('SITE_UPLOAD_PATH') . "/signature/complete/".$insert_id)) {
									if (!mkdir(GetConfig('SITE_UPLOAD_PATH')."/signature/complete/".$insert_id)) {
										die("\"temp\" folder not created. Permission problem.......");
									}
								}
							    unset($_SESSION['plan_id']); unset($_SESSION['plan_unit']);
								$_SESSION[GetSession('Success')] ='<div class="gap-8 py-5 px-4 pl-11 border-l-9 border-green-600 rounded-xl relative bg-white bg-gradient-to-r from-[#00B71B]/12 to-[#00B71B]/0 shadow-lg"><strong>Success! </strong>Signup success signin to create new signature</div>';
								$message= _getEmailTemplate('welcome');
								$send_mail = _SendMail($_POST['user_email'],'',$GLOBALS['EMAIL_SUBJECT'],$message);
								// $this->AddgohiLevelContact();
								$this->AddBrevoContact();
								
								$dataLayerData = [];
								$userSubscriptionData = $GLOBALS['DB']->row("SELECT * FROM `registerusers_subscription` WHERE `user_id`= ?",array($insert_id));
								$userPlanData = $GLOBALS['DB']->row("SELECT * FROM `plan` WHERE `plan_id`= ?",array($userSubscriptionData["plan_id"]));
								$userData = $GLOBALS['DB']->row("SELECT * FROM `registerusers` WHERE `user_id`= ?",array($insert_id));
								
								/// ADDED FOR DATA LAYER START/////
								$dataLayerData["plan_id"] = $userSubscriptionData["plan_id"];
								$dataLayerData["plan_name"] = $userPlanData["plan_name"]." ".ucfirst($userPlanData["plan_type"]);
								$dataLayerData["plan_status"] = $userSubscriptionData["plan_status"];
								$dataLayerData["customer_id"] = $userSubscriptionData["customer_id"];
								$dataLayerData["subscription_id"] = $userSubscriptionData["subscription_id"];
								$dataLayerData["price_id"] = $userSubscriptionData["price_id"];
								$dataLayerData["plan_interval"] = $userSubscriptionData["plan_interval"];
								$dataLayerData["plan_signaturelimit"] = $userSubscriptionData["plan_signaturelimit"];
								$dataLayerData["period_start"] = $userSubscriptionData["period_start"];
								$dataLayerData["period_end"] = $userSubscriptionData["period_end"];
								$dataLayerData["invoice_amount"] = ($userSubscriptionData["invoice_amount"]/100);
								// $dataLayerData["invoice_link"] = $userSubscriptionData["invoice_link"];
								$dataLayerData["apply_coupon"] = $userSubscriptionData["apply_coupon"];
								$dataLayerData["auto_renew"] = $userSubscriptionData["auto_renew"];
								$dataLayerData["free_trial"] = $userSubscriptionData["free_trial"];
								$dataLayerData["plan_cancel"] = $userSubscriptionData["plan_cancel"];
								$dataLayerData['user_data']["user_firstname"] = $userData["user_firstname"];
								$dataLayerData['user_data']["user_lastname"] = $userData["user_lastname"];
								$dataLayerData['user_data']["user_email"] = (isset($userData["user_email"]) && !empty($userData["user_email"])) ? hash('sha256', $userData["user_email"]) : null;
								$dataLayerData['user_data']["user_phone"] = (isset($userData["user_phone"]) && !empty($userData["user_phone"])) ? hash('sha256', $userData["user_phone"]) : null;
								$dataLayerData['user_data']["user_organization"] = $userData["user_organization"];
								$dataLayerData['user_data']["user_business"] = $userData["user_business"];
								$dataLayerData['user_data']["user_job_title"] = $userData["user_job_title"];
								$dataLayerData['user_data']["user_company_size"] = $userData["user_company_size"];
								$dataLayerData['user_data']["user_team_size"] = $userData["user_team_size"];
								$dataLayerData['user_data']["user_email_platform"] = $userData["user_email_platform"];
								$dataLayerData['user_data']["heard_about_us"] = $userData["heard_about_us"];
								$dataLayerData['user_data']["what_brought_you"] = $userData["what_brought_you"];
								
								$datalayer = json_encode($dataLayerData);
								$redirect_thnk = GetUrl(array('module'=>'thanks')).'/register?customer_id='.$insert_id.'&datalayer='.$datalayer;
								
								/// ADDED FOR DATA LAYER END/////
								// $redirect_thnk = GetUrl(array('module'=>'thanks')).'/register?customer_id='.$insert_id;
								
								if(isset($_REQUEST['id'])){
									$updateResult = $GLOBALS['DB']->update('registerusers',array('user_come_from_free_trial	' => 1),array('user_id'=>$insert_id));
									$this->saveFreeSignature($insert_id,$_REQUEST['id']);
								}
								else if(isset($_REQUEST['category_id'])){
									$updateResult = $GLOBALS['DB']->update('registerusers',array('user_come_from_free_trial	' => 1),array('user_id'=>$insert_id));
									$this->saveFreeSignature($insert_id,$_REQUEST['category_id']);
								}
								else if(isset($_SESSION['FREE_TRIAL_SIGNATURE_ID'])){
									$updateResult = $GLOBALS['DB']->update('registerusers',array('user_come_from_free_trial	' => 1),array('user_id'=>$insert_id));
									$this->saveFreeSignature($insert_id,$_SESSION['FREE_TRIAL_SIGNATURE_ID']);
								}
								
								GetFrontRedirectUrl($redirect_thnk);
							}else{
								$this->checkStripe3D($_POST);
								
								$_SESSION[GetSession('Error')] ='<div class="alert alert-danger" id="wrong"><strong> Failure! </strong>somthing wrong try again</div>';
								GetFrontRedirectUrl(GetUrl(array('module'=>$_REQUEST['module'])));
			
							}
				  }else{
						if($GLOBALS['Error_code'] == 'subscription_payment_intent_requires_action'){
							$this->checkStripe3D($_POST);
						}
						
					 	$GLOBALS['Message']='<div class="alert alert-danger" id="wrong"><strong> Fail! </strong>'.$GLOBALS['Error'].' please contact administrator. if your payment debit from your account.</div>';	
				  }
				}else{
				 	$GLOBALS['Message'] ='<div class="alert alert-danger" id="wrong"><strong> Fail! </strong>email address already registered!</div>';
				}
			 }else{
			 	$GLOBALS['Message'] ='<div class="alert alert-danger" id="wrong"><strong> Fail! </strong>please enter all required field!</div>';
			 }
		}
		
		$this->getPage();
		if(isset($_SESSION['plan_id']) && isset($_SESSION['plan_unit'])){ 
			$GLOBALS['plan_id'] = $_SESSION['plan_id']; 
			$GLOBALS['plan_unit'] = $_SESSION['plan_unit']; 
			$GLOBALS['free_trial'] = $_SESSION['free_trial'];
			$GLOBALS['plan_from_pricing'] = $_SESSION['plan_from_pricing'];
		}else{ 
			 $GLOBALS['plan_id'] = 4;
			 $GLOBALS['plan_unit'] = 1;
			 $GLOBALS['free_trial'] =0; 
		}
		$GLOBALS['freet_display'] = $_SESSION['free_trial'] == 1 ? '' : 'hidden';
		$GLOBALS['chnage_planlink'] = $_SESSION['free_trial'] == 1 ? $GLOBALS['linkModulePricing'] : 'javascript:void(0);';


		/*if(isset($_REQUEST['signature'])){
			$GLOBALS['FREE_TRIAL_SIGNATURE_PREVIEW'] = '<img src="https://betatry.customesignature.com/upload/signature/'.$_REQUEST['signature'].'.png" id="signature_img">';
		}else{
			$GLOBALS['FREE_TRIAL_SIGNATURE_PREVIEW'] = '<img src="https://betatry.customesignature.com/upload/default_sign_preview.png" id="signature_img">';
		}*/
			
		

		$GLOBALS['free_trial'] = '0';   // we are removing free trial plans

		$this->getPlanDetail($GLOBALS['plan_id'],$GLOBALS['plan_unit']);
		$GLOBALS['REGISTER_FORM_ACTION_URL'] = GetUrl(array('module'=>'signup'));
		if(isset($_REQUEST['id'])){
			$GLOBALS['REGISTER_FORM_ACTION_URL'] = GetUrl(array('module'=>'signup','id'=>$_REQUEST['id']));	
		}
		else if(isset($_REQUEST['category_id'])){
			$GLOBALS['REGISTER_FORM_ACTION_URL'] = GetUrl(array('module'=>'signup','id'=>$_REQUEST['category_id']));	
		}
		$GLOBALS['STRIPE_PUBLISHABLE_KEY'] = GetConfig('STRIPE_PUBLISHABLE_KEY');
		$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/register.html');	
		$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');			
		$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');
		$GLOBALS['SIDEBAR'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.sidebar.html');
		$GLOBALS['CLA_HTML']->display();

		RemoveMessageInfo();
		exit();	
		
	}
	
	public function verifyEmail() {
		if($_REQUEST['id'] == 'otp' && isset($_SESSION[GetSession('ve_otp')])){
			$GLOBALS['ve_step'] = 2;
			$GLOBALS['otp_email'] = $_SESSION[GetSession('ve_email')];
		}else{
			$GLOBALS['ve_step'] = 1;
		}

		// otp verify
		if(isset($_SESSION[GetSession('ve_otp')]) && $_POST['digit-1'] !="" && $_POST['digit-2'] !="" && $_POST['digit-3'] !="" && $_POST['digit-4'] !="" && $_POST['digit-5'] !="" && $_POST['digit-6'] !=""){
			$otp = $_POST['digit-1'].$_POST['digit-2'].$_POST['digit-3'].$_POST['digit-4'].$_POST['digit-5'].$_POST['digit-6'];
			if($otp == $_SESSION[GetSession('ve_otp')]){
				$_SESSION[GetSession('ve_otpverify')] = $otp;
				GetFrontRedirectUrl(GetUrl(array('module'=>$_REQUEST['module'],'category_id'=>'userdetails','email'=>bin2hex($_SESSION[GetSession('ve_email')]),'password'=>bin2hex($_SESSION[GetSession('ve_password')]))));
			}else{
				$_SESSION[GetSession('Error')]='<div class="success-error-message fixed top-0 right-0 p-3"><div class="gap-8 py-5 px-4 pl-11 border-l-9 border-red-600 rounded-xl relative bg-white bg-gradient-to-r from-[#EB4545]/12 to-[#EB4545]/0 shadow-lg"><img draggable="false" class="absolute left-4" src="%%DEFINE_IMAGE_LINK%%/images/error-message-icon.svg" alt=""><strong>Fail!</strong> code not match!.</div></div>';
				GetFrontRedirectUrl(GetUrl(array('module'=>$_REQUEST['module'],'category_id'=>$_REQUEST['category_id'],'id'=>'otp')));
			}
		}

		// resend otp
		if($_REQUEST['id'] == 'resendotp' && isset($_SESSION[GetSession('ve_email')])){
			$GLOBALS['VEEmail']= $_SESSION[GetSession('ve_email')];
			$GLOBALS['veotp'] = $_SESSION[GetSession('ve_otp')];
			$to = $_SESSION[GetSession('ve_email')];
			$message= _getEmailTemplate('register_verify_email');
			$send_mail = _SendMail($to,'',$GLOBALS['EMAIL_SUBJECT'],$message);
			if($send_mail){
				$_SESSION[GetSession('Success')]='<div class="success-error-message fixed top-0 right-0 p-3"><div class="gap-8 py-5 px-4 pl-11 border-l-9 border-green-600 rounded-xl relative bg-white bg-gradient-to-r from-[#00B71B]/12 to-[#00B71B]/0 shadow-lg" id="success"><img draggable="false" class="absolute left-4" src="%%DEFINE_IMAGE_LINK%%/images/success-message-icon.svg" alt=""><strong>Success!</strong> OTP sent to your email account!.</div></div>';
				GetFrontRedirectUrl(GetUrl(array('module'=>$_REQUEST['module'],'category_id'=>$_REQUEST['category_id'],'id'=>'otp')));
			}else{
				$_SESSION[GetSession('Error')]='<div class="success-error-message fixed top-0 right-0 p-3"><div class="gap-8 py-5 px-4 pl-11 border-l-9 border-red-600 rounded-xl relative bg-white bg-gradient-to-r from-[#EB4545]/12 to-[#EB4545]/0 shadow-lg"><img draggable="false" class="absolute left-4" src="%%DEFINE_IMAGE_LINK%%/images/error-message-icon.svg" alt=""><strong>Fail!</strong> mail not sent try again!.</div></div>';
				GetFrontRedirectUrl(GetUrl(array('module'=>$_REQUEST['module'],'category_id'=>$_REQUEST['category_id'],'id'=>'otp')));
			}
		}

		// send otp
		if($_POST['ve_email']){
			$user_email = $_POST['ve_email'];
			$user_password = $_POST['ve_password'];

			$rowVE = $GLOBALS['DB']->row("SELECT * FROM `registerusers` WHERE user_email = ?",array($user_email));
			$rowVESubUser = $GLOBALS['DB']->row("SELECT * FROM `registerusers_sub_users` WHERE email = ?",array($user_email));
			
			if($rowVE){
				$_SESSION[GetSession('Error')]='<div class="success-error-message fixed top-0 right-0 p-3"><div class="alert alert-danger gap-8 py-5 px-4 pl-11 border-l-9 border-red-600 rounded-xl relative bg-white bg-gradient-to-r from-[#EB4545]/12 to-[#EB4545]/0 shadow-lg"><img draggable="false" class="absolute left-4" src="%%DEFINE_IMAGE_LINK%%/images/error-message-icon.svg" alt=""><strong>Fail!</strong> email address already registered!</div></div>';
				GetFrontRedirectUrl(GetUrl(array('module'=>$_REQUEST['module'],'category_id'=>$_REQUEST['category_id'])));
			}
			else if($rowVESubUser){
				$_SESSION[GetSession('Error')]='<div class="success-error-message fixed top-0 right-0 p-3"><div class="alert alert-danger gap-8 py-5 px-4 pl-11 border-l-9 border-red-600 rounded-xl relative bg-white bg-gradient-to-r from-[#EB4545]/12 to-[#EB4545]/0 shadow-lg"><img draggable="false" class="absolute left-4" src="%%DEFINE_IMAGE_LINK%%/images/error-message-icon.svg" alt=""><strong>Fail!</strong> email address already registered!</div></div>';
				GetFrontRedirectUrl(GetUrl(array('module'=>$_REQUEST['module'],'category_id'=>$_REQUEST['category_id'])));
			}
			else{
				$GLOBALS['VEEmail'] = $user_email;
				$GLOBALS['veotp'] = rand(100000,999999);
				$_SESSION[GetSession('ve_otp')] = $GLOBALS['veotp'];
				$_SESSION[GetSession('ve_email')] = $user_email;
				$_SESSION[GetSession('ve_password')] = $user_password;
				$to = $user_email;
				$message= _getEmailTemplate('register_verify_email');
				$send_mail = _SendMail($to,'',$GLOBALS['EMAIL_SUBJECT'],$message);
				if($send_mail){
					$_SESSION[GetSession('Success')]='<div class="success-error-message fixed top-3 right-3"><div class="gap-8 py-5 px-4 pl-11 border-l-9 border-green-600 rounded-xl relative bg-white bg-gradient-to-r from-[#00B71B]/12 to-[#00B71B]/0 shadow-lg" id="success"><img draggable="false" class="absolute left-4" src="%%DEFINE_IMAGE_LINK%%/images/success-message-icon.svg" alt=""><strong>Success!</strong> OTP sent to your email account!.</div></div>';
					GetFrontRedirectUrl(GetUrl(array('module'=>$_REQUEST['module'],'category_id'=>$_REQUEST['category_id'],'id'=>'otp')));
				}else{
					$_SESSION[GetSession('Error')]='<div class="success-error-message fixed top-0 right-0 p-3"><div class="gap-8 py-5 px-4 pl-11 border-l-9 border-red-600 rounded-xl relative bg-white bg-gradient-to-r from-[#EB4545]/12 to-[#EB4545]/0 shadow-lg"><img draggable="false" class="absolute left-4" src="%%DEFINE_IMAGE_LINK%%/images/error-message-icon.svg" alt=""><strong>Fail!</strong> mail not sent try again!.</div></div>';
					GetFrontRedirectUrl(GetUrl(array('module'=>$_REQUEST['module'],'category_id'=>$_REQUEST['category_id'])));
				}
			}
		}
		
		



		$GLOBALS['STRIPE_PUBLISHABLE_KEY'] = GetConfig('STRIPE_PUBLISHABLE_KEY');
		$GLOBALS['li_resendotp'] = GetUrl(array('module'=>$_REQUEST['module'],'category_id'=>$_REQUEST['category_id'],'id'=>'resendotp'));
		$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/register-verifyemail.html');	
		$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');			
		$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');
		$GLOBALS['SIDEBAR'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.sidebar.html');
		$GLOBALS['CLA_HTML']->display();

		RemoveMessageInfo();
		exit();
	}

	public function userDetails() {
		if(isset($_REQUEST['id'])){
			$GLOBALS['USER_EMAIL'] = hex2bin($_REQUEST['id']);
			$GLOBALS['USER_PASSWORD'] = hex2bin($_REQUEST['subid']);
		}
		else{
			$_SESSION[GetSession('Error')]='<div class="success-error-message fixed top-0 right-0 p-3"><div class="gap-8 py-5 px-4 pl-11 border-l-9 border-red-600 rounded-xl relative bg-white bg-gradient-to-r from-[#EB4545]/12 to-[#EB4545]/0 shadow-lg"><img draggable="false" class="absolute left-4" src="%%DEFINE_IMAGE_LINK%%/images/error-message-icon.svg" alt=""><strong>Fail!</strong> something went wrong, try again later.</div></div>';
			GetFrontRedirectUrl(GetUrl(array('module'=>$_REQUEST['module'],'category_id'=>$_REQUEST['category_id'],'id'=>'otp')));
		}
		$GLOBALS['STRIPE_PUBLISHABLE_KEY'] = GetConfig('STRIPE_PUBLISHABLE_KEY');
		$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/register-userdetails.html');	
		$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');			
		$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');
		$GLOBALS['SIDEBAR'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.sidebar.html');
		$GLOBALS['CLA_HTML']->display();

		RemoveMessageInfo();
		exit();
	}

	public function registerFormSubmit() {
		$postData = $_POST;
		
		$rowUser = $GLOBALS['DB']->row("SELECT * FROM `registerusers` WHERE user_email = ?",array($postData['register_user_email']));
		$rowSubUser = $GLOBALS['DB']->row("SELECT * FROM `registerusers_sub_users` WHERE email = ?",array($postData['register_user_email']));
		
		if($rowUser){
			$_SESSION[GetSession('Error')] = '<div class="success-error-message fixed top-0 right-0 p-3"><div class="alert alert-danger gap-8 py-5 px-4 pl-11 border-l-9 border-red-600 rounded-xl relative bg-white bg-gradient-to-r from-[#EB4545]/12 to-[#EB4545]/0 shadow-lg"><img draggable="false" class="absolute left-4" src="%%DEFINE_IMAGE_LINK%%/images/error-message-icon.svg" alt=""><strong>Fail!</strong> email address already registered!</div></div>';
			$returnData = array('error'=>1,'redirect_url'=>GetUrl(array('module'=>$_REQUEST['module'],'category_id'=>'verifyemail')));
			header('Content-Type: application/json');
			echo json_encode($returnData);exit();
		}
		else if($rowSubUser){
			$_SESSION[GetSession('Error')] = '<div class="success-error-message fixed top-0 right-0 p-3"><div class="alert alert-danger gap-8 py-5 px-4 pl-11 border-l-9 border-red-600 rounded-xl relative bg-white bg-gradient-to-r from-[#EB4545]/12 to-[#EB4545]/0 shadow-lg"><img draggable="false" class="absolute left-4" src="%%DEFINE_IMAGE_LINK%%/images/error-message-icon.svg" alt=""><strong>Fail!</strong> email address already registered!</div></div>';
			$returnData = array('error'=>1,'redirect_url'=>GetUrl(array('module'=>$_REQUEST['module'],'category_id'=>'verifyemail')));
			header('Content-Type: application/json');
			echo json_encode($returnData);exit();
		}
		$user_password = md5($postData['register_user_password']);
		$data =array('user_firstname'=>trim($postData['user_first_name']),'user_lastname'=>trim($postData['user_last_name']),'user_email'=>trim(strtolower($postData['register_user_email'])),'user_phone'=>$postData['user_phone'],'user_password'=>$user_password,'user_organization'=>trim($postData['user_organization']),'user_business'=>$postData['user_business'],'user_company_size'=>$postData['user_company_size'],'user_job_title'=>$postData['user_job_title'],'user_team_size'=>$postData['user_team_size'],'user_email_platform'=>$postData['user_email_platform'],'heard_about_us'=>$postData['heard_about_us'],'what_brought_you'=>$postData['what_brought_you'],'user_ip'=>$_SERVER['REMOTE_ADDR'],'user_planactive'=>1);

		$insert_id = $GLOBALS['DB']->insert("registerusers",$data);
		if($insert_id){
			$dataSubscription = array('user_id' => $insert_id, 'plan_status' => 1,'period_end'=>'7289568000','plan_signaturelimit'=>1);
			$GLOBALS['DB']->insert("registerusers_subscription",$dataSubscription);
			if (!is_dir(GetConfig('SITE_UPLOAD_PATH') . "/signature/".$insert_id)) {
				if (!mkdir(GetConfig('SITE_UPLOAD_PATH')."/signature/".$insert_id)) {
					die();
					$_SESSION[GetSession('Error')] = '<div class="success-error-message fixed top-0 right-0 p-3"><div class="gap-8 py-5 px-4 pl-11 border-l-9 border-red-600 rounded-xl relative bg-white bg-gradient-to-r from-[#EB4545]/12 to-[#EB4545]/0 shadow-lg"><img draggable="false" class="absolute left-4" src="%%DEFINE_IMAGE_LINK%%/images/error-message-icon.svg" alt=""><strong>Fail!</strong> temp folder not created. Permission problem.</div></div>';
					$returnData = array('error'=>1,'redirect_url'=>GetUrl(array('module'=>$_REQUEST['module'],'category_id'=>'verifyemail')));
					header('Content-Type: application/json');
					echo json_encode($returnData);exit();
				}
			}
			if (!is_dir(GetConfig('SITE_UPLOAD_PATH') . "/signature/complete/".$insert_id)) {
				if (!mkdir(GetConfig('SITE_UPLOAD_PATH')."/signature/complete/".$insert_id)) {
					$_SESSION[GetSession('Error')] = '<div class="success-error-message fixed top-0 right-0 p-3"><div class="gap-8 py-5 px-4 pl-11 border-l-9 border-red-600 rounded-xl relative bg-white bg-gradient-to-r from-[#EB4545]/12 to-[#EB4545]/0 shadow-lg"><img draggable="false" class="absolute left-4" src="%%DEFINE_IMAGE_LINK%%/images/error-message-icon.svg" alt=""><strong>Fail!</strong> temp folder not created. Permission problem.</div></div>';
					$returnData = array('error'=>1,'redirect_url'=>GetUrl(array('module'=>$_REQUEST['module'],'category_id'=>'verifyemail')));
					header('Content-Type: application/json');
					echo json_encode($returnData);exit();
				}
			}
			unset($_SESSION['plan_id']); unset($_SESSION['plan_unit']);
		$_SESSION[GetSession('Success')] ='<div class="success-error-message fixed top-0 right-0 p-3"><div class="gap-8 py-5 px-4 pl-11 border-l-9 border-green-600 rounded-xl relative bg-white bg-gradient-to-r from-[#00B71B]/12 to-[#00B71B]/0 shadow-lg"><img draggable="false" class="absolute left-4" src="%%DEFINE_IMAGE_LINK%%/images/success-message-icon.svg" alt=""><strong>Success! </strong>Signup success signin to create new signature</div></div>';
			$message= _getEmailTemplate('welcome');
			$send_mail = _SendMail($_POST['user_email'],'',$GLOBALS['EMAIL_SUBJECT'],$message);
			// $this->AddgohiLevelContact();
			$this->AddBrevoContact();
			
			$dataLayerData = [];
			$userSubscriptionData = $GLOBALS['DB']->row("SELECT * FROM `registerusers_subscription` WHERE `user_id`= ?",array($insert_id));
			$userPlanData = $GLOBALS['DB']->row("SELECT * FROM `plan` WHERE `plan_id`= ?",array($userSubscriptionData["plan_id"]));
			$userData = $GLOBALS['DB']->row("SELECT * FROM `registerusers` WHERE `user_id`= ?",array($insert_id));
			
			/// ADDED FOR DATA LAYER START/////
			$dataLayerData["plan_id"] = $userSubscriptionData["plan_id"];
			$dataLayerData["plan_name"] = $userPlanData["plan_name"]." ".ucfirst($userPlanData["plan_type"]);
			$dataLayerData["plan_status"] = $userSubscriptionData["plan_status"];
			$dataLayerData["customer_id"] = $userSubscriptionData["customer_id"];
			$dataLayerData["subscription_id"] = $userSubscriptionData["subscription_id"];
			$dataLayerData["price_id"] = $userSubscriptionData["price_id"];
			$dataLayerData["plan_interval"] = $userSubscriptionData["plan_interval"];
			$dataLayerData["plan_signaturelimit"] = $userSubscriptionData["plan_signaturelimit"];
			$dataLayerData["period_start"] = $userSubscriptionData["period_start"];
			$dataLayerData["period_end"] = $userSubscriptionData["period_end"];
			$dataLayerData["invoice_amount"] = ($userSubscriptionData["invoice_amount"]/100);
			// $dataLayerData["invoice_link"] = $userSubscriptionData["invoice_link"];
			$dataLayerData["apply_coupon"] = $userSubscriptionData["apply_coupon"];
			$dataLayerData["auto_renew"] = $userSubscriptionData["auto_renew"];
			$dataLayerData["free_trial"] = $userSubscriptionData["free_trial"];
			$dataLayerData["plan_cancel"] = $userSubscriptionData["plan_cancel"];
			$dataLayerData['user_data']["user_firstname"] = $userData["user_firstname"];
			$dataLayerData['user_data']["user_lastname"] = $userData["user_lastname"];
			$dataLayerData['user_data']["user_email"] = (isset($userData["user_email"]) && !empty($userData["user_email"])) ? hash('sha256', $userData["user_email"]) : null;
			$dataLayerData['user_data']["user_phone"] = (isset($userData["user_phone"]) && !empty($userData["user_phone"])) ? hash('sha256', $userData["user_phone"]) : null;
			$dataLayerData['user_data']["user_organization"] = $userData["user_organization"];
			$dataLayerData['user_data']["user_business"] = $userData["user_business"];
			$dataLayerData['user_data']["user_job_title"] = $userData["user_job_title"];
			$dataLayerData['user_data']["user_company_size"] = $userData["user_company_size"];
			$dataLayerData['user_data']["user_team_size"] = $userData["user_team_size"];
			$dataLayerData['user_data']["user_email_platform"] = $userData["user_email_platform"];
			$dataLayerData['user_data']["heard_about_us"] = $userData["heard_about_us"];
			$dataLayerData['user_data']["what_brought_you"] = $userData["what_brought_you"];

			$datalayer = json_encode($dataLayerData);
			$redirect_thnk = GetUrl(array('module'=>'thanks'));
			$returnData = array('error'=>0,'redirect_thnk'=>$redirect_thnk,'datalayer'=>$datalayer);
			header('Content-Type: application/json');
			echo json_encode($returnData);
			exit;
		}
	}
	
	public function checkStripe3D($postArray)
	{
		$this->getPlanDetail($_POST['plan_id'], $_POST['plan_unit'],0);
		$postJson = json_encode($postArray);
		$redirect_sucess = GetUrl(array('module'=>'signup')).'?session_id={CHECKOUT_SESSION_ID}&3Dsecuresuccess=true&postarray='.$postJson;
		$rowAI = $GLOBALS['DB']->AutoIncrement("registerusers");
		$user_id = $rowAI['Auto_increment'];
		$session = \Stripe\Checkout\Session::create([
			'success_url' => $redirect_sucess,
			'cancel_url' => GetUrl(array('module'=>$_REQUEST['module'])),
			'metadata' => array('user_id'=>$user_id,'plan_id'=>$_POST['plan_id'],'plan_unit'=>$GLOBALS['plan_unit']),
			'mode' => 'subscription',
			'line_items' => [[
				'price' => $GLOBALS['plan_priceid'],
				'quantity' => $GLOBALS['plan_unit'],
			]],
		]);
		header("Location: " . $session->url);
	}
	
	public function createuserforsuccess3D()
	{
		$postDatajson = $_REQUEST['postarray'];
		$postData = json_decode($postDatajson);
		$sessionId = $_REQUEST['session_id'];
		
		$stripe = new \Stripe\StripeClient(GetConfig('STRIPE_SECRET_KEY'));
		$session = $stripe->checkout->sessions->retrieve(
		  $sessionId,
		  []
		);
		$subscriptionId = $session->subscription;
		$metadata = $session->metadata;
		$subscription = $stripe->subscriptions->retrieve($subscriptionId, []);
		$subscription->metadata = $metadata;
		$invoice = $stripe->invoices->retrieve($subscription->latest_invoice, []);
		$subscription->latest_invoice = $invoice->jsonSerialize();
		
		$GLOBALS['plan_signaturelimit'] = $postData->plan_unit;
		$this->addStripeSubscriptionData($subscription->jsonSerialize());
		if(isset($postData->user_googleid) && $postData->user_googleid !=""){ // google sign in
			$user_googleid  = $postData->user_googleid; 
			$user_password='';
			$user_outhprovider = $postData->user_outhprovider; 
		}else if(isset($postData->user_facebookid) && $postData->user_facebookid !=""){ // facebook sign in
			$user_facebookid  = $postData->user_facebookid; 
			$user_password='';
			$user_outhprovider = $postData->user_outhprovider; 
		}else if(isset($postData->user_x_id) && $postData->user_x_id !=""){ // X(twitter) sign in
			$user_x_id  = $postData->user_x_id; 
			$user_password='';
			$user_outhprovider = $postData->user_outhprovider; 
		}else{
			$user_googleid='';
			$user_facebookid='';
			$user_x_id='';
			$user_password = md5($postData->user_password);
			$user_image='';
			$user_outhprovider = 0;
		}
		$data =array('user_googleid'=>$user_googleid,'user_facebookid'=>$user_facebookid,'user_x_id'=>$user_x_id,'user_outhprovider'=>$user_outhprovider,'user_firstname'=>trim($postData->user_firstname),'user_lastname'=>trim($postData->user_lastname),'user_email'=>trim(strtolower($postData->user_email)),'user_password'=>$user_password,'user_organization'=>trim($postData->user_organization),'user_image'=>$user_image,'user_ip'=>$_SERVER['REMOTE_ADDR'],'user_planactive'=>1);
		$insert_id = $GLOBALS['DB']->insert("registerusers",$data);
		if($insert_id){
			if (!is_dir(GetConfig('SITE_UPLOAD_PATH') . "/signature/".$insert_id)) {
				if (!mkdir(GetConfig('SITE_UPLOAD_PATH')."/signature/".$insert_id)) {
					die("\"temp\" folder not created. Permission problem.......");
				}
			}
			if (!is_dir(GetConfig('SITE_UPLOAD_PATH') . "/signature/complete/".$insert_id)) {
				if (!mkdir(GetConfig('SITE_UPLOAD_PATH')."/signature/complete/".$insert_id)) {
					die("\"temp\" folder not created. Permission problem.......");
				}
			}
				unset($_SESSION['plan_id']); unset($_SESSION['plan_unit']);
			$_SESSION[GetSession('Success')] ='<div class="success-error-message fixed top-0 right-0 p-3"><div class="gap-8 py-5 px-4 pl-11 border-l-9 border-green-600 rounded-xl relative bg-white bg-gradient-to-r from-[#00B71B]/12 to-[#00B71B]/0 shadow-lg"><img draggable="false" class="absolute left-4" src="%%DEFINE_IMAGE_LINK%%/images/success-message-icon.svg" alt=""><strong>Success! </strong>Signup success signin to create new signature</div></div>';
			$message= _getEmailTemplate('welcome');
			$send_mail = _SendMail($postData->user_email,'',$GLOBALS['EMAIL_SUBJECT'],$message);
			// $this->AddgohiLevelContact();
			$this->AddBrevoContact();
			
			$dataLayerData = [];
			$userSubscriptionData = $GLOBALS['DB']->row("SELECT * FROM `registerusers_subscription` WHERE `user_id`= ?",array($insert_id));
			
			GetFrontRedirectUrl(GetUrl(array('module'=>'thanks')));
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
	
	public function emailExist($email){
		$existRow = $GLOBALS['DB']->row("SELECT user_id FROM registerusers WHERE user_email = ? LIMIT 0,1",array($email));
		if(isset($existRow['user_id'])){ return true; }
		return false;
	}
	
	// public function getPlanDetail($plan_id){
	// 	$plan_rows = $GLOBALS['DB']->query("SELECT * FROM `plan` WHERE plan_status=1 LIMIT 0,6");
	// 	$GLOBALS['plan_list'] ='';
	// 	foreach($plan_rows as $plan_row){
	// 		$plan_price =  GetPriceFormat($plan_row['plan_price']);
	// 		if($plan_row['plan_id'] == $plan_id){
	// 			$GLOBALS['plan_id'] = $plan_row['plan_id'];
	// 			$GLOBALS['plan_price'] = $plan_row['plan_price'];
	// 			$GLOBALS['plan_priceid'] = trim($plan_row['plan_priceid']); // stripe plan id
	// 			$GLOBALS['plan_format_price'] = GetPriceFormat($plan_row['plan_price']);
	// 			$GLOBALS['plan_type'] = $plan_row['plan_type'];
	// 			$GLOBALS['plan_name'] = $plan_row['plan_name'];
	// 			$GLOBALS['plan_desc'] = $plan_row['plan_feature'];
	// 			$dt1 = new DateTime();
	// 			$GLOBALS['plan_start'] = $dt1->format("Y-m-d");
				
	// 			if($GLOBALS['plan_type'] == 'month'){
	// 				$dt2 = new DateTime("+1 month");
	// 				$GLOBALS['plan_end'] = $dt2->format("Y-m-d");
	// 			}else{
	// 				$dt3 = new DateTime("+1 year");
	// 				$GLOBALS['plan_end'] = $dt3->format("Y-m-d");
	// 			}
	// 			$selected  = 'checked';
	// 			$GLOBALS['selected_plan'] = '<div class="order_details_box border-price"> <div class="text"><h6>'.$plan_row['plan_name'].' ('.$plan_row['plan_type'].')</h6></div> <div class="text"><h6>'.$plan_price.' USD<br><span>'.$plan_price.' USD</span></h6> </div></div>';
	// 		}else{
	// 			$selected ='';
	// 		}
	// 		$GLOBALS['plan_list'] .='<div class="signature_layot"><input type="radio" name="plan_id" id="plan_id'.$plan_row['plan_id'].'" class="hidden imgbgchk" value="'.$plan_row['plan_id'].'" '.$selected.' required="required"><label for="plan_id'.$plan_row['plan_id'].'" class="changeprice" data-price="'.$plan_price.'"><div class="order_details_box border-price"> <div class="text"><h6>'.$plan_row['plan_name'].' ('.$plan_row['plan_type'].')</h6></div> <div class="text"><h6>'.$plan_price.' USD<br><span>'.$plan_price.' USD</span></h6> </div></div> <div class="tick_container"><div class="tick"><img src="'.$GLOBALS['IMAGE_LINK'].'/images/right-icon.png" alt=""></div></div></label></div>';
	// 	}
	// }

	public function getPlanDetail($selplan_id='',$selunit='',$getunit =1){
		$planRows = $GLOBALS['DB']->query("SELECT * FROM `plan`  WHERE `plan_status` =1");
		foreach($planRows as $planRow){
			$plan_id = $planRow['plan_id'];
			$planname = strtolower($planRow['plan_name']);
			$plantype = strtolower($planRow['plan_type']);
			if($getunit == 1){
				$unitRows = $GLOBALS['DB']->query("SELECT * FROM plan_unit WHERE plan_id = ? ORDER BY plan_unit ASC",array($plan_id));
				foreach($unitRows as $unit){
					if($planname == 'basic' && $plantype == 'quarter'){
						$basic_quarter_arr[$unit['plan_unit']] = $unit['plan_unitprice'];
						$basic_quarter_arrspl[$unit['plan_unit']] = $unit['plan_unitsplprice'];
					}
					if($planname == 'pro' && $plantype == 'quarter'){
						$pro_quarter_arr[$unit['plan_unit']] = $unit['plan_unitprice'];
						$pro_quarter_arrspl[$unit['plan_unit']] = $unit['plan_unitsplprice'];
					}
					if($planname == 'basic' && $plantype == 'year'){
						$basic_year_arr[$unit['plan_unit']] = $unit['plan_unitprice'];
						$basic_year_arrspl[$unit['plan_unit']] = $unit['plan_unitsplprice'];
					}
					if($planname == 'pro' && $plantype == 'year'){
						$pro_year_arr[$unit['plan_unit']] = $unit['plan_unitprice'];
						$pro_year_arrspl[$unit['plan_unit']] = $unit['plan_unitsplprice'];
					}
					if($planRow['plan_id'] == $selplan_id && $selunit == $unit['plan_unit']){
						$plan_selprice = $unit['plan_unitprice']; 
						$plan_selpricespl = $unit['plan_unitsplprice'];
					}
				}
			}
			if($planRow['plan_id'] == $selplan_id){
					 $GLOBALS['selplan_'.$plan_id] ='';
					 $GLOBALS['plan_id'] = $plan_id;
					 $GLOBALS['plan_priceid'] = trim($planRow['plan_priceid']); 
					 $GLOBALS['plan_selunit'] = $selunit;
					 $GLOBALS['plan_selplan'.$selplan_id] = 'checked="checked"';
					 $mulperiod = $plantype == 'year' ? 12 : 3 ;
					 $GLOBALS['plan_format_price'] = GetPriceFormat($plan_selprice * $mulperiod);
					 $GLOBALS['plan_format_pricespl'] = GetPriceFormat($plan_selpricespl *$mulperiod);
					  $GLOBALS['plan_format_savings'] = GetPriceFormat(($plan_selpricespl * $mulperiod) -($plan_selprice * $mulperiod));
					 $GLOBALS['plan_price_hiden'] = ($plan_selprice * $mulperiod);
					 $GLOBALS['save_year_label'] = $plantype == 'year' ? 'hidden' : '';
					  $GLOBALS['save_text'] = $plantype == 'year' ? '' : 'hidden';
					 $offper = $plantype == 'year' ? '<span class="offper">Saving 20%</span>' : '';
					 
					 if($selplan_id %2 == 0){ // pro plan
						 $plan_text ='<li>Advanced Logo Animation</li><li>Animated Icons</li><li>Pro Layouts</li><li>2 Day Logo Turnaround</li>';
					 }else{
						 $plan_text ='<li>Basic Logo Animation</li><li>Static Icons</li><li>Basic Layouts</li><li>5 Day Logo Turnaround</li>';
					 }
					 $GLOBALS['plan_detail_formail'] = $planRow['plan_name'].' '.$selunit.' Signature'.' '.ucfirst($plantype).'ly plan' ; 
					 if( $_SESSION['free_trial'] == 1){
						 $GLOBALS['selected_plan'] = '<div class="order_details_box border-price">
						 <h6>'.$planRow['plan_name'].' ('.ucfirst($plantype).'ly)  <b style="float:none;"><span class="offper">7 Day Free Trial</b></span><b><span class="month_basicprice">7 Day Free</span></b></h6>  
						 <div class="text_price"><span>'.$selunit.'</span> Signature <div class="monthprice line-through" style="text-decoration-line:none;"><span>Then $'.$GLOBALS['plan_price_hiden'].'/'.$plantype.' after trial</span></div></div>
						 <ul><li>Static Logo (Upgrade Trial to Animate)</li><li>Animated Icon</li><li>Pro Layouts</li><li>Full Dashboard Suite</li></ul>
					   </div>';
					 }else{
					 	$GLOBALS['selected_plan'] = '<div class="order_details_box border-price">
						 <h6>'.$planRow['plan_name'].' ('.ucfirst($plantype).'ly)  <b>'.$freetrial_text.' '. $offper.'$<span class="month_basicprice">'.$plan_selprice.'</span> /mo</b></h6>  
						 <div class="text_price"><span>'.$selunit.'</span> Signature <div class="monthprice line-through">$<span>'.$plan_selpricespl.'</span></div></div>
						 <ul>'.$plan_text.'</ul>
					   </div>';
					 }
				}else{
					$selected ='';
	 			}
			}
		
		//$GLOBALS['basic_month_unit'] =  json_encode(array(1=>10,5=>15,10=>25,15=>35,20=>45,25=>50,30=>55,35=>60,40=>65,45=>70,50=>75)); 
		//$GLOBALS['pro_month_unit'] =  json_encode(array(1=>15,5=>20,10=>30,15=>40,20=>50,25=>55,30=>60,35=>65,40=>70,45=>75,50=>80)); 
		$GLOBALS['basic_quarter_unit'] = json_encode($basic_quarter_arr);
		$GLOBALS['basic_quarter_unitspl'] = json_encode($basic_quarter_arrspl);
		$GLOBALS['pro_quarter_unit'] = json_encode($pro_quarter_arr);
		$GLOBALS['pro_quarter_unitspl'] = json_encode($pro_quarter_arrspl);
		$GLOBALS['basic_year_unit'] = json_encode($basic_year_arr);
		$GLOBALS['basic_year_unitspl'] = json_encode($basic_year_arrspl);
		$GLOBALS['pro_year_unit'] = json_encode($pro_year_arr);
		$GLOBALS['pro_year_unitspl'] = json_encode($pro_year_arrspl);
		return false;
	}
	
	public function createSubscription($token,$plan_id,$plan_unit){
		// STRIPE_SECRET_KEY,  STRIPE_PUBLISHABLE_KEY, STRIPE_WEBHOOK_SECRET
		$this->getPlanDetail($plan_id,$plan_unit,0);
		$rowAI = $GLOBALS['DB']->AutoIncrement("registerusers");
		$user_id = $rowAI['Auto_increment'];
		$user_email  = $_POST['user_email'];
		$amount =  ($GLOBALS['plan_price'] * 100);
		
		$token  = $_POST['stripeToken']; 
		$name = $GLOBALS['user_firstname']; 
		$email = $GLOBALS['user_email'];
		$GLOBALS['plan_signaturelimit'] = $plan_unit;
		\Stripe\Stripe::setApiKey(GetConfig('STRIPE_SECRET_KEY'));
		
		if(isset($_POST['referral'])){
			$metadata = array('user_id' =>$user_id,'plan_id'=>$plan_id,'plan_unit'=>$plan_unit,'referral'=>$_POST['referral']);
		}else{
			$metadata = array('user_id' =>$user_id,'plan_id'=>$plan_id,'plan_unit'=>$plan_unit);
		}
		
		
		// create customer on stripe
		try {  
			 $customer = \Stripe\Customer::create(array(
			 'email' => $email,
			 'name' => $name,
			 'metadata' => $metadata, 
			 'source'  => $token 
			)); 
		
			//$cus = $customer->jsonSerialize();
			// get card detail
			$customerCard = \Stripe\Customer::retrieveSource($customer->id,$customer->default_source,array());
			$cardData = $customerCard->jsonSerialize();
			$this->saveUserCard($user_id,$cardData);
		}catch(Exception $e) {  
			$GLOBALS['Error_code'] = $e->getError()->code;
			$api_error = $e->getMessage();  
		} 

		if(empty($api_error) && $customer){   // create subscription
			try { 
				if($_SESSION['free_trial'] == 1){ // add trial period
				
					if($_POST['coupon_id']){ // apply coupon
						$coupon_id = trim($_POST['coupon_id']);
						$subscription = \Stripe\Subscription::create(array(
							"coupon" => $coupon_id, 
							"customer" => $customer->id, 
							'metadata' => array('user_id' => $user_id,'plan_id' => $plan_id,'plan_unit'=>$plan_unit),
							"items" => array( 
								array( 
									"price" => $GLOBALS['plan_priceid'],
									"quantity" => $plan_unit 
								), 
							), 
							'expand' => array('latest_invoice.payment_intent'),
							'trial_period_days' =>7
						));
					}else{
						$subscription = \Stripe\Subscription::create(array(
							"customer" => $customer->id, 
							'metadata' => array('user_id' => $user_id,'plan_id' => $plan_id,'plan_unit'=>$plan_unit),
							"items" => array( 
								array( 
									"price" => $GLOBALS['plan_priceid'], 
									"quantity" => $plan_unit 
								), 
							), 
							'expand' => array('latest_invoice.payment_intent'),
							'trial_period_days' =>7
						));
					}
				}else{
					
					if($_POST['coupon_id']){ // apply coupon
						$coupon_id = trim($_POST['coupon_id']);
						$subscription = \Stripe\Subscription::create(array(
							"coupon" => $coupon_id, 
							"customer" => $customer->id, 
							'metadata' => array('user_id' => $user_id,'plan_id' => $plan_id,'plan_unit'=>$plan_unit),
							"payment_behavior" =>'error_if_incomplete',
							"items" => array( 
								array( 
									"price" => $GLOBALS['plan_priceid'],
									"quantity" => $plan_unit 
								), 
							), 
							'expand' => array('latest_invoice.payment_intent'),
						));
					}else{
						$subscription = \Stripe\Subscription::create(array(
							"customer" => $customer->id, 
							'metadata' => array('user_id' => $user_id,'plan_id' => $plan_id,'plan_unit'=>$plan_unit),
							"payment_behavior" =>'error_if_incomplete',
							"items" => array( 
								array( 
									"price" => $GLOBALS['plan_priceid'], 
									"quantity" => $plan_unit 
								), 
							), 
							'expand' => array('latest_invoice.payment_intent'),
						));
					}
				}
			}catch(Exception $e) { 
				$GLOBALS['Error_code'] = $e->getError()->code;
				$api_error = $e->getMessage();  
			} 
			
			
			 if(empty($api_error) && $subscription){ 
				$subsData = $subscription->jsonSerialize(); 
				if($subsData['id'] != ''){ 
					return $this->addStripeSubscriptionData($subsData);
				}
			}
		}
		 $GLOBALS['Error']= $api_error; 
		return false;
	}

	private function saveUserCard($user_id,$cardData){
		if($cardData){
			$data =array('user_id'=>$user_id,'id'=>$cardData['id'],'brand'=>$cardData['brand'],'customer'=>$cardData['customer'],'exp_month'=>$cardData['exp_month'],'exp_year'=>$cardData['exp_year'],'last4'=>$cardData['last4']);
			$GLOBALS['DB']->insert("registerusers_card",$data);
		}
	}

	private function getUserCardDeatail($user_id){
		$cardRow = $GLOBALS['DB']->row("SELECT * FROM `registerusers_card` WHERE `user_id` = ?",array($user_id));
		foreach($cardRow as $key => $value){
			$GLOBALS[$key] = $value;
		}
	}
	
	
	public function addStripeSubscriptionData($subsData){
		
		$invoice_link =  $subsData['latest_invoice']['invoice_pdf'];
		$amount_paid =  $subsData['latest_invoice']['amount_paid'];
		$subscription_id =  $subsData['id'];
		$start_time = $subsData['current_period_start'];
		$end_time = $subsData['current_period_end'];
		$customer_id = $subsData['customer'];
		$customer_email =  $subsData['latest_invoice']['customer_email'];
		$plan_id = $subsData['items']['data'][0]['plan']['id'];
		$plan_interval = $subsData['items']['data'][0]['plan']['interval'];
		$userId =  $subsData['metadata']['user_id'];
		$planId = $subsData['metadata']['plan_id'];
		$coupon_id = $subsData['discount']['coupon']['id'];
		$invoice_no =$subsData['latest_invoice']['id'];

		$memRow = $GLOBALS['DB']->row("SELECT user_id FROM registerusers_subscription WHERE user_id= ?",array($userId));
		
		$free_trial = $subsData['status'] == 'trialing' ? 1 : 0;	
		if($memRow['user_id']){
			$data = array('plan_id'=>$planId,'customer_id'=>$customer_id,'subscription_id' => $subscription_id,'price_id' =>$plan_id,'plan_interval' => $plan_interval,'plan_signaturelimit'=>$GLOBALS['plan_signaturelimit'],'period_start' => $start_time,'period_end' => $end_time,'apply_coupon'=>$coupon_id,'invoice_amount'=>$amount_paid,'invoice_link' => $invoice_link,'free_trial'=>$free_trial);
			$where = array('user_id'=>$userId);
			$add = $GLOBALS['DB']->update('registerusers_subscription',$data,$where);
			
			// add transaction detail
				 $trdata =array('trn_userid'=>$userId,'trn_planid'=>$GLOBALS['plan_id'],'trn_invoiceno'=>$invoice_no,'trn_invoicefile'=>$invoice_link,'trn_total'=>$amount_paid);
			     $GLOBALS['DB']->insert("registerusers_transaction",$trdata);
				 
				 $GLOBALS['plan_amount_paid'] = number_format(($amount_paid / 100),2);
				 $message= _getEmailTemplate('subscription_admin','admin');
				_SendMailAdmin($customer_email,'',$GLOBALS['EMAIL_SUBJECT'],$message);
				 return true;
		}else{
			$data = array('user_id'=>$userId,'plan_id'=>$planId,'customer_id'=>$customer_id,'subscription_id' => $subscription_id,'price_id' =>$plan_id,'plan_interval' => $plan_interval,'plan_signaturelimit'=>$GLOBALS['plan_signaturelimit'],'period_start' => $start_time,'period_end' => $end_time,'apply_coupon'=>$coupon_id,'invoice_amount'=>$amount_paid,'invoice_link' =>$invoice_link,'free_trial'=>$free_trial);
				$add = $GLOBALS['DB']->insert("registerusers_subscription",$data);
			
				// add transaction detail
			    $trdata =array('trn_userid'=>$userId,'trn_planid'=>$GLOBALS['plan_id'],'trn_invoiceno'=>$invoice_no,'trn_invoicefile'=>$invoice_link,'trn_total'=>$amount_paid);
			    $GLOBALS['DB']->insert("registerusers_transaction",$trdata);
				
				$GLOBALS['plan_amount_paid'] = number_format(($amount_paid / 100),2);
				$message= _getEmailTemplate('subscription_admin','admin');
				_SendMailMasterAdmin($customer_email,1,$GLOBALS['EMAIL_SUBJECT'],$message);
				return true;
		}
		
		return false;

	}
	
		public function getUserDetails($id){
		$userRow = $GLOBALS['DB']->row("SELECT * FROM registerusers WHERE usr_id=? LIMIT 0,1",array($id));
		$GLOBALS['user_email'] = $userRow['usr_email'];
		$GLOBALS['user_name'] = $userRow['usr_firstname'].' '.$userRow['usr_lastname'];
	}
	
	public function getStripeUserDetails($id){
		$suserRow = $GLOBALS['DB']->row("SELECT * FROM registerusers_stripe WHERE usr_id=? LIMIT 0,1",array($id));
		$GLOBALS['subscriptionId'] = $suserRow['subscriptionId'];
		$GLOBALS['planid'] = $suserRow['priceId'];
	}
	
	private function isCouponValid($code){
		 \Stripe\Stripe::setApiKey(GetConfig('STRIPE_SECRET_KEY'));
		try {
			$coupon = \Stripe\PromotionCode::all(array("active"=>true,'code'=>$code));
			//print_r($coupon->data[0]); exit;
			$couponid = $coupon->data[0]->coupon->id;
			$percent_off = $coupon->data[0]->coupon->percent_off;
			$amount_off = $coupon->data[0]->coupon->amount_off;
			
			if($couponid){
				$plan_id = $_REQUEST['id']; // gold month, gold year
				$plan_details = $GLOBALS['stripe_plans'][$plan_id];
				$plan_price = $plan_details['price'];
				if($percent_off){
					$amount_per = ($plan_price *  $percent_off / 100);
					$plan_price = ($plan_price - $amount_per);
					$msg='Special offer applied! '.$percent_off.'% Off';
					$coupondata = array('coupon_id'=>$couponid,'coupon_amount'=>$percent_off,'coupon_type'=>'per','msg'=>$msg);
				}else{
					$amount_off = ($amount_off / 100);
					$plan_price = ($plan_price - $amount_off);
					$msg='Special offer applied! $'.$amount_off.' Off';
					$coupondata = array('coupon_id'=>$couponid,'amount'=>$amount_off,'coupon_type'=>'fix','msg'=>$msg);
				}
				return json_encode($coupondata);
				/*Stripe\PromotionCode Object ( [id] => promo_1KEqPVGE4ZvK3hRiWpCeCNOj [object] => promotion_code [active] => 1 [code] => WEMADMEMBER [coupon] => Stripe\Coupon Object ( [id] => UEf3sHtc [object] => coupon [amount_off] => [created] => 1641454160 [currency] => [duration] => forever [duration_in_months] => [livemode] => [max_redemptions] => [metadata] => Stripe\StripeObject Object ( ) [name] => Gold Membership Discount [percent_off] => 20 [redeem_by] => [times_redeemed] => 0 [valid] => 1 ) [created] => 1641454161 [customer] => [expires_at] => [livemode] => [max_redemptions] => [metadata] => Stripe\StripeObject Object ( ) [restrictions] => Stripe\StripeObject Object ( [first_time_transaction] => [minimum_amount] => [minimum_amount_currency] => ) [times_redeemed] => 0 )*/
			}else{
				$coupondata = array('coupon_id'=>0,'msg'=>'<span style="color:#F00;">Please enter a valid Discount code</span>');
				return json_encode($coupondata);
			}
		} catch(\Exception $e) {
			$coupondata = array('coupon_id'=>0,'msg'=>'<span style="color:#F00;">Please enter a valid Discount code</span>');
			return json_encode($coupondata);
		}
	}
	
	
    private function StripeWebhook(){ 
		\Stripe\Stripe::setApiKey(GetConfig('STRIPE_SECRET_KEY'));
		$endpoint_secret = GetConfig('STRIPE_WEBHOOK_SECRET');  // test secret
		$payload = @file_get_contents('php://input');
	    $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
		$event = null;
		try {
			$event = \Stripe\Webhook::constructEvent(
				$payload, $sig_header, $endpoint_secret
			);
		} catch(\UnexpectedValueException $e) {
			// Invalid payload
			return http_response_code(400);
			exit();
		} catch(\Stripe\Exception\SignatureVerificationException $e) {
			// Invalid signature
			return http_response_code(400);
			exit();
		}
		
		//Saving webhook data to database
		$eventName = $event->type;
		$eventResponse = $event->data->object;
		$subscription = $eventResponse->lines->data[0];
		$userId =  $subscription->metadata->user_id;
		
		$data['user_id'] = $userId;
		$data['event_name'] = $eventName;
		$data['event_response'] = $eventResponse;
		
		$GLOBALS['DB']->insert("stripe_webhook",$data);
		
		switch ($event->type) {
			case 'invoice.payment_succeeded': // subscription create success
			 	$paymentIntent = $event->data->object;	
				$invoice_link =  $paymentIntent->invoice_pdf;
				$amount_paid =  $paymentIntent->amount_paid;
				$subscription = $paymentIntent->lines->data[0];	
				$subscription_id =  $subscription->subscription;
				$plan_id = $subscription->plan->id;
				$start_time = $subscription->period->start;
				$end_time = $subscription->period->end;
				$customer_id = $paymentIntent->customer;
				$customer_email = $paymentIntent->customer_email;
				$plan_id = $subscription->plan->id;
				$plan_interval = $subscription->plan->interval; 
				$userId =  $subscription->metadata->user_id;
				$planId =  $subscription->metadata->plan_id;
				$planUnit =  $subscription->metadata->plan_unit;
				$invoice_no = $paymentIntent->id;
				
				$memRow = $GLOBALS['DB']->row("SELECT * FROM registerusers RU LEFT JOIN registerusers_subscription SU ON RU.user_id = SU.user_id WHERE RU.user_id=?",array($userId));
				
				if($memRow['user_status'] == 0){
					$updateResult = $GLOBALS['DB']->update('registerusers',array('user_status' => 1),array('user_id'=>$memRow['user_id']));
					if($updateResult){
						// $current_dir = GetConfig('SITE_UPLOAD_PATH') . "/signature/complete/".$userId."-expire";
						// $rename_dir =  GetConfig('SITE_UPLOAD_PATH') . "/signature/complete/".$userId;

						// if(is_dir($current_dir)){
							//// do the code put below try catch here for check local folders
						// }
						// rename($current_dir,$rename_dir);
						try {
							$bucket = $GLOBALS['BUCKETNAME'];
							$oldPrefix = "upload-beta/signature/complete/".$userId."-expire";
							$newPrefix = "upload-beta/signature/complete/".$userId;
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
							
							$oldPrefixSignatureProfile = "upload-beta/signature/profile/".$userId."-expire";
							$newPrefixSignatureProfile = "upload-beta/signature/profile/".$userId;
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
					}
				}
				if($memRow['user_id']){
					$data = array('plan_id'=>$planId,'customer_id'=>$customer_id,'subscription_id' => $subscription_id,'price_id' =>$plan_id,'plan_interval' => $plan_interval,'plan_signaturelimit' => $planUnit ,'period_start' => $start_time,'period_end' => $end_time,'invoice_link' => $invoice_link,'invoice_amount'=>$amount_paid,'plan_cancel'=>0);
					$where = array('user_id'=>$userId);
					$add = $GLOBALS['DB']->update('registerusers_subscription',$data,$where);
					
				}else{
					$data = array('plan_id'=>$planId,'customer_id'=>$customer_id,'subscription_id' => $subscription_id,'price_id' =>$plan_id,'plan_interval' => $plan_interval,'period_start' => $start_time,'period_end' => $end_time,'apply_coupon'=>$coupon_id,'invoice_link' => $invoice_link,'invoice_amount'=>$amount_paid,'plan_cancel'=>0);
					$add = $GLOBALS['DB']->insert("registerusers_subscription",$data);
				}
				$GLOBALS['amount_paid'] = GetPriceFormat($amount_paid / 100);
				$GLOBALS['payment_date'] = date('M d, Y');
				$GLOBALS['renew_date'] = date('M d, Y',$end_time);
				$GLOBALS['invoce_link'] = $invoice_link;
				$GLOBALS['plan_limit'] = $planUnit;
				$this->getPlanDetail($planId);
				$this->getUserCardDeatail($userId);
				$message= _getEmailTemplate('subscription');
				$send_mail = _SendMail($customer_email,'',$GLOBALS['EMAIL_SUBJECT'],$message);
				//https://stripe.com/docs/api/invoices/object
			break;
			case 'invoice.payment_failed':
				
				$paymentIntent = $event->data->object;	
				$invoice_link =  $paymentIntent->invoice_pdf;
				$amount_paid =  $paymentIntent->amount_paid;
				$subscription = $paymentIntent->lines->data[0];	
				$subscription_id =  $subscription->subscription;
				$plan_id = $subscription->plan->id;
				$start_time = $subscription->period->start;
				$end_time = $subscription->period->end;
				$customer_id = $paymentIntent->customer;
				$customer_email = $paymentIntent->customer_email;
				$plan_id = $subscription->plan->id;
				$plan_interval = $subscription->plan->interval; 
				$puser_id =  $subscription->metadata->user_id;
				$planId =  $subscription->metadata->plan_id;
				$planUnit =  $subscription->metadata->plan_unit;
				$invoice_no = $paymentIntent->id;
				
				
				$updateResult = $GLOBALS['DB']->update('registerusers',array('user_planactive' => 0),array('user_id'=>$puser_id));
				if($updateResult){
					// plan canceled
					$GLOBALS['DB']->update('registerusers_subscription',array('plan_cancel'=>1),array('user_id'=>$puser_id));
					// $current_dir = GetConfig('SITE_UPLOAD_PATH')."/signature/complete/".$puser_id;
					// $rename_dir =  GetConfig('SITE_UPLOAD_PATH')."/signature/complete/".$puser_id.'-expire';

					// if(is_dir($current_dir)){
						//// do the code put below try catch here for check local folders
					// }
					// rename($current_dir,$rename_dir);

					try {
						$bucket = $GLOBALS['BUCKETNAME'];
						$oldPrefix = "upload-beta/signature/complete/".$puser_id;
						$newPrefix = "upload-beta/signature/complete/".$puser_id."-expire";
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
						
						$oldPrefixSignatureProfile = "upload-beta/signature/profile/".$puser_id;
						$newPrefixSignatureProfile = "upload-beta/signature/profile/".$puser_id."-expire";
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
					
					$userRow = $GLOBALS['DB']->row("SELECT `user_email`,`user_firstname` FROM `registerusers` WHERE user_id = ?",array($puser_id));
					if($userRow['user_email'] != ""){
						$GLOBALS['USERNAME'] = $userRow['user_firstname'];
						$customer_email = $userRow['user_email'];
						$message= _getEmailTemplate('payment_failed');
						$send_mail = _SendMail($customer_email,'',$GLOBALS['EMAIL_SUBJECT'],$message);
					}
				}
				
								
				
				return http_response_code(200);
			break;
			case 'customer.subscription.created':
				$subscription = $event->data->object;	
				$subscriptionId = $subscription->id;
				$userId = $subscription->metadata->user_id;
				$free_trial = $subscription->status == 'trialing' ? 1 : 0;	
				$data = array('free_trial'=>$free_trial,'plan_cancel'=>0);
					$where = array('user_id'=>$userId);
					$add = $GLOBALS['DB']->update('registerusers_subscription',$data,$where);
				
				return http_response_code(200);
			break;
			case 'charge.refunded':
				$refundIntent = $event->data->object;
				
				if($refundIntent->refunded == true){ // check charge has been fully refunded	
					$customer_id = $refundIntent->customer;
					$userRow = $GLOBALS['DB']->row("SELECT * FROM `registerusers_subscription` US INNER JOIN registerusers RU ON US.user_id = RU.user_id WHERE US.customer_id = ? LIMIT 0,1",array($customer_id));
					if($userRow['user_id']){
						$puser_id = $userRow['user_id'];
						$updateResult = $GLOBALS['DB']->update('registerusers',array('user_planactive' => 0),array('user_id'=>$puser_id));
						if($updateResult){
							// plan canceled
							$GLOBALS['DB']->update('registerusers_subscription',array('plan_cancel'=>1,'plan_signaturelimit'=>1,'period_start'=>0,'period_end'=>0),array('user_id'=>$puser_id));
							// $current_dir = GetConfig('SITE_UPLOAD_PATH')."/signature/complete/".$puser_id;
							// $rename_dir =  GetConfig('SITE_UPLOAD_PATH')."/signature/complete/".$puser_id.'-expire';
							// if(is_dir($current_dir)){
								//// do the code put below try catch here for check local folders
							// }
							// rename($current_dir,$rename_dir);

							try {
								$bucket = $GLOBALS['BUCKETNAME'];
								$oldPrefix = "upload-beta/signature/complete/".$puser_id;
								$newPrefix = "upload-beta/signature/complete/".$puser_id."-expire";
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
								
								$oldPrefixSignatureProfile = "upload-beta/signature/profile/".$puser_id;
								$newPrefixSignatureProfile = "upload-beta/signature/profile/".$puser_id."-expire";
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
						}
					}
				}
				
				return http_response_code(200);
			break;
			
			case 'customer.subscription.updated': // subscription period end
				$subscription = $event->data->object;	
				$subscriptionId = $subscription->id;
				$userId = $subscription->metadata->user_id;
				$start_time =  $subscription->current_period_start;
				$end_time = $subscription->current_period_end;
				$free_trial = $subscription->status == 'trialing' ? 1 : 0;
				
				if($subscription->status == 'active' || $subscription->status == 'trialing'){	
					$data = array('free_trial'=>$free_trial,'plan_cancel'=>0,'period_start' => $start_time,'period_end' =>$end_time);
					$where = array('user_id'=>$userId);
					$add = $GLOBALS['DB']->update('registerusers_subscription',$data,$where);
					
					// $current_dir = GetConfig('SITE_UPLOAD_PATH') . "/signature/complete/".$userId."-expire";
					// $rename_dir =  GetConfig('SITE_UPLOAD_PATH') . "/signature/complete/".$userId;
					// if(is_dir($current_dir)){
					// 	//// do the code put below try catch here for check local folders
					// }
					// rename($current_dir,$rename_dir);

					try {
						$bucket = $GLOBALS['BUCKETNAME'];
						$oldPrefix = "upload-beta/signature/complete/".$userId."-expire";
						$newPrefix = "upload-beta/signature/complete/".$userId;
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
						
						$oldPrefixSignatureProfile = "upload-beta/signature/profile/".$userId."-expire";
						$newPrefixSignatureProfile = "upload-beta/signature/profile/".$userId;
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
				}else{
					$data = array('free_trial'=>0,'plan_cancel'=>1,'period_start' =>0,'period_end'=>0);
					$where = array('user_id'=>$userId);
					$add = $GLOBALS['DB']->update('registerusers_subscription',$data,$where);
					
					// $current_dir = GetConfig('SITE_UPLOAD_PATH')."/signature/complete/".$userId;
					// $rename_dir =  GetConfig('SITE_UPLOAD_PATH')."/signature/complete/".$userId.'-expire';
					// if(is_dir($current_dir)){
					// 	//// do the code put below try catch here for check local folders
					// }
					// rename($current_dir,$rename_dir);

					try {
						$bucket = $GLOBALS['BUCKETNAME'];
						$oldPrefix = "upload-beta/signature/complete/".$userId;
						$newPrefix = "upload-beta/signature/complete/".$userId."-expire";
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
						
						$oldPrefixSignatureProfile = "upload-beta/signature/profile/".$userId;
						$newPrefixSignatureProfile = "upload-beta/signature/profile/".$userId."-expire";
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
				}
				
				return http_response_code(200);
			break;
			
			case 'customer.subscription.deleted': // subscription period end
				$subscription = $event->data->object;	
				$subscriptionId = $subscription->id;
				$userId = $subscription->metadata->user_id;
				$message = 'S'.$memberId;

				$data = array('plan_id' => '','plan_interval' => '','period_start' => 0,'period_end' => 0,'plan_cancel'=>1);
				$where = array('user_id'=>$userId);
				$GLOBALS['DB']->update('registerusers_subscription',$data,$where);

				// update register table
					$data = array('user_planactive' => 0);
					$where = array('user_id'=>$userId);
					$GLOBALS['DB']->update('registerusers',$data,$where);
			break;
			
			case 'invoice.finalized': // invoice finalized stripe success
			 	$paymentIntent = $event->data->object;	
				$invoice_link =  $paymentIntent->invoice_pdf;
				$amount_paid =  $paymentIntent->amount_paid;
				$subscription = $paymentIntent->lines->data[0];	
				$subscription_id =  $subscription->subscription;
				$plan_id = $subscription->plan->id;
				$start_time = $subscription->period->start;
				$end_time = $subscription->period->end;
				$customer_id = $paymentIntent->customer;
				$customer_email = $paymentIntent->customer_email;
				$plan_id = $subscription->plan->id;
				$plan_interval = $subscription->plan->interval; 
				$userId =  $subscription->metadata->user_id;
				$planId =  $subscription->metadata->plan_id;
				$planUnit =  $subscription->metadata->plan_unit;
				$invoice_no = $paymentIntent->id;
				
				$memRow = $GLOBALS['DB']->row("SELECT * FROM registerusers RU LEFT JOIN registerusers_subscription SU ON RU.user_id = SU.user_id WHERE RU.user_id=?",array($userId));
					
				if($memRow['user_status'] == 0){
					$updateResult = $GLOBALS['DB']->update('registerusers',array('user_status' => 1),array('user_id'=>$memRow['user_id']));
					if($updateResult){
						// $current_dir = GetConfig('SITE_UPLOAD_PATH') . "/signature/complete/".$userId."-expire";
						// $rename_dir =  GetConfig('SITE_UPLOAD_PATH') . "/signature/complete/".$userId;
						// if(is_dir($current_dir)){
						// 	//// do the code put below try catch here for check local folders
						// }
						// // rename($current_dir,$rename_dir);

						try {
							$bucket = $GLOBALS['BUCKETNAME'];
							$oldPrefix = "upload-beta/signature/complete/".$userId."-expire";
							$newPrefix = "upload-beta/signature/complete/".$userId;
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
							
							$oldPrefixSignatureProfile = "upload-beta/signature/profile/".$userId."-expire";
							$newPrefixSignatureProfile = "upload-beta/signature/profile/".$userId;
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
					}
				}
				if($memRow['user_id']){
					$data = array('plan_id'=>$planId,'customer_id'=>$customer_id,'subscription_id' => $subscription_id,'price_id' =>$plan_id,'plan_interval' => $plan_interval,'period_start' => $start_time,'period_end' => $end_time,'invoice_link' => $invoice_link,'invoice_amount'=>$amount_paid,'plan_cancel'=>0);
					$where = array('user_id'=>$userId);
					$add = $GLOBALS['DB']->update('registerusers_subscription',$data,$where);
					
				}else{
					$data = array('plan_id'=>$planId,'customer_id'=>$customer_id,'subscription_id' => $subscription_id,'price_id' =>$plan_id,'plan_interval' => $plan_interval,'period_start' => $start_time,'period_end' => $end_time,'apply_coupon'=>$coupon_id,'invoice_link' => $invoice_link,'invoice_amount'=>$amount_paid,'plan_cancel'=>0);
					$add = $GLOBALS['DB']->insert("registerusers_subscription",$data);
				}
				$GLOBALS['amount_paid'] = GetPriceFormat($amount_paid / 100);
				$GLOBALS['payment_date'] = date('M d, Y');
				$GLOBALS['renew_date'] = date('M d, Y',$end_time);
				$GLOBALS['invoce_link'] = $invoice_link;
				$GLOBALS['plan_limit'] = $planUnit;
				$this->getPlanDetail($planId);
				$this->getUserCardDeatail($userId);
				$message= _getEmailTemplate('subscription');
				$send_mail = _SendMail($customer_email,'',$GLOBALS['EMAIL_SUBJECT'],$message);
				//https://stripe.com/docs/api/invoices/object
			break;
			
			default:
			 	return http_response_code(200);
        		exit();
		}
		$message .= 'stripe webhook run succcess!';
		//_SendMail('dhvlpatel906@gmail.com',1,'Sstripe Webhook',$message); 
		return http_response_code(200);
	}
	
	private function AddgohiLevelContact(){
		if($_POST['user_email'] != "" && $_POST['user_firstname'] !=""){
			$parts = explode(" ", $_POST['user_firstname']);
			$gh_firstname = $parts[0]; 
			$gh_lastname = $parts[1];
			$gh_email = $_POST['user_email'];
			$gh_org = $_POST['user_organization'];
			$gh_user_business = $_POST['user_business'];
			$gh_user_company_size = $_POST['user_company_size'];
			$gh_user_job_title = $_POST['user_job_title'];
			$gh_user_team_size = $_POST['user_team_size'];
			$gh_user_email_platform = $_POST['user_email_platform'];
			$gh_heard_about_us = $_POST['heard_about_us'];
			$gh_what_brought_you = $_POST['what_brought_you'];


			// retrive user if already exist on gohighlevel by email start
			$curlForExistingContact = curl_init();
				curl_setopt_array($curlForExistingContact, array(
				CURLOPT_URL => 'https://rest.gohighlevel.com/v1/contacts/?query='.$gh_email,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => '',
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 0,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_HTTPHEADER => array(
					'Content-Type: application/json',
					'Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJsb2NhdGlvbl9pZCI6Ik5HQk5lYTVnRFl5cXMwTEl5MmJPIiwiY29tcGFueV9pZCI6ImdIUVd2OGdJTjJHRUJMdktKUmN0IiwidmVyc2lvbiI6MSwiaWF0IjoxNjkxNzkwNTI5OTAxLCJzdWIiOiJZcG1salNaNWY3OXJzcEhJRGJsdSJ9.LgPs08g-ndwxUq_0q6UJFZ6HzzzWdQIShrlFNN_bycc'
				),
				));
			$responseExistingContact = curl_exec($curlForExistingContact);

			// retrive user if already exist on gohighlevel by email end

			
			// managing tags if existing user found start
			$existingContact = json_decode($responseExistingContact);
			$oldTags = [];
			if(count($existingContact->contacts) === 0){ // empty condition
				$oldTags = [];
			}else{
				$oldTags = json_decode($responseExistingContact)->contacts[0]->tags;
			}

			if(count($oldTags) === 0){ // empty condition
				$tagsArray = [
						"new user"
					];
			}else{
				$tagsArray = $oldTags;
				if (!in_array("new user", $tagsArray)){
					array_push($tagsArray,"new user");
				}
			}


			$numItems = count($tagsArray);
			$tagsArrayStr = "[";
			foreach ($tagsArray as $key => $tag) {
				if($numItems === ($key+1)){ // last element condition
					$tagsArrayStr .= '"'.$tag.'"';
				}else{
					$tagsArrayStr .= '"'.$tag.'",';
				}
			}
			$tagsArrayStr .= "]";
			// managing tags if existing user found end
			
			$curl = curl_init();
			curl_setopt_array($curl, array(
			  CURLOPT_URL => 'https://rest.gohighlevel.com/v1/contacts/',
			  CURLOPT_RETURNTRANSFER => true,
			  CURLOPT_ENCODING => '',
			  CURLOPT_MAXREDIRS => 10,
			  CURLOPT_TIMEOUT => 0,
			  CURLOPT_FOLLOWLOCATION => true,
			  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			  CURLOPT_CUSTOMREQUEST => 'POST',
			  CURLOPT_POSTFIELDS =>'{
				"email": "'.$gh_email.'",
				"firstName": "'.$gh_firstname.'",
				"lastName": "'.$gh_lastname.'",
				"companyName": "'.$gh_org.'",
				"businessName": "'.$gh_user_business.'",
				"companySize": "'.$gh_user_company_size.'",
				"jobTitle": "'.$gh_user_job_title.'",
				"teamSize": "'.$gh_user_team_size.'",
				"emailPlatform": "'.$gh_user_email_platform.'",
				"heardAboutUs": "'.$gh_heard_about_us.'",
				"whatBroughtYou": "'.$gh_what_brought_you.'",
				"website": "'.$GLOBALS['SITE_TITLE'].'",
				"tags": '. $tagsArrayStr.',
				"source": "public api"
			}',
			  CURLOPT_HTTPHEADER => array(
				'Content-Type: application/json',
				'Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJsb2NhdGlvbl9pZCI6Ik5HQk5lYTVnRFl5cXMwTEl5MmJPIiwiY29tcGFueV9pZCI6ImdIUVd2OGdJTjJHRUJMdktKUmN0IiwidmVyc2lvbiI6MSwiaWF0IjoxNjkxNzkwNTI5OTAxLCJzdWIiOiJZcG1salNaNWY3OXJzcEhJRGJsdSJ9.LgPs08g-ndwxUq_0q6UJFZ6HzzzWdQIShrlFNN_bycc'
			  ),
			));
			$response = curl_exec($curl);
			curl_close($curl);
			//echo $response;
		}
	}


	private function receiveFreeTrialSavedSignature($signature_id){
		$curl = curl_init();
			curl_setopt_array($curl, array(
			CURLOPT_URL => 'https://betatry.customesignature.com/callajax.php?category_id=receiveSignature&receive_signature_id='.$signature_id,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1
		));
		return curl_exec($curl);
	}

	private function saveFreeSignature($userId,$signature_id) {
		try{
			$curl = curl_init();
				curl_setopt_array($curl, array(
				CURLOPT_URL => 'https://betatry.customesignature.com/callajax.php?category_id=saveSignature&receive_signature_id='.$signature_id,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => '',
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 0,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1
			));
			$responseJson =  curl_exec($curl);

			$responseData = json_decode($responseJson);
			$signatureLogo = "";

			if(isset($responseData->signature_data)){
				$signatureTableData = $responseData->signature_data;
				unset($signatureTableData->signature_id);
				$signatureLogo = $signatureTableData->signature_logo;
				unset($signatureTableData->signature_logo);

				$signatureTableData->user_id = $userId;

				$signatureTableDataArray = (array) $signatureTableData;
				$signatureTableDataArrayStyle = unserialize($signatureTableDataArray['signature_style']);

				$signatureTableDataArrayStyle['signature_profileanimation1'] = array(
																			'signature_profileanimation' => '0',
																			'signature_profileanimation_gif_name' => 'giphyy-1.gif',
																			'signature_profileanimation_gif' => '1',
				);
                $signatureTableDataArray['signature_style'] = serialize($signatureTableDataArrayStyle);
				$insertedSignatureId = $GLOBALS['DB']->insert("signature",$signatureTableDataArray);


				if(isset($responseData->signature_customfield_data)){
					$signatureCustomFieldTableData = $responseData->signature_customfield_data;
					
					foreach ($signatureCustomFieldTableData as $key => $signatureCustomField) {
						$signatureCustomField->signature_id = $insertedSignatureId;
						$signatureCustomFieldArray = (array) $signatureCustomField;
						$insertedCustomFieldId = $GLOBALS['DB']->insert("signature_customfield",$signatureCustomFieldArray);
					}
				}


				$profileName = $signatureTableDataArray['signature_profile'];
				if($profileName != ""){
					$location =  GetConfig('SITE_UPLOAD_PATH').'/signature/profile/'.$profileName ;
					$url = "https://betatry.customesignature.com/upload/profile/".$profileName;
					$ch = curl_init($url);
					$fp = fopen($location, "wb");
					curl_setopt($ch, CURLOPT_FILE, $fp);
					curl_setopt($ch, CURLOPT_HEADER, 0);
					curl_exec($ch);
					curl_close($ch);
					fclose($fp);

					$result = $GLOBALS['S3Client']->putObject(array( // upload image s3bucket
						'Bucket'=>$GLOBALS['BUCKETNAME'],
						'Key' =>  'upload-beta/signature/profile/'.$profileName,
						'SourceFile' => $location,
						'StorageClass' => 'REDUCED_REDUNDANCY',
						'ACL'   => 'public-read'
					));
				}
			
				if($signatureLogo != ""){
					$extension = pathinfo($signatureLogo, PATHINFO_EXTENSION);
					if(strtolower($extension) == 'png' || strtolower($extension) == 'svg'){
						$logoName = $userId.'.png';
						if(strtolower($extension) == 'svg'){
							$logoName = $userId.'.svg';
						}
						$location =  GetConfig('SITE_UPLOAD_PATH').'/signature/'.$userId.'/'.$logoName;
						if (!file_exists(GetConfig('SITE_UPLOAD_PATH').'/signature/'.$userId)) {
							mkdir(GetConfig('SITE_UPLOAD_PATH').'/signature/'.$userId, 0777, true);
						}
						$url = "https://betatry.customesignature.com/upload/logo/".$signatureLogo;
						$ch = curl_init($url);
						$fp = fopen($location, "wb");
						curl_setopt($ch, CURLOPT_FILE, $fp);
						curl_setopt($ch, CURLOPT_HEADER, 0);
						curl_exec($ch);
						curl_close($ch);
						fclose($fp);

						$result = $GLOBALS['S3Client']->putObject(array( // upload image s3bucket
							'Bucket'=>$GLOBALS['BUCKETNAME'],
							'Key' =>  'upload-beta/signature/'.$userId.'/'.$logoName,
							'SourceFile' => $location,
							'StorageClass' => 'REDUCED_REDUNDANCY',
							'ACL'   => 'public-read'
						));

						$GLOBALS['DB']->insert("signature_logo",array('user_id'=>$userId,'logo'=>$logoName));
						$add = $GLOBALS['DB']->update('registerusers',array('user_uploadlimit'=>'0'),array('user_id'=>$userId));
					}
				}
			
			}

		}catch(Exception $e) {  
			$api_error = $e->getMessage();  
		} 
	}

	private function AddBrevoContact(){
		if($_POST['register_user_email'] != "" && ($_POST['user_first_name'] !="" || $_POST['user_last_name'] !="")){
			$gh_firstname = $_POST['user_first_name']; 
			$gh_lastname = $_POST['user_last_name'];
			$gh_email = $_POST['register_user_email'];
			$gh_phone = $_POST['user_phone'];
			$gh_org = $_POST['user_organization'];
			$gh_user_business = $_POST['user_business'];
			$gh_user_company_size = $_POST['user_company_size'];
			$gh_user_job_title = $_POST['user_job_title'];
			$gh_user_team_size = $_POST['user_team_size'];
			$gh_user_email_platform = $_POST['user_email_platform'];
			$gh_heard_about_us = $_POST['heard_about_us'];
			$gh_what_brought_you = $_POST['what_brought_you'];

			// New tags to add
			$newTags = ['lead', 'trial-user', 'marketing'];

			//Get existing tags
			$curl = curl_init();
			curl_setopt_array($curl, [
				CURLOPT_URL => 'https://api.brevo.com/v3/contacts/' . urlencode($email),
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_HTTPHEADER => [
					'accept: application/json',
					'api-key: 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'
				]
			]);
			$response = curl_exec($curl);
			if (curl_errno($curl)) {
				echo 'Curl error (GET): ' . curl_error($curl);
				exit;
			}
			curl_close($curl);

			$data = json_decode($response, true);
			$oldTags = [];
			if (isset($data['tags']) && is_array($data['tags'])) {
				$oldTags = $data['tags'];
			}

			//Merge old and new tags, remove duplicates
			$mergedTags = array_unique(array_merge($oldTags, $newTags));
			$tagsArrayStr = json_encode(implode(',', $mergedTags));
			$tagsArrayStr = str_replace('"', '', $tagsArrayStr);
			// Create/Update New Customer
			$curl = curl_init();
			curl_setopt_array($curl, array(
				CURLOPT_URL => 'https://api.brevo.com/v3/contacts',
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => '',
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 0,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => 'POST',
				CURLOPT_POSTFIELDS => '{
					"email": "'.$gh_email.'",
					"attributes": {
						"FIRSTNAME": "'.$gh_firstname.'",
						"LASTNAME": "'.$gh_lastname.'",
						"SMS": "+91'.$gh_phone.'",
						"COMPANYNAME": "'.$gh_org.'",
						"BUSINESSNAME": "'.$gh_user_business.'",
						"COMPANYSIZE": "'.$gh_user_company_size.'",
						"JOB_TITLE": "'.$gh_user_job_title.'",
						"TEAMSIZE": "'.$gh_user_team_size.'",
						"EMAILPLATFORM": "'.$gh_user_email_platform.'",
						"HEARDABOUTUS": "'.$gh_heard_about_us.'",
						"WHATBROUGHTYOU": "'.$gh_what_brought_you.'",
						"WEBSITE": "'.$GLOBALS['SITE_TITLE'].'",
						"TAGS": "'.$tagsArrayStr.'"
					},
					"updateEnabled": true
				}',
				CURLOPT_HTTPHEADER => array(
					'accept: application/json',
					'api-key: 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',
					'content-type: application/json'
				),
			));
			$response = curl_exec($curl);
			curl_close($curl);
			// echo '<pre>'; print_r($response); echo '</pre>'; exit('<br>pre exit');
		}
	}

}

?>