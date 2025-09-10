<?php
require_once(GetConfig('SITE_BASE_PATH').'/lib/stripe-php/stripe-config.php');	  // stripe
require_once(GetConfig('SITE_BASE_PATH').'/lib/stripe-php/init.php');	  // bpoint  // stripe
require_once($GLOBALS['BASE_LINK'].'/'.GetConfig('CLASSES').'/dashboard.php');
class CIT_UPDATESUBSCRIPTIONDURATION
{
	
	public function __construct()
	{	
		// if(!isset($_SESSION[GetSession('user_id')]) && !isset($_REQUEST['uuid'])){
		// 	GetFrontRedirectUrl(GetUrl(array('module'=>'signin')));
		// }
		// $GLOBALS['SIGNATURE'] = GetClass('CIT_DASHBOARD');
	}
	
	public function displayPage(){
		$subscriptionUserList = $GLOBALS['DB']->query("SELECT * FROM `registerusers_subscription` RUS RIGHT JOIN registerusers RU ON RUS.user_id = RU.user_id WHERE `period_end`='0' AND `script_runned`='0' ORDER BY RUS.user_id DESC"); // update subscription of user which hain 0 in period_start or period_end.
		// echo '<pre>a'; print_r(($subscriptionUserList)); echo '<br></pre>'; exit('Fim'); 
		$updatedCustomerData = [];
		
		$stripe = new \Stripe\StripeClient('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');
		// $stripeCustomer = $stripe->customers->retrieve('cus_PqzZBSw4zYlnqy',[ 'expand' => ['subscriptions'] ]);
		// echo '<pre>'; print_r($stripeCustomer); echo '<br></pre>'; exit('Fim'); 
		$logFile = $GLOBALS['ROOT_LINK'].'/.webhooklog/updatesubscriptionduration.log';
		
		
		foreach ($subscriptionUserList as $key => $subscriptionUser) {
		
			if(isset($subscriptionUser['customer_id']) && isset($subscriptionUser['subscription_id'])){
				if($subscriptionUser['subscription_id'] == '_admin'){
					$data = array('script_runned'=>'1');
					$where = array('user_id'=>$subscriptionUser['user_id']);
					$add = $GLOBALS['DB']->update('registerusers_subscription',$data,$where);
				}
				elseif($subscriptionUser['subscription_id'] == '_free'){
					$data = array('script_runned'=>'1');
					$where = array('user_id'=>$subscriptionUser['user_id']);
					$add = $GLOBALS['DB']->update('registerusers_subscription',$data,$where);
				}
				else{
					$stripeCustomer = $stripe->customers->retrieve($subscriptionUser['customer_id'],[ 'expand' => ['subscriptions'] ]);
					$stripeSubscriptionData = $stripeCustomer->subscriptions->data;
					$timestamp = date('Y-m-d H:i:s'); // Get current date/time
					if(count($stripeSubscriptionData) && isset($stripeSubscriptionData[0])){
						$periodStart = $stripeSubscriptionData[0]->current_period_start;
						$periodEnd = $stripeSubscriptionData[0]->current_period_end;
						$updatedCustomerData[$key]['dataupdated'] = true;
						$updatedCustomerData[$key]['customer_id'] = $subscriptionUser['customer_id'];
						$updatedCustomerData[$key]['period_start'] = $periodStart;
						$updatedCustomerData[$key]['period_end'] = $periodEnd;
						$message = " TimeStamp -- $timestamp \n customer_id : ".$subscriptionUser['customer_id']." \n customer_email : ".$subscriptionUser['user_email']." \n period_start : ".$periodStart." \n period_end : ".$periodEnd." \n-------------------------------\n"; // Log a message
						
						$handle = fopen($logFile, 'a');
						if ($handle) {
							fwrite($handle, $message); // Write to the log file
				    	fclose($handle);// Close the log file
						} else {
						    echo "Unable to open log file!";
						}
						$data = array('period_start'=>$periodStart,'period_end'=>$periodEnd,'script_runned'=>'1');
						$where = array('user_id'=>$subscriptionUser['user_id']);
						$add = $GLOBALS['DB']->update('registerusers_subscription',$data,$where);
					}else{
						$updatedCustomerData[$key]['dataupdated'] = false;
						$updatedCustomerData[$key]['customer_id'] = $subscriptionUser['customer_id'];
						$updatedCustomerData[$key]['message'] = "No subscription found";
						
						$message = " TimeStamp -- $timestamp \n customer_id : ".$subscriptionUser['customer_id']." \n customer_email : ".$subscriptionUser['user_email']." \n message : No subscription found \n-------------------------------\n";
						$handle = fopen($logFile, 'a');
						if ($handle) {
							fwrite($handle, $message); // Write to the log file
				    	fclose($handle);// Close the log file
						} else {
						    echo "Unable to open log file!";
						}
						$data = array('script_runned'=>'1');
						$where = array('user_id'=>$subscriptionUser['user_id']);
						$add = $GLOBALS['DB']->update('registerusers_subscription',$data,$where);
					}
				}
			}
		}
		// echo '<pre>'; print_r($updatedCustomerData); echo '<br></pre>'; 
		if(count($updatedCustomerData)){
			// $this->generateLog($updatedCustomerData);
			echo "Subscription durations are updated successfully and log file created named 'updatesubscriptionduration' at root folder";
		}else{
			echo "No user's subscription found to be updated";
		}
	}
	public function generateLog($data)
	{
		$logFile = $GLOBALS['ROOT_LINK'].'/webhooklog/updatesubscriptionduration.log';

		// Open or create the log file
		$handle = fopen($logFile, 'a');

		// Check if the file was opened successfully
		if ($handle) {
		    $timestamp = date('Y-m-d H:i:s'); // Get current date/time

				foreach ($data as $key => $value) {
					$message = "";
					if($value['dataupdated']){
						$message = " TimeStamp -- $timestamp \n customer_id : ".$value['customer_id']." \n period_start : ".$value['period_start']." \n period_end : ".$value['period_end']." \n-------------------------------\n"; // Log a message
					}
					else{
						
						$message = " TimeStamp -- $timestamp \n customer_id : ".$value['customer_id']." \n message : ".$value['message']." \n-------------------------------\n"; // Log a message
					}
					fwrite($handle, $message); // Write to the log file
				}

		    fclose($handle);// Close the log file
		} else {
		    echo "Unable to open log file!";
		}

	}
		

	
	
}