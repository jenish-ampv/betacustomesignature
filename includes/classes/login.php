<?php
// require_once(GetConfig('SITE_BASE_PATH').'/google-login/vendor/autoload.php');	// signup/signin With Google
// require_once(GetConfig('SITE_BASE_PATH').'/x-login/autoload.php');	// signup/signin With X(twitter)
// use Abraham\TwitterOAuth\TwitterOAuth;  // signup/signin With X(twitter)
class CIT_LOGIN
{
	
	public function __construct()
	{	

		// // signin With Google
		// $clientID = '700717033122-sp4ftaocagohsis648s6bp1dv295cp86.apps.googleusercontent.com';
		// $clientSecret = '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';
		// $redirectUri = GetUrl(array('module'=>'signin','category_id'=>'signinwithgoogle'));

		// // create Client Request to access Google API
		// $client = new Google_Client();
		// $client->setClientId($clientID);
		// $client->setClientSecret($clientSecret);
		// $client->setRedirectUri($redirectUri);
		// $client->addScope("email");
		// $client->addScope("profile");
		// $GLOBALS['GOOGLE_LOGIN_AUTHURL'] = $client->createAuthUrl();
		// $GLOBALS['GOOGLE_CLIENT_LOGIN_OBJECT'] = $client;
		// // signin With Google

		// // signin With Facebook
		// unset($_SESSION['FACEBOOK_SIGNUP_FAIL_URL']);
		// unset($_SESSION['FACEBOOK_SIGNUP_SUCCESS_URL']);
		// unset($_SESSION['FACEBOOK_SIGNUP']);
		// $GLOBALS['FACEBOOK_SIGNIN_AUTHURL'] = $GLOBALS['ROOT_LINK'].'/facebook-login/facebook-oauth.php';
		// $_SESSION['FACEBOOK_SIGNIN_FAIL_URL'] = GetUrl(array('module'=>'signin'));
		// $_SESSION['FACEBOOK_SIGNIN_SUCCESS_URL'] = GetUrl(array('module'=>'signin','category_id'=>'signinwithfacebook'));
		// $_SESSION['FACEBOOK_SIGNIN'] = TRUE;
		// // signin With Facebook

		// // signin With X(twitter)
		// if($_SESSION['x_oauth_token'] == '' || $GLOBALS['X_SIGNUP_AUTHURL'] == ""){
		// 	$redirectUriXLogin = GetUrl(array('module'=>'signup','category_id'=>'signupwithx'));
		// 	$GLOBALS['X_CONSUMER_KEY'] = '3k5COeQodclpbzNHM0dy7zsD7'; // Replace with your API key
		// 	$GLOBALS['X_CONSUMER_SECRET'] = '6avwSk2AHXJSZcrJ88mUZin26eaa2F3A5JACQVA3NVzt0Yxy13'; // Replace with your API secret key
		// 	$GLOBALS['X_OAUTH_CALLBACK'] = $redirectUriXLogin; // Replace with your callback URL
		// 	$connection = new TwitterOAuth('3k5COeQodclpbzNHM0dy7zsD7', '6avwSk2AHXJSZcrJ88mUZin26eaa2F3A5JACQVA3NVzt0Yxy13');
		// 	$request_token = $connection->oauth('oauth/request_token', array('oauth_callback' => $GLOBALS['X_OAUTH_CALLBACK']));
		// 	$_SESSION['x_oauth_token_signin'] = $request_token['oauth_token'];
		// 	$_SESSION['x_oauth_token_secret_signin'] = $request_token['oauth_token_secret'];
		// 	$GLOBALS['X_SIGNIN_AUTHURL'] = $connection->url('oauth/authorize', array('oauth_token' => $request_token['oauth_token']));
		// 	$_SESSION['X_SIGNIN_AUTHURL'] = $connection->url('oauth/authorize', array('oauth_token' => $request_token['oauth_token']));
		// }

		// // signin With X(twitter)
	}
	
	public function displayPage(){
		AddMessageInfo();	
		if(isset($_REQUEST['category_id'])){
			$action = trim($_REQUEST['category_id']);
		} else {
			$action = '';
		}


		// Sign in with google
		if($_REQUEST['category_id']=='signinwithgoogle'){
			if(isset($_REQUEST['error']) && $_REQUEST['error']=='access_denied'){
				$_SESSION[GetSession('Error')] ='<div class="alert alert-danger" id="wrong"><strong> Failure! </strong>Access Denied, please try again!</div>';
				GetFrontRedirectUrl(GetUrl(array('module'=>$_REQUEST['module'])));
			}
			$googleClient = $GLOBALS['GOOGLE_CLIENT_LOGIN_OBJECT'];

			$token = $googleClient->fetchAccessTokenWithAuthCode($_GET['code']);
			$googleClient->setAccessToken($token['access_token']);

			// get profile info
			$google_oauth = new Google_Service_Oauth2($googleClient);
			$google_account_info = $google_oauth->userinfo->get();
			$user_email = $google_account_info['email'];
			$token = $google_account_info['id'];
			$rowLogin = $GLOBALS['DB']->row("SELECT * FROM `registerusers` WHERE user_email = ?  AND user_googleid= ? AND user_outhprovider = 1  LIMIT 0,1",array($user_email,$token));
			if($rowLogin){
				if($rowLogin['user_status'] == 1){
					$_SESSION[GetSession('user_id')] = $rowLogin['user_id'];
					$_SESSION[GetSession('user_email')] = $rowLogin['user_email'];
					$_SESSION[GetSession('user_name')] = $rowLogin['user_firstname'].' '.$rowLogin['user_lastname'];
					$_SESSION[GetSession('user_firstname')] = $rowLogin['user_firstname'];
					$_SESSION[GetSession('user_lastname')] = $rowLogin['user_lastname'];
					$_SESSION[GetSession('user_uploadlimit')] = $rowLogin['user_uploadlimit'];
					$_SESSION[GetSession('user_type')] = $rowLogin['user_type'];
					// if($rowLogin['user_type'] == 'enterprise'){
					// 	$_SESSION[GetSession('user_uploadlimit')] = 1;
					// }
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
				if(isset($existRow['user_outhprovider']) && $existRow['user_outhprovider'] == 2){
					$_SESSION[GetSession('Error')] ='<div class="alert alert-danger" id="wrong"><strong> Fail! </strong>Oops! Email address already used in facebook!</div>';
				}
				else if(isset($existRow['user_outhprovider']) && $existRow['user_outhprovider'] == 3){
					$_SESSION[GetSession('Error')] ='<div class="alert alert-danger" id="wrong"><strong> Fail! </strong>Oops! Email address already used in x(twiter)!</div>';
				}
				else{
					$_SESSION[GetSession('Error')] ='<div class="alert alert-danger" id="wrong"><strong> Fail! </strong>No user associated with this email address!</div>';
				}
				GetFrontRedirectUrl(GetUrl(array('module'=>$_REQUEST['module'])));
			}
		}
		// Sign in with google

		// Sign in with facebook
		if($_REQUEST['category_id']=='signinwithfacebook'){

			$GLOBALS['user_firstname'] = isset($nameFromFacebookArray[0]) ? $nameFromFacebookArray[0] : "";
			$GLOBALS['user_lastname'] = isset($nameFromFacebookArray[1]) ? $nameFromFacebookArray[1] : "";
			$GLOBALS['user_email'] = $_SESSION['facebook_email'];
			$GLOBALS['signup_google_user_password_display_hide'] = 'style="display:none;"';
			$GLOBALS['user_password'] = "CustomEsign#2024@googleSigup";
			$GLOBALS['user_googleid'] = $_SESSION['facebook_loggedin'];
			$GLOBALS['user_outhprovider'] = "3";

			$user_email = $_SESSION['facebook_email'];
			$token = $_SESSION['facebook_loggedin'];
			$rowLogin = $GLOBALS['DB']->row("SELECT * FROM `registerusers` WHERE user_email = ?  AND user_facebookid= ? AND user_outhprovider = 2  LIMIT 0,1",array($user_email,$token));
			if($rowLogin){
				if($rowLogin['user_status'] == 1){
					$_SESSION[GetSession('user_id')] = $rowLogin['user_id'];
					$_SESSION[GetSession('user_email')] = $rowLogin['user_email'];
					$_SESSION[GetSession('user_name')] = $rowLogin['user_firstname'].' '.$rowLogin['user_lastname'];
					$_SESSION[GetSession('user_firstname')] = $rowLogin['user_firstname'];
					$_SESSION[GetSession('user_lastname')] = $rowLogin['user_lastname'];
					$_SESSION[GetSession('user_uploadlimit')] = $rowLogin['user_uploadlimit'];
					$_SESSION[GetSession('user_type')] = $rowLogin['user_type'];
					// if($rowLogin['user_type'] == 'enterprise'){
					// 	$_SESSION[GetSession('user_uploadlimit')] = 1;
					// }
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
				else if(isset($existRow['user_outhprovider']) && $existRow['user_outhprovider'] == 3){
					$_SESSION[GetSession('Error')] ='<div class="alert alert-danger" id="wrong"><strong> Fail! </strong>Oops! Email address already used in x(twiter)!</div>';
				}
				else{
					$_SESSION[GetSession('Error')] ='<div class="alert alert-danger" id="wrong"><strong> Fail! </strong>not found any user associate this email address!</div>';
				}
				GetFrontRedirectUrl(GetUrl(array('module'=>$_REQUEST['module'])));
			}
		}
		// Sign in with facebook

		
		if($_REQUEST['category_id']=='admin' && isset($_REQUEST['id']) && isset($_REQUEST['subid']) ){
			
			$adminkey = $_REQUEST['id'];
			$md5userid = $_REQUEST['subid'];
			if($adminkey == GetConfig('ADMIN_LOGINKEY')){
				$rowLogin = $GLOBALS['DB']->row("SELECT * FROM `registerusers` WHERE  md5(user_id) = ?  LIMIT 0,1",array($md5userid));
				if($rowLogin){
					
					if($rowLogin['user_status'] == 1){
						//Always clear old cookies
						setcookie('username', '', time() - 3600, '/');
						setcookie('password', '', time() - 3600, '/');
						unset($_COOKIE['username']);
						unset($_COOKIE['password']);

						unset($_SESSION[GetSession('user_id')]);
						unset($_SESSION[GetSession('sub_user_id')]);
						unset($_SESSION[GetSession('user_email')]);
						unset($_SESSION[GetSession('user_name')]);
						unset($_SESSION[GetSession('user_firstname')]);
						unset($_SESSION[GetSession('user_lastname')]);
						unset($_SESSION[GetSession('user_organization')]);
						unset($_SESSION[GetSession('user_uploadlimit')]);
						unset($_SESSION[GetSession('user_type')]);
						unset($_SESSION[GetSession('is_sub_user')]);
						unset($_SESSION[GetSession('permission')]);
						unset($_SESSION[GetSession('department_list')]);
						unset($_SESSION[GetSession('user_status')]);
						unset($_SESSION[GetSession('user_planactive')]);

						$_SESSION[GetSession('user_id')] = $rowLogin['user_id'];
						$_SESSION[GetSession('sub_user_id')] = 0;
						$_SESSION[GetSession('user_email')] = $rowLogin['user_email'];
						$_SESSION[GetSession('user_name')] = $rowLogin['user_firstname'].' '.$rowLogin['user_lastname'];
						$_SESSION[GetSession('user_firstname')] = $rowLogin['user_firstname'];
						$_SESSION[GetSession('user_lastname')] = $rowLogin['user_lastname'];
						$_SESSION[GetSession('user_organization')] = $rowLogin['user_organization'];
						$_SESSION[GetSession('user_uploadlimit')] = $rowLogin['user_uploadlimit'];
						$_SESSION[GetSession('user_type')] = $rowLogin['user_type'];
						$_SESSION[GetSession('is_sub_user')] = false;
						$_SESSION[GetSession('permission')] = "";
						$_SESSION[GetSession('department_list')] = "";
					    $_SESSION[GetSession('user_status')] = $rowLogin['user_status'];
						$_SESSION[GetSession('user_planactive')] =  $rowLogin['user_planactive'];

						if ($_POST['user_remember'] == 1 && isset($_POST['user_remember'])) {
						/* Set cookie to last 1 year */
							//$cookie_url= parse_url($GLOBALS['ROOT_LINK'], PHP_URL_HOST);
							setcookie('username',$_POST['user_email'], time()+60*60*24*365,'/');
							setcookie('password',$_POST['user_password'], time()+60*60*24*365,'/');
						} else {
							setcookie('username', '', time() - 3600, '/');
							setcookie('password', '', time() - 3600, '/');
						}													
						//$GLOBALS['CLA_SESSION']->userSession();	
						GetFrontRedirectUrl(GetUrl(array('module'=>'dashboard')));
						exit();
					}
				}
			}
			GetFrontRedirectUrl(GetUrl(array('module'=>$_REQUEST['module'])));
		}

		if($_REQUEST['category_id']=='adminsub' && isset($_REQUEST['id']) && isset($_REQUEST['subid']) ){
			
			$adminkey = $_REQUEST['id'];
			$md5userid = $_REQUEST['subid'];
			if($adminkey == GetConfig('ADMIN_LOGINKEY')){
				$rowSubLogin = $GLOBALS['DB']->row("SELECT * FROM `registerusers_sub_users` WHERE  md5(id) = ?  LIMIT 0,1",array($md5userid));
				if($rowSubLogin){
					
					if($rowSubLogin['is_active'] == 1){
						//Always clear old cookies
						setcookie('username', '', time() - 3600, '/');
						setcookie('password', '', time() - 3600, '/');
						unset($_COOKIE['username']);
						unset($_COOKIE['password']);

						unset($_SESSION[GetSession('user_id')]);
						unset($_SESSION[GetSession('sub_user_id')]);
						unset($_SESSION[GetSession('user_email')]);
						unset($_SESSION[GetSession('user_name')]);
						unset($_SESSION[GetSession('user_firstname')]);
						unset($_SESSION[GetSession('user_lastname')]);
						unset($_SESSION[GetSession('user_organization')]);
						unset($_SESSION[GetSession('user_uploadlimit')]);
						unset($_SESSION[GetSession('user_type')]);
						unset($_SESSION[GetSession('is_sub_user')]);
						unset($_SESSION[GetSession('permission')]);
						unset($_SESSION[GetSession('department_list')]);
						unset($_SESSION[GetSession('user_status')]);
						unset($_SESSION[GetSession('user_planactive')]);

						$rowLogin = $GLOBALS['DB']->row("SELECT * FROM `registerusers` WHERE user_id = ? LIMIT 0,1",array($rowSubLogin['parent_user_id']));
						$_SESSION[GetSession('user_id')] = $rowLogin['user_id'];
						$_SESSION[GetSession('sub_user_id')] = $rowSubLogin['id'];
						$_SESSION[GetSession('user_email')] = $rowLogin['user_email'];
						$_SESSION[GetSession('user_name')] = $rowSubLogin['user_firstname'].' '.$rowSubLogin['user_lastname'];
						$_SESSION[GetSession('user_organization')] = $rowLogin['user_organization'];
						$_SESSION[GetSession('user_uploadlimit')] = $rowLogin['user_uploadlimit'];
						$_SESSION[GetSession('user_type')] = $rowLogin['user_type'];
						$_SESSION[GetSession('is_sub_user')] = true;
						$_SESSION[GetSession('permission')] = $rowSubLogin['permission'];
						$_SESSION[GetSession('department_list')] = $rowSubLogin['department_list'];
						// if($rowLogin['user_type'] == 'enterprise'){
						// 	$_SESSION[GetSession('user_uploadlimit')] = 1;
						// }
					    $_SESSION[GetSession('user_status')] = $rowLogin['user_status'];
						$_SESSION[GetSession('user_planactive')] =  $rowLogin['user_planactive'];
						
						if ($_POST['user_remember'] == 1) {
						/* Set cookie to last 1 year */
							//$cookie_url= parse_url($GLOBALS['ROOT_LINK'], PHP_URL_HOST);
							setcookie('username',$_POST['user_email'], time()+60*60*24*365,'/');
							setcookie('password',$_POST['user_password'], time()+60*60*24*365,'/');
						} else {
							setcookie('username', '', time() - 3600, '/');
							setcookie('password', '', time() - 3600, '/');
						}													
						//$GLOBALS['CLA_SESSION']->userSession();	
						GetFrontRedirectUrl(GetUrl(array('module'=>'dashboard')));
					
					}else{
						$_SESSION[GetSession('Error')] ='<div class="alert alert-danger" id="wrong"><strong> Failure! </strong>your account is temporarily disabled!</div>';
						GetFrontRedirectUrl(GetUrl(array('module'=>$_REQUEST['module'])));
					}
				}
			}else{
				GetFrontRedirectUrl(GetUrl(array('module'=>$_REQUEST['module'])));
			}
		}
		
		
		if($_REQUEST['category_id']=='resetpass' && isset($_REQUEST['id'])){
			$GLOBALS['KEY'] = $_REQUEST['id'];
			$user_id = $_REQUEST['subid'];
			$rowPassword = $GLOBALS['DB']->row("SELECT user_id,user_password FROM `registerusers` WHERE md5(user_id)= ? and md5(user_password)= ?  LIMIT 0,1",array($user_id,$GLOBALS['KEY']));
			
				if($rowPassword){
					if($_POST['RESETPASSKEY'] !='' && $_POST['password'] !='' && $_POST['cpassword'] !=''){
							$user_id = $rowPassword['user_id'];
							$newpassword = md5($_POST['password']);
							$change_pass = $GLOBALS['DB']->update("registerusers",array('user_password'=>$newpassword),array('user_id' =>$user_id));
							if($change_pass){	
								$_SESSION[GetSession('Success')]='<div class="gap-8 py-5 px-4 pl-11 border-l-9 border-green-600 rounded-xl relative bg-white bg-gradient-to-r from-[#00B71B]/12 to-[#00B71B]/0 shadow-lg" id="success"><strong>Success!</strong> Your Password Reset successful!.</div>';
								GetFrontRedirectUrl(GetUrl(array('module'=>$_REQUEST['module'])));
							}else{
								$_SESSION[GetSession('Error')] ='<div class="alert alert-danger" id="wrong"><strong> Fail! </strong>somthing wrong try again.</div>';
							}
					}
					$this->getPage();
					$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/resetpassword.html');	
					$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');			
					$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');
					$GLOBALS['SIDEBAR'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.sidebar.html');
					$GLOBALS['CLA_HTML']->display();
					RemoveMessageInfo();
					exit();	
				}else{
					$_SESSION[GetSession('Error')] ='<div class="alert alert-danger" id="wrong"><strong> Failure! </strong>not a valid link try again!</div>';
					GetFrontRedirectUrl(GetUrl(array('module'=>$_REQUEST['module'])));
				}
			}
		
		if(isset($_POST['signin'])){
			$user_email = trim($_POST['user_email']);
			$user_password = trim($_POST['user_password']);
			if($user_email !="" && $user_password !=""){
				$user_password = md5($user_password);
				$rowLogin = $GLOBALS['DB']->row("SELECT * FROM `registerusers` WHERE user_email = ?  AND user_password= ?  LIMIT 0,1",array($user_email,$user_password));
				$rowSubLogin = $GLOBALS['DB']->row("SELECT * FROM `registerusers_sub_users` WHERE email = ?  AND password= ?  LIMIT 0,1",array($user_email,$user_password));
				if($rowLogin){
					if($rowLogin['user_status'] == 1){
						$_SESSION[GetSession('user_id')] = $rowLogin['user_id'];
						$_SESSION[GetSession('user_email')] = $rowLogin['user_email'];
						$_SESSION[GetSession('user_name')] = $rowLogin['user_firstname'].' '.$rowLogin['user_lastname'];
						$_SESSION[GetSession('user_firstname')] = $rowLogin['user_firstname'];
						$_SESSION[GetSession('user_lastname')] = $rowLogin['user_lastname'];
						$_SESSION[GetSession('user_organization')] = $rowLogin['user_organization'];
						$_SESSION[GetSession('user_uploadlimit')] = $rowLogin['user_uploadlimit'];
						$_SESSION[GetSession('user_type')] = $rowLogin['user_type'];
						$_SESSION[GetSession('sub_user_id')] = 0;
						$_SESSION[GetSession('is_sub_user')] = false;
						$_SESSION[GetSession('permission')] = "";
						$_SESSION[GetSession('department_list')] = "";
						// if($rowLogin['user_type'] == 'enterprise'){
						// 	$_SESSION[GetSession('user_uploadlimit')] = 1;
						// }
					    $_SESSION[GetSession('user_status')] = $rowLogin['user_status'];
						$_SESSION[GetSession('user_planactive')] =  $rowLogin['user_planactive'];
						
						if ($_POST['user_remember'] == 1) {
						/* Set cookie to last 1 year */
							//$cookie_url= parse_url($GLOBALS['ROOT_LINK'], PHP_URL_HOST);
							setcookie('username',$_POST['user_email'], time()+60*60*24*365,'/');
							setcookie('password',$_POST['user_password'], time()+60*60*24*365,'/');
						} else {
							setcookie('username', '', time() - 3600, '/');
							setcookie('password', '', time() - 3600, '/');
						}													
						
						//$GLOBALS['CLA_SESSION']->userSession();	
						GetFrontRedirectUrl(GetUrl(array('module'=>'dashboard')));
					}else{
						$_SESSION[GetSession('Error')] ='<div class="alert alert-danger" id="wrong"><strong> Failure! </strong>your account is temporarily disabled!</div>';
						GetFrontRedirectUrl(GetUrl(array('module'=>$_REQUEST['module'])));
					}
				}
				else if($rowSubLogin){
					if($rowSubLogin['is_active'] == 1){
						$rowLogin = $GLOBALS['DB']->row("SELECT * FROM `registerusers` WHERE user_id = ? LIMIT 0,1",array($rowSubLogin['parent_user_id']));
						$_SESSION[GetSession('user_id')] = $rowLogin['user_id'];
						$_SESSION[GetSession('sub_user_id')] = $rowSubLogin['id'];
						$_SESSION[GetSession('user_email')] = $rowLogin['user_email'];
						$_SESSION[GetSession('user_name')] = $rowSubLogin['user_firstname'].' '.$rowSubLogin['user_lastname'];
						$_SESSION[GetSession('user_organization')] = $rowLogin['user_organization'];
						$_SESSION[GetSession('user_uploadlimit')] = $rowLogin['user_uploadlimit'];
						$_SESSION[GetSession('user_type')] = $rowLogin['user_type'];
						$_SESSION[GetSession('is_sub_user')] = true;
						$_SESSION[GetSession('permission')] = $rowSubLogin['permission'];
						$_SESSION[GetSession('department_list')] = $rowSubLogin['department_list'];
						// if($rowLogin['user_type'] == 'enterprise'){
						// 	$_SESSION[GetSession('user_uploadlimit')] = 1;
						// }
					    $_SESSION[GetSession('user_status')] = $rowLogin['user_status'];
						$_SESSION[GetSession('user_planactive')] =  $rowLogin['user_planactive'];
						
						if ($_POST['user_remember'] == 1) {
						/* Set cookie to last 1 year */
							//$cookie_url= parse_url($GLOBALS['ROOT_LINK'], PHP_URL_HOST);
							setcookie('username',$_POST['user_email'], time()+60*60*24*365,'/');
							setcookie('password',$_POST['user_password'], time()+60*60*24*365,'/');
						} else {
							setcookie('username', '', time() - 3600, '/');
							setcookie('password', '', time() - 3600, '/');
						}													
						
						//$GLOBALS['CLA_SESSION']->userSession();	
						GetFrontRedirectUrl(GetUrl(array('module'=>'dashboard')));
					
					}else{
						$_SESSION[GetSession('Error')] ='<div class="alert alert-danger" id="wrong"><strong> Failure! </strong>your account is temporarily disabled!</div>';
						GetFrontRedirectUrl(GetUrl(array('module'=>$_REQUEST['module'])));
					}
				}
				else{
	
					$_SESSION[GetSession('Error')] ='<div class="alert alert-danger" id="wrong"><strong> Failure! </strong>wrong username or password!</div>';
					GetFrontRedirectUrl(GetUrl(array('module'=>$_REQUEST['module'])));

				}
				//$_SESSION[GetSession('Success')] = '';
			}else{
				$_SESSION[GetSession('Error')] = '<div class="alert alert-danger"><strong>Fail!</strong> please enter username and password!</div>';	
			}
		}
		
		
		$this->getPage();
		$GLOBALS['registerUserLink'] = GetUrl(array('module'=>'signup','category_id'=>'verifyemail'));
		$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/login.html');	
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