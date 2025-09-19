<?php
// ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
class CIT_USERMANAGEMENT
{
   

	public function __construct()
	{
        if(!isset($_SESSION[GetSession('user_id')]) && !isset($_REQUEST['uuid']) && isset($_REQUEST['category_id']) && ($_REQUEST['category_id'] != "createpassword") && ($_REQUEST['category_id'] != "savepassword")){
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
        if($action == "saveuser"){
            $this->saveUser();
        }
        if($action == "saveuserstatus"){
            $this->saveUserStatus();
        }
        if($action == "deleteuser"){
            $this->deleteUser();
        }
        if($action == "createpassword"){
            $this->createPassword();
        }
        if($action == "savepassword"){
            $this->savePassword();
        }
        if($action == "inviteuser"){
            $user_id = 0;
            if(isset($_REQUEST['user_id'])){
                $user_id = $_REQUEST['user_id'];
            }
            $this->sendInviteMail($user_id);
        }
        
        $manageSubUsers = $GLOBALS['DB']->query("select RU.*,RSU.* FROM `registerusers_sub_users` RSU LEFT JOIN  registerusers RU ON RU.user_id = RSU.parent_user_id WHERE RSU.parent_user_id=?;",array($GLOBALS['USERID']));
        $GLOBALS['departmentUserTableBody'] = "";
		foreach ($manageSubUsers as $row) {
            if($row['is_active'] == 1){
                $status = "<span class='kt-badge kt-badge-mono enabled-btn'>Enabled</span>";
                $checked = "checked";
            }else if($row['is_active'] == 2){
                $status = "<span class='kt-badge kt-badge-warning pending-btn'>Pending</span>";
                $checked = "checked";
            }else{
                $status = "<span class='kt-badge kt-badge-secondary disabled-btn'>Disabled</span>";
                $checked = "";
            }
            if(!$row['permission']){
                $row['permission'] = " ";
            }
            if(!$row['department_list']){
                $row['department_list'] = " ";
            }
            $GLOBALS['departmentUserTableBody'] .= "<tr>
                <td class='text-nowrap'>".$row['user_firstname']." ".$row['user_lastname']."</td>
                <td>".$row['email']."</td>
                <td>".$status."</td>
                <td>
                    <div class='flex items-center justify-end gap-3'>";
                        if($row['is_active'] == 2){
                            $GLOBALS['departmentUserTableBody'] .= "
                            <a href='javascript:void(0);' onclick='sendInviteMail(\"".$GLOBALS['usermanagement']."/inviteuser?user_id=".$row['id']."\")' class='feather icon-edit invite_link' title='Send Password Link'>
                                <i class='hgi hgi-stroke hgi-mail-01 text-xl'></i>
                            </a>";
                        }
                        $GLOBALS['departmentUserTableBody'] .= "
                        <a href='javascript:void(0);' class='feather icon-edit edit_user' data-fname='".$row['user_firstname']."' data-lname='".$row['user_lastname']."' data-email='".$row['email']."' data-user_status='".$row['is_active']."' data-permission='".$row['permission']."' data-department_list='".$row['department_list']."' title='View' data-kt-modal-toggle='#addUserModel'>
                            <i class='hgi hgi-stroke hgi-pencil-edit-02 text-xl'></i>
                        </a>
                        <a href='".$GLOBALS['usermanagement']."/deleteuser?user_id=".$row['id']."' class='feather icon-trash delete_user' title='Delete'>
                            <i class='text-danger hgi hgi-stroke hgi-delete-02 text-xl'></i>
                        </a> 
                        <div class='departmentUser-change-status'>
                            <input class='kt-switch' type='checkbox' role='switch' id='departmentUser-action' name='user_status_button' value='1' data-sub_user_id='".$row['id']."' ".$checked.">
                        </div
                    </div>
                </td>
                </tr>";
        }
        if($GLOBALS['departmentUserTableBody'] == ""){
            $GLOBALS['departmentUserTableBody'] = '<td class="text-center" colspan="4">No user found</td>';
        }
        $departments = $GLOBALS['DB']->query("select * FROM `registerusers_departments` WHERE user_id=? ",array($GLOBALS['USERID']));
        foreach ($departments as $department) {
            $GLOBALS['department_list'] .= "<div class='flex items-center gap-2'>
                       <input type='checkbox' class='kt-checkbox department-checkbox' name='department_list' id='department_".$department['department_id']."' value='".$department['department_id']."'>
                       <label class='kt-label' for='department_".$department['department_id']."'>".$department['department_name']."</label>
                    </div>
                ";
        }
            
        $GLOBALS['usermanagement_datatable'] = GetUrl(array('module'=>'usermanagement','category_id' => 'datatableregdata'));
        $GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/usermanagement.html');	
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

    public function saveUser(){
        $postdata = json_decode(file_get_contents("php://input"), true);
		if(isset($postdata['edit_user']) && $postdata['edit_user']){
            $permission = "";
            $department_list = "";
            if(isset($postdata['permission']) && $postdata['permission']){
                if(is_array($postdata['permission'])){
                    $permission = implode("," ,$postdata['permission']);
                }else{
                    $permission = $postdata['permission'];
                }
            }
            if(isset($postdata['department_list']) && $postdata['department_list']){
                if(is_array($postdata['department_list'])){
                    $department_list = implode("," ,$postdata['department_list']);
                }else{
                    $department_list = $postdata['department_list'];
                }
            }
            // if($postdata['user_status']){
            //     $user_status = 1;
            // }else{
            //     $user_status = 0;
            // }
            // $user_status = 1;
            $data = array('user_firstname'=>$postdata['user_firstname'],'user_lastname'=>$postdata['user_lastname'], 'permission'=>$permission,'department_list'=>$department_list);
            $where = array('email'=>$postdata['email']); 
            $addDepartment = $GLOBALS['DB']->update("registerusers_sub_users",$data,$where);
            
            $return_arrs = array('error'=>0,'msg'=>'User Updated success');
			
			echo json_encode($return_arrs); exit;
		}else{
            if($postdata['user_firstname'] !="" && $postdata['user_lastname'] !="" && $postdata['email'] !=""){
				$rowExist = $GLOBALS['DB']->row("SELECT * FROM `registerusers_sub_users` WHERE `email`= ? LIMIT 0,1",array(strtolower($postdata['email'])));
				if($rowExist){
					$return_arrs = array('error'=>1,'msg'=>'User already exist with this email id.');
				}else{
				    $rowMainUserExist = $GLOBALS['DB']->row("SELECT * FROM `registerusers` WHERE `user_email`= ? LIMIT 0,1",array(strtolower($postdata['email'])));
                    if($rowMainUserExist){
                        $return_arrs = array('error'=>1,'msg'=>'User already exist with this email id.');
                    }else{
                        $permission = "";
                        $department_list = "";
                        if(isset($postdata['permission']) && $postdata['permission']){
                            if(is_array($postdata['permission'])){
                                $permission = implode("," ,$postdata['permission']);
                            }else{
                                $permission = $postdata['permission'];
                            }
                        }
                        if(isset($postdata['department_list']) && $postdata['department_list']){
                            if(is_array($postdata['department_list'])){
                                $department_list = implode("," ,$postdata['department_list']);
                            }else{
                                $department_list = $postdata['department_list'];
                            }
                        }
                        // if($postdata['user_status']){
                        //     $user_status = 1;
                        // }else{
                        //     $user_status = 0;
                        // }
                        $user_status = 2;
                        $GLOBALS['encrypt_decrypt_key'] = 'mySuperSecretKeyeSign2025'.time(); 

                        $data = array('parent_user_id'=>$GLOBALS['USERID'],'user_firstname'=>$postdata['user_firstname'],'user_lastname'=>$postdata['user_lastname'],'email'=>$postdata['email'],'permission'=>$permission,'department_list'=>$department_list,'is_active'=>$user_status,'invite_key'=>$GLOBALS['encrypt_decrypt_key']);
                        $subuserid = $GLOBALS['DB']->insert("registerusers_sub_users",$data);
                        $return_arrs = array('error'=>0,'msg'=>'User add success');


                        $key = $GLOBALS['encrypt_decrypt_key'];
                        $encodedMail = $this->encryptNoSpecialChars($postdata['email'],$key);
                        $expires_at = $this->encryptNoSpecialChars(date("Y-m-d H:i:s", strtotime('+2 hours')),$key);
                        $setPasswordLink = GetUrl(array('module'=>'usermanagement','category_id' => 'createpassword','token'=>$expires_at,'user'=>$encodedMail,'user_id'=>$subuserid));
                        $GLOBALS['user_name'] = $postdata['user_firstname']." ".$postdata['user_lastname'];
                        $GLOBALS['parent_user_name'] = trim($GLOBALS['USERNAME']);
                        $GLOBALS['linkSetPassword'] = $setPasswordLink;
                        $message= _getEmailTemplate('invite_sub_user');
                        $send_mail = _SendMail($postdata['email'],'',$GLOBALS['EMAIL_SUBJECT'],$message);
                        $return_arrs = array('error'=>0);
                    }
				}
				

			}else{
				$return_arrs = array('error'=>1,'msg'=>'please fill all required field');
			}
			echo json_encode($return_arrs); exit;
        }
	}

    public function saveUserStatus() {
        $postdata = json_decode(file_get_contents("php://input"), true);
        if($postdata['sub_user_id'] != ""){
			$sub_user_id = $postdata['sub_user_id'];
			$data = array('is_active'=>$postdata['user_status']);
            $where = array('id'=>$postdata['sub_user_id']); 
            $updateStatus = $GLOBALS['DB']->update("registerusers_sub_users",$data,$where);
			if($updateStatus){
				$return_result = array('error'=>0,'msg'=>'Success');
			}else{
				$return_result = array('error'=>1,'msg'=>'Somthing wrong try again','signature'=>'');
			}
			echo json_encode($return_result); exit;
		}
    }

    public function deleteUser() {
        if($_REQUEST['user_id'] != ""){
			$user_id = $_REQUEST['user_id'];
			$delete = $GLOBALS['DB']->query("DELETE FROM registerusers_sub_users WHERE `id` = ?",array($user_id));
			if($delete){
				$return_result = array('error'=>0,'msg'=>'Success');
			}else{
				$return_result = array('error'=>1,'msg'=>'Somthing wrong try again','signature'=>'');
			}
			
			GetFrontRedirectUrl($GLOBALS['usermanagement']);
		}
    }

    public function createPassword() {
        if(isset($_REQUEST['id']) && isset($_REQUEST['subid'])){
            $tokenStr = $_REQUEST['id'];
            $uEmailStr = $_REQUEST['subid'];
            $userid = $_REQUEST['sid'];
            $userData = $GLOBALS['DB']->row("SELECT * FROM `registerusers_sub_users` WHERE `id`= ? LIMIT 0,1",array($userid));
            if($userData){
                $key = $userData['invite_key'];
                $token = $this->decryptNoSpecialChars($tokenStr, $key);
                $uEmail = $this->decryptNoSpecialChars($uEmailStr, $key);
                $givenTime = new DateTime($token);
                $currentTime = new DateTime(); // Gets the current time

                if ($givenTime > $currentTime) {
    		        AddMessageInfo();
                    $GLOBALS['suser_email'] = $uEmail;
                    $GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/createpassword.html');	
                    $GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');			
                    $GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');
                    $GLOBALS['CLA_HTML']->display();
                    RemoveMessageInfo();
                    exit();	

                } else {
    		        AddMessageInfo();
                    $GLOBALS['Message'] ='<div class="alert alert-danger" id="wrong"><strong> Fail! </strong>Your link is expired!</div>';
                    $GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/login.html');	
                    $GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');			
                    $GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');
                    $GLOBALS['SIDEBAR'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.sidebar.html');
                    $GLOBALS['CLA_HTML']->display();
                    RemoveMessageInfo();
                    sleep(2);
                    GetFrontRedirectUrl(GetUrl(array('module'=>'login')));
                    exit();	
                }
            }
            else{
                AddMessageInfo();
                $GLOBALS['Message'] ='<div class="alert alert-danger" id="wrong"><strong> Fail! </strong>Your link is expired!</div>';
                $GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/login.html');   
                $GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');         
                $GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');
                $GLOBALS['SIDEBAR'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.sidebar.html');
                $GLOBALS['CLA_HTML']->display();
                RemoveMessageInfo();
                sleep(2);
                GetFrontRedirectUrl(GetUrl(array('module'=>'login')));
                exit(); 
            }
        }else{

        }
    }
    
    function encryptNoSpecialChars($data, $key) {
        $ivLength = openssl_cipher_iv_length('aes-256-cbc');
        $iv = openssl_random_pseudo_bytes($ivLength);
        
        $ciphertext = openssl_encrypt($data, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        
        // Calculate HMAC using the key
        $hmac = hash_hmac('sha256', $iv . $ciphertext, $key, true);
        
        // Combine IV + HMAC + ciphertext and encode
        return bin2hex($iv . $hmac . $ciphertext);
    }

    function decryptNoSpecialChars($hexData, $key) {
        $data = hex2bin($hexData);
        
        $ivLength = openssl_cipher_iv_length('aes-256-cbc');
        $hmacLength = 32; // SHA-256 produces 32-byte hashes
        
        $iv = substr($data, 0, $ivLength);
        $hmac = substr($data, $ivLength, $hmacLength);
        $ciphertext = substr($data, $ivLength + $hmacLength);
        
        // Recalculate HMAC
        $calculatedHmac = hash_hmac('sha256', $iv . $ciphertext, $key, true);
        
        // Securely compare HMACs
        if (!hash_equals($hmac, $calculatedHmac)) {
            return false; // Tampering or wrong key
        }
        
        return openssl_decrypt($ciphertext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
    }

    function savePassword() {
        $postdata = $_POST;
		if(isset($postdata['suser_password']) && $postdata['suser_confirm_password']){

            if($postdata['suser_password'] != $postdata['suser_confirm_password']){
                $return_arrs = array('error'=>1,'msg'=>'Please enter both password same');
            }
            else{
                $data = array('password'=>md5($postdata['suser_password']),'is_active'=>1);
                $where = array('email'=>$postdata['suser_email']); 
                $addDepartment = $GLOBALS['DB']->update("registerusers_sub_users",$data,$where);
                
                $return_arrs = array('error'=>0,'msg'=>'Password is set.');
            }
        
        }else{
            $return_arrs = array('error'=>1,'msg'=>'please fill all required field');
        }
        echo json_encode($return_arrs); exit;
    }
	

    public function sendInviteMail($userid)
    {
        $userData = $GLOBALS['DB']->row("SELECT * FROM `registerusers_sub_users` WHERE `id`= ? LIMIT 0,1",array($userid));
        $GLOBALS['encrypt_decrypt_key'] = 'mySuperSecretKeyeSign2025'.time(); 
        $data = array('invite_key'=>$GLOBALS['encrypt_decrypt_key']);
        $where = array('id'=>$userid); 
        $updateStatus = $GLOBALS['DB']->update("registerusers_sub_users",$data,$where);
        $key = $GLOBALS['encrypt_decrypt_key'];
        $encodedMail = $this->encryptNoSpecialChars($userData['email'],$key);
        $expires_at = $this->encryptNoSpecialChars(date("Y-m-d H:i:s", strtotime('+2 hours')),$key);
        $setPasswordLink = GetUrl(array('module'=>'usermanagement','category_id' => 'createpassword','token'=>$expires_at,'user'=>$encodedMail,'user_id'=>$userid));
        $GLOBALS['user_name'] = $userData['user_firstname']." ".$userData['user_lastname'];
        $GLOBALS['parent_user_name'] = trim($GLOBALS['USERNAME']);
        $GLOBALS['linkSetPassword'] = $setPasswordLink;
        $message= _getEmailTemplate('invite_sub_user');
        $send_mail = _SendMail($userData['email'],'',$GLOBALS['EMAIL_SUBJECT'],$message);

    }

}

?>



