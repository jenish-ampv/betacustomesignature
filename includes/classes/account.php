<?php
class CIT_account
{
	
	public function __construct()
	{	
		if(!isset($_SESSION[GetSession('user_id')])){
			GetFrontRedirectUrl(GetUrl(array('module'=>'signin')));
		}
	}
	
	public function displayPage(){
		AddMessageInfo();	
		if(isset($_REQUEST['category_id'])){
			$action = trim($_REQUEST['category_id']);
		} else {
			$action = '';
		}
		
		if($_REQUEST['category_id'] == 'uploadimg'){
			
			$filename = $_FILES['file']['name'];
			$filesize = $_FILES['file']['size'];
			$displayname = $filename;
			$valid_extensions = array('png', 'svg','jpeg','jpg','gif');
			$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
			
			if(in_array($ext, $valid_extensions)) {
				//$location = "upload-beta/".$filename;
				$filename = $GLOBALS['USERID'].'.'.$ext;
				$location =  GetConfig('SITE_UPLOAD_PATH').'/profile/'.$filename ;
				$return_arr = array();
				if(move_uploaded_file($_FILES['file']['tmp_name'],$location)){
					$result = $GLOBALS['S3Client']->putObject(array( // upload image s3bucket
						'Bucket'=>$GLOBALS['BUCKETNAME'],
						'Key' =>  'upload-beta/profile/'.$filename,
						'SourceFile' => $location,
						'StorageClass' => 'REDUCED_REDUNDANCY',
						'ACL'   => 'public-read'
					));
					if(is_array(getimagesize($location))){
						$src = $GLOBALS['UPLOAD_LINK'].'/profile/'.$filename;
						
						$data =array('user_image'=>$filename); $where =array('user_id'=>$GLOBALS['USERID']); 
						$update = $GLOBALS['DB']->update("registerusers",$data,$where);
					}
					$return_arr = array("name" => $filename,"displayname" => $displayname, "size" => $filesize, "src"=> $src, "error"=>0);
				}
			}else{
				$return_arr = array("error" =>1, "msg"=>"please upload valid jpg, jpeg, png gif or svg image");
			}
			echo json_encode($return_arr); exit;
		}
		
		if($_POST['update_detail'] == 'updatedetail' && is_numeric($GLOBALS['USERID'])){
			if($this->emailExist($_POST['user_email']) == false){
				if($_POST['user_firstname'] !=""){
					if(is_numeric($GLOBALS['SUBUSERID']) && $GLOBALS['SUBUSERID'] != '0' ){
						$dataSub =array('user_firstname'=>$_POST['user_firstname'],'user_lastname'=>$_POST['user_lastname']);
						$whereSub =array('id'=>$GLOBALS['SUBUSERID']);
						$update = $GLOBALS['DB']->update("registerusers_sub_users",$dataSub,$whereSub);
					}else{
						// $data =array('user_firstname'=>$_POST['user_firstname'],'user_lastname'=>$_POST['user_lastname'],'user_organization'=>$_POST['user_organization'],'user_phone'=>$_POST['user_phone'],'user_job_title'=>$_POST['user_job_title']);
						$data =array('user_firstname'=>$_POST['user_firstname'],'user_lastname'=>$_POST['user_lastname']);
						$where =array('user_id'=>$GLOBALS['USERID']);
						$update = $GLOBALS['DB']->update("registerusers",$data,$where);
					}
					$_SESSION[GetSession('user_name')] = $_POST['user_firstname'] ." ".$_POST['user_lastname'];
					$_SESSION[GetSession('Success')] = '<div class="alert alert-success"><strong>Success!</strong> Detail update!</div>';	
				}else{
					$_SESSION[GetSession('Error')] = '<div class="alert alert-danger"><strong>Fail!</strong> please enter required field</div>';	
				}
			}
			else{
				$_SESSION[GetSession('Error')] = '<div class="alert alert-danger" id="wrong"><strong> Fail! </strong>email address already registered!</div>';
			}
			GetFrontRedirectUrl(GetUrl(array('module'=>'account')));
		}
		
		if($_POST['update_detail'] == 'updatepassword' && is_numeric($GLOBALS['USERID'])){
			$this->getUserDeatil();
			$pass = trim($_POST['user_password']); $cpass = trim($_POST['user_cpassword']); $current_pass = trim($_POST['user_current_password']);
			if($pass !="" && $cpass !="" && $current_pass !=""){
				if($pass == $cpass){
					if(is_numeric($GLOBALS['SUBUSERID']) && $GLOBALS['SUBUSERID'] != '0' ){
						$subUserRow = $GLOBALS['DB']->row("SELECT * FROM registerusers_sub_users WHERE id= ?",array($GLOBALS['SUBUSERID']));
						if(md5($current_pass) == $subUserRow['password']){
							$data =array('password'=>md5($_POST['user_password']));
							$where =array('id'=>$GLOBALS['SUBUSERID']);
							$update = $GLOBALS['DB']->update("registerusers_sub_users",$data,$where);
							$_SESSION[GetSession('Success')] = '<div class="alert alert-success"><strong>Success!</strong> password update.</div>';	
						}else{
							$_SESSION[GetSession('Error')] = '<div class="alert alert-danger"><strong>Fail!</strong> current password does not match.</div>';	
						}
					}else{
						if(md5($current_pass) == $GLOBALS['user_password']){
							$data =array('user_password'=>md5($_POST['user_password']));
							$where =array('user_id'=>$GLOBALS['USERID']);
							$update = $GLOBALS['DB']->update("registerusers",$data,$where);
							$_SESSION[GetSession('Success')] = '<div class="alert alert-success"><strong>Success!</strong> password update.</div>';	
						}else{
							$_SESSION[GetSession('Error')] = '<div class="alert alert-danger"><strong>Fail!</strong> current password does not match.</div>';	
						}
					}
				}else{
					$_SESSION[GetSession('Error')] = '<div class="alert alert-danger"><strong>Fail!</strong> password and confirm password not match.</div>';	
				}
			}else{
				$_SESSION[GetSession('Error')] = '<div class="alert alert-danger"><strong>Fail!</strong> please enter required field.</div>';	
			}
			GetFrontRedirectUrl(GetUrl(array('module'=>'account','id'=>'changepassword')));
		}
	
		
		$this->getPage();
		$this->getUserDeatil();
		$GLOBALS['editaccount'] = GetUrl(array('module'=>'account','id'=>'edit'));
		$GLOBALS['changepassword'] = GetUrl(array('module'=>'account','id'=>'changepassword'));
		if($_REQUEST['category_id'] == 'edit'){
			$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/editaccount.html');
		} else if($_REQUEST['category_id'] == 'changepassword'){
			$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/changepassword.html');
		}else{
			$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/account.html');	
		}
		$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');			
		$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');
		$GLOBALS['SIDEBAR'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.sidebar.html');
		$GLOBALS['CLA_HTML']->display();
		RemoveMessageInfo();
		exit();	
		
	}
	
	public function getUserDeatil(){
		
		$userRow = $GLOBALS['DB']->row("SELECT * FROM registerusers WHERE user_id= ?",array($GLOBALS['USERID']));
		foreach($userRow as $key => $value){
			$GLOBALS[$key] = $value;
		}
		if (!filter_var($GLOBALS['user_image'], FILTER_VALIDATE_URL) === false) { // chek if is url or not
			$GLOBALS['user_profilepic'] = $GLOBALS['user_image'];
		}else{
			$GLOBALS['user_profilepic'] = $GLOBALS['UPLOAD_LINK'].'/profile/'.$GLOBALS['user_image'];
		}
		if($GLOBALS['ISSUBUSER']){
			$subUserRow = $GLOBALS['DB']->row("SELECT * FROM registerusers_sub_users WHERE id= ?",array($GLOBALS['SUBUSERID']));

			$GLOBALS['user_firstname'] = $subUserRow['user_firstname'];
			$GLOBALS['user_lastname'] = $subUserRow['user_lastname'];
			$GLOBALS['user_email'] = $subUserRow['email'];
			$GLOBALS['user_phone'] = "";
			$GLOBALS['user_organization'] = "";
			$GLOBALS['user_job_title'] = "";
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
	
	
}

?>