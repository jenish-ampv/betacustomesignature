<?php
require_once(GetConfig('SITE_BASE_PATH').'/lib/stripe-php/stripe-config.php');	  // stripe
require_once(GetConfig('SITE_BASE_PATH').'/lib/stripe-php/init.php');	  // bpoint  // stripe
$GLOBALS['user_type']= $_SESSION[GetSession('AdminType')];

class CIT_REGISTERUSERS{
	private $count;
	private $result;
	private $user;
	private $id ='';
	public function __construct(){	
		$GLOBALS['ModuleName'] = 'Customer Users';	
		
	}
	public function displayPage(){	
		if(isset($_REQUEST['action'])){
			$action = trim($_REQUEST['action']);
		} else {
			$action = '';
		}
		switch($action){
			case "delete":
				$this->deleteRegisterusers();
				break;
			case "view":
				$this->viewRegisterusers();
				break;
			case "status":
				$this->statusRegisterusers();
				break;	
			case "add":
				$this->addRegisterusers();
				break;	
			case "export":
				$this->exportRegisterusers();
				break;
			case "datatableregdata";
				$this->datatableRegisterusers();
				break;
			case "upgrade";
				$this->subUpgrade();
				break;
			case "subusers";
				$this->subRegisterusers();
				break;
			case "datatableregsubuserdata";
				$this->datatableRegisterSubUsers();
				break;
			case "deletesubuser":
				$this->deleteRegisterSubusers();
				break;
			default:
				$this->Registerusers();
				break;			
		}
	}
	
	private function subUpgrade(){  // upgrade subscription
		if(isset($_REQUEST['id'])){
			$user_id = $_REQUEST['id'];
		} else {
			$user_id =  '';
		}	
		
		if(is_numeric($user_id)){
						
			$userRow = $GLOBALS['DB']->row("SELECT * FROM registerusers RU LEFT JOIN registerusers_subscription SU ON RU.user_id = SU.user_id WHERE RU.user_id= ?",array($user_id));
			
			if($_POST['plan_id'] && $_POST['plan_unit']){
				
				
				if($userRow['subscription_id'] == '_admin' || $userRow['subscription_id'] == '_free'){ // user have not payment method 
					$plan_id = $_POST['plan_id'];
					$signature_limit = $_POST['plan_unit'];
					if($plan_id == 1 || $plan_id == 2){
						$start_time =  strtotime(date('Y-m-d'));
						$newdate = date('Y-m-d', strtotime(' + 3 months'));
						$end_time =  strtotime($newdate);
						$plan_interval ='month';
					}
					if($plan_id == 3 || $plan_id == 4){
						$start_time =  strtotime(date('Y-m-d'));
						$newdate = date('Y-m-d', strtotime(' + 1 year'));
						$end_time =  strtotime($newdate);
						$plan_interval ='year';
					}
					
					if($userRow['user_id']){
						$data = array('plan_id'=>$plan_id,'plan_interval' => $plan_interval,'plan_signaturelimit'=>$signature_limit,'period_start' => $start_time,'period_end' => $end_time);
						$where = array('user_id'=>$userRow['user_id']);
						$add = $GLOBALS['DB']->update('registerusers_subscription',$data,$where);
						$dataUserType = array('user_type'=>$_POST['user_type']);
						$whereUserType = array('user_id'=>$userRow['user_id']);
						$add = $GLOBALS['DB']->update('registerusers',$dataUserType,$whereUserType);
						$_SESSION['Success'] = '<div class="alert alert-success">Plan upgrade success.</div>';
						GetAdminRedirectUrl(GetAdminUrl(array('module'=>'registerusers')));
					}else{
						$_SESSION['Error'] = '<div class="alert alert-danger">User not found.</div>';
						GetAdminRedirectUrl(GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'upgrade','id'=>$user_id)));	
					}
					// $_SESSION['Error'] = '<div class="alert alert-danger">this user subscribe by admin not any payment method.</div>';
					// GetAdminRedirectUrl(GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'upgrade','id'=>$user_id)));
				}
				
				
				if($_POST['plan_id'] != $userRow['plan_id'] || $_POST['plan_unit'] != $userRow['plan_signaturelimit']){
					if($userRow['customer_id'] != "" && $userRow['subscription_id'] != ""){
						$plan_id = $_POST['plan_id'];
						$plan_unit = $_POST['plan_unit'];
						$customer_id = $userRow['customer_id'];
						$subscription_id = $userRow['subscription_id'];
						
						$priceRow = $GLOBALS['DB']->row("SELECT plan_priceid FROM plan WHERE plan_id =?",array($plan_id));
						$stripe_price = $priceRow['plan_priceid'];
						
						// update subscription plan
						 \Stripe\Stripe::setApiKey(GetConfig('STRIPE_SECRET_KEY'));
						 error_reporting(1);
						 try {  
							 $subscription = \Stripe\Subscription::retrieve($subscription_id);
								$update = \Stripe\Subscription::update(
								  $subscription->id,
								  array(
									//'payment_behavior' => 'pending_if_incomplete',
									'proration_behavior' => 'always_invoice', // create_prorations , always_invoice
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
							}catch(Exception $e) {  
								$api_error = $e->getMessage();  
							} 
							 if(empty($api_error) && $subscription){ 
								$subsData = $subscription->jsonSerialize();
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
								
								$data = array('plan_id'=>$planId,'price_id' =>$plan_id,'plan_interval' => $plan_interval,'plan_signaturelimit'=>$plan_unit,'period_start' => $start_time,'period_end' => $end_time,'apply_coupon'=>$coupon_id);
								$where = array('user_id'=>$userId);
								$add = $GLOBALS['DB']->update('registerusers_subscription',$data,$where);
								
															// update customer metadata
								$customer = \Stripe\Customer::update($customer_id,array(
									'metadata' => array('user_id' =>$user_id,'plan_id' =>$plan_id,'plan_unit'=>$plan_unit),
								)); 
								
								$_SESSION['Success'] = '<div class="alert alert-success">Plan upgrade success.</div>';
								GetAdminRedirectUrl(GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'upgrade','id'=>$user_id)));
							  }
					}
				
				}else{
					$_SESSION['Error'] = '<div class="alert alert-danger">please select different plan.</div>';
					GetAdminRedirectUrl(GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'upgrade','id'=>$user_id)));
				}
				
			}
			if($userRow){
				$GLOBALS['user_fullname'] =$userRow['user_firstname'];
				$GLOBALS['user_emailaddress'] =$userRow['user_email'];
				
				$GLOBALS['Plansel'.$userRow['plan_id']] ='selected';
				$GLOBALS['Sigsel'.$userRow['plan_signaturelimit']] ='selected';
				$GLOBALS['user_type_'.$userRow['user_type']] ='selected';
				
				AddMessageInfo();	
				$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/'.$GLOBALS['Module'].'/registerusers.upgrade.html');				
				$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');			
				$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');						
				$GLOBALS['LEFT'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.left.html');			
				$GLOBALS['CLA_HTML']->display();
				RemoveMessageInfo();
			}else{
				$_SESSION['Error'] = '<div class="alert alert-danger">please select valid user.</div>';
				GetAdminRedirectUrl(GetAdminUrl(array('module'=>$_REQUEST['module']),0));
			}
		}else{
			$_SESSION['Error'] = '<div class="alert alert-danger">record not valid.</div>';
				GetAdminRedirectUrl(GetAdminUrl(array('module'=>$_REQUEST['module']),0));
		}
	}
	
	private function addRegisterusers(){
		
		if($_POST['addnewuser'] == 1){
			if($_POST['user_email'] != "" && $_POST['user_firstname'] != "" && $_POST['user_organization'] != "" && $_POST['plan_id'] != "" && $_POST['plan_unit'] != "" ){
				if($this->emailExist(trim(strtolower($_POST['user_email']))) == false){
					$Subscription = $this->createSubscription($_POST['plan_id'],$_POST['plan_unit']);
					if($Subscription){
						$user_password = md5($_POST['user_password']);
						$data =array('user_firstname'=>trim($_POST['user_firstname']),'user_email'=>trim(strtolower($_POST['user_email'])),'user_password'=>trim($user_password),'user_organization'=>trim($_POST['user_organization']),'user_ip'=>$_SERVER['REMOTE_ADDR'],'user_planactive'=>1,'user_type'=>$_POST['user_type']);
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
							if($_POST['user_type'] == 1){
								$_SESSION[GetSession('Success')] ='<div class="alert alert-success"><strong>Success! </strong>Free trial user created</div>';
							}else{
								$_SESSION[GetSession('Success')] ='<div class="alert alert-success"><strong>Success! </strong>Signup success signin to create new signature</div>';
							}
						}
					}else{
						$_SESSION[GetSession('Error')] ='<div class="alert alert-danger" id="wrong"><strong> Failure! </strong>somthing wrong try again</div>';
					}
				}else{
						$_SESSION[GetSession('Error')] ='<div class="alert alert-danger" id="wrong"><strong> Fail! </strong>email address already exist!</div>';
				}
			}else{
				$_SESSION['Error'] = '<div class="alert alert-danger">please enter required field.</div>';
				GetAdminRedirectUrl(GetAdminUrl(array('module'=>$_REQUEST['module']),0));
			}
		}
		
		AddMessageInfo();	
		$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/'.$GLOBALS['Module'].'/registerusers.add.html');				
		$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');			
		$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');						
		$GLOBALS['LEFT'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.left.html');			
		$GLOBALS['CLA_HTML']->display();
		RemoveMessageInfo();			
	}
	
	
	private function createSubscription($plan_id,$signature_limit){
		if($_POST['user_type'] == 1){
			$subid = '_free';
			$start_time =  strtotime(date('Y-m-d'));
			$newdate = date('Y-m-d', strtotime(' + 7 day'));
			$end_time =  strtotime($newdate);
			$plan_interval ='week';
		}else{
			$subid = '_admin';
			if($plan_id == 1 || $plan_id == 2){
				$start_time =  strtotime(date('Y-m-d'));
				$newdate = date('Y-m-d', strtotime(' + 3 months'));
				$end_time =  strtotime($newdate);
				$plan_interval ='month';
			}
			if($plan_id == 3 || $plan_id == 4){
				$start_time =  strtotime(date('Y-m-d'));
				$newdate = date('Y-m-d', strtotime(' + 1 year'));
				$end_time =  strtotime($newdate);
				$plan_interval ='year';
			}
		}
		$rowAI = $GLOBALS['DB']->AutoIncrement("registerusers");
		$user_id = $rowAI['Auto_increment'];
		$user_email  = $_POST['user_email'];
		$amount =0;
		$amount_paid =0;
		$memRow = $GLOBALS['DB']->row("SELECT user_id FROM registerusers_subscription WHERE user_id= ?",array($userId));	
		
		if($memRow['user_id']){
			$data = array('user_id'=>$user_id,'plan_id'=>$plan_id,'customer_id'=>$user_id,'subscription_id' =>$subid,'price_id' =>$plan_id,'plan_interval' => $plan_interval,'plan_signaturelimit'=>$signature_limit,'period_start' => $start_time,'period_end' => $end_time,'invoice_amount'=>$amount_paid,'invoice_link' => '','auto_renew'=>1);
			$where = array('user_id'=>$userId);
			$add = $GLOBALS['DB']->update('registerusers_subscription',$data,$where);
			
			// add transaction detail
				 $trdata =array('trn_userid'=>$userId,'trn_planid'=>$plan_id,'trn_invoiceno'=>'BYADMIN','trn_invoicefile'=>$invoice_link,'trn_total'=>$amount_paid);
			     $GLOBALS['DB']->insert("registerusers_transaction",$trdata);
				 return true;
		}else{
			$data = array('user_id'=>$user_id,'plan_id'=>$plan_id,'customer_id'=>$user_id,'subscription_id' =>$subid,'price_id' =>$plan_id,'plan_interval' => $plan_interval,'plan_signaturelimit'=>$signature_limit,'period_start' => $start_time,'period_end' => $end_time,'invoice_amount'=>$amount_paid,'invoice_link' => '','auto_renew'=>1);
				$add = $GLOBALS['DB']->insert("registerusers_subscription",$data);
			
				// add transaction detail
			    $trdata =array('trn_userid'=>$userId,'trn_planid'=>$plan_id,'trn_invoiceno'=>'BYADMIN','trn_invoicefile'=>'','trn_total'=>$amount_paid);
			    $GLOBALS['DB']->insert("registerusers_transaction",$trdata);
				return true;
		}
		return false;
	}
	private function emailExist($email){
		$existRow = $GLOBALS['DB']->row("SELECT user_id FROM registerusers WHERE user_email = ? LIMIT 0,1",array($email));
		if(isset($existRow['user_id'])){ return true; }
		return false;
	}
	


	private function statusRegisterusers(){ 
		if(isset($_REQUEST['id'])){
			$user_id = $_REQUEST['id'];
		} else {
			$user_id =  '';
		}	
		
		if(is_numeric($user_id)){

			$addResult = $GLOBALS['DB']->update('registerusers',array('user_status' => $_REQUEST['status']),array('user_id'=>$_REQUEST['id']));
			if($addResult){
				if($_REQUEST['status'] == 1){
					$current_dir = GetConfig('SITE_UPLOAD_PATH') . "/signature/complete/".$_REQUEST['id']."-expire";
					$rename_dir =  GetConfig('SITE_UPLOAD_PATH') . "/signature/complete/".$_REQUEST['id'];
					rename($current_dir,$rename_dir);
				}else{
					$current_dir = GetConfig('SITE_UPLOAD_PATH')."/signature/complete/".$_REQUEST['id'];
					$rename_dir =  GetConfig('SITE_UPLOAD_PATH')."/signature/complete/".$_REQUEST['id'].'-expire';
					rename($current_dir,$rename_dir);
				}
				$_SESSION['Success'] = '<div class="alert alert-success">Status changed successfully</div>';
			} else {
				$_SESSION['Error'] = '<div class="alert alert-danger">An error occurred while you trying to change status, please try again.</div>';
			}
			GetAdminRedirectUrl();
		} else {
			$_SESSION['Error'] = '<div class="alert alert-danger">Record not valid.</div>';
			GetAdminRedirectUrl(GetAdminUrl(array('module'=>$_REQUEST['module']),0));
		}					
	}

	public function deleteDirectory($dir) {
		if (!file_exists($dir)) {
			return true; // Nothing to delete
		}
	
		if (!is_dir($dir)) {
			return unlink($dir); // Delete file
		}
	
		// Loop through contents
		foreach (scandir($dir) as $item) {
			if ($item === '.' || $item === '..') {
				continue;
			}
	
			$path = $dir . DIRECTORY_SEPARATOR . $item;
			if (is_dir($path)) {
				deleteDirectory($path); // Recursively delete subdirectory
			} else {
				unlink($path); // Delete file
			}
		}
	
		return rmdir($dir); // Delete the now-empty directory
	}
	
	

	private function deleteRegisterusers(){		
		if(isset($_REQUEST['id'])){
			$id = $_REQUEST['id'];
		} else {
			$id =  '';
		}
		if(is_numeric($id)){		
			$GLOBALS['DB']->query("DELETE FROM registerusers_subscription WHERE user_id = ?", array($id)); // delete subscription data also
			$addResult = $GLOBALS['DB']->query("DELETE FROM registerusers WHERE user_id = ?", array($id));
			if($addResult){
				$location =  GetConfig('SITE_UPLOAD_PATH').'/signature/'.$id;
				$this->deleteDirectory($location);
				$_SESSION['Success'] = '<div class="alert alert-success">Register User deleted successfully</div>';
				GetAdminRedirectUrl(GetAdminUrl(array('module'=>$_REQUEST['module'])));	
			} else {
				$_SESSION['Error'] = '<div class="alert alert-danger">An error occurred while you trying to delete Register User, please try again.</div>';
				GetAdminRedirectUrl(GetAdminUrl(array('module'=>$_REQUEST['module']),0));	
			}
		} else {
			$_SESSION['Error'] = '<div class="alert alert-danger">Record not valid.</div>';
			GetAdminRedirectUrl(GetAdminUrl(array('module'=>$_REQUEST['module']),0));	
		}				
	}

	private function viewRegisterusers(){
		if(isset($_REQUEST['id'])){
			$id = $_REQUEST['id'];
		} else {
			$id =  '';
		}	
		
		if(is_numeric($id)){ 


			$row = $GLOBALS['DB']->row("SELECT * FROM `registerusers` WHERE `user_id` = ?  ORDER BY `user_id` ASC LIMIT 0,1",array($id));	

		   	$GLOBALS['MFirstname'] = $row['user_firstname']; 
			$GLOBALS['MLastname'] = $row['user_lastname']; 
			$GLOBALS['MEmail'] = $row['user_email'];
			$GLOBALS['MPassword'] = $row['user_password'];
			$GLOBALS['MUsername'] = $row['user_username'];
			$GLOBALS['MPhone'] = $row['user_phone'];
			$GLOBALS['UserId'] = $row['user_id'];
			$GLOBALS['MRdate'] = GetDateFormat($row['user_created']); 
			if($row['usr_lastlogin'] != 0){ 
					$GLOBALS['GetLastlogin'] = date('d/m/Y h:i:s A',$row['user_lastlogin']);
			}else{
				$GLOBALS['GetLastlogin'] =0;
			}
			$GLOBALS['ADMINREDIRECTURL'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'view','id'=>$id));

			if($row['user_lastlogin'] != 0){ 
				if($row['user_loginby'] == 1){
					$GLOBALS['GetLastlogin'] = date('d/m/Y h:i:s A',$row['user_lastlogin']).' By App';
				}else{
					$GLOBALS['GetLastlogin'] = date('d/m/Y h:i:s A',$row['user_lastlogin']).' By Web';
				}
			}else{
				$reguser[$count]['GetLastlogin'] ='';
			}
			
			if($row['user_status']==1){
				 $GLOBALS['MStatus'] = 'Activate Member';
			}else if($row['user_status']==0){
				$GLOBALS['MStatus'] = 'Deactivate Member';
			}

			
			AddMessageInfo();
			
			$GLOBALS['li_ajaxurl'] = GetAdminUrl(array('module' => $_REQUEST['module'],'action' => 'upatememberno','id'=>$row['user_id']));		
			$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/'.$GLOBALS['Module'].'/registerusers.view.html');				
			$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');			
			$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');						
			$GLOBALS['LEFT'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.left.html');			
			$GLOBALS['CLA_HTML']->display();
			RemoveMessageInfo();			
		} else {
			$_SESSION['Error'] = '<div class="alert alert-danger">Record not valid.</div>';
			GetAdminRedirectUrl(GetAdminUrl(array('module'=>$_REQUEST['module']),0));	
		}		
	}

	
	private function datatableRegisterusers(){
		$draw = $_POST['draw'];
		$row = $_POST['start'];
		$rowperpage = $_POST['length'];
		$columnIndex = $_POST['order'][0]['column'];
		$columnName = $_POST['columns'][$columnIndex]['data'];
		$columnSortOrder = $_POST['order'][0]['dir'];
		$searchValue = $_POST['search']['value'];
		$filter_val = $_POST['filter_val'];
		$searchQuery = " ";
		
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
			}else if($_POST['filter_val'] == 5){
				$searchQuery .= "AND SU.`subscription_id` ='_admin'"; 
			}else if($_POST['filter_val'] == 6){
				$searchQuery .= "AND SU.`subscription_id` !='_admin'"; 
			}

			if($_POST['filter_user_type_val'] != ''){
				$searchQuery = " AND RU.user_type='".$_POST['filter_user_type_val']."' ";
			}
			
			$records = $GLOBALS['DB']->row("select count(*) as allcount FROM `registerusers` RU LEFT JOIN  registerusers_subscription SU ON RU.user_id = SU.user_id WHERE RU.user_id!='0' " .$searchQuery);
			$totalRecordwithFilter = $records['allcount'];
			$registeruserRecords = $GLOBALS['DB']->query("select RU.*,SU.*,PL.*,SU.plan_signaturelimit as nofsig FROM `registerusers` RU LEFT JOIN  registerusers_subscription SU ON RU.user_id = SU.user_id LEFT JOIN plan PL ON PL.plan_id = SU.plan_id  WHERE RU.user_id!='0' ". $searchQuery." order by RU.".$columnName." ".$columnSortOrder." limit ".$row.",".$rowperpage);

		$data = array();
		$count=1;  $adminkey = GetConfig('ADMIN_LOGINKEY');

		foreach ($registeruserRecords as $row) {
			$reguser[$count]['GetRegisterdate'] = GetDateFormat($row['user_created']);
			if($row['user_lastlogin'] != 0){ 
				$reguser[$count]['GetLastlogin'] = date('d/M/Y h:i:s a',$row['user_lastlogin']);
			} else {
				$reguser[$count]['GetLastlogin'] ='';
			}
			
			if($row['user_status']){
				$GLOBALS['StatusImage'] = $GLOBALS['ADMIN_LINK'].'/images/status_on.png';	
				$GLOBALS['StatusLink'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'status','id'=>$row['user_id'],'status'=>'0'));
				$GLOBALS['Statusclass'] = 'c-gr f-22 feather icon-toggle-right';	
			} else {
				$GLOBALS['StatusImage'] =  $GLOBALS['ADMIN_LINK'].'/images/status_off.png';	
				$GLOBALS['StatusLink'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'status','id'=>$row['user_id'],'status'=>'1'));
				$GLOBALS['Statusclass'] = 'c-rd f-22 feather icon-toggle-left';
			}
			
			// if($row['logo_process'] == 2){
			// 	$status_link = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'view','id'=>$row['id']));
			// 	$logoprocess = '<a href="'.$status_link.'" title="Click Here to Processing" onclick="'.$GLOBALS['on_process'].'"><label class="badge badge-light-success" style="cursor:pointer;">Complete</label></a>';
			// }else if($row['logo_process'] == 1){
			// 	$status_link = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'view','id'=>$row['id']));
			// 	$logoprocess = '<a href="'.$status_link.'" title="Click here to Complete" onclick="'.$GLOBALS['on_complete'].'"><label class="badge badge-light-primary" style="cursor:pointer;">Revision </label></a>';
			// }else if($row['logo_process'] == 0){
			// 	//$status_link = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'processstatus','id'=>$row['signature_id'],'status'=>'1'));
			// 	$status_link = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'view','id'=>$row['id']));
			// 	$logoprocess = '<a href="'.$status_link.'" title="Click here to Complete" onclick="'.$GLOBALS['on_complete'].'"><label class="badge badge-light-primary" style="cursor:pointer;">Processing</label></a>';
			// }else{
			// 	$logoprocess = 'Not Uploaded';
			// }
			
			if($row['free_trial'] == 1){
				$userlable ='<label class="badge badge-light-primary" style="cursor:pointer;">Free</label>';
			}else{
				$userlable ='';
			}
			
			
			$GLOBALS['li_view'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'view','id'=>$row['user_id']));
			$GLOBALS['li_delete'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'delete','id'=>$row['user_id']));	
			$GLOBALS['li_upgrade'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'upgrade','id'=>$row['user_id']));	
			$GLOBALS['on_delete'] = 'return jsConfirm("","delete")';
			$frontlogin = GetUrl(array('module'=>'signin','category_id'=>'admin','id'=>$adminkey,'subid'=>md5($row['user_id'])));
			$stripeLink = GetAdminUrl(array('module'=>'stripe','action'=>'viewUser','id'=>$row['user_id']));
			$user_created = $row['subscription_id'] == '_admin' ? 'By admin' : 'By self';
			$GLOBALS['li_view_department_logo'] = GetAdminUrl(array('module'=>'signature','action'=>'viewdepartmentlogo','id'=>$row['user_id']));
			$GLOBALS['li_view_subusers'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'subusers','id'=>$row['user_id']));
				
			$registerSubUserRecords = $GLOBALS['DB']->row("select count(*) as subUserCount FROM registerusers_sub_users WHERE parent_user_id = ?",array($row['user_id']));
			$integrationRecord = $GLOBALS['DB']->query("select * FROM registerusers_token WHERE user_id = ?",array($row['user_id']));
			if($row['user_type'] == 'enterprise'){
				if($registerSubUserRecords['subUserCount'] > 0){
					$dataArray = array(
						"user_status"=>"<a href='".$GLOBALS['StatusLink']."' class='".$GLOBALS['Statusclass']."'></a>",
						"user_id"=>$row['user_id'],
						"user_firstname"=>'<a href="'.$GLOBALS['li_view'].'" class="view-link">'.$row['user_firstname']." ".$row['user_lastname'].$userlable." <label class='badge badge-light-success' style='cursor:pointer;'> ".$row['user_type']."</label> <br>".$row['user_email']."<br>".$row['user_phone'].'</a>',
						"user_plandetail"=>$row['plan_name']." ".ucfirst($row['plan_type'])."ly<br> ".$row['nofsig']." Signature",
						"user_rdate"=>'<a href="'.$GLOBALS['li_view'].'" class="view-link">'.$reguser[$count]['GetRegisterdate'].' <br> '.$user_created.'</a>',
						"action"=>"<a href='".$GLOBALS['li_view']."' class='f-20 feather icon-eye' title='View'>&nbsp;</a> <a href='".$GLOBALS['li_delete']."' class='f-20 feather icon-trace' title='View'>&nbsp;</a><a href='".$GLOBALS['li_delete']."' class='f-20 feather icon-trash' title='Delete' onclick='".$GLOBALS['on_delete']."'>&nbsp;</a> <a href='".$GLOBALS['li_upgrade']."' class='f-20 feather icon-edit' title='Upgrade'>&nbsp;</a><a href='".$frontlogin."' target='_blank' class='f-20 feather icon-user' title='Login'>&nbsp;</a><a href='".$stripeLink."' class='f-20 feather icon-airplay' title='stripe'>&nbsp;</a><a href='".$GLOBALS['li_view_department_logo']."' class='f-20 feather icon-edit-2' title='View Department Logo'>&nbsp;</a><a href='".$GLOBALS['li_view_subusers']."' class='f-20 feather icon-users' title='View Sub Users'>&nbsp;</a>"
					);
					$dataArray['integration_status'] = "";
					if($integrationRecord){
						foreach ($integrationRecord as $key => $record) {
							if($record['token_platform'] == "0"){
								$dataArray['integration_status'] .= '<img src="'.$GLOBALS['IMAGE_LINK'].'/images/ms-office.svg" alt="" style="height:37px;width:37px;margin-right:10px;">';
							}else if($record['token_platform'] == "1"){
								$dataArray['integration_status'] .= '<img src="'.$GLOBALS['IMAGE_LINK'].'/images/g-suite.svg" alt="" style="height:37px;width:37px;margin-right:10px;">';
							}
						}
					}else{
						$dataArray['integration_status'] = '<label class="badge badge-light-error" style="cursor:pointer;">Not Connected</label>';
					}
					$data[] = $dataArray;
				}else{
					$dataArray = array(
						"user_status"=>"<a href='".$GLOBALS['StatusLink']."' class='".$GLOBALS['Statusclass']."'></a>",
						"user_id"=>$row['user_id'],
						"user_firstname"=>'<a href="'.$GLOBALS['li_view'].'" class="view-link">'.$row['user_firstname']." ".$row['user_lastname'].$userlable." <label class='badge badge-light-success' style='cursor:pointer;'> ".$row['user_type']."</label> <br>".$row['user_email']."<br>".$row['user_phone'].'</a>',
						"user_plandetail"=>$row['plan_name']." ".ucfirst($row['plan_type'])."ly<br> ".$row['nofsig']." Signature",
						"user_rdate"=>'<a href="'.$GLOBALS['li_view'].'" class="view-link">'.$reguser[$count]['GetRegisterdate'].' <br> '.$user_created.'</a>',
						"action"=>"<a href='".$GLOBALS['li_view']."' class='f-20 feather icon-eye' title='View'>&nbsp;</a> <a href='".$GLOBALS['li_delete']."' class='f-20 feather icon-trace' title='View'>&nbsp;</a><a href='".$GLOBALS['li_delete']."' class='f-20 feather icon-trash' title='Delete' onclick='".$GLOBALS['on_delete']."'>&nbsp;</a> <a href='".$GLOBALS['li_upgrade']."' class='f-20 feather icon-edit' title='Upgrade'>&nbsp;</a><a href='".$frontlogin."' target='_blank' class='f-20 feather icon-user' title='Login'>&nbsp;</a><a href='".$stripeLink."' class='f-20 feather icon-airplay' title='stripe'>&nbsp;</a><a href='".$GLOBALS['li_view_department_logo']."' class='f-20 feather icon-edit-2' title='View Department Logo'>&nbsp;</a>"
					);
					$dataArray['integration_status'] = "";
					if($integrationRecord){
						foreach ($integrationRecord as $key => $record) {
							if($record['token_platform'] == "0"){
								$dataArray['integration_status'] .= '<img src="'.$GLOBALS['IMAGE_LINK'].'/images/ms-office.svg" alt="" style="height:37px;width:37px;margin-right:10px;">';
							}else if($record['token_platform'] == "1"){
								$dataArray['integration_status'] .= '<img src="'.$GLOBALS['IMAGE_LINK'].'/images/g-suite.svg" alt="" style="height:37px;width:37px;margin-right:10px;">';
							}
						}
					}else{
						$dataArray['integration_status'] = '<label class="badge badge-light-error" style="cursor:pointer;">Not Connected</label>';
					}
					$data[] = $dataArray;
				}
			}else{
				if($registerSubUserRecords['subUserCount'] > 0){
					$dataArray = array(
						"user_status"=>"<a href='".$GLOBALS['StatusLink']."' class='".$GLOBALS['Statusclass']."'></a>",
						"user_id"=>$row['user_id'],
						"user_firstname"=>'<a href="'.$GLOBALS['li_view'].'" class="view-link">'.$row['user_firstname']." ".$row['user_lastname'].$userlable."<br>".$row['user_email']."<br>".$row['user_phone'].'</a>',
						"user_plandetail"=>$row['plan_name']." ".ucfirst($row['plan_type'])."ly<br> ".$row['nofsig']." Signature",
						"user_rdate"=>'<a href="'.$GLOBALS['li_view'].'" class="view-link">'.$reguser[$count]['GetRegisterdate'].' <br> '.$user_created.'</a>',
						"action"=>"<a href='".$GLOBALS['li_view']."' class='f-20 feather icon-eye' title='View'>&nbsp;</a> <a href='".$GLOBALS['li_delete']."' class='f-20 feather icon-trace' title='View'>&nbsp;</a><a href='".$GLOBALS['li_delete']."' class='f-20 feather icon-trash' title='Delete' onclick='".$GLOBALS['on_delete']."'>&nbsp;</a> <a href='".$GLOBALS['li_upgrade']."' class='f-20 feather icon-edit' title='Upgrade'>&nbsp;</a><a href='".$frontlogin."' target='_blank' class='f-20 feather icon-user' title='Login'>&nbsp;</a><a href='".$stripeLink."' class='f-20 feather icon-airplay' title='stripe'>&nbsp;</a><a href='".$GLOBALS['li_view_subusers']."' class='f-20 feather icon-users' title='View Sub Users'>&nbsp;</a>"
					);
					$dataArray['integration_status'] = "";
					if($integrationRecord){
						foreach ($integrationRecord as $key => $record) {
							if($record['token_platform'] == "0"){
								$dataArray['integration_status'] .= '<img src="'.$GLOBALS['IMAGE_LINK'].'/images/ms-office.svg" alt="" style="height:37px;width:37px;margin-right:10px;">';
							}else if($record['token_platform'] == "1"){
								$dataArray['integration_status'] .= '<img src="'.$GLOBALS['IMAGE_LINK'].'/images/g-suite.svg" alt="" style="height:37px;width:37px;margin-right:10px;">';
							}
						}
					}else{
						$dataArray['integration_status'] = '<label class="badge badge-light-error" style="cursor:pointer;">Not Connected</label>';
					}
					$data[] = $dataArray;
				}else{
					$dataArray = array(
						"user_status"=>"<a href='".$GLOBALS['StatusLink']."' class='".$GLOBALS['Statusclass']."'></a>",
						"user_id"=>$row['user_id'],
						"user_firstname"=>'<a href="'.$GLOBALS['li_view'].'" class="view-link">'.$row['user_firstname']." ".$row['user_lastname'].$userlable."<br>".$row['user_email']."<br>".$row['user_phone'].'</a>',
						"user_plandetail"=>$row['plan_name']." ".ucfirst($row['plan_type'])."ly<br> ".$row['nofsig']." Signature",
						"user_rdate"=>'<a href="'.$GLOBALS['li_view'].'" class="view-link">'.$reguser[$count]['GetRegisterdate'].' <br> '.$user_created.'</a>',
						"action"=>"<a href='".$GLOBALS['li_view']."' class='f-20 feather icon-eye' title='View'>&nbsp;</a> <a href='".$GLOBALS['li_delete']."' class='f-20 feather icon-trace' title='View'>&nbsp;</a><a href='".$GLOBALS['li_delete']."' class='f-20 feather icon-trash' title='Delete' onclick='".$GLOBALS['on_delete']."'>&nbsp;</a> <a href='".$GLOBALS['li_upgrade']."' class='f-20 feather icon-edit' title='Upgrade'>&nbsp;</a><a href='".$frontlogin."' target='_blank' class='f-20 feather icon-user' title='Login'>&nbsp;</a><a href='".$stripeLink."' class='f-20 feather icon-airplay' title='stripe'>&nbsp;</a>"
					);
					$dataArray['integration_status'] = "";
					if($integrationRecord){
						foreach ($integrationRecord as $key => $record) {
							if($record['token_platform'] == "0"){
								$dataArray['integration_status'] .= '<img src="'.$GLOBALS['IMAGE_LINK'].'/images/ms-office.svg" alt="" style="height:37px;width:37px;margin-right:10px;">';
							}else if($record['token_platform'] == "1"){
								$dataArray['integration_status'] .= '<img src="'.$GLOBALS['IMAGE_LINK'].'/images/g-suite.svg" alt="" style="height:37px;width:37px;margin-right:10px;">';
							}
						}
					}else{
						$dataArray['integration_status'] = '<label class="badge badge-light-error" style="cursor:pointer;">Not Connected</label>';
					}
					$data[] = $dataArray;
				}
			}
			$count++;
		}
		$response = array(
			"draw" => intval($draw),
			"iTotalDisplayRecords" => $totalRecordwithFilter,
			"aaData" => $data
		);
		echo json_encode($response);
	}

	private function Registerusers(){
		
		AddMessageInfo();
		$GLOBALS['li_getuserdata'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'datatableregdata'));
		$GLOBALS['li_export'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'export'));
		$GLOBALS['Totaluser'] = $count-1;	
		// $GLOBALS['DIS_PAGE']->Page('registerusers','site_id='.$GLOBALS['SITE_ID']);
		$GLOBALS['PageNextImage'] = $GLOBALS['DIS_PAGE']->nextImage();
		$GLOBALS['PagePrevImage'] = $GLOBALS['DIS_PAGE']->prevImage();
		$GLOBALS['PagePageLink'] = $GLOBALS['DIS_PAGE']->pageLink();
		$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/'.$GLOBALS['Module'].'/registerusers.html');	
		$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');			
		$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');						
		$GLOBALS['LEFT'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.left.html');			
		$GLOBALS['CLA_HTML']->SetLoop('FEEDBACK',$reguser);
		$GLOBALS['CLA_HTML']->display();
		RemoveMessageInfo();		
	}

	private function subRegisterusers(){
		
		AddMessageInfo();
		$GLOBALS['li_getuserdata'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'datatableregsubuserdata','id'=>$_REQUEST['id']));
		$GLOBALS['li_export'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'export'));
		$GLOBALS['Totaluser'] = $count-1;	
		// $GLOBALS['DIS_PAGE']->Page('registerusers','site_id='.$GLOBALS['SITE_ID']);
		$GLOBALS['PageNextImage'] = $GLOBALS['DIS_PAGE']->nextImage();
		$GLOBALS['PagePrevImage'] = $GLOBALS['DIS_PAGE']->prevImage();
		$GLOBALS['PagePageLink'] = $GLOBALS['DIS_PAGE']->pageLink();
		$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/'.$GLOBALS['Module'].'/registerusers.html');	
		$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');			
		$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');						
		$GLOBALS['LEFT'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.left.html');			
		$GLOBALS['CLA_HTML']->SetLoop('FEEDBACK',$reguser);
		$GLOBALS['CLA_HTML']->display();
		RemoveMessageInfo();		
	}

	private function datatableRegisterSubUsers(){
		$draw = $_POST['draw'];
		$row = $_POST['start'];
		$rowperpage = $_POST['length'];
		$columnIndex = $_POST['order'][0]['column'];
		$columnName = $_POST['columns'][$columnIndex]['data'];
		$columnSortOrder = $_POST['order'][0]['dir'];
		$searchValue = $_POST['search']['value'];
		$filter_val = $_POST['filter_val'];
		$searchQuery = " ";
		
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
		}else if($_POST['filter_val'] == 5){
			$searchQuery .= "AND SU.`subscription_id` ='_admin'"; 
		}else if($_POST['filter_val'] == 6){
			$searchQuery .= "AND SU.`subscription_id` !='_admin'"; 
		}

		if($_POST['filter_user_type_val'] != ''){
			$searchQuery = " AND RU.user_type='".$_POST['filter_user_type_val']."' ";
		}
		if(isset($_REQUEST['id'])){
			$searchQuery .= "AND SU.`user_id` = ".$_REQUEST['id']; 
		}
		$columnName = "parent_user_id";
		$records = $GLOBALS['DB']->row("select count(*) as allcount FROM `registerusers_sub_users` RSU LEFT JOIN  registerusers_subscription SU ON RSU.user_id = SU.user_id WHERE RSU.user_id!='0' " .$searchQuery);
		$totalRecordwithFilter = $records['allcount'];
		if(isset($_REQUEST['id'])){
			$registeruserRecords = $GLOBALS['DB']->query("select RSU.*,RSU.name as user_firstname,RSU.email as user_email,SU.*,PL.*,SU.plan_signaturelimit as nofsig FROM `registerusers_sub_users` RSU LEFT JOIN  registerusers_subscription SU ON RSU.parent_user_id = SU.user_id LEFT JOIN plan PL ON PL.plan_id = SU.plan_id  WHERE RSU.parent_user_id!='0' ". $searchQuery." order by RSU.".$columnName." ".$columnSortOrder." limit ".$row.",".$rowperpage);
		}

		$data = array();
		$count=1;  $adminkey = GetConfig('ADMIN_LOGINKEY');

		foreach ($registeruserRecords as $row) {
			$reguser[$count]['GetRegisterdate'] = GetDateFormat($row['user_created']);
			if($row['user_lastlogin'] != 0){ 
				$reguser[$count]['GetLastlogin'] = date('d/M/Y h:i:s a',$row['user_lastlogin']);
			} else {
				$reguser[$count]['GetLastlogin'] ='';
			}
			
			if($row['user_status']){
				$GLOBALS['StatusImage'] = $GLOBALS['ADMIN_LINK'].'/images/status_on.png';	
				$GLOBALS['StatusLink'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'status','id'=>$row['user_id'],'status'=>'0'));
				$GLOBALS['Statusclass'] = 'c-gr f-22 feather icon-toggle-right';	
			} else {
				$GLOBALS['StatusImage'] =  $GLOBALS['ADMIN_LINK'].'/images/status_off.png';	
				$GLOBALS['StatusLink'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'status','id'=>$row['user_id'],'status'=>'1'));
				$GLOBALS['Statusclass'] = 'c-rd f-22 feather icon-toggle-left';
			}
			
			
			if($row['free_trial'] == 1){
				$userlable ='<label class="badge badge-light-primary" style="cursor:pointer;">Free</label>';
			}else{
				$userlable ='';
			}
			
			
			$GLOBALS['li_view_subuser'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'viewsubuser','id'=>$row['id']));
			$GLOBALS['li_delete'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'deletesubuser','id'=>$row['id']));	
			$GLOBALS['on_delete'] = 'return jsConfirm("","delete")';
			$frontlogin = GetUrl(array('module'=>'signin','category_id'=>'adminsub','id'=>$adminkey,'subid'=>md5($row['id'])));
			$user_created = $row['subscription_id'] == '_admin' ? 'By admin' : 'By self';

			$userStatus = "";
			if($row['is_active']){
				$userStatus = "<label class='badge badge-light-success' style='cursor:pointer;'>Enabled</label>";
			}else{
				$userStatus = "<label class='badge badge-light-danger' style='cursor:pointer;'>Disabled</label>";

			}
			$data[] = array(
				// "user_status"=>"<a href='".$GLOBALS['StatusLink']."' class='".$GLOBALS['Statusclass']."'></a>",
				"user_status"=> $userStatus,
				"user_id"=>$row['id'],
				"user_firstname"=>$row['user_firstname']." ".$row['user_lastname'].$userlable."<br>".$row['user_email']."<br>".$row['user_phone'],
				"user_plandetail"=>$row['plan_name']." ".ucfirst($row['plan_type'])."ly<br> ".$row['nofsig']." Signature",
				"user_logoprocess"=>$logoprocess,
				"user_rdate"=>$reguser[$count]['GetRegisterdate'].' <br> '.$user_created,
				"action"=>"<a href='".$GLOBALS['li_delete']."' class='f-20 feather icon-trace' title='View'>&nbsp;</a><a href='".$GLOBALS['li_delete']."' class='f-20 feather icon-trash' title='Delete' onclick='".$GLOBALS['on_delete']."'>&nbsp;</a> <a href='".$frontlogin."' target='_blank' class='f-20 feather icon-user' title='Login'>&nbsp;</a>"
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

	private function deleteRegisterSubusers(){		
		if(isset($_REQUEST['id'])){
			$id = $_REQUEST['id'];
		} else {
			$id =  '';
		}
		if(is_numeric($id)){		
			$subUserDataBeforeDelete =  $GLOBALS['DB']->row("select * FROM `registerusers_sub_users` RSU WHERE RSU.parent_user_id!='0' AND RSU.id=?",array($id));
			$addResult = $GLOBALS['DB']->query("DELETE FROM registerusers_sub_users WHERE id = ?", array($id));
			if($addResult){
				$_SESSION['Success'] = '<div class="alert alert-success">Register Sub User deleted successfully</div>';
				GetAdminRedirectUrl(GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'subusers','id'=>$subUserDataBeforeDelete['parent_user_id'])));	
			} else {
				$_SESSION['Error'] = '<div class="alert alert-danger">An error occurred while you trying to delete Register User, please try again.</div>';
				GetAdminRedirectUrl(GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'subusers','id'=>$subUserDataBeforeDelete['parent_user_id'])));	
			}
		} else {
			$_SESSION['Error'] = '<div class="alert alert-danger">Record not valid.</div>';
			GetAdminRedirectUrl(GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'subusers','id'=>$subUserDataBeforeDelete['parent_user_id'])));	
		}				
	}
	
}