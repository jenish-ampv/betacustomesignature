<?php
require_once(GetConfig('SITE_BASE_PATH').'/lib/stripe-php/init.php');	  // bpoint  // stripe
class CIT_BILLING
{
	
	public function __construct()
	{	
		if($GLOBALS['billing_settings'] != "billing_settings"){
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
		if(isset($_REQUEST['category_id'])){
			$action = trim($_REQUEST['category_id']);
		} else {
			$action = '';
		}
		
		if($_POST['proration'] == 1){
			$result =  $this->getProration($GLOBALS['USERID']);
			echo json_encode($result); exit;
		}
		
		if($_POST['upgrade_plan'] == 'UPGRADEPLAN'){
			$result =  $this->subUpgrade($GLOBALS['USERID']);
			echo json_encode($result); exit;
		}
		if($_REQUEST['category_id'] == 'cancel-subscription'){
			$this->cancelSubscription();
			exit;
		}
		//if($_REQUEST[])


		if($_POST['subscription_id'] !="" && isset($_POST['autorenew'])){
			if($_POST['autorenew'] == 0){ // auto renew off
				$autorenew = 1;
				$subscriptionId = $_POST['subscription_id'];
				\Stripe\Stripe::setApiKey(GetConfig('STRIPE_SECRET_KEY'));
				try {  
				$canceledSubscription = \Stripe\Subscription::update(
					$subscriptionId, array(
					'cancel_at_period_end' => true,
					));
				}catch(Exception $e) {  
				$api_error = $e->getMessage();  
				} 
			}else if($_POST['autorenew'] == 1){ // auto renew on
				$autorenew = 0;
				$subscriptionId = $_POST['subscription_id'];
				\Stripe\Stripe::setApiKey(GetConfig('STRIPE_SECRET_KEY'));
				try {  
					$canceledSubscription = \Stripe\Subscription::update(
						$subscriptionId, array(
						'cancel_at_period_end' => false,
						));
					}catch(Exception $e) {  
					$api_error = $e->getMessage();  
					} 
			}else{
				return 0;
			}

			if(empty($api_error) && $canceledSubscription){
				$data = array('auto_renew' => $autorenew);
				$where = array('user_id'=>$GLOBALS['USERID']);
				$update = $GLOBALS['DB']->update('registerusers_subscription',$data,$where);
				if($update){
					if($autorenew ==1){
						$return_arrs = array('error'=>0,'status'=>200,'msg'=>'Turn on auto renewal','label'=>'OFF');
					}else{
						$return_arrs = array('error'=>0,'status'=>200,'msg'=>'Turn off auto renewal','label'=>'ON');
					}
				}
			}else{
				$return_arrs = array('error'=>1,'status'=>201,'msg'=>$api_error);
				$GLOBALS['Error'] = $api_error;
			}
			echo json_encode($return_arrs); exit;
		}
		
		
		if($_POST['stripeToken'] !="" && $_POST['customer_id'] !=""){ // UPDATE CARD DETAILS
			
			$customerId = $_POST['customer_id'];
			\Stripe\Stripe::setApiKey(GetConfig('STRIPE_SECRET_KEY'));
			try {  
				$createSource = \Stripe\Customer::createSource(  // create card source 
					$customerId, array(
						'source' => $_POST['stripeToken'],
					));
				$customer = \Stripe\Customer::update(  // update customer card
					$customerId, array(
					'default_source' =>$createSource->id,
					));
					
			}catch(Exception $e) {  
				$api_error = $e->getMessage();  
			} 
			
			if(empty($api_error) && $customer){
				
				$customerCard = \Stripe\Customer::retrieveSource($customer->id,$customer->default_source,array());
				$cardData = $customerCard->jsonSerialize();
				$updatecard = $this->saveUserCard($cardData);
				if($updatecard){
					$_SESSION[GetSession('Success')] ='<div class="alert alert-success"><strong>Success! </strong>card detail updated!</div>';
				}else{
					$_SESSION[GetSession('Error')] ='<div class="alert alert-danger" id="wrong"><strong> Fail! </strong>somthing wrong please contact administrator. if your payment detail not updated.</div>';
				}
			}else{
				$_SESSION[GetSession('Error')] ='<div class="alert alert-danger" id="wrong"><strong> Fail! </strong>'.$api_error.' please contact administrator. if your payment detail not updated.</div>';
			}
			GetFrontRedirectUrl(GetUrl(array('module'=>'billing')));
			
		}
		if($_REQUEST['action'] == 'saveInvoice'){
			$this->saveInvoicePdf($_REQUEST['invoice_id']);
		}
		
		$GLOBALS['upgrade_freetrial'] = $_REQUEST['action'] == 'freetrial' ? 1 : 0;
		
		//$this->getPage();
		$this->getPlanDetails();
		$GLOBALS['li_billing'] = GetUrl(array('module'=>$_REQUEST['module'],'id'=>'billing'));
		$GLOBALS['li_purchasehistory'] = GetUrl(array('module'=>$_REQUEST['module'],'id'=>'purchasehistory'));
		$GLOBALS['li_cancelsubscription'] = GetUrl(array('module'=>$_REQUEST['module'],'id'=>'cancel-subscription'));
		$this->getCardDetails();
		$this->getPurchaseHistory();
		$GLOBALS['STRIPE_PUBLISHABLE_KEY'] = GetConfig('STRIPE_PUBLISHABLE_KEY');
		$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/billing.html');
		$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');			
		$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');
		$GLOBALS['SIDEBAR'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.sidebar.html');
		$GLOBALS['CLA_HTML']->display();
		RemoveMessageInfo();
		exit();	
	}

	private function getPlanDetails(){

		$planRow = $GLOBALS['DB']->row("SELECT SUB.*,P.*, SUB.plan_signaturelimit as nosignature FROM `registerusers_subscription` SUB LEFT JOIN plan P ON P.plan_id = SUB.plan_id WHERE SUB.`user_id` = ?",array($GLOBALS['USERID']));
		$GLOBALS['plan_name'] = $planRow['plan_name'];
		$GLOBALS['plan_feature'] = $planRow['plan_feature'];
		$GLOBALS['subscription_id'] = $planRow['subscription_id'];
		$GLOBALS['customer_id'] = $planRow['customer_id'];
		$GLOBALS['price_id'] = $planRow['price_id'];
		$GLOBALS['plan_autorenew'] = $planRow['auto_renew'] == 0 ? 'ON' : 'OFF';
		$GLOBALS['plan_autorenewbtn'] = $planRow['auto_renew'] == 0 ? 'off' : 'on';
		$GLOBALS['plan_autorenewvalue'] = $planRow['auto_renew'];
		$GLOBALS['plan_interval'] = $planRow['plan_interval'] == 'month' ? 'Quarterly' : 'Yearly';
		$GLOBALS['plan_enddate'] = $planRow['period_end'] != '' ? date('M d, Y', $planRow['period_end']) : '';
		$GLOBALS['plan_signature'] = $planRow['nosignature'];
		$GLOBALS['plan_upgradebtnd'] = $planRow['free_trial'] == 0 ? 'disabled="disabled"' : '';
		$GLOBALS['plan_popuptitle'] = $planRow['free_trial'] == 0 ? 'Upgrade Plan' : 'Confirm Your Plan';
		if($planRow['invoice_amount']){
			$invoice_amount = ($planRow['invoice_amount'] / 100);
			$GLOBALS['plan_invoiceamount'] =  GetPriceFormat($invoice_amount);
		}else{
			$GLOBALS['plan_invoiceamount'] ='0.00';
		}
		if($planRow['period_end'] !=""){
			$GLOBALS['plan_end'] = date('d F Y h:i:s a', $planRow['period_end']);
		}else{
			$GLOBALS['plan_end'] ='';
		}
		 $GLOBALS['Plansel'.$planRow['plan_id']] ='selected';
		 $GLOBALS['Sigsel'.$planRow['nosignature']] ='selected';
		 $GLOBALS['free_trial'] = $planRow['free_trial'];
		 if($planRow['subscription_id'] == '_free' || $planRow['subscription_id'] == '_admin'){
			 	 $GLOBALS['display_cardupdate'] = 'd-none';
		 }else{
			 $GLOBALS['display_cardupdate'] = '';
		 }
	
		 
		 $this->getPlanDetails2($planRow['plan_id'],$planRow['nosignature']);
				
				
	}
	
	
	public function getPlanDetails2($selplan_id='',$selunit='',$getunit =1){
		$exist_plan_unit = "";
		$existCustomer = $GLOBALS['DB']->row("SELECT customer_id,plan_id,subscription_id FROM registerusers_subscription WHERE user_id = ? LIMIT 0,1",array($GLOBALS['USERID']));
		if(isset($existCustomer['plan_id']) && $existCustomer['plan_id']){
			$selplan_id = $existCustomer['plan_id'];
		}
		elseif(isset($existCustomer['subscription_id'])){
			if($existCustomer['subscription_id'] == '_admin'){ // if user created from admin
				$selplan_id='1';
				$selunit='1';
			}
		}
		elseif(isset($existCustomer['customer_id'])){
			$stripe = new \Stripe\StripeClient(GetConfig('STRIPE_SECRET_KEY'));
			$customer = $stripe->customers->retrieve($existCustomer['customer_id'], []);
			if (isset($customer->metadata)){
				$metadata = $customer->metadata;
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
						if(is_null($GLOBALS['plan_name'])){
							$GLOBALS['plan_name'] = $planname;
						}
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
					$GLOBALS['selected_plan'] = '<div class="bg-gradient-to-r from-[#26B7FF]/10 to-[#1D4AFE]/10 p-5 rounded-lg relative">
						<div class="flex justify-between mt-5 flex-col sm:flex-row">
							<h5 class="text-gray-950 font-semibold">'.$planRow['plan_name'].' ('.ucfirst($plantype).'ly)</h5>
							<h5 class="text-gray-950 font-semibold">$<span class="month_basicprice">'.$plan_selprice.'</span> /mo</b></h5>
						</div>
						<div class="flex justify-between">
							<div class="text_price"><span>'.$selunit.'</span> Signature</div>
							<span class="kt-badge bg-gradient text-white rounded-full absolute left-2 top-2">'. $offper.'</span>';
					if($plantype == 'year'){
						$GLOBALS['selected_plan'] .= '<div class="monthprice">$<span>'.$plan_selpricespl.'</span></div>';
					}
					$GLOBALS['selected_plan'] .= '</div></div>';
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

	private function getCardDetails(){
		$cards = $GLOBALS['DB']->query("SELECT * FROM `registerusers_card` WHERE `user_id` = ? ORDER BY card_id LIMIT 1 ",array($GLOBALS['USERID']));
		$GLOBALS['user_cards'] ='';
		foreach($cards as $card){
			$GLOBALS['user_cards'] .='<p class="text-gray-950 font-semibold">'.$card['brand'].' ending in '.$card['last4'].' &nbsp; <span class="text-gray-400">Expires : '.$card['exp_month'].'/'.$card['exp_year'].'</span></p>';
		}
	}

	private function getPurchaseHistory(){
		$transactions = $GLOBALS['DB']->query("SELECT * FROM registerusers_transaction WHERE trn_userid = ? ORDER BY `trn_id` DESC",array($GLOBALS['USERID']));
		$GLOBALS['transaction'] = '';
		foreach($transactions as $trn){
			$trn_total = ($trn['trn_total'] / 100);
			$GLOBALS['transaction'] .= '<tr>
			<td>'.GetOnlyDate($trn['trn_created']).'</td>
			<td>'.$trn['trn_invoiceno'].'</td>
			<td>US '.GetPriceFormat($trn_total).'</td>
			<td><a class="kt-btn kt-btn-sm kt-btn-outline" href="'.GetUrl(array('module'=>'billing','id'=>'saveInvoice')).'/'.$trn['trn_invoiceno'].'" target="_blank">Save Invoice</a></td>
		  </tr>';
		}

	}
	
	private function saveUserCard($cardData){
		if($cardData){
			$data =array('id'=>$cardData['id'],'brand'=>$cardData['brand'],'customer'=>$cardData['customer'],'exp_month'=>$cardData['exp_month'],'exp_year'=>$cardData['exp_year'],'last4'=>$cardData['last4']);
			$where = array('user_id'=>$GLOBALS['USERID']);
			return $GLOBALS['DB']->update("registerusers_card",$data,$where);
		}
		return false;

	}
	
	private function getProration($user_id){
		
		
		$userRow = $GLOBALS['DB']->row("SELECT * FROM registerusers RU LEFT JOIN registerusers_subscription SU ON RU.user_id = SU.user_id WHERE RU.user_id= ?",array($user_id));
		
		if($_POST['plan_id'] == $userRow['plan_id'] && $_POST['plan_unit'] == $userRow['plan_signaturelimit'] && $userRow['free_trial'] ==0){
			 return array('error'=>1,'message'=>'<div class="alert alert-danger">please select a different plan.</div>');
		}

		if($userRow['customer_id'] != "" && $userRow['subscription_id'] != ""){
			
		   $customer_id = $userRow['customer_id'];
		   $subscription_id = $userRow['subscription_id'];
		   $plan_id = $_POST['plan_id'];
		   $plan_unit = $_POST['plan_unit'];
		   $priceRow = $GLOBALS['DB']->row("SELECT plan_priceid FROM plan WHERE plan_id =?",array($plan_id));
		   $stripe_price = $priceRow['plan_priceid'];

		
			\Stripe\Stripe::setApiKey(GetConfig('STRIPE_SECRET_KEY'));
			$proration_date = time();
			$subscription = \Stripe\Subscription::retrieve($subscription_id);
			
			// See what the next invoice would look like with a price switch
			// and proration set:
			$items = array(
			  array(
				'id' => $subscription->items->data[0]->id,
				'price' => $stripe_price,
				'quantity' => $plan_unit
			  ),
			);
			try {  
				$invoice = \Stripe\Invoice::upcoming(array(
				  'customer' => $customer_id,
				  'subscription' => $subscription_id,
				  'subscription_items' => $items,
				  'subscription_proration_date' => $proration_date,
				  //'subscription_proration_behavior' => 'create_prorations',
				));
				$invoiceData = $invoice->jsonSerialize();
				//echo '<pre>'; 
				//print_r($invoiceData); 
				//$amount_due = ($invoiceData['amount_due'] / 100);
				//$amount_due = ($invoiceData['amount_paid'] / 100);
				
				
				 $starting_balanced  = $invoiceData['starting_balance'];
			     $amount_due = ($invoiceData['lines']['data'][1]['amount'] - abs($invoiceData['lines']['data'][0]['amount'] +$starting_balanced));
				 $amount_due = ($amount_due / 100);
				return array('error'=>0,'amount_due'=>$amount_due,'proration_date'=>$proration_date,'free_trial'=>$userRow['free_trial']);
			}catch(Exception $e) {  
				$api_error = $e->getMessage();
				return array('error'=>1,'message'=>$api_error); 
			} 
			
			
		}
	}
	
	private function subUpgrade($user_id){  // upgrade subscription
	
		if(is_numeric($user_id)){				
			$userRow = $GLOBALS['DB']->row("SELECT * FROM registerusers RU LEFT JOIN registerusers_subscription SU ON RU.user_id = SU.user_id WHERE RU.user_id= ?",array($user_id));
			if($_POST['plan_id'] && $_POST['plan_unit']){
				
				 \Stripe\Stripe::setApiKey(GetConfig('STRIPE_SECRET_KEY'));
				 $plan_id = $_POST['plan_id'];
				 $plan_unit = $_POST['plan_unit'];
				 $customer_id = $userRow['customer_id'];
				$subscription_id = $userRow['subscription_id'];
				if($_POST['plan_id'] != $userRow['plan_id'] || $_POST['plan_unit'] != $userRow['plan_signaturelimit']){
					if($userRow['customer_id'] != "" && $userRow['subscription_id'] != ""){
						
						$priceRow = $GLOBALS['DB']->row("SELECT plan_priceid FROM plan WHERE plan_id =?",array($plan_id));
						$stripe_price = $priceRow['plan_priceid'];
						
						// update subscription plan
						 //error_reporting(1);
						 try {  
							 $subscription = \Stripe\Subscription::retrieve($subscription_id);
							 if($userRow['free_trial'] == 1){
								$update = \Stripe\Subscription::update(
								  $subscription->id,
								  array(
									//'payment_behavior' => 'pending_if_incomplete',
									//'billing_cycle_anchor' => 'now',
									"payment_behavior" =>'error_if_incomplete', // create_prorations , always_invoice
									"proration_behavior" =>'always_invoice',
									'trial_end' => 'now',
									'proration_date' =>$_POST['proration_date'],
									'metadata' => array('user_id' =>$user_id,'plan_id' =>$plan_id,'plan_unit'=>$plan_unit),
									'items' => array(
									  array(
										'id' => $subscription->items->data[0]->id,
										'price' => $stripe_price,
										'quantity' => $plan_unit
									  ),
									  
									),
								  )
								);
							 }else{
								$update = \Stripe\Subscription::update(
								  $subscription->id,
								  array(
									//'payment_behavior' => 'pending_if_incomplete',
									//'billing_cycle_anchor' => 'now',
									"payment_behavior" =>'error_if_incomplete', // create_prorations , always_invoice
									"proration_behavior" =>'always_invoice',
									'proration_date' =>$_POST['proration_date'],
									'metadata' => array('user_id' =>$user_id,'plan_id' =>$plan_id,'plan_unit'=>$plan_unit),
									'items' => array(
									  array(
										'id' => $subscription->items->data[0]->id,
										'price' => $stripe_price,
										'quantity' => $plan_unit
									  ),
									  
									),
								  )
								);
							 }
							
							 if(empty($api_error) && $update){ 

								$subsData = $update->jsonSerialize();
							// echo '<pre>'; 
							// print_r($subsData); exit;
								
								$subscription_id =  $subsData['id'];
								$start_time = $subsData['current_period_start'];
								$end_time = $subsData['current_period_end'];
								$customer_id = $subsData['customer'];
								$plan_id = $subsData['items']['data'][0]['plan']['id'];
								$plan_interval = $subsData['items']['data'][0]['plan']['interval'];
								$userId =  $subsData['metadata']['user_id'];
								$planId = $subsData['metadata']['plan_id'];
								$coupon_id = $subsData['discount']['coupon']['id'];
								
							
								// get updated subscription invoice detail
								$invoice = \Stripe\Invoice::retrieve($subsData['latest_invoice']);
								$invoiceData =  $invoice->jsonSerialize();
								
								$invoice_no = $invoiceData['id'];
								$invoice_link =  $invoiceData['invoice_pdf'];
								$amount_paid =  $invoiceData['amount_paid'];
								$customer_email =  $invoiceData['customer_email'];
								
								$data = array('plan_id'=>$planId,'customer_id'=>$customer_id,'subscription_id' => $subscription_id,'price_id' =>$plan_id,'plan_interval' => $plan_interval,'plan_signaturelimit'=>$plan_unit,'period_start' => $start_time,'period_end' => $end_time,'apply_coupon'=>0,'invoice_amount'=>$amount_paid,'invoice_link' => $invoice_link,'free_trial'=>0,'plan_cancel'=>0);
								$where = array('user_id'=>$userId);
								$add = $GLOBALS['DB']->update('registerusers_subscription',$data,$where);
								
								 $trdata =array('trn_userid'=>$userId,'trn_planid'=>$planId,'trn_invoiceno'=>$invoice_no,'trn_invoicefile'=>$invoice_link,'trn_total'=>$amount_paid);
			     				$insert_tr =  $GLOBALS['DB']->insert("registerusers_transaction",$trdata);
				 				$GLOBALS['plan_amount_paid'] = number_format(($amount_paid / 100),2);
								
								// update customer metadata
								$customer = \Stripe\Customer::update($customer_id,array(
									'metadata' => array('user_id' =>$user_id,'plan_id' =>$plan_id,'plan_unit'=>$plan_unit),
								));
								
								 if($userRow['free_trial'] == 1){
									$message= _getEmailTemplate('animation_process'); 	// send mail
									$send_mail = _SendMail($GLOBALS['USEREMAIL'],'',$GLOBALS['EMAIL_SUBJECT'],$message);
								 }
								
								if($add == 1){
									 return array('error'=>0,'message'=>'<div class="alert alert-success">You Plan has been updated.</div>');
								}else{
									return array('error'=>1,'message'=>'<div class="alert alert-danger">somthing wrong if your payment is debit from your account please contact support team.</div>');
								}
								
								
							  }
						 }catch(Exception $e) { 
							 return array('error'=>1,'message'=>'<div class="alert alert-danger">'.$api_error = $e->getMessage().'.</div>'); 
						} 
					}
				
				}else{
					 if($userRow['free_trial'] == 1){
						 try{
						 	$update = \Stripe\Subscription::update($userRow['subscription_id'],array('trial_end' => 'now',"payment_behavior" =>'error_if_incomplete'));
							if($update){
								$subsData = $update->jsonSerialize();								
								$subscription_id =  $subsData['id'];
								$start_time = $subsData['current_period_start'];
								$end_time = $subsData['current_period_end'];
								$customer_id = $subsData['customer'];
								$plan_id = $subsData['items']['data'][0]['plan']['id'];
								$plan_interval = $subsData['items']['data'][0]['plan']['interval'];
								$userId =  $subsData['metadata']['user_id'];
								$planId = $subsData['metadata']['plan_id'];
								
							
								// get updated subscription invoice detail
								$invoice = \Stripe\Invoice::retrieve($subsData['latest_invoice']);
								$invoiceData =  $invoice->jsonSerialize();
								
								$invoice_no = $invoiceData['id'];
								$invoice_link =  $invoiceData['invoice_pdf'];
								$amount_paid =  $invoiceData['amount_paid'];
								$customer_email =  $invoiceData['customer_email'];
								
								$data = array('plan_id'=>$planId,'customer_id'=>$customer_id,'subscription_id' => $subscription_id,'price_id' =>$plan_id,'plan_interval' => $plan_interval,'plan_signaturelimit'=>$plan_unit,'period_start' => $start_time,'period_end' => $end_time,'apply_coupon'=>0,'invoice_amount'=>$amount_paid,'invoice_link' =>$invoice_link,'free_trial'=>0,'plan_cancel'=>0);
								$where = array('user_id'=>$userId);
								$add = $GLOBALS['DB']->update('registerusers_subscription',$data,$where);
								
								 $trdata =array('trn_userid'=>$userId,'trn_planid'=>$planId,'trn_invoiceno'=>$invoice_no,'trn_invoicefile'=>$invoice_link,'trn_total'=>$amount_paid);
			     				$insert_tr =  $GLOBALS['DB']->insert("registerusers_transaction",$trdata);
								
								$message= _getEmailTemplate('animation_process'); 	// send mail
								$send_mail = _SendMail($GLOBALS['USEREMAIL'],'',$GLOBALS['EMAIL_SUBJECT'],$message);
							}
							 return array('error'=>0,'message'=>'<div class="alert alert-success">You Plan has been updated.</div>');
						 }catch(Exception $e) { 
							 return array('error'=>1,'message'=>'<div class="alert alert-danger">'.$e->getMessage().'.</div>'); 
						} 
						 
						 
					 }
					 return array('error'=>1,'message'=>'<div class="alert alert-danger">please select a different plan.</div>');
				}
				
			}
		}else{
			 return array('error'=>1,'message'=>'<div class="alert alert-danger">record not valid.</div>');
		}
	}
	
	private function cancelSubscription(){
		$userRow = $GLOBALS['DB']->row("SELECT RU.*,SU.*,P.plan_name FROM registerusers RU LEFT JOIN registerusers_subscription SU ON RU.user_id = SU.user_id LEFT JOIN plan P ON SU.plan_id= P.plan_id WHERE RU.user_id= ?",array($GLOBALS['USERID']));
		if($userRow){
			 \Stripe\Stripe::setApiKey(GetConfig('STRIPE_SECRET_KEY'));
			  $subscription_id = $userRow['subscription_id'];
			  $user_email = $userRow['user_email'];
			  try {  
				/* $canceledSubscription = \Stripe\Subscription::update(
					$subscription_id, array(
					'metadata' => array('user_id' =>$user_id,'plan_id' =>0,'plan_unit'=>0),
					'cancel_at_period_end' => true,
					)); */
					$stripe = new \Stripe\StripeClient(GetConfig('STRIPE_SECRET_KEY'));
					$canceledSubscription = $stripe->subscriptions->cancel($subscription_id, []);
				}catch(Exception $e) {  
					$api_error = $e->getMessage();  
				} 
				if(empty($api_error) && $canceledSubscription){
					$data = array('plan_cancel'=>1);
					$where = array('user_id'=>$GLOBALS['USERID']);
					$add = $GLOBALS['DB']->update('registerusers_subscription',$data,$where);
					$GLOBALS['USERNAME'] = $userRow['user_firstname'];
					$GLOBALS['user_firstname'] = $userRow['user_firstname'];
					$GLOBALS['user_email'] = $userRow['user_email'];
					
					$GLOBALS['plan_detail_formail'] = $userRow['plan_name'].' '.$userRow['plan_signaturelimit'].' Signature'.' '.ucfirst($userRow['plan_interval']).'ly plan' ; 
					$message= _getEmailTemplate('cancel_subscription');
					$send_mail = _SendMail($user_email,'',$GLOBALS['EMAIL_SUBJECT'],$message);

					$_SESSION[GetSession('Success')] = '<div class="alert alert-success"><strong>Success!</strong>Plan cancelled.</div>';
					GetFrontRedirectUrl(GetUrl(array('module'=>'renewaccount')));
				}else{
					$_SESSION[GetSession('Error')] = '<div class="alert alert-info"><strong>Failed!</strong> '.$api_error.'</div>';
					GetFrontRedirectUrl(GetUrl(array('module'=>'billing')));
				}
		}
	}
	
	
	private function subUpgradeFreeTrial($user_id){
		if(is_numeric($user_id)){				
			$userRow = $GLOBALS['DB']->row("SELECT * FROM registerusers RU LEFT JOIN registerusers_subscription SU ON RU.user_id = SU.user_id WHERE RU.user_id= ?",array($user_id));
			if($_POST['plan_id'] && $_POST['plan_unit'] && $userRow['free_tial'] == 1){
				
				 \Stripe\Stripe::setApiKey(GetConfig('STRIPE_SECRET_KEY'));
				 $plan_id = $_POST['plan_id'];
				 $plan_unit = $_POST['plan_unit'];
				 $customer_id = $userRow['customer_id'];
				 $subscription_id = $userRow['subscription_id'];
				if($_POST['plan_id'] != $userRow['plan_id'] || $_POST['plan_unit'] != $userRow['plan_signaturelimit']){
					if($userRow['customer_id'] != "" && $userRow['subscription_id'] != ""){
						$priceRow = $GLOBALS['DB']->row("SELECT plan_priceid FROM plan WHERE plan_id =?",array($plan_id));
						$stripe_price = $priceRow['plan_priceid'];
						
						try{
							$delete = $stripe->subscriptionItems->delete($subscription_id, array());
							$subscription = \Stripe\Subscription::create(array(
								"customer" => $customer_id, 
								'metadata' => array('user_id' =>$user_id,'plan_id' =>$plan_id,'plan_unit'=> $plan_unit),
								"payment_behavior" =>'error_if_incomplete',
								"items" => array( 
									array( 
										"price" => $stripe_price,
										"quantity" => $plan_unit 
									), 
								), 
								'expand' => array('latest_invoice.payment_intent'),
							));
						}catch(Exception $e) { 
							 return array('error'=>1,'message'=>'<div class="alert alert-danger">'.$e->getMessage().'.</div>'); 
						} 
					}
				}else{
					try{
						$subscription = \Stripe\Subscription::update($userRow['subscription_id'],array('trial_end' => 'now'));
					}catch(Exception $e) { 
						 return array('error'=>1,'message'=>'<div class="alert alert-danger">'.$e->getMessage().'.</div>'); 
					} 
				}
				
				if($subscription){
					$subsData = $subscription->jsonSerialize();								
					$subscription_id =  $subsData['id'];
					$start_time = $subsData['current_period_start'];
					$end_time = $subsData['current_period_end'];
					$customer_id = $subsData['customer'];
					$plan_id = $subsData['items']['data'][0]['plan']['id'];
					$plan_interval = $subsData['items']['data'][0]['plan']['interval'];
					$userId =  $subsData['metadata']['user_id'];
					$planId = $subsData['metadata']['plan_id'];
					
				
					// get updated subscription invoice detail
					$invoice = \Stripe\Invoice::retrieve($subsData['latest_invoice']);
					$invoiceData =  $invoice->jsonSerialize();
					
					$invoice_no = $invoiceData['id'];
					$invoice_link =  $invoiceData['invoice_pdf'];
					$amount_paid =  $invoiceData['amount_paid'];
					$customer_email =  $invoiceData['customer_email'];
					
					$data = array('plan_id'=>$planId,'customer_id'=>$customer_id,'subscription_id' => $subscription_id,'price_id' =>$plan_id,'plan_interval' => $plan_interval,'plan_signaturelimit'=>$plan_unit,'period_start' => $start_time,'period_end' => $end_time,'apply_coupon'=>0,'invoice_amount'=>$amount_paid,'invoice_link' =>$invoice_link,'free_trial'=>0);
					$where = array('user_id'=>$userId);
					$add = $GLOBALS['DB']->update('registerusers_subscription',$data,$where);
					
					 $trdata =array('trn_userid'=>$userId,'trn_planid'=>$planId,'trn_invoiceno'=>$invoice_no,'trn_invoicefile'=>$invoice_link,'trn_total'=>$amount_paid);
					$insert_tr =  $GLOBALS['DB']->insert("registerusers_transaction",$trdata);
				}
				 return array('error'=>0,'message'=>'<div class="alert alert-success">You Plan has been updated.</div>');
				
			}
		}else{
			 return array('error'=>1,'message'=>'<div class="alert alert-danger">record not valid.</div>');
		}
	
	}
	
	public function saveInvoicePdf($invoice_id)
	{
		$stripe = new \Stripe\StripeClient(GetConfig('STRIPE_SECRET_KEY'));
		$invoice = $stripe->invoices->retrieve($invoice_id, []);
		
		header("Location: ".$invoice['invoice_pdf']);
	}

	
}

?>