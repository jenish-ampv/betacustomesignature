<?php 
class CIT_DASHBOARD{
	public function __construct(){				
		
	}
	public function displayPage(){	
	
		$users = $this->getuser(10);	         
		
		$this->getDashboardInfo();
		$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/'.$GLOBALS['Module'].'/dashboard.html');
		$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');	
		$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');
		$GLOBALS['LEFT'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.left.html');	
		$GLOBALS['CLA_HTML']->SetLoop('USER',$users);
		$GLOBALS['CLA_HTML']->display();
	}
	
	public function getDashboardInfo(){
		
		// get total user

		$totaluser = $GLOBALS['DB']->row("SELECT count(*) as totaluser FROM registerusers WHERE user_status=1");
		$GLOBALS['TotalUser'] = $totaluser['totaluser']; 
		
		$totaluser = $GLOBALS['DB']->row("SELECT count(*) as signaturecomplete FROM signature_logo WHERE logo_process=2");
		$GLOBALS['CompleteSignature'] = $totaluser['signaturecomplete']; 
		
		$totaluser = $GLOBALS['DB']->row("SELECT count(*) as signatureprocess FROM signature_logo WHERE (logo_process=0 OR logo_process=1)");
		$GLOBALS['ProcessSignature'] = $totaluser['signatureprocess']; 
	}
	
	public function getuser($limit){
		$users = $GLOBALS['DB']->query("SELECT * FROM registerusers ORDER BY user_id DESC LIMIT 0,$limit");
		$user_count =0; $user_index =1;
		foreach($users as $userRow){
			if($userRow['user_status'] == 1){
				$status = '<label class="badge badge-light-success" style="cursor:pointer;">Active</label>';
			}else{
				$status = '<label class="badge badge-light-danger" style="cursor:pointer;">DeActive</label>';
			}
			$user[$user_count]['user_id'] = $userRow['user_id']; 
			$user[$user_count]['user_fullname'] = $userRow['user_firstname'].' '.$userRow['user_lastname']; 
			$user[$user_count]['user_email'] = $userRow['user_email']; 
			$user[$user_count]['user_created'] = GetDateFormat($userRow['user_created']); 
			$user[$user_count]['user_status'] = $status ;
			$user[$user_count]['user_view'] = GetAdminUrl(array('module'=>'registerusers','action'=>'view','id'=>$userRow['user_id']));
			
			$user_count++; $user_index++;
		}
		$GLOBALS['user_count'] = $user_count;
		return $user;
		
	}
	
}

