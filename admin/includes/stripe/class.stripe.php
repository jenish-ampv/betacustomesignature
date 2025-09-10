<?php
require_once(GetConfig('SITE_BASE_PATH').'/lib/stripe-php/stripe-config.php');	  // stripe
require_once(GetConfig('SITE_BASE_PATH').'/lib/stripe-php/init.php');	  // bpoint  // stripe
$GLOBALS['user_type']= $_SESSION[GetSession('AdminType')];

class CIT_STRIPE{
	private $count;
	private $result;
	private $user;
	private $id ='';
	public function __construct(){	
		$GLOBALS['ModuleName'] = 'Stripe Webhook';	
	}
	public function displayPage(){	
		// echo '<pre>'; print_r($_REQUEST); echo '</pre>'; exit('<br>pre exit');
		if(isset($_REQUEST['action'])){
			$action = trim($_REQUEST['action']);
		} else {
			$action = '';
		}
		switch($action){
			case "datatableregdata":
				$this->datatableStripeWebhooks();
				break;
			case "showpayments":
				$this->showPayments();
				break;
			case "datatablepaymentdata":
				$this->datatableStripePayments();
				break;
			case "makerefund":
				$this->showRefund();
				break;
			case "refundpayment":
				$this->refundPayment();
				break;
			case "showsubscriptions":
				$this->showSubscriptions();
				break;
			case "datatablesubscriptiondata":
				$this->datatableSubscriptionData();
				break;
			case "canclesubscription":
				$this->cancleSubscription();
				break;
			default:
				$this->StripeWebhooks(); // default for 'view' action
				break;			
		}
	}

	private function StripeWebhooks(){
		
		AddMessageInfo();
		$GLOBALS['getstripedata'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'datatableregdata','id'=>$_REQUEST['id']));
		$GLOBALS['Totaluser'] = $count-1;	
		$GLOBALS['PageNextImage'] = $GLOBALS['DIS_PAGE']->nextImage();
		$GLOBALS['PagePrevImage'] = $GLOBALS['DIS_PAGE']->prevImage();
		$GLOBALS['PagePageLink'] = $GLOBALS['DIS_PAGE']->pageLink();
		$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/'.$GLOBALS['Module'].'/stripe.html');	
		$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');			
		$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');						
		$GLOBALS['LEFT'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.left.html');			
		$GLOBALS['CLA_HTML']->SetLoop('FEEDBACK',$webhookData);
		$GLOBALS['CLA_HTML']->display();
		RemoveMessageInfo();		
	}
	
	private function datatableStripeWebhooks(){
		try {

			$draw = $_POST['draw'];
			$row = $_POST['start'];
			$rowperpage = $_POST['length'];
			$columnIndex = $_POST['order'][0]['column'];
			$columnName = $_POST['columns'][$columnIndex]['data'];
			$columnSortOrder = $_POST['order'][0]['dir'];
			$searchValue = $_POST['search']['value'];
			$filter_val = $_POST['filter_val'];
			$user_id = $_REQUEST['id'];
			$searchQuery = " ";
			$stripe = new \Stripe\StripeClient(GetConfig('STRIPE_SECRET_KEY'));
			
			if($searchValue != ''){
				$searchQuery = " AND (RU.user_id like '%".$searchValue."%' or RU.user_firstname like '%".$searchValue."%' or RU.user_email like '%".$searchValue."%')";
			}
			
				if($_POST['filter_val'] == 1){
					$searchQuery .= "AND RU.`user_status`= 0";
				}else if($_POST['filter_val'] == 2){ 
					$searchQuery .= "AND RU.`user_status`= 1";
				}else if($_POST['filter_val'] == 3){ 
				$searchQuery .= "AND RU.`user_lastlogin` <= ".time()." AND RU.`user_lastlogin`!=0";
				}else if($_POST['filter_val'] == 4){
					$searchQuery .= "AND RU.`user_lastlogin` =0"; 
				} 
				
				$records = $GLOBALS['DB']->row("select count(*) as allcount FROM `registerusers` RU WHERE 1" .$searchQuery);
				$totalRecordwithFilter = $records['allcount'];
				// $stripeWebhookRecords = $GLOBALS['DB']->query("select RU.*,SU.*,PL.*,SU.plan_signaturelimit as nofsig,SL.logo_process FROM `registerusers` RU LEFT JOIN  registerusers_subscription SU ON RU.user_id = SU.user_id LEFT JOIN plan PL ON PL.plan_id = SU.plan_id LEFT JOIN signature_logo SL ON SL.user_id = RU.user_id   WHERE 1 ". $searchQuery." order by RU.".$columnName." ".$columnSortOrder." limit ".$row.",".$rowperpage);
				$stripeWebhookRecords = $GLOBALS['DB']->query("select SW.*,RU.`user_firstname` FROM `stripe_webhook` as SW LEFT JOIN `registerusers` as RU ON RU.`user_id` = SW.`user_id` WHERE SW.`user_id` = ?",array($user_id));

			$data = array();
			$count=1;  $adminkey = GetConfig('ADMIN_LOGINKEY');

			foreach ($stripeWebhookRecords as $row) {
				$webhookData[$count]['GetRegisterdate'] = GetDateFormat($row['user_created']);
				$eventResponseRaw = $row['event_response'];
				$eventResponseJson = trim(substr($eventResponseRaw, strpos($eventResponseRaw, "JSON:") + 5));
				$eventResponse = json_decode($eventResponseJson);	
				$invoice_link =  $eventResponse->invoice_pdf;
				$amount_paid =  $eventResponse->amount_paid;
				$subscription = $eventResponse->lines->data[0];	
				$subscription_id =  $subscription->subscription;
				$plan_id = $subscription->plan->id;
				$start_time = $subscription->period->start;
				$end_time = $subscription->period->end;
				$customer_id = $eventResponse->customer;
				$customer_email = $eventResponse->customer_email;
				$plan_id = $subscription->plan->id;
				$plan_interval = $subscription->plan->interval; 
				$userId =  $subscription->metadata->user_id;
				$planId =  $subscription->metadata->plan_id;
				$planUnit =  $subscription->metadata->plan_unit;
				$invoice_no = $eventResponse->id;
				$customer_id = $eventResponse->customer;
				
				$subscriptionsDetails = $stripe->subscriptions->retrieve($subscription_id, []);
				$subscriptionsStatus = "";
				$currentSubscription = $GLOBALS['DB']->row("select `subscription_id` FROM registerusers_subscription WHERE `user_id` = ?",array($user_id));
				$currentSubscriptionId = 0;
				if($currentSubscription){
					if(isset($currentSubscription['subscription_id'])){
						$currentSubscriptionId = $currentSubscription['subscription_id'];
					}
				}


				
				$currentActiveFlag = "";
				if($currentSubscriptionId == $subscription_id){
					$currentActiveFlag = '<label class="badge badge-light-success" style="cursor:pointer;">Currently Active</label>';
				}

				if($subscriptionsDetails){
					$subscriptionsStatus = $subscriptionsDetails->status;
					$subscriptionLinkToStripe = "https://dashboard.stripe.com/test/subscriptions/".$subscription_id;
				}
				
				$subscription = $eventResponse->lines->data[0];
				$paymentLinkOfSubscription = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'showpayments','user_id'=>$user_id,'customer_id'=>$customer_id));
				$showSubscriptionsLink = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'showsubscriptions','user_id'=>$user_id,'customer_id'=>$customer_id));
				
				$data[] = array(
					"user_name"=>$row['user_firstname'],
					"event_name"=>$row['event_name'],
					"subscriptions"=>$subscriptionsStatus." ".$currentActiveFlag." <a target='_blank' href='".$subscriptionLinkToStripe."' >link</a>",
					"action"=>"<a target='_blank' href='".$paymentLinkOfSubscription."' class='f-20 feather icon-eye' title='View Payments'>&nbsp;</a>  <a href='".$showSubscriptionsLink."' class='f-20 feather icon-edit' title='Show Subscription'>&nbsp;</a>"
				);
				$count++;
			}
			$response = array(
				"draw" => intval($draw),
				"iTotalDisplayRecords" => $totalRecordwithFilter,
				"aaData" => $data
			);
			echo json_encode($response);
		} 
		catch(\Stripe\Exception\CardException $e) {
			$response = array(
				"draw" => intval($draw),
				"iTotalDisplayRecords" => $totalRecordwithFilter,
				"aaData" => []
			);
			echo json_encode($response);
		} 
		catch (\Stripe\Exception\InvalidRequestException $e) {
			$response = array(
				"draw" => intval($draw),
				"iTotalDisplayRecords" => $totalRecordwithFilter,
				"aaData" => []
			);
			echo json_encode($response);
		} 
		catch (Exception $e) {
			$response = array(
				"draw" => intval($draw),
				"iTotalDisplayRecords" => $totalRecordwithFilter,
				"aaData" => []
			);
			echo json_encode($response);
		}
	}

	private function showPayments(){
		
		AddMessageInfo();
		$GLOBALS['li_getpayments'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'datatablepaymentdata','user_id'=>$_REQUEST['id'],'customer_id'=>$_REQUEST['db_name'])); // here db_name stands for customer_id
			
		$GLOBALS['Totaluser'] = $count-1;	
		$GLOBALS['PageNextImage'] = $GLOBALS['DIS_PAGE']->nextImage();
		$GLOBALS['PagePrevImage'] = $GLOBALS['DIS_PAGE']->prevImage();
		$GLOBALS['PagePageLink'] = $GLOBALS['DIS_PAGE']->pageLink();
		$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/'.$GLOBALS['Module'].'/payments.html');	
		$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');			
		$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');						
		$GLOBALS['LEFT'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.left.html');			
		$GLOBALS['CLA_HTML']->SetLoop('FEEDBACK',$webhookData);
		$GLOBALS['CLA_HTML']->display();
		RemoveMessageInfo();		
	}

	private function datatableStripePayments(){
		$draw = $_POST['draw'];
		// echo '<pre>'; print_r($_REQUEST); echo '</pre>'; exit('<br>pre exit');
		$customer_id = $_REQUEST['db_name']; // here db_name stands for customer_id
		// $customer_id = 'cus_Q2jfI7zOG0AX8o'; 
		$stripe = new \Stripe\StripeClient(GetConfig('STRIPE_SECRET_KEY'));
		\Stripe\Stripe::setApiKey(GetConfig('STRIPE_SECRET_KEY'));
		$stripePayments = \Stripe\PaymentIntent::all(['customer' => $customer_id]);

		$user_id = $_REQUEST['id']; // here subscription_id stands for customer_id
		$user_name = "";
		$userData = $GLOBALS['DB']->row("SELECT user_firstname FROM registerusers WHERE `user_id`=?",array($user_id));
		if($userData){
			$user_name = $userData['user_firstname'];
		}
		
		$records = $GLOBALS['DB']->row("SELECT count(*) as allcount FROM `registerusers_transaction` WHERE trn_userid=?",array($user_id));
		$totalRecordwithFilter = $records['allcount'];
		$allPayments = $GLOBALS['DB']->query("SELECT * FROM `registerusers_transaction` WHERE trn_userid=?",array($user_id));

		$data = array();
		$count=1;  $adminkey = GetConfig('ADMIN_LOGINKEY');
		foreach ($stripePayments->data as $payment) {
			$amount = isset($payment->amount) ? ($payment->amount/100) : 0;
			$description = isset($payment->description) ? $payment->description : '';

			$date = isset($payment->created) ? date('M d Y, h:i:A', $payment->created) : '';
			$charge_id = $payment['latest_charge'];
			$refundHistory = $GLOBALS['DB']->row("SELECT SUM(refund_amount) as refunded_amount FROM `refund_history` WHERE `charge_id`=?",array($charge_id));
			$refund_amount = isset($refundHistory['refunded_amount']) ? $refundHistory['refunded_amount'] : 0;

			$saveInvoiceLink = GetAdminUrl(array('module'=>'stripe','action'=>'makerefund','charge_id'=>$payment['latest_charge']));

			if($refund_amount > 0){
				$amount .= "Partial Refunded(".$refund_amount.")";
			}
			
			$data[] = array(
				"user_name"=>$user_name,
				"amount"=>$amount,
				"invoice_date"=>$description,
				"save_invoice"=>$date,
				"actions"=>"<a targrt='_blank' href='".$saveInvoiceLink."' class='f-20 feather icon-edit' title='Refund Payment'>&nbsp;</a>"
			);
			$count++;
		}
		$response = array(
			"draw" => intval($draw),
			"iTotalDisplayRecords" => $totalRecordwithFilter,
			"aaData" => $data
		);
		echo json_encode($response);
	}
	
	private function showRefund(){
		AddMessageInfo();
		$GLOBALS['refundformurl'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'refundpayment'));
		$GLOBALS['charge_id'] = $_REQUEST['subscription_id']; // for here subscription_id is for charge id in URL params


		try {
			$stripe = new \Stripe\StripeClient(GetConfig('STRIPE_SECRET_KEY'));
			$chargeObj = $stripe->charges->retrieve($_REQUEST['subscription_id'], []);
			if($chargeObj){
				$GLOBALS['total_possible_refund_amount'] = ($chargeObj->amount)/100;
			}
		} 
		catch(\Stripe\Exception\CardException $e) {
			error_log("A payment error occurred: {$e->getError()->message}");
		} 
		catch (\Stripe\Exception\InvalidRequestException $e) {
			error_log("An invalid request occurred: {$e->getError()->message}");
		} 
		catch (Exception $e) {
			error_log("Another problem occurred, maybe unrelated to Stripe.");
		}
			
		$GLOBALS['Totaluser'] = $count-1;	
		$GLOBALS['PageNextImage'] = $GLOBALS['DIS_PAGE']->nextImage();
		$GLOBALS['PagePrevImage'] = $GLOBALS['DIS_PAGE']->prevImage();
		$GLOBALS['PagePageLink'] = $GLOBALS['DIS_PAGE']->pageLink();
		$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/'.$GLOBALS['Module'].'/refund.html');	
		$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');			
		$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');						
		$GLOBALS['LEFT'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.left.html');			
		$GLOBALS['CLA_HTML']->SetLoop('FEEDBACK',$webhookData);
		$GLOBALS['CLA_HTML']->display();
		RemoveMessageInfo();		
	}

	private function refundPayment() {
		$admin_id = $_SESSION['AdminIdPHPSESSID'];

		$charge_id = $_POST['charge_id'];
		// $charge_id = 'ch_3PIWBXHp7DgNkCIv1k9gHjnG';
		$refund_amount = $_POST['refund_amount']*100; // multiply by 100 as stripe count $1 as 100 
		// $amount = 100;
		$reason = $_POST['reason'];
		$message = $_POST['reason_detail'];
		$refundData = [];
		try {
			$stripe = new \Stripe\StripeClient(GetConfig('STRIPE_SECRET_KEY'));
			$refundStatus = $stripe->refunds->create(['charge' => $charge_id,'amount' => $refund_amount, 'reason' => $reason]);
			$status = "";
			if($refundStatus->status == 'succeeded'){
				// refund is succeeded
				$status = "succeeded";
				echo('Refund succeess');
				$_SESSION['Success'] = '<div class="alert alert-success">Plan upgrade success.</div>';
				GetAdminRedirectUrl(GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'showpayments')));
			}
			$refundData = array(
				'admin_id' => $admin_id,
				'charge_id' => $charge_id,
				'refund_amount' => $refund_amount,
				'refund_reason' => $reason,
				'status' => $status,
				'message' => $message,
			);
		} 
		catch(\Stripe\Exception\CardException $e) {
			$refundData = array(
				'admin_id' => $admin_id,
				'charge_id' => $charge_id,
				'refund_amount' => $refund_amount,
				'refund_reason' => $reason,
				'status' => 'error',
				'message' => $e->getError()->message,
			);
			$_SESSION['Error'] = "A payment error occurred: {$e->getError()->message}";
			GetAdminRedirectUrl(GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'showpayments')));
		} 
		catch (\Stripe\Exception\InvalidRequestException $e) {
			$refundData = array(
				'admin_id' => $admin_id,
				'charge_id' => $charge_id,
				'refund_amount' => $refund_amount,
				'refund_reason' => $reason,
				'status' => 'error',
				'message' => $e->getError()->message,
			);
			$_SESSION['Error'] = "An invalid request occurred: {$e->getError()->message}";
			GetAdminRedirectUrl(GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'showpayments')));
			
		} 
		catch (Exception $e) {
			$refundData = array(
				'admin_id' => $admin_id,
				'charge_id' => $charge_id,
				'refund_amount' => $refund_amount,
				'refund_reason' => $reason,
				'status' => 'error',
				'message' => $e->getError()->message,
			);
			$_SESSION['Error'] = "Another problem occurred, maybe unrelated to Stripe.";
			GetAdminRedirectUrl(GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'showpayments')));
		}

		if($refundData){
			$GLOBALS['DB']->insert("refund_history",$refundData);
		}
	}
		
	private function showSubscriptions() {
		AddMessageInfo();
		$GLOBALS['getsubscriptiondata'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'datatablesubscriptiondata','user_id'=>$_REQUEST['id'],'customer_id'=>$_REQUEST['db_name'])); // here db_name stands for customer_id
			
		$GLOBALS['Totaluser'] = $count-1;	
		$GLOBALS['PageNextImage'] = $GLOBALS['DIS_PAGE']->nextImage();
		$GLOBALS['PagePrevImage'] = $GLOBALS['DIS_PAGE']->prevImage();
		$GLOBALS['PagePageLink'] = $GLOBALS['DIS_PAGE']->pageLink();
		$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/'.$GLOBALS['Module'].'/subscriptions.html');	
		$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');			
		$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');						
		$GLOBALS['LEFT'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.left.html');			
		$GLOBALS['CLA_HTML']->SetLoop('FEEDBACK',$webhookData);
		$GLOBALS['CLA_HTML']->display();
		RemoveMessageInfo();		
	}

	private function datatableSubscriptionData() {
		$customer_id = $_REQUEST['db_name']; // here db_name stands for customer_id
		$user_id = $_REQUEST['id'];
		$user_name = "";
		$userData = $GLOBALS['DB']->row("SELECT user_firstname FROM registerusers WHERE `user_id`=?",array($user_id));
		if($userData){
			$user_name = $userData['user_firstname'];
		}


		$currentSubscription = $GLOBALS['DB']->row("select `subscription_id` FROM registerusers_subscription WHERE `user_id` = ?",array($user_id));

		$stripe = new \Stripe\StripeClient(GetConfig('STRIPE_SECRET_KEY'));
		$subscriptions = $stripe->subscriptions->all(['customer' => $customer_id,'status' => 'all']);

		foreach ($subscriptions->data as $subscription) {
			$subscriptionId = $subscription->id;
			$cancleSubscriptionLink = GetAdminUrl(array('module'=>'stripe','action'=>'canclesubscription','subscription_id'=>$subscriptionId,'user_id' => $user_id,'customer_id'=>$customer_id));

			if($subscriptionId == $currentSubscription['subscription_id']){
				$subscriptionId .= ' <label class="badge badge-light-success" style="cursor:pointer;">Currently Active</label>';
			}
			if($subscription->status == 'active'){
				$status = '<label class="badge badge-light-success" style="cursor:pointer;">Active</label>';
			}else if($subscription->status == 'canceled'){
				$status = '<label class="badge badge-light-danger" style="cursor:pointer;">Canceled</label>';
			}
			else{
				$status = '<label class="badge badge-light-warning" style="cursor:pointer;">'.$subscription->status.'</label>';
			}
			$data[] = array(
				"user_name"=>$user_name,
				"subscription"=>$subscriptionId,
				"status"=>$status,
				"action"=>"<a targrt='_blank' href='".$cancleSubscriptionLink."' class='f-20 feather icon-edit' title='Cancle Subscription'>&nbsp;</a>"
			);
		}
		
		$response = array(
			"draw" => intval($draw),
			"iTotalDisplayRecords" => $totalRecordwithFilter,
			"aaData" => $data
		);
		echo json_encode($response);
	}
	private function cancleSubscription() {
		try{
			$subscription_id = $_REQUEST['subscription_id'];
			$customer_id = $_REQUEST['customer_id'];
			$user_id = $_REQUEST['user_id'];

			$stripe = new \Stripe\StripeClient(GetConfig('STRIPE_SECRET_KEY'));
			$subscription = $stripe->subscriptions->cancel($subscription_id, []);
			$_SESSION['Error'] = "Subscription is successfully cancled";
			GetAdminRedirectUrl(GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'showsubscriptions','user_id'=>$user_id,'customer_id'=>$customer_id)));
		} 
		catch(\Stripe\Exception\CardException $e) {
			$_SESSION['Error'] = "A payment error occurred: {$e->getError()->message}";
			GetAdminRedirectUrl(GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'showsubscriptions','user_id'=>$user_id,'customer_id'=>$customer_id)));
		} 
		catch (\Stripe\Exception\InvalidRequestException $e) {
			$_SESSION['Error'] = "An invalid request occurred: {$e->getError()->message}";
			GetAdminRedirectUrl(GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'showsubscriptions','user_id'=>$user_id,'customer_id'=>$customer_id)));
			
		} 
		catch (Exception $e) {
			$_SESSION['Error'] = "Another problem occurred, maybe unrelated to Stripe.";
			GetAdminRedirectUrl(GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'showsubscriptions','user_id'=>$user_id,'customer_id'=>$customer_id)));
		}
	}
}
