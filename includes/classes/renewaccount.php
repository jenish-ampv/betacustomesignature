<?php
require_once(GetConfig('SITE_BASE_PATH').'/lib/stripe-php/stripe-config.php');	  // stripe
require_once(GetConfig('SITE_BASE_PATH').'/lib/stripe-php/init.php');	  // bpoint  // stripe
class CIT_RENEWACCOUNT
{
	
	public function __construct()
	{	
		if(!isset($_SESSION[GetSession('user_id')]) && !isset($_REQUEST['uuid'])){
			GetFrontRedirectUrl(GetUrl(array('module'=>'signin')));
		}
		$GLOBALS['plan_selunit'] =1;
		$GLOBALS['billing'] ="javascript:updatehereonly();";
	}
	
	public function displayPage(){
		AddMessageInfo();	
		\Stripe\Stripe::setApiKey(GetConfig('STRIPE_SECRET_KEY'));
		
		if($_POST['coupon_code'] !="" && $_POST['coupon_apply'] ==1){
			$coupon_code = trim($_POST['coupon_code']);
			echo $this->isCouponValid($coupon_code); exit;
		}

		if($_REQUEST['category_id'] == 'plan' && is_numeric($_REQUEST['plan_id']) && is_numeric($_REQUEST['plan_unit'])){
			$_SESSION['plan_id'] = $_REQUEST['plan_id'];
			$_SESSION['plan_unit'] = $_REQUEST['plan_unit'];
			GetFrontRedirectUrl(GetUrl(array('module'=>'signup')));
		}
		
		if(isset($_REQUEST['category_id'])){
			$action = trim($_REQUEST['category_id']);
		} else {
			$action = '';
		}
		
		
		if($_POST['registersubmit'] == 1){  // SUBMIT REGISTER 

			//$_SESSION['plan_id'] = $_POST['plan_id'];
			//$_SESSION['plan_unit'] = $_POST['plan_unit'];
			foreach($_POST as $key => $value){ $GLOBALS[$key] = $value; }
					
			 if($_POST['stripeToken']!="" && $_POST['user_email'] !="" && $_POST['user_firstname'] !="" && $_POST['plan_id'] !="" && $_POST['plan_unit'] !=""){
				if($this->emailExist($_POST['user_email']) == false){

					// Cancelling all other subsciption in stripe

					$email = $_POST['user_email']; // The email you want to search for

				    // 1. Find customer(s) by email
				    $customers = \Stripe\Customer::all(['email' => $email, 'limit' => 1]);

				    if (count($customers->data) === 0) {
				        // echo "No customer found with email: $email";
				        // return;
				    }else{
				    	$customer = $customers->data[0];

					    // 2. Get all subscriptions for this customer
					    $subscriptions = \Stripe\Subscription::all([
					        'customer' => $customer->id,
					        'status' => 'all',
					        'limit' => 100
					    ]);

					    // 3. Cancel each subscription
					    foreach ($subscriptions->data as $subscription) {
					    	if($subscription->status != 'canceled' && $subscription->status != 'incomplete_expired'){
					        	$subscription->cancel();
					    	}
					        // echo "Cancelled subscription: " . $subscription->id . "\n";
					    }
				    }
				    
					// Cancelling all other subsciption in stripe
				    


					$Subscription = $this->createSubscription($_POST['stripeToken'],$_POST['plan_id'], $_POST['plan_unit']);
					if($Subscription){	
						if(isset($_POST['user_googleid']) && $_POST['user_googleid'] !=""){ // google sign in
							$user_googleid  = $_POST['user_googleid']; 
							$user_password='';
							$user_image = $_POST['user_image'];
							$user_outhprovider = 1;
						}else{
							$user_googleid='';
							$user_password = md5($_POST['user_password']);
							$user_image='';
							$user_outhprovider = 0;
						}
						
						$data =array('user_firstname'=>trim($_POST['user_firstname']),'user_email'=>trim(strtolower($_POST['user_email'])),'user_organization'=>trim($_POST['user_organization']),'user_planactive'=>1);
						$were = array('user_id'=>$GLOBALS['USERID']);
						$insert_id = $GLOBALS['DB']->update("registerusers",$data,$were);
						if($insert_id){
							
						    unset($_SESSION['plan_id']); unset($_SESSION['plan_unit']);
							$_SESSION[GetSession('Success')] ='<div class="alert alert-success"><strong>Success! </strong>Signup success signin to create new signature</div>';
							$message= _getEmailTemplate('welcome');
							$send_mail = _SendMail($_POST['user_email'],'',$GLOBALS['EMAIL_SUBJECT'],$message);
							//$this->AddgohiLevelContact();
							$redirect_thnk = GetUrl(array('module'=>'thanks')).'/register?customer_id='.$insert_id.'&&fpr=testyhdk34rd';
							GetFrontRedirectUrl($redirect_thnk);
						}else{
			
							$_SESSION[GetSession('Error')] ='<div class="alert alert-danger" id="wrong"><strong> Failure! </strong>somthing wrong try again</div>';
							GetFrontRedirectUrl(GetUrl(array('module'=>$_REQUEST['module'])));
		
						}
				  }else{
					 	$GLOBALS['Message']='<div class="alert alert-danger" id="wrong"><strong> Fail! </strong>somthing wrong please contact administrator. if your payment debit from your account.</div>';	
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
		}else{ 
			 $GLOBALS['plan_id'] = 1;
			 $GLOBALS['plan_unit'] = 1; 
		}
		$this->getPlanDetail($GLOBALS['plan_id'],$GLOBALS['plan_unit']);
		$GLOBALS['STRIPE_PUBLISHABLE_KEY'] = GetConfig('STRIPE_PUBLISHABLE_KEY');
		$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/renewaccount.html');	
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
	
	public function emailExist($email){
		$existRow = $GLOBALS['DB']->row("SELECT user_id FROM registerusers WHERE user_email = ? AND user_id != ? LIMIT 0,1",array($email,$GLOBALS['USERID']));
		if(isset($existRow['user_id'])){ return true; }
		return false;
	}
	

	public function getPlanDetail($selplan_id='',$selunit='',$getunit =1){
		try {
			$exist_plan_unit = "";
			$existCustomer = $GLOBALS['DB']->row("SELECT customer_id,plan_id,subscription_id,plan_signaturelimit FROM registerusers_subscription WHERE user_id = ? LIMIT 0,1",array($GLOBALS['USERID']));
			if(isset($existCustomer['plan_id']) && $existCustomer['plan_id']){
				$selplan_id = $existCustomer['plan_id'];
				if(isset($existCustomer['plan_signaturelimit']) && $existCustomer['plan_signaturelimit']){
					$selunit = $existCustomer['plan_signaturelimit'];
				}
			}
			elseif(isset($existCustomer['subscription_id'])){
				if($existCustomer['subscription_id'] == '_admin'){ // if user created from admin
					$selplan_id='4';
					$selunit='1';
				}else{
					$stripe = new \Stripe\StripeClient(GetConfig('STRIPE_SECRET_KEY'));
					$subscription = $stripe->subscriptions->retrieve($existCustomer['subscription_id'], []);
					if (isset($subscription->metadata)){
						$metadata = $subscription->metadata;
						if (isset($metadata->plan_id)){
							if($existplanid = (int) $metadata->plan_id){
								$selplan_id = $existplanid;
							}
							if($existplanunit = (int) $metadata->plan_unit){
								$selunit = $existplanunit;
							}
						}
					}
				}
			}
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
					$GLOBALS['save_year_label'] = $plantype == 'year' ? 'd-none' : '';
					$GLOBALS['save_text'] = $plantype == 'year' ? '' : 'd-none';
					$offper = $plantype == 'year' ? 20 : 15;

					if($selplan_id %2 == 0){ // pro plan
					 $plan_text ='<li><b>Interactive Signature</b></li><li>AI - Logo Animation™</li><li>Remove CES Watermark</li><li>Team Dashboard</sup></li><li>Interactive Social Icons</li><li>Animated Profile</li></ul><ul><li>Verification Badge<img src="%%DEFINE_IMAGE_LINK%%/images/verification-badge1.svg" alt=""></li><li>Pro Templates</li><li>Team Dashboard</li><li>Outlook and Gmail Sync<img src="%%DEFINE_IMAGE_LINK%%/images/gmail-icon1.svg" alt=""><img src="%%DEFINE_IMAGE_LINK%%/images/outlook-icon1.svg" alt=""></li><li class="right">Instant Load Speed</li>';
					}else{
					 $plan_text ='<li>Basic Logo Animation</li><li>Static Icons</li><li>Basic Layouts</li><li>5 Day Logo Turnaround</li>';
					}
					$GLOBALS['plan_detail_formail'] = $planRow['plan_name'].' '.$selunit.' Signature'.' '.ucfirst($plantype).'ly plan' ; 
					$GLOBALS['selected_plan'] = '<div class="order_details_box border-price">
					 <h6>'.$planRow['plan_name'].' ('.ucfirst($plantype).'ly) <b>$<span class="month_basicprice">'.$plan_selprice.'</span> /mo</b><span class="offper">'. $offper.'% OFF</span></h6>  
					 <div class="text_price"><span>'.$selunit.'</span> Signature <div class="monthprice">$<span>'.$plan_selpricespl.'</span></div></div>
					 <ul>'.$plan_text.'</ul>
					</div>';
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
		} catch (Exception $e) {
			$selplan_id='4';
			$selunit='1';
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
					$GLOBALS['save_year_label'] = $plantype == 'year' ? 'd-none' : '';
					$GLOBALS['save_text'] = $plantype == 'year' ? '' : 'd-none';
					$offper = $plantype == 'year' ? 20 : 15;

					if($selplan_id %2 == 0){ // pro plan
					 $plan_text ='<li><b>Interactive Signature</b></li><li>AI - Logo Animation™</li><li>Remove CES Watermark</li><li>Team Dashboard</sup></li><li>Interactive Social Icons</li><li>Animated Profile</li></ul><ul><li>Verification Badge<img src="%%DEFINE_IMAGE_LINK%%/images/verification-badge1.svg" alt=""></li><li>Pro Templates</li><li>Team Dashboard</li><li>Outlook and Gmail Sync<img src="%%DEFINE_IMAGE_LINK%%/images/gmail-icon1.svg" alt=""><img src="%%DEFINE_IMAGE_LINK%%/images/outlook-icon1.svg" alt=""></li><li class="right">Instant Load Speed</li>';
					}else{
					 $plan_text ='<li>Basic Logo Animation</li><li>Static Icons</li><li>Basic Layouts</li><li>5 Day Logo Turnaround</li>';
					}
					$GLOBALS['plan_detail_formail'] = $planRow['plan_name'].' '.$selunit.' Signature'.' '.ucfirst($plantype).'ly plan' ; 
					$GLOBALS['selected_plan'] = '<div class="order_details_box border-price">
					 <h6>'.$planRow['plan_name'].' ('.ucfirst($plantype).'ly) <b>$<span class="month_basicprice">'.$plan_selprice.'</span> /mo</b><span class="offper">'. $offper.'% OFF</span></h6>  
					 <div class="text_price"><span>'.$selunit.'</span> Signature <div class="monthprice">$<span>'.$plan_selpricespl.'</span></div></div>
					 <ul>'.$plan_text.'</ul>
					</div>';
				}else{
					$selected ='';
	 			}
			}
		}
	}
	
	public function createSubscription($token,$plan_id,$plan_unit){
		// STRIPE_SECRET_KEY,  STRIPE_PUBLISHABLE_KEY, STRIPE_WEBHOOK_SECRET
		$this->getPlanDetail($plan_id,$plan_unit,0);
		$user_id = $GLOBALS['USERID'];
		$user_email  = $_POST['user_email'];
		$amount =  ($GLOBALS['plan_price'] * 100);
		
		$token  = $_POST['stripeToken']; 
		$name = $GLOBALS['user_firstname']; 
		$email = $GLOBALS['user_email'];
		$GLOBALS['plan_signaturelimit'] = $plan_unit;
		\Stripe\Stripe::setApiKey(GetConfig('STRIPE_SECRET_KEY'));
		
		$memRow = $GLOBALS['DB']->row("SELECT * FROM registerusers_subscription WHERE user_id= ?",array($user_id));	
		
		
		try {  
			if(isset($memRow['customer_id']) && !is_null($memRow['customer_id'])){
				// update customer on stripe
				$customer = \Stripe\Customer::retrieve($memRow['customer_id']);
			    $customer->source = $token;
			    $customer->metadata =array('user_id' =>$user_id,'plan_id'=>$plan_id,'plan_unit'=>$plan_unit);
			    $customer->save();
			}
			else{
				// create customer on stripe
				$customer = \Stripe\Customer::create(array(
					'email' => $email,
					'name' => $name,
					'metadata' => array('user_id' =>$user_id,'plan_id'=>$plan_id,'plan_unit'=>$plan_unit), 
					'source'  => $token 
			 	)); 
			}
			
		}catch(Exception $e) {  
			// $api_error = $e->getMessage();  
			$customer = \Stripe\Customer::create(array(
				'email' => $email,
				'name' => $name,
				'metadata' => array('user_id' =>$user_id,'plan_id'=>$plan_id,'plan_unit'=>$plan_unit), 
				'source'  => $token 
			)); 
		} 
		//$cus = $customer->jsonSerialize();
		// get card detail
		try {
			$customerCard = \Stripe\Customer::retrieveSource($customer->id,$customer->default_source,array());
			$cardData = $customerCard->jsonSerialize();
			$this->saveUserCard($user_id,$cardData);
		}catch(Exception $e) {  
			$api_error = $e->getMessage();
		} 
		if(empty($api_error) && $customer){   // create subscription
			try {
				if(isset($memRow['subscription_id']) && !is_null($memRow['subscription_id']) && !in_array($memRow['subscription_id'],['_free','_admin']) ){
					// $stripe = new \Stripe\StripeClient(GetConfig('STRIPE_SECRET_KEY'));
					// $subscription = $stripe->subscriptions->retrieve($memRow['subscription_id'], []);
					// $subscriptionStatus = $subscription->status;
					// if($subscriptionStatus == 'active'){
					// 	$stripe->subscriptions->cancel($memRow['subscription_id'], []);
					// }
				}
				if($_POST['coupon_id']){
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
					));
				}
			}catch(Exception $e) {  $api_error = $e->getMessage();  } 
			
			 if(empty($api_error) && $subscription){ 
				$subsData = $subscription->jsonSerialize(); 
				if($subsData['status'] == 'active'){ 
					return $this->addStripeSubscriptionData($subsData);
				}
			}
		}
		echo $GLOBALS['Error']= $api_error; 
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
		if($memRow['user_id']){
			$data = array('plan_id'=>$planId,'customer_id'=>$customer_id,'subscription_id' => $subscription_id,'price_id' =>$plan_id,'plan_interval' => $plan_interval,'plan_signaturelimit'=>$GLOBALS['plan_signaturelimit'],'period_start' => $start_time,'period_end' => $end_time,'apply_coupon'=>$coupon_id,'invoice_amount'=>$amount_paid,'invoice_link' => $invoice_link,'plan_cancel'=>0,'free_trial'=>0);
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
			$data = array('user_id'=>$userId,'plan_id'=>$planId,'customer_id'=>$customer_id,'subscription_id' => $subscription_id,'price_id' =>$plan_id,'plan_interval' => $plan_interval,'plan_signaturelimit'=>$GLOBALS['plan_signaturelimit'],'period_start' => $start_time,'period_end' => $end_time,'apply_coupon'=>$coupon_id,'invoice_amount'=>$amount_paid,'invoice_link' => $invoice_link,'plan_cancel'=>0,'free_trial'=>0);
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
				
				$memRow = $GLOBALS['DB']->row("SELECT user_id FROM registerusers_subscription WHERE user_id= ?",array($userId));	
		
				if($memRow['user_id']){
					$data = array('plan_id'=>$planId,'customer_id'=>$customer_id,'subscription_id' => $subscription_id,'price_id' =>$plan_id,'plan_interval' => $plan_interval,'period_start' => $start_time,'period_end' => $end_time,'invoice_link' => $invoice_link,'invoice_amount'=>$amount_paid,'plan_cancel'=>0,'free_trial'=>0);
					$where = array('user_id'=>$userId);
					$add = $GLOBALS['DB']->update('registerusers_subscription',$data,$where);
					
				}else{
					$data = array('plan_id'=>$planId,'customer_id'=>$customer_id,'subscription_id' => $subscription_id,'price_id' =>$plan_id,'plan_interval' => $plan_interval,'period_start' => $start_time,'period_end' => $end_time,'apply_coupon'=>$coupon_id,'invoice_link' => $invoice_link,'invoice_amount'=>$amount_paid,'plan_cancel'=>0,'free_trial'=>0);
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
				$userRow = $GLOBALS['DB']->row("SELECT * FROM registerusers WHERE usr_id=? LIMIT 0,1",array($GLOBALS['USERID']));
				if($userRow['user_email'] != ""){
					$GLOBALS['USERNAME'] = $userRow['user_firstname'];
					$customer_email = $userRow['user_email'];
					$message= _getEmailTemplate('payment_failed');
					$send_mail = _SendMail($customer_email,'',$GLOBALS['EMAIL_SUBJECT'],$message);
				}
				return http_response_code(200);
			break;
			case 'customer.subscription.created':
				return http_response_code(200);
			break;
			case 'customer.subscription.deleted': // subscription period end
				$subscription = $event->data->object;	
				$subscriptionId = $subscription->id;
				$userId = $subscription->metadata->user_id;
				$message = 'S'.$memberId;

				$data = array('plan_id' => '','plan_interval' => '','period_start' => 0,'period_end' => 0);
				$where = array('user_id'=>$userId);
				$GLOBALS['DB']->update('registerusers_subscription',$data,$where);

				// update register table
					$data = array('user_planactive' => 0);
					$where = array('user_id'=>$userId);
					$GLOBALS['DB']->update('registerusers',$data,$where);
			break;
			default:
			 	http_response_code(400);
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
				"website": "'.$GLOBALS['SITE_TITLE'].'",
				"tags": [
					"new user"
				],
				"source": "public api"
			}',
			  CURLOPT_HTTPHEADER => array(
				'Content-Type: application/json',
				'Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJsb2NhdGlvbl9pZCI6Ik5HQk5lYTVnRFl5cXMwTEl5MmJPIiwiY29tcGFueV9pZCI6ImdIUVd2OGdJTjJHRUJMdktKUmN0IiwidmVyc2lvbiI6MSwiaWF0IjoxNjcwNTMyODc1Mjc2LCJzdWIiOiJ1c2VyX2lkIn0.SdJS70_Q0YvIOEfCAeGVhyzT1EIDMkpQO_IOrNpl2jc'
			  ),
			));
			$response = curl_exec($curl);
			curl_close($curl);
			//echo $response;
		}
	}
	
	
}

?>