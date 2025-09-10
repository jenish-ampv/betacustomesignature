<?php
require_once('../config/db.php');  // configuration of sites 
require_once('../config/config.php');  // configuration of sites 
require_once('../lib/general.php');  // general settings of site
require_once(GetConfig('SITE_BASE_PATH').'/lib/template/class.template_engine.php');	  // template engine for html
require_once(GetConfig('SITE_BASE_PATH').'/lib/session/class.session.php');	  // template engine for html	
require_once(GetConfig('SITE_BASE_PATH').'/admin/includes/display/Pagination.php');	 /// admin default settings
require_once(GetConfig('SITE_BASE_PATH').'/lib/detect/mdetect.php');	 /// admin default settings	
require_once(GetConfig('SITE_BASE_PATH').'/lib/s3bucket/s3bucketinit.php'); // s3bucket init

	//Define Rewards Admin Site ID
	$GLOBALS['AdminID'] = GetConfig("WEBSITE_ID");
	$GLOBALS['MASTERROOT_LINK'] = GetConfig('MASTERSITE_URL');	 /// http://www.domain.com
	$GLOBALS['MASTERADMINUPLOAD_LINK'] = GetConfig('MASTERSITE_URL').'/upload-beta';
	
	// coding for pagination
	$GLOBALS['DIS_PAGE'] = new Pagination();	
	$GLOBALS['PageStart'] = 0;
	// coding for pagination
	
	// default varibale 	
	$GLOBALS['JsStatus'] = 0;
	// default variable
		
	// coding for session		
	$GLOBALS['CLA_SESSION'] = GetClass('CIT_SESSION');
	// $GLOBALS['CLA_SESSION']->checkSession();
	if(isset($_SESSION[GetSession('SessionTimout')])){
		$GLOBALS['CLA_SESSION']->adminSession();
	}	
	// coding for session
	
	// coding for database
	//$GLOBALS['CLA_DB'] = GetClass('CIT_MYSQLi');
    //$GLOBALS['CLA_DB']->TablePrefix = GetConfig("tablePrefix");
	//$GLOBALS['CLA_DB']->charset = GetConfig('dbEncoding');
	//$GLOBALS['CLA_DB']->timezone = '+0:00'; // Tell the database server to always do its time operations in GMT +0. We perform adjustments in the code for the timezon
	//$mysqliObj = $GLOBALS['CLA_DB']->Connect(GetConfig('DB_HOSTNAME'),GetConfig('DB_USERNAME'),GetConfig('DB_PASSWORD'),GetConfig('DB_NAME'));	
	// coding for database
	
	/// define special variable	
	$GLOBALS['BASE_LINK'] =  GetConfig('SITE_BASE_PATH'); // c:/dir/dir/.......	
	$GLOBALS['ROOT_LINK'] = GetConfig('SITE_URL');	 /// http://www.domain.com
	$GLOBALS['ADMIN_LINK'] = GetConfig('SITE_URL').'/admin'; /// http://www.domain.com/admin
	$GLOBALS['UPLOAD_LINK'] = GetConfig('SITE_URL').'/upload-beta'; // image uploaded path http://www.domain.com/upload
	$GLOBALS['EDITOR_LINK'] = $GLOBALS['ADMIN_LINK'].'/editor/'; // http://www.domain.com/admin/editor
	$GLOBALS['SITE_APPURL'] = GetConfig('SITE_APPURL');
	$GLOBALS['SITE_BGCOLOR'] = GetConfig('SITE_BGCOLOR');
	$GLOBALS['SITE_SMTP_DOMAIN'] = GetConfig('smtpdomain');
	$GLOBALS['SITE_QRCODEURL'] = GetConfig('SITE_QRCODEURL');
		
	$GLOBALS['eVMTUP_AMOUNT'] = GetConfig('MTUP_AMOUNT'); 
	$GLOBALS['FAMOUNT'] = GetConfig('FLOAT_AMOUNT');
	// template
	$GLOBALS['WWW_TPL'] = $GLOBALS['ADMIN_LINK'].'/'.GetConfig('AdminTemplate');			
	// classes 
	$GLOBALS['CLASSES'] = $GLOBALS['BASE_LINK'].'/admin/'.GetConfig('AdminClasses');
	$GLOBALS['EMAIL_TPL'] =	$GLOBALS['ROOT_LINK'].GetConfig('EMAILTPL');	
	if(GetConfig('mod_rewrite')){			
		$GLOBALS['SeoUnabled'] = 1;							 
	} else {
		$GLOBALS['SeoUnabled'] = 0;							 
	}	
	$GLOBALS['CURRENT_URL'] =  url();
	$GLOBALS['SITE_TITLE'] = GetConfig('SITE_TITLE'); 
	$GLOBALS['TITLE'] = GetConfig('SITE_TITLE');
	$GLOBALS['SITE_COMMUNITY'] = GetConfig('SITE_COMMUNITY');
	$GLOBALS['POSTAL_CODE'] =  GetConfig('POSTAL_CODE'); // Master Admin create site ID
	$GLOBALS['SITE_PHONE_NUMBER'] =  GetConfig('SITE_PHONE_NUMBER');
	$GLOBALS['SITE_EMAIL_ADDRESS'] =  GetConfig('SITE_EMAIL_ADDRESS');
	$GLOBALS['SITE_ADDRESS'] =  GetConfig('SITE_ADDRESS');
	$GLOBALS['SITE_CURRENCYSYMBOL'] = GetConfig('SITE_CURRENCYSYMBOL');
	$GLOBALS['SITE_COLOR'] = GetConfig('SITE_COLOR');
	$GLOBALS['SITE_BGCOLOR'] = GetConfig('SITE_BGCOLOR');
	$GLOBALS['SITE_COLOR'] = GetConfig('SITE_COLOR');
	$GLOBALS['SITE_BGCOLOR'] = GetConfig('SITE_BGCOLOR');
	$GLOBALS['SITE_CSSPRIMARYCOLOR'] = GetConfig('SITE_BGCOLOR');
    $GLOBALS['SITE_CSSSECONDARYCOLOR'] = GetConfig('SITE_COLOR');
	
	/// define special variable
	
	// get template class
	$GLOBALS['CLA_HTML'] = GetClass('CIT_Html');	
	
	// If magic_quotes_gpc is on, strip all the slashes it added. By doing
	// this we can be sure that all the gpc vars never have slashes and so
	// we will always need to treat them the same way
	// if (get_magic_quotes_gpc()) {
		$_POST		= stripslashes_deep($_POST);
		$_GET		= stripslashes_deep($_GET);
		$_COOKIE	= stripslashes_deep($_COOKIE);
		$_REQUEST	= stripslashes_deep($_REQUEST);
	// }	
	convertRequestInput();	
	
	
	
	
	$GLOBALS['PerPage'] = 20;		
	
		
	if(GetConfig('S3BUCKET') == 1){
		$GLOBALS['IMAGE_LINK'] = $GLOBALS['BUCKETBASEURL'];
		$GLOBALS['UPLOAD_LINK'] = $GLOBALS['BUCKETBASEURL'].'/upload-beta';
	}else{
		$GLOBALS['IMAGE_LINK'] = GetConfig('SITE_URL').'/admin/images'; /// http://www.domain.com/admin/images	
	}
	$GLOBALS['CSS_LINK'] = GetConfig('SITE_URL').'/admin/style'; // http://www.domain.com/admin/style		
	$GLOBALS['JS_LINK'] = GetConfig('SITE_URL').'/admin/script'; // http://www.domain.com/admin/script

	
	// delte tthis coe
	// coding for index page
	require_once($GLOBALS['CLASSES'].'/class.index.php');		
	$GLOBALS['CLA_INDEX'] = GetClass('CIT_INDEX');
	$GLOBALS['CLA_INDEX']->displayPage();
	// coding for index page	