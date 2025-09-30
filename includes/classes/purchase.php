<?php
require_once($GLOBALS['BASE_LINK'].'/'.GetConfig('CLASSES').'/dashboard.php');
require_once(GetConfig('SITE_BASE_PATH').'/lib/stripe-php/stripe-config.php');	  // stripe
require_once(GetConfig('SITE_BASE_PATH').'/lib/stripe-php/init.php');	  // bpoint  // stripe
class CIT_PURCHASE
{
	
	public function __construct()
	{
		
	}
	
	public function displayPage(){
		AddMessageInfo();
		$GLOBALS['free_trial'] = 0;
		
		if($_POST['coupon_code'] !="" && $_POST['coupon_apply'] ==1){
			$coupon_code = trim($_POST['coupon_code']);
			echo $this->isCouponValid($coupon_code); exit;
		}

		if($_REQUEST['category_id'] == 'plan'){
			$_SESSION['plan_id'] = $_REQUEST['id'];
			$_SESSION['plan_unit'] = $_REQUEST['subid'];
			$_SESSION['plan_from_pricing'] = 'true';
		}else{
			$GLOBALS['plan_from_pricing'] = 'false';
		}
		
		if(isset($_REQUEST['category_id'])){
			$action = trim($_REQUEST['category_id']);
		} else {
			$action = '';
		}
		if(isset($_POST['useSavedCard'] )&& $_POST['useSavedCard']){

			\Stripe\Stripe::setApiKey(GetConfig('STRIPE_SECRET_KEY'));

			$rowUserCard = $GLOBALS['DB']->row("SELECT * FROM `registerusers_card` WHERE user_id = ? LIMIT 0,1",array($GLOBALS['USERID']));
			if($rowUserCard){
				$rowUserSubscription = $GLOBALS['DB']->row("SELECT subscription_id,customer_id FROM `registerusers_subscription` WHERE user_id = ? LIMIT 0,1",array($GLOBALS['USERID']));
				if($rowUserSubscription){

					
					try {
						// Inputs
						$subscriptionId = $rowUserSubscription['subscription_id']; // May be null or empty
						$planDetails = $GLOBALS['DB']->row("SELECT * FROM `plan` WHERE plan_id = ? LIMIT 0,1",array($_POST['plan_id']));
						if($planDetails){
							$newPriceId = $planDetails['plan_priceid'];
						}else{
							throw new Exception("No active plan found with your selection");
						}
						$newQuantity    = $_POST['plan_unit'];
						$customerId     = $rowUserSubscription['customer_id'];

						$rowUser = $GLOBALS['DB']->row("SELECT * FROM `registerusers` WHERE user_id = ? LIMIT 0,1",array($GLOBALS['USERID']));

						if(!$rowUser){
							throw new Exception("No user found with your logged in id");
						}
						// Cancelling all other subsciption in stripe
						$email = $rowUser['user_email']; // The email you want to search for

						//Find customer(s) by email
						$customers = \Stripe\Customer::all(['email' => $email, 'limit' => 1]);

						if (count($customers->data) === 0) {
							// echo "No customer found with email: $email";
							// return;
						}else{
							$customer = $customers->data[0];

							//Get all subscriptions for this customer
							$subscriptions = \Stripe\Subscription::all([
								'customer' => $customer->id,
								'status' => 'all',
								'limit' => 100
							]);

							//Cancel each subscription
							foreach ($subscriptions->data as $subscription) {
								if($subscription->status != 'canceled' && $subscription->status != 'incomplete_expired'){
									$subscription->cancel();
								}
								// echo "Cancelled subscription: " . $subscription->id . "\n";
							}
						}
						// Cancelling all other subsciption in stripe

						$subscriptionExists = false;

						// ✅ Try retrieving the subscription
						if (!empty($subscriptionId)) {
							try {
								$subscription = \Stripe\Subscription::retrieve($subscriptionId);
								$subscriptionExists = true;
							} catch (\Stripe\Exception\InvalidRequestException $e) {
								// Log the exception if needed
								// The subscription doesn't exist or was deleted
								$subscriptionExists = false;
							}
						}
						
						if ($subscriptionExists) {
							// 🔁 Update existing subscription
							$itemId = $subscription->items->data[0]->id;

							// Retrieve the subscription
							$subscription = \Stripe\Subscription::retrieve($subscriptionId);

							if ($subscription->status !== 'active') {
								// The subscription is not active (could be 'canceled', 'incomplete', 'past_due', etc.) — create a new one
								
								// Check if latest_invoice exists
								if (!empty($subscription->latest_invoice)) {
									// Retrieve the invoice
									$invoice = \Stripe\Invoice::retrieve($subscription->latest_invoice);

									// Cancel the invoice if it's still open
									if (in_array($invoice->status, ['open', 'draft'])) {
										// Try voiding the invoice
										\Stripe\Invoice::voidInvoice($invoice->id);
									}
								}
								
								$newSubscription = \Stripe\Subscription::create([
									'customer' => $subscription->customer,
									'items' => [[
										'price'    => $newPriceId,
										'quantity' => $newQuantity,
									]],
									'metadata' => array('user_id'=>$GLOBALS['USERID'],'plan_id'=>$_POST['plan_id'],'plan_unit'=>$newQuantity),
									'proration_behavior' => 'none',
									'collection_method' => 'charge_automatically',
    								'payment_behavior' => 'default_incomplete'
								]);

								// Retrieve the latest invoice from the new subscription
								$invoiceId = $newSubscription->latest_invoice;

								if ($invoiceId) {
									$invoice = \Stripe\Invoice::retrieve($invoiceId);

									// pay the invoice immediately
									if ($invoice->status === 'open') {
										$invoice->pay();
									}
								}

							} else {
								// Subscription is active — update it
								$updatedSubscription = \Stripe\Subscription::update($subscriptionId, [
									'items' => [[
										'id'       => $itemId,
										'price'    => $newPriceId,
										'quantity' => $newQuantity,
									]],
									'metadata' => array('user_id'=>$GLOBALS['USERID'],'plan_id'=>$_POST['plan_id'],'plan_unit'=>$newQuantity),
									'proration_behavior' => 'none'
								]);

								// Create and finalize invoice for the updated subscription
								$invoice = \Stripe\Invoice::create([
									'customer'     => $updatedSubscription->customer,
									'subscription' => $updatedSubscription->id,
									'auto_advance' => true
								]);

								// Finalize the invoice (makes it payable)
								$finalizedInvoice = \Stripe\Invoice::finalizeInvoice($invoice->id);

								// Pay the invoice immediately
								if ($finalizedInvoice->status === 'open') {
									$finalizedInvoice->pay();
								}
							}
							
						} else {
							
							$customerExists = false;

							if (!empty($customerId)) {
								try {
									// Try fetching the customer from Stripe
									$stripeCustomer = \Stripe\Customer::retrieve($customerId);
									if (!empty($stripeCustomer) && empty($stripeCustomer->deleted)) {
										$customerExists = true;
									}
								} catch (\Stripe\Exception\InvalidRequestException $e) {
									// Customer doesn't exist or is invalid
									$customerExists = false;
								}
							}
							//Create new customer if not found
							if (!$customerExists) {
								$rowUser = $GLOBALS['DB']->row("SELECT * FROM `registerusers` WHERE user_id = ? LIMIT 0,1",array($GLOBALS['USERID']));
								if($rowUser){
									$stripeCustomer = \Stripe\Customer::create([
										'email' => $rowUser['user_email'],             // Replace with actual field
										'name'  => $rowUser['user_firstname'] . ' ' . $rowUser['user_lastname'],// Or use first/last name
									]);
									$customerId = $stripeCustomer->id;
								}else{
									throw new Exception("Your user is not found in stripe.");
								}
								

								// 🔒 Optionally save this to DB for future use
								// saveStripeCustomerIdToDB($userId, $customerId);
							}

							$cardId = $rowUserCard['id']; // example: 'card_1NfgC4Hp7DgNkCIvazpw309G'
							$cardExists = false;

							if (!empty($cardId) && strpos($cardId, 'card_') === 0) {
								try {
									// Try to retrieve the card source from Stripe
									$card = \Stripe\Customer::retrieveSource($customerId, $cardId);

									// Optional: Check card status (not deleted, etc.)
									if (!empty($card) && empty($card->deleted)) {
										$cardExists = true;
									}
								} catch (\Stripe\Exception\InvalidRequestException $e) {
									// Card doesn't exist or not attached to the customer
									$cardExists = false;
								}
							}


							if ($cardExists) {
								// Set it as the default source (legacy way)
								\Stripe\Customer::update($customerId, [
									'default_source' => $cardId,
								]);
							} else {
								// Handle missing card
								$_SESSION[GetSession('Success')] = '<div class="alert alert-danger"><strong>Error:</strong> Saved card not found. Please add a new payment method.</div>';
								GetFrontRedirectUrl(GetUrl(['module' => 'purchase','category_id'=>'renewaccount']));
								exit;
							}

							//Create new subscription
							$newSubscription = \Stripe\Subscription::create([
								'customer' => $customerId,
								'items'    => [[
									'price'    => $newPriceId,
									'quantity' => $newQuantity,
								]],
								'metadata' => array('user_id'=>$GLOBALS['USERID'],'plan_id'=>$_POST['plan_id'],'plan_unit'=>$newQuantity),
								'expand' => ['latest_invoice.payment_intent'],
							]);

							// 🔒 Optionally save the new subscription ID
							// updateSubscriptionIdInDB($userId, $newSubscription->id);

						}

						$_SESSION[GetSession('Success')] = '<div class="success-error-message gap-8 py-5 px-4 pl-11 border-l-9 border-green-600 rounded-xl relative bg-white bg-gradient-to-r from-[#00B71B]/12 to-[#00B71B]/0 shadow-lg"><strong>Success! </strong>Your user has been upgraded.</div>';
						GetFrontRedirectUrl(GetUrl(['module' => 'dashboard','category_id'=>'planrenewed']));exit();

					} catch (\Stripe\Exception\CardException $e) {
						$_SESSION[GetSession('Success')] = '<div class="alert alert-danger"><strong>Card Error:</strong> ' . $e->getMessage() . '</div>';
					} catch (\Stripe\Exception\ApiErrorException $e) {
						$_SESSION[GetSession('Success')] = '<div class="alert alert-danger"><strong>API Error:</strong> ' . $e->getMessage() . '</div>';
					} catch (Exception $e) {
						$_SESSION[GetSession('Success')] = '<div class="alert alert-danger"><strong>Unexpected Error:</strong> ' . $e->getMessage() . '</div>';
					}
				}
			}else{
				$_SESSION[GetSession('Success')] = '<div class="alert alert-danger" id="wrong"><strong> Fail! </strong>No saved card found for you account.</div>';
			}
		}

		if($_POST['paymentsubmit'] == 1){  // SUBMIT REGISTER 
			//$_SESSION['plan_id'] = $_POST['plan_id'];
			//$_SESSION['plan_unit'] = $_POST['plan_unit'];
			$GLOBALS['free_trial'] = 1;
			if($_POST['stripeToken']!="" && $_POST['plan_id'] !="" && $_POST['plan_unit'] !="" && $_POST['user_id'] !="" && $_POST['user_email'] !="" && $_POST['user_name'] !=""){
				$planDetails = $GLOBALS['DB']->row("SELECT * FROM `plan` WHERE plan_id = ? LIMIT 0,1",array($_POST['plan_id']));
				$Subscription = false;
				if($planDetails){
					$GLOBALS['plan_priceid'] = $planDetails['plan_priceid'];
					$Subscription = $this->createSubscription($_POST['stripeToken'],$_POST['plan_id'], $_POST['plan_unit'],$_POST['user_id'],$_POST['user_email'],$_POST['user_name']);
				}else{
					$_SESSION[GetSession('Error')]='<div class="alert alert-danger"><strong>Fail!</strong> Plan is not active!.</div>';
					GetFrontRedirectUrl(GetUrl(array('module'=>$_REQUEST['module'],'category_id'=>$_REQUEST['category_id'])));exit();
				}
				
				if($Subscription){				
					$_SESSION[GetSession('Success')] ='<div class="success-error-message gap-8 py-5 px-4 pl-11 border-l-9 border-green-600 rounded-xl relative bg-white bg-gradient-to-r from-[#00B71B]/12 to-[#00B71B]/0 shadow-lg"><strong>Success! </strong>Your user has been upgraded.</div>';
					GetFrontRedirectUrl(GetUrl(['module' => 'dashboard','category_id'=>'planrenewed']));exit();
				}else{
					if($GLOBALS['Error_code'] == 'subscription_payment_intent_requires_action'){
						$this->checkStripe3D($_POST);
					}
					
					$_SESSION[GetSession('Success')] = '<div class="alert alert-danger" id="wrong"><strong> Fail! </strong>'.$GLOBALS['Error'].' please contact administrator. if your payment debit from your account.</div>';	
				}
				
			 }else{
			 	$_SESSION[GetSession('Success')] = '<div class="alert alert-danger" id="wrong"><strong> Fail! </strong>please enter all required field!</div>';
			 }
		}

		if($_POST['stripeToken']!="" && $_POST['plan_id'] !="" && $_POST['plan_unit'] !=""){

			\Stripe\Stripe::setApiKey(GetConfig('STRIPE_SECRET_KEY'));

			$rowUser = $GLOBALS['DB']->row("SELECT * FROM `registerusers` WHERE user_id = ? LIMIT 0,1",array($GLOBALS['USERID']));
			if($rowUser){
				// Cancelling all other subsciption in stripe
				$email = $rowUser['user_email']; // The email you want to search for

				//Find customer(s) by email
				$customers = \Stripe\Customer::all(['email' => $email, 'limit' => 1]);

				if (count($customers->data) === 0) {
					// echo "No customer found with email: $email";
					// return;
				}else{
					$customer = $customers->data[0];

					//Get all subscriptions for this customer
					$subscriptions = \Stripe\Subscription::all([
						'customer' => $customer->id,
						'status' => 'all',
						'limit' => 100
					]);

					//Cancel each subscription
					foreach ($subscriptions->data as $subscription) {
						if($subscription->status != 'canceled' && $subscription->status != 'incomplete_expired'){
							$subscription->cancel();
						}
						// echo "Cancelled subscription: " . $subscription->id . "\n";
					}
				}
				// Cancelling all other subsciption in stripe
				$planDetails = $GLOBALS['DB']->row("SELECT * FROM `plan` WHERE plan_id = ? LIMIT 0,1",array($_POST['plan_id']));
				$Subscription = false;
				if($planDetails){
					$GLOBALS['plan_priceid'] = $planDetails['plan_priceid'];
					$Subscription = $this->createSubscription($_POST['stripeToken'],$_POST['plan_id'], $_POST['plan_unit'], $rowUser['user_id'],$rowUser['user_email'],$rowUser['user_firstname'].' '.$rowUser['user_lastname']);
				}else{
					$_SESSION[GetSession('Error')]='<div class="alert alert-danger"><strong>Fail!</strong> Plan is not active!.</div>';
					GetFrontRedirectUrl(GetUrl(array('module'=>$_REQUEST['module'],'category_id'=>$_REQUEST['category_id'])));exit();
				}
				
				if($Subscription){	
					$_SESSION[GetSession('Success')] = '<div class="success-error-message gap-8 py-5 px-4 pl-11 border-l-9 border-green-600 rounded-xl relative bg-white bg-gradient-to-r from-[#00B71B]/12 to-[#00B71B]/0 shadow-lg"><strong>Success! </strong>Your user has been upgraded.</div>';
					GetFrontRedirectUrl(GetUrl(['module' => 'dashboard','category_id'=>'planrenewed']));exit();
				}else{
					$_SESSION[GetSession('Success')] = '<div class="alert alert-danger" id="wrong"><strong> Fail! </strong>somthing wrong please contact administrator. if your payment debit from your account.</div>';	
				}
			}else{
				$_SESSION[GetSession('Success')] = '<div class="alert alert-danger" id="wrong"><strong> Fail! </strong>somthing wrong please contact administrator. if your payment debit from your account.</div>';	
			}
		}
		
		
		if($action == 'renewaccount'){
			$this->getPage();
			$rowUserSubscription = $GLOBALS['DB']->row("SELECT plan_id,plan_signaturelimit FROM `registerusers_subscription` WHERE user_id = ? LIMIT 0,1",array($GLOBALS['USERID']));
			if($rowUserSubscription){
				$plan_id = $rowUserSubscription['plan_id'];
				$plan_unit = $rowUserSubscription['plan_signaturelimit'];
				if($plan_id < 1 || $plan_unit < 1){
					$this->getPlanDetail(8,1,1,false); // Default new yearly plan
				}else{
					$this->getPlanDetail($plan_id,$plan_unit,1,true);
				}
			}else{
				$this->getPlanDetail(8,1,1,false); // Default new yearly plan
			}

			$rowUser = $GLOBALS['DB']->row("SELECT * FROM `registerusers` WHERE user_id = ? LIMIT 0,1",array($GLOBALS['USERID']));
			if($rowUser){
				$GLOBALS['RENEW_PAYMENT_USER_ID'] = $rowUser['user_id'];
				$GLOBALS['RENEW_PAYMENT_USER_EMAIL'] = $rowUser['user_email'];
				$GLOBALS['RENEW_PAYMENT_USER_NAME'] = $rowUser['user_firstname'] ." ". $rowUser['user_lastname'];
			}else{
				$GLOBALS['RENEW_PAYMENT_USER_ID'] = "";
				$GLOBALS['RENEW_PAYMENT_USER_EMAIL'] = "";
				$GLOBALS['RENEW_PAYMENT_USER_NAME'] = "";
			}
			
			$GLOBALS['STRIPE_PUBLISHABLE_KEY'] = GetConfig('STRIPE_PUBLISHABLE_KEY');
			$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/purchase-renewaccount.html');	
			$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');			
			$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');
			$GLOBALS['SIDEBAR'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.sidebar.html');
			$GLOBALS['CLA_HTML']->display();
			RemoveMessageInfo();
			exit();
		}


		
		
		$this->getPage();
		if(isset($_SESSION['plan_id']) && isset($_SESSION['plan_unit'])){ 
			$GLOBALS['plan_id'] = $_SESSION['plan_id']; 
			$GLOBALS['plan_unit'] = $_SESSION['plan_unit']; 
			$GLOBALS['plan_from_pricing'] = $_SESSION['plan_from_pricing'];
		}else{ 
			 $GLOBALS['plan_id'] = 4;
			 $GLOBALS['plan_unit'] = 1;
		}
		$GLOBALS['chnage_planlink'] = $GLOBALS['linkModulePricing'];
		


		

		$rowUser = $GLOBALS['DB']->row("SELECT * FROM `registerusers` WHERE user_id = ? LIMIT 0,1",array($GLOBALS['USERID']));
		if($rowUser){
			$GLOBALS['PAYMENT_USER_ID'] = $rowUser['user_id'];
			$GLOBALS['PAYMENT_USER_EMAIL'] = $rowUser['user_email'];
			$GLOBALS['PAYMENT_USER_NAME'] = $rowUser['user_firstname'] ." ". $rowUser['user_lastname'];
		}else{
			$GLOBALS['PAYMENT_USER_ID'] = "";
			$GLOBALS['PAYMENT_USER_EMAIL'] = "";
			$GLOBALS['PAYMENT_USER_NAME'] = "";
		}

		$this->getPlanDetail($GLOBALS['plan_id'],$GLOBALS['plan_unit']);
		$GLOBALS['STRIPE_PUBLISHABLE_KEY'] = GetConfig('STRIPE_PUBLISHABLE_KEY');
		$GLOBALS['PAYMENT_FORM_ACTION_URL'] = GetUrl(array('module'=>'purchase'));

		$GLOBALS['SIGNATURE'] = GetClass('CIT_DASHBOARD');
		$signatureId = $GLOBALS['DB']->row("SELECT signature_id FROM `signature` WHERE user_id = ? ORDER BY signature_id LIMIT 0,1",array($GLOBALS['USERID']));
		$GLOBALS['USER_SIGNATURE'] = $GLOBALS['SIGNATURE']->getUserSignature($signatureId['signature_id']);
		
		$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/purchase.html');	
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
				GetFrontRedirectUrl(GetUrl(array('module'=>$_REQUEST['module'],'category_id'=>'userdetails','email'=>bin2hex($_SESSION[GetSession('ve_email')]))));
			}else{
				$_SESSION[GetSession('Error')]='<div class="alert alert-danger"><strong>Fail!</strong> code not match!.</div>';
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
				$_SESSION[GetSession('Success')]='<div class="success-error-message gap-8 py-5 px-4 pl-11 border-l-9 border-green-600 rounded-xl relative bg-white bg-gradient-to-r from-[#00B71B]/12 to-[#00B71B]/0 shadow-lg" id="success"><strong>Success!</strong> OTP sent to your email account!.</div>';
				GetFrontRedirectUrl(GetUrl(array('module'=>$_REQUEST['module'],'category_id'=>$_REQUEST['category_id'],'id'=>'otp')));
			}else{
				$_SESSION[GetSession('Error')]='<div class="alert alert-danger"><strong>Fail!</strong> mail not sent try again!.</div>';
				GetFrontRedirectUrl(GetUrl(array('module'=>$_REQUEST['module'],'category_id'=>$_REQUEST['category_id'],'id'=>'otp')));
			}
		}

		// send otp
		if($_POST['ve_email']){
			$user_email = $_POST['ve_email'];

			$rowVE = $GLOBALS['DB']->row("SELECT * FROM `registerusers` WHERE user_email = ?",array($user_email));
			$rowVESubUser = $GLOBALS['DB']->row("SELECT * FROM `registerusers_sub_users` WHERE email = ?",array($user_email));
			
			if($rowVE){
				$_SESSION[GetSession('Error')]='<div class="alert alert-danger"><strong>Fail!</strong> email address already registered!</div>';
				GetFrontRedirectUrl(GetUrl(array('module'=>$_REQUEST['module'],'category_id'=>$_REQUEST['category_id'])));
			}
			else if($rowVESubUser){
				$_SESSION[GetSession('Error')]='<div class="alert alert-danger"><strong>Fail!</strong> email address already registered!</div>';
				GetFrontRedirectUrl(GetUrl(array('module'=>$_REQUEST['module'],'category_id'=>$_REQUEST['category_id'])));
			}
			else{
				$GLOBALS['VEEmail'] = $user_email;
				$GLOBALS['veotp'] = rand(100000,999999);
				$_SESSION[GetSession('ve_otp')] = $GLOBALS['veotp'];
				$_SESSION[GetSession('ve_email')] = $user_email;
				$to = $user_email;
				$message= _getEmailTemplate('register_verify_email');
				$send_mail = _SendMail($to,'',$GLOBALS['EMAIL_SUBJECT'],$message);
				if($send_mail){
					$_SESSION[GetSession('Success')]='<div class="success-error-message gap-8 py-5 px-4 pl-11 border-l-9 border-green-600 rounded-xl relative bg-white bg-gradient-to-r from-[#00B71B]/12 to-[#00B71B]/0 shadow-lg" id="success"><strong>Success!</strong> OTP sent to your email account!.</div>';
					GetFrontRedirectUrl(GetUrl(array('module'=>$_REQUEST['module'],'category_id'=>$_REQUEST['category_id'],'id'=>'otp')));
				}else{
					$_SESSION[GetSession('Error')]='<div class="alert alert-danger"><strong>Fail!</strong> mail not sent try again!.</div>';
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
		}
		else{
			$_SESSION[GetSession('Error')]='<div class="alert alert-danger"><strong>Fail!</strong> something went wrong, try again later.</div>';
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
			$_SESSION[GetSession('Success')] ='<div class="success-error-message gap-8 py-5 px-4 pl-11 border-l-9 border-green-600 rounded-xl relative bg-white bg-gradient-to-r from-[#00B71B]/12 to-[#00B71B]/0 shadow-lg"><strong>Success! </strong>Signup success signin to create new signature</div>';
			$message= _getEmailTemplate('welcome');
			$send_mail = _SendMail($postData->user_email,'',$GLOBALS['EMAIL_SUBJECT'],$message);
			$this->AddgohiLevelContact();
			
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
	
	public function getPlanDetail($selplan_id='',$selunit='',$getunit =1, $renewAccount = false){
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
					if($planname == 'pro month new' && $plantype == 'month'){
						$pro_month_arr_new[$unit['plan_unit']] = $unit['plan_unitprice'];
						$pro_month_arrspl_new[$unit['plan_unit']] = $unit['plan_unitsplprice'];
					}
					if($planname == 'pro year new' && $plantype == 'year'){
						$pro_year_arr_new[$unit['plan_unit']] = $unit['plan_unitprice'];
						$pro_year_arrspl_new[$unit['plan_unit']] = $unit['plan_unitsplprice'];
					}
				}
			}
			if($planRow['plan_id'] == $selplan_id){
				$GLOBALS['plan_id'] = $plan_id;
				$GLOBALS['plan_selunit'] = $selunit;
				$GLOBALS['plan_selplan'.$selplan_id] = 'checked="checked"';
				$mulperiod = $plantype == 'year' ? 12 : 3 ;
				if($selplan_id > 5){
					$mulperiod = $plantype == 'year' ? 12 : 1 ;
				}
				$GLOBALS['plan_format_price'] = GetPriceFormat($plan_selprice * $mulperiod);
				$GLOBALS['plan_format_pricespl'] = GetPriceFormat($plan_selpricespl *$mulperiod);
				$GLOBALS['plan_format_savings'] = GetPriceFormat(($plan_selpricespl * $mulperiod) -($plan_selprice * $mulperiod));
				$GLOBALS['plan_price_hiden'] = ($plan_selprice * $mulperiod);
			}else{
				$selected ='';
			}
		}

		$GLOBALS['quartrly_enabled'] = 'false';
		$GLOBALS['pro_month_unit'] = json_encode($pro_month_arr_new);
		$GLOBALS['pro_month_unitspl'] = json_encode($pro_month_arrspl_new);
		$GLOBALS['pro_year_unit'] = json_encode($pro_year_arr_new);
		$GLOBALS['pro_year_unitspl'] = json_encode($pro_year_arrspl_new);

		if($renewAccount){
			$GLOBALS['quartrly_enabled'] = 'true';
			$GLOBALS['pro_month_unit'] = json_encode($pro_quarter_arr);
			$GLOBALS['pro_month_unitspl'] = json_encode($pro_quarter_arrspl);
			$GLOBALS['pro_year_unit'] = json_encode($pro_year_arr);
			$GLOBALS['pro_year_unitspl'] = json_encode($pro_year_arrspl);
		}

		return false;
	}
	
	public function createSubscription($token,$plan_id,$plan_unit,$user_id,$user_email,$user_name){
		// STRIPE_SECRET_KEY,  STRIPE_PUBLISHABLE_KEY, STRIPE_WEBHOOK_SECRET
		$this->getPlanDetail($plan_id,$plan_unit,0);
		$user_id = $user_id;
		$user_email  = $user_email;
		$amount =  ($GLOBALS['plan_price'] * 100);
		
		$token  = $_POST['stripeToken']; 
		$name = $user_name; 
		$email = $user_email;
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
				if($GLOBALS['free_trial'] == 1){ // add trial period
				
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
						var_dump('helo');

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
}

?>