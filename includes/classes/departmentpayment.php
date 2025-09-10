<?php
// ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
require_once(GetConfig('SITE_BASE_PATH').'/lib/stripe-php/stripe-config.php');	  // stripe
require_once(GetConfig('SITE_BASE_PATH').'/lib/stripe-php/init.php');	  // bpoint  // stripe
class CIT_DEPARTMENTPAYMENT
{
	
	public function __construct()
	{	
		$GLOBALS['plan_selunit'] =1;
		if(isset($_REQUEST['department_id'])){
			$GLOBALS['current_department_id'] = $_REQUEST['department_id'];
		}else{
			$GLOBALS['current_department_id'] = 0;
		}
	}
	
	public function displayPage(){
		AddMessageInfo();	
		// if($_REQUEST['3Dsecuresuccess'] == true){
		// 	$this->createuserforsuccess3D(); exit;
		// }
		
		if(isset($_REQUEST['category_id'])){
			$action = trim($_REQUEST['category_id']);
		} else {
			$action = '';
		}
		
		
		if($_POST['departmentpayment'] == 1){  // SUBMIT REGISTER 

			//$_SESSION['plan_id'] = $_POST['plan_id'];
			//$_SESSION['plan_unit'] = $_POST['plan_unit'];
			foreach($_POST as $key => $value){ $GLOBALS[$key] = $value; }
					
			if($_POST['stripeToken']!="" ){
				\Stripe\Stripe::setApiKey(GetConfig('STRIPE_SECRET_KEY'));
				$charge = \Stripe\Charge::create([
					'amount' => 2000,  // Amount in cents (20 USD = 2000 cents)
					'currency' => 'usd',
					'source' => $_POST['stripeToken'],  // The token from Stripe
					'description' => 'Payment for product/service',
				]);
				$data = array("logo_animation_payment" => 1);
				$where = array('department_id'=>$GLOBALS['current_department_id']);
				$addSignature = $GLOBALS['DB']->update("registerusers_departments",$data,$where);
				$_SESSION[GetSession('Success')] ='<div class="alert alert-success"><strong> Success! </strong>Payment completed!</div>';
				GetFrontRedirectUrl(GetUrl(array('module'=>'dashboard')));
			}else{
			 	$GLOBALS['Message'] ='<div class="alert alert-danger" id="wrong"><strong> Fail! </strong>please enter all required field!</div>';
				GetFrontRedirectUrl(GetUrl(array('module'=>$_REQUEST['module'])));
			}
		}
		
		$this->getPage();
	
		$GLOBALS['DEPARTMENT_PAYMENT_FORM_ACTION_URL'] = GetUrl(array('module'=>'departmentpayment')).'?department_id='.$GLOBALS['current_department_id'];
		$GLOBALS['STRIPE_PUBLISHABLE_KEY'] = GetConfig('STRIPE_PUBLISHABLE_KEY');
		$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/departmentpayment.html');	
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