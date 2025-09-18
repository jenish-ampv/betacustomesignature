<?php
// ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
require_once(GetConfig('SITE_BASE_PATH').'/lib/stripe-php/stripe-config.php');	  // stripe
require_once(GetConfig('SITE_BASE_PATH').'/lib/stripe-php/init.php');	  // bpoint  // stripe
class CIT_PROCESSPAYMENT
{
	
	public function __construct()
	{	

	}
	
	public function displayPage(){

		\Stripe\Stripe::setApiKey(GetConfig('STRIPE_SECRET_KEY'));

		header('Content-Type: application/json');
		try {
		    // Step 1: Parse JSON input
		    $json = json_decode(file_get_contents('php://input'), true);
		    $paymentMethodId = $json['payment_method'];
		    $email = $json['user_email'];
		    $plan_id = $json['plan_id'];
			$plan_unit = $json['plan_unit'];
			$GLOBALS['user_firstname'] = $json['user_firstname'];
			$GLOBALS['user_lastname'] = $json['user_lastname'];
			$GLOBALS['user_organization'] = $json['user_organization'];
			$GLOBALS['user_email'] = $json['user_email'];
			$GLOBALS['user_password'] = md5($json['user_password']);
			$plan_id = $json['plan_id'];
			$plan_unit = $json['plan_unit'];

		    // Step 2: Create Customer with Payment Method
		    $customer = \Stripe\Customer::create([
		        'email' => $email,
		        'payment_method' => $paymentMethodId,
		        'invoice_settings' => [
		            'default_payment_method' => $paymentMethodId,
		        ],
		    ]);
		    $this->getPlanDetail($plan_id,$plan_unit,0);
		    $rowAI = $GLOBALS['DB']->AutoIncrement("registerusers");
			$user_id = $rowAI['Auto_increment'];
		    // Step 3: Create Subscription
		    $subscription = \Stripe\Subscription::create([
		        'customer' => $customer->id,
		        'items' => [
		            [
		                "price" => $GLOBALS['plan_priceid'],
		            ],
		        ],
		        'expand' => ['latest_invoice.payment_intent'],
		    ]);
		    // Step 4: Check Payment Status
		    $paymentIntent = $subscription->latest_invoice->payment_intent;

			$free_trial = $subscription->status == 'trialing' ? 1 : 0;
			$customer_id = $customer->id;
			$subscription_id = $subscription->id;
			$price_id = $GLOBALS['plan_priceid'];
			$plan_interval = $subscription->plan->interval;
			$start_time = $subscription->latest_invoice->lines->data[0]->period->start;
			$end_time = $subscription->latest_invoice->lines->data[0]->period->end;
			$amount_paid = $subscription->latest_invoice->amount_paid;
			$invoice_link = $subscription->latest_invoice->invoice_pdf;
			$invoice_no = $paymentIntent->invoice;
			$coupon_id = "";

			$data = array('user_id'=>$user_id,'plan_id'=>$plan_id,'customer_id'=>$customer_id,'subscription_id' => $subscription_id,'price_id' =>$price_id,'plan_interval' => $plan_interval,'plan_signaturelimit'=>$plan_unit,'period_start' => $start_time,'period_end' => $end_time,'apply_coupon'=>$coupon_id,'invoice_amount'=>$amount_paid,'invoice_link' =>$invoice_link,'free_trial'=>$free_trial);
			$add = $GLOBALS['DB']->insert("registerusers_subscription",$data);
			
			
			$GLOBALS['plan_amount_paid'] = number_format(($amount_paid / 100),2);
			$message= _getEmailTemplate('subscription_admin','admin');
			_SendMailMasterAdmin($email,1,$GLOBALS['EMAIL_SUBJECT'],$message);

		    $data =array('user_firstname'=>trim($GLOBALS['user_firstname']),'user_lastname'=>trim($GLOBALS['user_lastname']),'user_email'=>trim(strtolower($GLOBALS['user_email'])),'user_password'=>$GLOBALS['user_password'],'user_organization'=>trim($GLOBALS['user_organization']),'user_ip'=>$_SERVER['REMOTE_ADDR'],'user_planactive'=>1);
			$insert_id = $GLOBALS['DB']->insert("registerusers",$data);

			// add transaction detail
		    $trdata =array('trn_userid'=>$insert_id,'trn_planid'=>$GLOBALS['plan_id'],'trn_invoiceno'=>$invoice_no,'trn_invoicefile'=>$invoice_link,'trn_total'=>$amount_paid);
		    $GLOBALS['DB']->insert("registerusers_transaction",$trdata);
			

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
				$_SESSION[GetSession('Success')] ='<div class="alert alert-success"><strong>Success! </strong>Signup success signin to create new signature</div>';
				$message= _getEmailTemplate('welcome');
				$send_mail = _SendMail($email,'',$GLOBALS['EMAIL_SUBJECT'],$message);
				$this->AddgohiLevelContact();
				
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
				
				$datalayer = json_encode($dataLayerData);
				$redirect_thnk = GetUrl(array('module'=>'thanks')).'/register?customer_id='.$insert_id.'&datalayer='.$datalayer;
				
				/// ADDED FOR DATA LAYER END/////
				if(isset($_REQUEST['id'])){
					$this->saveFreeSignature($insert_id,$_REQUEST['id']);
				}
				else if(isset($_REQUEST['category_id'])){
					$this->saveFreeSignature($insert_id,$_REQUEST['category_id']);
				}
				// GetFrontRedirectUrl(GetUrl(array('module'=>'thanks')));
			}

		    if ($paymentIntent->status === 'succeeded') {
		        echo json_encode(['success' => true, 'subscription_id' => $subscription->id]);
		    } else {
		        echo json_encode(['error' => 'Payment failed.']);
		    }
		} catch (\Stripe\Exception\ApiErrorException $e) {
		    echo json_encode(['error' => $e->getMessage()]);
		}
	
	}

	public function getPlanDetail($selplan_id='',$selunit='',$getunit =1){
		$planRows = $GLOBALS['DB']->query("SELECT * FROM `plan`  WHERE `plan_status` =1");
		foreach($planRows as $planRow){
			$plan_id = $planRow['plan_id'];
			$planname = strtolower($planRow['plan_name']);
			$plantype = strtolower($planRow['plan_type']);
			$basic_quarter_arr = "";
			$basic_quarter_arrspl = "";
			$pro_quarter_arr = "";
			$pro_quarter_arrspl = "";
			$basic_year_arr = "";
			$basic_year_arrspl = "";
			$pro_year_arr = "";
			$pro_year_arrspl = "";
			$plan_selprice = 0;
			$plan_selpricespl = 0;
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
						$plan_selprice =  $unit['plan_unitprice']; 
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
					 
				}else{
					$selected ='';
	 			}
			}
		
		// $GLOBALS['basic_quarter_unit'] = json_encode($basic_quarter_arr);
		// $GLOBALS['basic_quarter_unitspl'] = json_encode($basic_quarter_arrspl);
		$GLOBALS['pro_quarter_unit'] = json_encode($pro_quarter_arr);
		$GLOBALS['pro_quarter_unitspl'] = json_encode($pro_quarter_arrspl);
		// $GLOBALS['basic_year_unit'] = json_encode($basic_year_arr);
		// $GLOBALS['basic_year_unitspl'] = json_encode($basic_year_arrspl);
		$GLOBALS['pro_year_unit'] = json_encode($pro_year_arr);
		$GLOBALS['pro_year_unitspl'] = json_encode($pro_year_arrspl);
		return false;
	}


	private function AddgohiLevelContact(){
		if($GLOBALS['user_email'] != "" && $GLOBALS['user_firstname'] !=""){
			$gh_firstname = $GLOBALS['user_firstname']; 
			$gh_lastname = $GLOBALS['user_lastname']; 
			$gh_email = $GLOBALS['user_email'];
			$gh_org = $GLOBALS['user_organization'];

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