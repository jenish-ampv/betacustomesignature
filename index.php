<?php 
error_reporting(1);
	require_once('config/db.php'); 
	require_once('config/config.php');  // configuration of sites
	require_once('lib/general.php');  // general settings of site
	require_once(GetConfig('SITE_BASE_PATH').'/lib/s3bucket/s3bucketinit.php'); // s3bucket init
	require_once(GetConfig('SITE_BASE_PATH').'/lib/template/class.template_engine.php');
	require_once(GetConfig('SITE_BASE_PATH').'/lib/session/class.session.php');	
	// for procedure pagination
	$GLOBALS['PageStart'] = 0;
	$GLOBALS['PerPage'] = 20;
	// for procedure pagination

	// default varibale 	
	$GLOBALS['JsStatus'] = 0;
	// default variable
		
	// coding for session	
	$GLOBALS['CLA_SESSION'] = GetClass('CIT_SESSION');
	// $GLOBALS['CLA_SESSION']->checkSession();
	// coding for session
	
	// coding for database
	//$GLOBALS['CLA_DB'] = GetClass('CIT_MYSQLi');
	//$GLOBALS['CLA_DB']->charset = GetConfig('dbEncoding');
	//$GLOBALS['CLA_DB']->timezone = '+0:00'; // Tell the database server to always do its time operations in GMT +0. We perform adjustments in the code for the timezon
	//$GLOBALS['CLA_DB']->Connect(GetConfig('DB_HOSTNAME'),GetConfig('DB_USERNAME'),GetConfig('DB_PASSWORD'),GetConfig('DB_NAME'));	
	// coding for database
	// SIte Configuration
	$GLOBALS['SITE_ID'] =  GetConfig('WEBSITE_ID'); // Master Admin create site ID
	$GLOBALS['JS_VERSION'] =  GetConfig('JS_VERSION');
	$GLOBALS['CSS_VERSION'] =  GetConfig('CSS_VERSION');
	$GLOBALS['SITE_TITLE'] = GetConfig('SITE_TITLE');
	$GLOBALS['SITE_SORTURL'] = GetConfig('SITE_SORTURL');
	$GLOBALS['SITE_APPURL'] = GetConfig('SITE_APPURL');
	$GLOBALS['MetaTitle'] =  GetConfig('SITE_TITLE');
	$GLOBALS['SITE_TAGLINE'] = GetConfig('SITE_TAGLINE');
	$GLOBALS['SUPPORT_CHARITY'] = GetConfig('SUPPORT_CHARITY');
	$GLOBALS['LOCALSHOP_SITE'] =  GetConfig('LOCALSHOP_SITE'); // 1 =off ,0 =on localshop on site
	$GLOBALS['POSTAL_CODE'] =  GetConfig('POSTAL_CODE'); // Postal Code For Local Shop
	$GLOBALS['LOCALSHOP_SPONSERID'] =  GetConfig('LOCALSHOP_SPONSERID'); // Sponsor Id For Local Shop
	$GLOBALS['LOCALSHOP_EMENU'] =  GetConfig('LOCALSHOP_EMENU');
	$GLOBALS['LOCALSHOP_EMENULINK'] =  GetConfig('LOCALSHOP_EMENULINK');
	$GLOBALS['SITE_PHONE_NUMBER'] =  GetConfig('SITE_PHONE_NUMBER');
	$GLOBALS['SITE_EMAIL_ADDRESS'] =  GetConfig('SITE_EMAIL_ADDRESS');
	$GLOBALS['SITE_ADDRESS'] =  GetConfig('SITE_ADDRESS');
	$GLOBALS['SITE_COLOR'] =  GetConfig('SITE_COLOR');
	$GLOBALS['SITE_BGCOLOR'] =  GetConfig('SITE_BGCOLOR');
	$GLOBALS['SITE_RGBCOLOR'] =  GetConfig('SITE_RGBCOLOR');
	$GLOBALS['PAYPAL_TESTMODE'] =  GetConfig('PAYPAL_TESTMODE');
	$GLOBALS['TESTMODE_EMAIL'] =  GetConfig('TESTMODE_EMAIL');
	$GLOBALS['TESTMODE_PHONE'] =  GetConfig('TESTMODE_PHONE');
	$GLOBALS['ECARD_CHECKOUT'] =  GetConfig('ECARD_CHECKOUT');
	$GLOBALS['ECARD_AUTOSEND'] =  GetConfig('ECARD_AUTOSEND');
	$GLOBALS['PAYPAL_EMAILID'] =  GetConfig('PAYPAL_EMAILID');
	$GLOBALS['PAYMENT_BPOINT'] =  GetConfig('PAYMENT_BPOINT');
	$GLOBALS['PAYMENT_POLI'] =  GetConfig('PAYMENT_POLI');
	$GLOBALS['PAYMENT_AZUPAY'] =  GetConfig('PAYMENT_AZUPAY');
	$GLOBALS['PAYMENT_SWAPCARD'] =  GetConfig('PAYMENT_SWAPCARD');
	$GLOBALS['PAYMENT_EVOUCHER'] =  GetConfig('PAYMENT_EVOUCHER');
	$GLOBALS['FB_APPID'] = GetConfig('FB_APPID');
	$GLOBALS['GL_APPID'] = GetConfig('GL_APPID');
	$GLOBALS['ANDROID_LINK'] = GetConfig('ANDROID_LINK');
	$GLOBALS['ITUNES_LINK'] = GetConfig('ITUNES_LINK');
	$GLOBALS['SITE_SORTTITLE'] = GetConfig('SITE_SORTTITLE');
	$GLOBALS['SITE_COUNTRYCODE'] = GetConfig('SITE_COUNTRY');
	$GLOBALS['SITE_CURRENCYSYMBOL'] = GetConfig('SITE_CURRENCYSYMBOL');

	// SITE CHARITY & SUPPORT
	$GLOBALS['SUPPORT_CHARITY'] =  GetConfig('SUPPORT_CHARITY');
	$GLOBALS['CHARITY_TYPE'] =  GetConfig('CHARITY_TYPE');
	$GLOBALS['SUPPORT_AMOUNT'] =  GetConfig('SUPPORT_AMOUNT');
	$GLOBALS['SUPPORT_TEXT'] = GetConfig('SUPPORT_TEXT');
	$GLOBALS['REDIRECT_TEXT'] = GetConfig('REDIRECT_TEXT');
	$GLOBALS['REDIRECT_DISCLAMIER'] = GetConfig('REDIRECT_DISCLAMIER');
	
	// Site Social Media Link
	$GLOBALS['SITE_FACEBOOK_LINK'] =  GetConfig('FACEBOOK_LINK');
	$GLOBALS['SITE_GOOGLE_LINK'] =  GetConfig('GOOGLE_LINK');
	$GLOBALS['SITE_TWITTER_LINK'] =  GetConfig('TWITTER_LINK');
	$GLOBALS['SITE_LINKEDIN_LINK'] =  GetConfig('LINKEDIN_LINK');
	$GLOBALS['SITE_PINTEREST_LINK'] =  GetConfig('PINTEREST_LINK');
	$GLOBALS['SITE_INSTAGRAM_LINK'] =  GetConfig('INSTAGRAM_LINK');
	
	// google anaytics account number
	$GLOBALS['SITE_GOOGLEANALYTICS'] =  GetConfig('SITE_GOOGLEANALYTICS');
	$GLOBALS['GL_DATASITEKEY'] = GoogleDataSiteKey();
	
	//amount
	$GLOBALS['GOLD_AMOUNT'] =  GetConfig('GOLD_AMOUNT'); // gold member amount
	$GLOBALS['PLATINUM_AMOUNT'] = GetConfig('PLATINUM_AMOUNT');	 // platinum member amount
	$GLOBALS['LSGOLD_AMOUNT'] = GetConfig('LSGOLD_AMOUNT');	 // localshop member amount

	
	/// define special variable	
	$GLOBALS['BASE_LINK'] =  GetConfig('SITE_BASE_PATH'); // c:/dir/dir/.......	
	$GLOBALS['ROOT_LINK'] = GetConfig('SITE_URL');	 /// http://www.domain.com
	$GLOBALS['MASTERROOT_LINK'] = GetConfig('MASTERSITE_URL');	 /// http://www.domain.com
	$GLOBALS['UPLOAD_LINK'] = GetConfig('SITE_URL').'/upload-beta'; // image uploaded path 
	$GLOBALS['JS_LINK'] = GetConfig('SITE_URL').'/script'; // http://www.domain.com/admin/script
	$GLOBALS['CSS_LINK'] = GetConfig('SITE_URL').'/style'; // http://www.domain.com/admin/style	
	$GLOBALS['CURRENT_DATE']= date("M d, Y");
	/// define master admin link
	
	if(GetConfig('S3BUCKET') == 1){
		$GLOBALS['IMAGE_LINK'] = $GLOBALS['BUCKETBASEURL'];
		$GLOBALS['UPLOAD_LINK'] = $GLOBALS['BUCKETBASEURL'].'/upload-beta';
		// $GLOBALS['IMAGE_LINK'] = $GLOBALS['ROOT_LINK'];
	}else{
		$GLOBALS['IMAGE_LINK'] = $GLOBALS['ROOT_LINK'];
	}
	// template
	$GLOBALS['WWW_TPL'] = $GLOBALS['ROOT_LINK'].GetConfig('WWW');
	$GLOBALS['EMAIL_TPL'] = $GLOBALS['ROOT_LINK'].GetConfig('EMAILTPL');
				
	// classes 
	$GLOBALS['CLASSES'] = $GLOBALS['BASE_LINK'].'/'.GetConfig('CLASSES');		
	$GLOBALS['CURRENT_URL'] = url();	
	/// define special variable
	
	// get template class
	$GLOBALS['CLA_HTML'] = GetClass('CIT_Html');
	
	// sub site settings
	$GLOBALS['SUBSITE'] =  GetConfig('SUBSITE');
	if(is_array(GetConfig('SUBSITE_IDS'))){
		$subsite_id = implode(",",GetConfig('SUBSITE_IDS'));
		$GLOBALS['SUBSITE_IDS'] =  $subsite_id ; // Master Admin create site ID
	}else{
		$GLOBALS['SUBSITE_IDS'] ='';
	}
		
	// If magic_quotes_gpc is on, strip all the slashes it added. By doing
	// this we can be sure that all the gpc vars never have slashes and so
	// we will always need to treat them the same way
	
	
		$_POST		= stripslashes_deep($_POST);
		$_GET		= stripslashes_deep($_GET);
		$_COOKIE	= stripslashes_deep($_COOKIE);
		$_REQUEST	= stripslashes_deep($_REQUEST);

	
	$enable_editor = array('newsignature','editsignature','azuread','gsuite');
	
	if(!in_array($_REQUEST['module'],$enable_editor) && !in_array($_REQUEST['category_id'],['registerFormSubmit'])){
		$_POST		= strip_tag($_POST);
		$_GET		= strip_tag($_GET);
		$_COOKIE	= strip_tag($_COOKIE);
		$_REQUEST	= strip_tag($_REQUEST);
	}
	
	convertRequestInput();	
	// define varibale here for script 
	$GLOBALS['FormValidation'] = 0;
	// define varibale here for script 
	
	//debug error
	if(GetConfig('cit_dbdebug')){
		ini_set('display_errors', 1);
		ini_set('display_startup_errors', 1);
		error_reporting(E_ALL);
	}
	
	if(GetConfig('mod_rewrite')){			
		$GLOBALS['SeoUnabled'] = 1;							 
	} else {
		$GLOBALS['SeoUnabled'] = 0;							 
	}	
		
	// coding for index page
	require_once($GLOBALS['CLASSES'].'/home.php');		
	$GLOBALS['CLA_INDEX'] = GetClass('CIT_INDEX');	
	$GLOBALS['CLA_INDEX']->displayPage();
	// coding for index page