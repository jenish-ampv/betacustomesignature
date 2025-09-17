<?php
class CIT_DASHBOARD
{

	public function __construct()
	{
		if(!isset($_SESSION[GetSession('user_id')]) && !isset($_REQUEST['uuid'])){
			GetFrontRedirectUrl(GetUrl(array('module'=>'signin')));exit();
		}

		

		if($_REQUEST['category_id'] != 'planrenewed' && $GLOBALS['plan_cancel'] == 1){
			GetFrontRedirectUrl(GetUrl(array('module'=>'purchase','category_id'=>'renewaccount')));exit();
		}
		
		if($GLOBALS['plan_type'] == 'FREE' && $GLOBALS['freeperiod_dayleft'] == 0){
			$redirect = $GLOBALS['billing'].'?action=freetrial';
			GetFrontRedirectUrl($redirect);exit();
		}
		elseif($_REQUEST['category_id'] != 'planrenewed' && $GLOBALS['PLAN_STATUS'] == 0){
			if(!isset($_REQUEST['category_id']) && !isset($_REQUEST['uuid'])){
				GetFrontRedirectUrl(GetUrl(array('module'=>'purchase','category_id'=>'renewaccount')));exit();
			}
		}

		if($_REQUEST['category_id'] == 'planrenewed'){
			sleep(5);
			GetFrontRedirectUrl(GetUrl(array('module'=>'dashboard')));exit();
		}

		if(isset($_REQUEST['department_id'])){
			$GLOBALS['current_department_id'] = $_REQUEST['department_id'];
		}else{
			$GLOBALS['current_department_id'] = 0;
		}
		if($GLOBALS['ISSUBUSER']){
			$subUserDepartmentList = $GLOBALS['DB']->row("SELECT department_list FROM registerusers_sub_users WHERE id=? ",array($GLOBALS['SUBUSERID']));
			if($subUserDepartmentList){
				$department_ids = explode(",",$subUserDepartmentList['department_list']);
				if(!in_array($GLOBALS['current_department_id'],$department_ids)){
					$redirect = $GLOBALS['dashboard'].'?department_id='.$department_ids[0];
					GetFrontRedirectUrl($redirect);exit();
				}
			}
		}
		$user = $GLOBALS['DB']->row("SELECT RU.user_id,RU.user_come_from_free_trial,count(*) as logo_count FROM `registerusers` as RU RIGHT JOIN signature_logo as SL ON SL.user_id = RU.user_id WHERE RU.user_id=? ",array($GLOBALS['USERID']));
		if($user['user_come_from_free_trial'] && $user['logo_count'] == '0'){
			GetFrontRedirectUrl($GLOBALS['uploadbrandlogo']);exit();
		}
	}

	public function displayPage(){

		AddMessageInfo();
		if(isset($_REQUEST['category_id'])){
			$action = trim($_REQUEST['category_id']);
		} else {
			$action = '';
		}

		if(isset($_POST['action']) && isset($_POST['id'])){
			$saction = $_POST['action']; $sid = $_POST['id'];
			echo $this->actionSignature($saction,$sid); exit;
		}

		if(isset($_POST['feedback']) && isset($_POST['signature_id'])){
			$feedback = $_POST['feedback']; $sid = $_POST['signature_id'];
			echo $this->feedbackSignature($feedback,$sid); exit;
		}
		if($_POST['reuploadLogo']){
			$GLOBALS['DB']->update("signature_logo",array('logo_change_process'=>0),array('user_id'=>$GLOBALS['USERID']));
			$return_arr = array("url"=> $GLOBALS['UrlRewriteBase'].'dashboard');
			echo json_encode($return_arr); exit;
		}

		// create a logo store directory
		// if (!is_dir(GetConfig('SITE_UPLOAD_PATH') . "/signature/".$GLOBALS['USERID'])) {
		// 	if (!mkdir(GetConfig('SITE_UPLOAD_PATH')."/signature/".$GLOBALS['USERID'])) {
		// 		$error = 1;
		// 		die("\"temp\" folder not created. Permission problem.......");
		// 	}
		// }

		// create a signature store directory
		// if (!is_dir(GetConfig('SITE_UPLOAD_PATH') . "/signature/complete/".$insert_id)) {
		// 	if (!mkdir(GetConfig('SITE_UPLOAD_PATH')."/signature/complete/".$insert_id)) {
		// 		die("\"temp\" folder not created. Permission problem.......");
		// 	}
		// }

		$user = $GLOBALS['DB']->row("SELECT * FROM `registerusers` WHERE user_id = ? ",array($GLOBALS['USERID']));
		if($user['user_type'] == "enterprise"){
			$signature_lists = $GLOBALS['DB']->query("SELECT SG.signature_id,department_id FROM `signature` SG LEFT JOIN signature_layout SL ON SG.layout_id = SL.layout_id  WHERE SG.signature_status = 1 AND SG.user_id = ?",array($GLOBALS['USERID']));
			if($signature_lists){
				$department_list = $GLOBALS['DB']->row("SELECT department_id FROM `registerusers_departments` WHERE user_id = ?",array($GLOBALS['USERID']));
				$department_id = 0;
				if($department_list){
					$department_id = $department_list['department_id'];
					$_POST['current_department'] = $department_id;
				}else{
					$data = array('user_id'=>$GLOBALS['USERID'],'department_name'=>'Division #1');
					$department_id = $GLOBALS['DB']->insert("registerusers_departments",$data);
					$_POST['current_department'] = $department_id;
				}
				
				foreach ($signature_lists as $key => $signature) {
					if($signature['department_id'] == 0){
						$GLOBALS['DB']->update('signature',['department_id'=>$department_id],['signature_id'=>$signature['signature_id']]);
					}
				}
			}
		}else{
			$signature_lists = $GLOBALS['DB']->query("SELECT SG.signature_id FROM `signature` SG LEFT JOIN signature_layout SL ON SG.layout_id = SL.layout_id  WHERE SG.signature_status = 1 AND SG.user_id = ?",array($GLOBALS['USERID']));
			if($signature_lists){
				foreach ($signature_lists as $key => $signature) {
					$GLOBALS['DB']->update('signature',['department_id'=>0],['signature_id'=>$signature['signature_id']]);
				}
			}
		}

		$this->getPage();
		$this->getUserSigProcess();
		$this->getUserSignature();
		if($_POST["reasonMessage"]){
			$reason = $_POST["reason"];
			$logo_id = $_POST["logo_id"];
			$logoData = ['feedback' => $reason,'user_id'=>$GLOBALS["USERID"], 'signature_logo_id' => $logo_id, 'feedback_date' => date("d-m-Y")];
			$where = array('user_id'=>$GLOBALS['USERID']);
			$GLOBALS['DB']->insert("signature_logo_feedback",$logoData);
			$return_arr = array("success" =>1, "msg"=>"Your logo has been uploaded!!");
			echo json_encode($return_arr); exit;
			
		}
		if($_POST["departmentLogoChanged"]){
			$logo_name = $_POST["logo_name"];
			$department_id = $_POST["department_id"];
			$GLOBALS['DB']->insert("signature_logo",array('user_id'=>$GLOBALS['USERID'],'department_id'=>$department_id,'logo'=>$logo_name));
			$return_arr = array("success" =>1, "msg"=>"Your logo has been uploaded!!");
			echo json_encode($return_arr); exit;
			
		}

		if($_FILES['signature_logo']['name'] !=""){
			$filename = $_FILES['signature_logo']['name'];
			$filesize = $_FILES['signature_logo']['size'];
			$displayname = $filename;
			$valid_extensions = array('png', 'svg','jpeg','jpg','gif');
			$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
			if(in_array($ext, $valid_extensions)) {
				//$location = "upload-beta/".$filename;
				$filename = time().'-'.$GLOBALS['USERID'].'.'.$ext;
				// $filename = $GLOBALS['USERID'].'.png';
				$location =  GetConfig('SITE_UPLOAD_PATH').'/signature/'.$GLOBALS['USERID'].'/'.$filename ;
				$uploadFolder =  GetConfig('SITE_UPLOAD_PATH').'/signature/';
				$return_arr = array();
				if (!file_exists($uploadFolder)) {
				    mkdir($uploadFolder, 0777, true);
				}
				if(move_uploaded_file($_FILES['signature_logo']['tmp_name'],$location)){
					$result = $GLOBALS['S3Client']->putObject(array( // upload image s3bucket
						'Bucket'=>$GLOBALS['BUCKETNAME'],
						'Key' =>  'upload-beta/signature/'.$GLOBALS['USERID'].'/'.$filename,
						'SourceFile' => $location,
						'StorageClass' => 'REDUCED_REDUNDANCY',
						'ACL'   => 'public-read'
					));
					// if(is_array(getimagesize($location))){
						$src = $GLOBALS['UPLOAD_LINK'].'/signature/'.$GLOBALS['USERID'].'/'.$filename ;
						$data =array('user_image'=>$filename); $where =array('user_id'=>$GLOBALS['USERID']);
					// }
					$return_arr = array("name" => $filename,"displayname" => $displayname, "size" => $filesize, "src"=> $src, "error"=>0);
					$logoData = ['changed_logo' => $filename, 'logo_change_process'=>1];
					$where = array('user_id'=>$GLOBALS['USERID']);
					$GLOBALS['DB']->update("signature_logo",$logoData,$where);
				}
			}else{
				$return_arr = array("error" =>1, "msg"=>"Please upload a valid JPG, JPEG, PNG, GIF, or SVG image");
			}
			echo json_encode($return_arr); exit;
		}

		if($_FILES['signature_department_logo']['name'] !=""){
			$filename = $_FILES['signature_department_logo']['name'];
			$filesize = $_FILES['signature_department_logo']['size'];
			$displayname = $filename;
			$valid_extensions = array('png', 'svg','jpeg','jpg','gif');
			$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
			if(in_array($ext, $valid_extensions)) {
				//$location = "upload-beta/".$filename;
				$filename = time().'-'.$GLOBALS['USERID'].'.'.$ext;
				// $filename = $GLOBALS['USERID'].'.png';
				$location =  GetConfig('SITE_UPLOAD_PATH').'/signature/'.$GLOBALS['USERID'].'/'.$filename ;
				$uploadFolder =  GetConfig('SITE_UPLOAD_PATH').'/signature/';
				$return_arr = array();
				if (!file_exists($uploadFolder)) {
				    mkdir($uploadFolder, 0777, true);
				}
				if(move_uploaded_file($_FILES['signature_department_logo']['tmp_name'],$location)){
					$result = $GLOBALS['S3Client']->putObject(array( // upload image s3bucket
						'Bucket'=>$GLOBALS['BUCKETNAME'],
						'Key' =>  'upload-beta/signature/'.$GLOBALS['USERID'].'/'.$filename,
						'SourceFile' => $location,
						'StorageClass' => 'REDUCED_REDUNDANCY',
						'ACL'   => 'public-read'
					));
					// if(is_array(getimagesize($location))){
						$src = $GLOBALS['UPLOAD_LINK'].'/signature/'.$GLOBALS['USERID'].'/'.$filename ;
						$data =array('user_image'=>$filename); $where =array('user_id'=>$GLOBALS['USERID']);
					// }
					$return_arr = array("name" => $filename,"displayname" => $displayname, "size" => $filesize, "src"=> $src, "error"=>0);
					$logoData = ['changed_logo' => $filename, 'logo_change_process'=>1];
					$where = array('user_id'=>$GLOBALS['USERID']);
					$GLOBALS['DB']->update("signature_department_logo",$logoData,$where);
				}
			}else{
				$return_arr = array("error" =>1, "msg"=>"please upload valid jpg, jpeg, png gif or svg image");
			}
			echo json_encode($return_arr); exit;
		}

		// IMPORT_SIGNATURE_PENDING_LEFTBAR
		$pendingCount = $GLOBALS['DB']->row("SELECT count(*) as pending_count FROM `signature_import_process_data` WHERE user_id = ? AND `status`='0'",array($GLOBALS['USERID']));
		$processPendingImportSignatureLink = GetUrl(array('module'=>'processPendingImportSignature'));

		$user = $GLOBALS['DB']->row("SELECT * FROM `registerusers` WHERE user_id = ? ",array($GLOBALS['USERID']));
		if($user['user_type'] == "enterprise"){
			if($GLOBALS['current_department_id']){
				$processPendingImportSignatureLink = GetUrl(array('module'=>'processPendingImportSignature')).'?department_id='.$GLOBALS['current_department_id'];
			}else{
				$processPendingImportSignatureLink = GetUrl(array('module'=>'processPendingImportSignature')).'?department_id='.$_REQUEST['current_department_id'];
			}
		}
		$totalCount = $GLOBALS['DB']->row("SELECT count(*) as total_count FROM `signature_import_process_data` WHERE user_id = ? AND `status`=0 ",array($GLOBALS['USERID']));
		$GLOBALS['IMPORT_SIGNATURE_PENDING_COUNT'] = $pendingCount['pending_count'];
		if($pendingCount['pending_count'] > 0){
			$progressBarWidthPercentage = ($pendingCount['pending_count']/$totalCount['total_count']) * 100 ;
			$GLOBALS['IMPORT_SIGNATURE_PENDING_LEFTBAR'] = '<div class="pending_signature_bg">
														<h6>Pending Signature <img src="%%DEFINE_IMAGE_LINK%%/images/pending-icon.svg" alt=""></h6>
															<div class="progress" role="progressbar" aria-label="Example 1px high" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100" >
															<div class="progress-bar" style="width: '.$progressBarWidthPercentage.'%"></div>
															</div>
															<div class="banner_size"><span id="sig_processing_number">'.$pendingCount['pending_count'].'</span>/<span id="sig_total_processing_number">'.$totalCount['total_count'].'</span></div>
															<h6 style="display:none;"><a href="javascript:void(0);" id="process_pending_imported_signature" data-url="'.$processPendingImportSignatureLink.'" onclick="processPendingImportedSignature()">Click here to popup</a></h6>
													</div>';
		}

		$department_lists_arr = $GLOBALS['DB']->query("SELECT * FROM `registerusers_departments` WHERE user_id = ? ORDER BY position",array($GLOBALS['USERID']));
		$GLOBALS['department_list'] = "";
		$GLOBALS['selected_department'] = "";
		
		$defaultFirstSelectedSubUser = "";
		foreach ($department_lists_arr as $key => $department) {
			$defaultFirstSelected = "";
			if(($key === array_key_first($department_lists_arr))){ // very first department
				$GLOBALS['selected_department'] = $department['department_id'];
				$defaultFirstSelected = "department_selected";
			}
			$thisDepartmentSignatures = $this->getTotalSignatureCreateByDepartment($department['department_id']);

			if($GLOBALS['ISSUBUSER']){
				if(isset($GLOBALS['department_'.$department['department_id']])){
					if($defaultFirstSelectedSubUser == ""){
						$GLOBALS['selected_department'] = $department['department_id'];
						$defaultFirstSelectedSubUser = "department_selected";
					}
					$GLOBALS['department_list'] .= '<li class="department_container handle '.$defaultFirstSelectedSubUser.'" data-department-id="'.$department['department_id'].'" draggable="true">
								<div class="text_left"><span class="name"><img src="%%DEFINE_IMAGE_LINK%%/images/new-drag-and-drop-icon.svg" alt=""></span>'.$department['department_name'].'</div>
								<div class="text_center">'.$thisDepartmentSignatures.' Members</div>
								<div class="icon_right"><a href="javascript:void(0);" class="mastersig clickableAnchor" data-department_id="'.$department['department_id'].'" data-department_name="'.$department['department_name'].'" data-bs-toggle="modal" data-bs-target="#departmentModel"><img class="color" src="%%DEFINE_IMAGE_LINK%%/images/new-edit-icon.svg" alt=""></a>
								</div>
							</li>';
				}

			}else{

				if(sizeof($department_lists_arr) == 1){
					$GLOBALS['department_list'] .= '<li class="department_container handle '.$defaultFirstSelected.'" data-department-id="'.$department['department_id'].'" draggable="true">
								<div class="text_left"><span class="name"><img src="%%DEFINE_IMAGE_LINK%%/images/new-drag-and-drop-icon.svg" alt="" draggable="false"></span>'.$department['department_name'].'</div>
								<div class="text_center">'.$thisDepartmentSignatures.' Members</div>
								<div class="icon_right"><a href="javascript:void(0);" class="mastersig clickableAnchor" data-department_id="'.$department['department_id'].'" data-department_name="'.$department['department_name'].'" data-bs-toggle="modal" data-bs-target="#departmentModel"><img class="color" src="%%DEFINE_IMAGE_LINK%%/images/new-edit-icon.svg" alt=""></a>
								</div>
							</li>';
				}else{
					$GLOBALS['department_list'] .= '<li class="department_container handle '.$defaultFirstSelected.'" data-department-id="'.$department['department_id'].'" draggable="true">
								<div class="text_left"><span class="name"><img src="%%DEFINE_IMAGE_LINK%%/images/new-drag-and-drop-icon.svg" alt="" draggable="false"></span>'.$department['department_name'].'</div>
								<div class="text_center">'.$thisDepartmentSignatures.' Members</div>
								<div class="icon_right"><a href="javascript:void(0);" class="mastersig clickableAnchor" data-department_id="'.$department['department_id'].'" data-department_name="'.$department['department_name'].'" data-bs-toggle="modal" data-bs-target="#departmentModel"><img class="color" src="%%DEFINE_IMAGE_LINK%%/images/new-edit-icon.svg" alt=""></a>
								<a class="mastersig delete_department" href="'.$GLOBALS['departmententerprise'].'/deleteDepartment?department_id='.$department['department_id'].'"><i class="hgi hgi-stroke hgi-delete-02"></i></a></div>
							</li>';
				}
			}
		}
		$user = $GLOBALS['DB']->row("SELECT * FROM `registerusers` WHERE user_id = ? ",array($GLOBALS['USERID']));
		if($user['user_type'] == "enterprise"){
			$department_lists = $GLOBALS['DB']->row("SELECT count(*) as total_department FROM `registerusers_departments` WHERE user_id = ? ",array($GLOBALS['USERID']));
			if($department_lists['total_department'] == 0){
				$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/welcome.html');
				$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');
			}else{
				$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/dashboardenterprise.html');
				$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');
			}
		}
		else {
			if($GLOBALS['signature_count'] == 0){ // redirect welcome page
				$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/welcome.html');
				$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');
			}
			else{
				$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/dashboard.html');
				$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');
			}
		}


		
		$gmailAutenticated = $GLOBALS['DB']->row("SELECT * FROM `registerusers` WHERE `user_email` = ? AND `gmail_authenticated`=1",array($GLOBALS['USEREMAIL']));
		if(is_array($gmailAutenticated)){
			if($GLOBALS['current_department_id'] == 0)
				$GLOBALS['googledeploylink'] = GetUrl(array('module'=>'deploydata','action'=>'googledeploy'));
			else
				$GLOBALS['googledeploylink'] = GetUrl(array('module'=>'deploydata','action'=>'googledeploy')).'?department_id='.$GLOBALS['current_department_id'];
		}else{
			$GLOBALS['googledeploylink'] = GetUrl(array('module'=>'integrations'));
		}

		$GLOBALS['plan_signature'] = $this->getTotalPlanSignature();
		$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');
		$GLOBALS['SIDEBAR'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.sidebar.html');
		$GLOBALS['CLA_HTML']->display();
		RemoveMessageInfo();
		exit();

	}

	public function getUserSigProcess(){
			$signature_logo = $GLOBALS['DB']->row("SELECT * FROM `signature_logo` WHERE user_id = ? ",array($GLOBALS['USERID']));
			$signatureSubscription = $GLOBALS['DB']->row("SELECT subscription_id FROM `registerusers_subscription` WHERE user_id = ? ",array($GLOBALS['USERID']));
			// $signature_logo_analytics = $GLOBALS['DB']->row("SELECT * FROM `registerusers_analytics` WHERE user_id = ? AND analytic_type='logo'",array($GLOBALS['USERID']));
			// if(isset($signature_logo_analytics['url'])){
			// 	$signature_logo['logo'] = $signature_logo_analytics['url'];
			// }
			
			$GLOBALS['signature_logo_id'] = $signature_logo['id'];
			if($signature_logo['logo_process'] == 2){ // && $GLOBALS['PLAN_STATUS'] == 1
				$signature_image_without_analytics = $GLOBALS['UPLOAD_LINK'].'/signature/complete/'.$GLOBALS['USERID'].'/'.$signature_logo['logo_animation'];
			}else if($signature_logo['logo_process'] == 1 && $GLOBALS['PLAN_STATUS'] ==1){
				$signature_image_without_analytics = $GLOBALS['UPLOAD_LINK'].'/signature/complete/'.$GLOBALS['USERID'].'/'.$signature_logo['logo_animation'];
			}else{
				$signature_image_without_analytics = $GLOBALS['UPLOAD_LINK'].'/signature/'.$GLOBALS['USERID'].'/'.$signature_logo['logo'];
			}
 			if($GLOBALS['logo_process'] == 1){
					$GLOBALS['signature_process'] ='<div class="sin_dashboard_box sin_process">
								<div class="flex-1">
									<img src="'.$GLOBALS['UPLOAD_LINK'].'/signature/complete/'.$signature_logo['user_id'].'/'.$signature_logo['logo_animation'].'" alt="">
									<p class="text-xl mt-3 text-gray-950">'.$GLOBALS['USERNAME'].'!</p>
									<p class="text-gray-600">Your Logo Animation ready! Please review it</p>
									<a class="kt-btn kt-btn-primary my-4" id="reviewLogo" data-img="'.$signature_image_without_analytics.'" data-id="'.$GLOBALS['logo_id'].'" data-kt-modal-toggle="#reviewModal">Review Logo</a>
								</div>';
						if($signature_logo['logo_change_process'] != 2){
							$GLOBALS['signature_process'] .= '<p class="text-gray-400">Did you mistakenly upload the wrong logo? Please <a class="text-primary underline" id="change_signature_logo" data-img="'.$signature_image_without_analytics.'" data-id="'.$GLOBALS['logo_id'].'" data-kt-modal-toggle="#changeLogoModel">click here.</a></p>';
						}
					$GLOBALS['signature_process'] .= '</div></div>';
				}else if($GLOBALS['logo_process'] == 0 || $GLOBALS['logo_process'] == 3 ){
					$GLOBALS['signature_process'] ='<div class="sin_dashboard_box sin_process flex">
								<div class="flex-1">
								<p class="text-xl text-gray-950">'.$GLOBALS['USERNAME'].'!</p>
								<p class="text-gray-600">Your Logo Animation is Processing</p>
 								<div class="flex items-center mt-6 mb-5 gap-4">
								<div class="flex-1 bg-black/5 h-[5px] rounded-full gap-3">
								  <div class="rounded-full shadow-[0_2px_15px_0_rgba(29,156,254,0.6)] bg-gradient-to-r from-[#26B7FF] to-[#1D4AFE] h-full w-[50px]" role="progressbar" aria-valuenow="50" aria-valuemin="0" aria-valuemax="100"></div>
								</div>
								<span class="text-xs text-gray-950 text-nowrap">1/2 Days</span>
								</div>';
					if($signature_logo['logo_change_process'] != 2){
						$GLOBALS['signature_process'] .= '<div class="change_logo_btn">Did you mistakenly upload the wrong logo? Please <a class="underline" id="change_signature_logo" data-img="'.$signature_image_without_analytics.'" data-id="'.$GLOBALS['logo_id'].'" data-kt-modal-toggle="#changeLogoModel">click here.</a></div></div>';
					}
					$GLOBALS['signature_process'] .= '<div class="pl-20"><div class="w-[150px] h-[56px] rounded-xl relative border border-gray-400 flex items-center justify-center">
									<img src="'.$GLOBALS['UPLOAD_LINK'].'/signature/'.$signature_logo['user_id'].'/'.$signature_logo['logo'].'" alt="">
									<div class="animation_img absolute w-full h-full top-0 left-0"><lottie-player autoplay loop mode="normal" src="'.$GLOBALS['ROOT_LINK'].'/images/line-animation.json"></lottie-player></div>
								</div>';
					if($signatureSubscription){
						if($signatureSubscription['subscription_id'] == ""){
							$GLOBALS['signature_process'] .= '<button class="kt-btn kt-btn-primary mt-5" data-kt-modal-toggle="#upgrade-plan-popup">Upgrade to animate</button>';
						}
					}
					$GLOBALS['signature_process'] .= '</div></div>';
				}
				elseif ($signature_logo['logo_change_process'] == 0 || $signature_logo['logo_change_process'] == 1) {
					$GLOBALS['signature_process'] ='<div class="col-xl-4 col-lg-6 col-md-6 col-12">
							<div class="sin_dashboard_box sin_process">
								<div class="logo_right">
									<img src="'.$GLOBALS['UPLOAD_LINK'].'/signature/'.$signature_logo['user_id'].'/'.$signature_logo['logo'].'" alt="">
									<div class="animation_img"><lottie-player autoplay loop mode="normal" src="'.$GLOBALS['ROOT_LINK'].'/images/line-animation.json"></lottie-player></div>
								</div>
								<div class="progress_details_left">
								<h2>'.$GLOBALS['USERNAME'].'!</h2>
								<h3>Your Logo is Under Review</h3>';
					if($signature_logo['logo_change_process'] != 2){
						$GLOBALS['signature_process'] .= '<div class="change_logo_btn">Did you mistakenly upload the wrong logo? Please <a class="" id="change_signature_logo" data-img="'.$signature_image_without_analytics.'" data-id="'.$GLOBALS['logo_id'].'" data-kt-modal-toggle="#changeLogoModel">click here.</a></div>';
					}
					$GLOBALS['signature_process'] .= '</div>
						</div></div>';
				}
				elseif ($signature_logo['logo_change_process'] == 3) {
					$GLOBALS['signature_process'] ='<div class="col-xl-4 col-lg-6 col-md-6 col-12">
							<div class="sin_dashboard_box sin_process">
								<div class="logo_right">
									<img src="'.$GLOBALS['UPLOAD_LINK'].'/signature/'.$signature_logo['user_id'].'/'.$signature_logo['logo'].'" alt="">
									<div class="animation_img"><lottie-player autoplay loop mode="normal" src="'.$GLOBALS['ROOT_LINK'].'/images/line-animation.json"></lottie-player></div>
								</div>
								<div class="progress_details_left">
								<h2>Your logo is not matching with our guideline.</h2>
								<ul>
								<li>'.$signature_logo['change_logo_reject_reason'].'</li>
								</ul>
								<div class="review_btn"><a class="btn btn-primary" id="change_signature_logo" data-url="'.$GLOBALS['UrlRewriteBase'].'dashboard" data-kt-modal-toggle="#changeLogoModel">Reupload Logo<img src="'.$GLOBALS['IMAGE_LINK'].'/images/arrow-right.svg" alt=""></a></div></div>
						</div></div>';
				}
				elseif ($signature_logo['logo_change_process'] == 4 ) {
					$GLOBALS['signature_process'] ='<div class="col-xl-4 col-lg-6 col-md-6 col-12">
							<div class="sin_dashboard_box sin_process">
								<div class="logo_right">
									<img src="'.$GLOBALS['UPLOAD_LINK'].'/signature/'.$signature_logo['user_id'].'/'.$signature_logo['logo'].'" alt="">
									<div class="animation_img"><lottie-player autoplay loop mode="normal" src="'.$GLOBALS['ROOT_LINK'].'/images/line-animation.json"></lottie-player></div>
								</div>
								<h2>'.$GLOBALS['USERNAME'].'!</h2>
								<div class="progress_details_left">
								<h3>Your logo animation is in process</h3>
								<p>Thank you for your patience.</p>
								<div class="progress_days_box">
								<div class="progress">
								  <div class="progress-bar w-50" role="progressbar" aria-valuenow="50" aria-valuemin="0" aria-valuemax="100"></div>
								</div>
								<span>1/2 Days</span>
								</div>';
					if($signature_logo['logo_change_process'] != 2){
						$GLOBALS['signature_process'] .= '<div class="change_logo_btn">Did you mistakenly upload the wrong logo? Please <a class="" id="change_signature_logo" data-img="'.$signature_image_without_analytics.'" data-id="'.$GLOBALS['logo_id'].'" data-kt-modal-toggle="#changeLogoModel">click here.</a></div>';
					}
					$GLOBALS['signature_process'] .= '</div>
						</div></div>';
				}
				elseif( $GLOBALS['logo_process'] == 4 ){
					$GLOBALS['signature_process'] ='<div class="col-xl-4 col-lg-6 col-md-6 col-12">
							<div class="sin_dashboard_box sin_process">
								<div class="logo_right">
									<img src="'.$GLOBALS['UPLOAD_LINK'].'/signature/'.$signature_logo['user_id'].'/'.$signature_logo['logo'].'" alt="">
									<div class="animation_img"><lottie-player autoplay loop mode="normal" src="'.$GLOBALS['ROOT_LINK'].'/images/line-animation.json"></lottie-player></div>
								</div>
								<div class="progress_details_left">
								<h2>'.$GLOBALS['USERNAME'].'!</h2>
								<h3>Your logo animation is in process</h3>
								<p>Thank you for your patience.</p>
								<div class="progress_days_box">
								<div class="progress">
								  <div class="progress-bar w-50" role="progressbar" aria-valuenow="50" aria-valuemin="0" aria-valuemax="100"></div>
								</div>
								<span>1/2 Days</span>
								</div>';
					if($signature_logo['logo_change_process'] != 2){
						$GLOBALS['signature_process'] .= '<div class="change_logo_btn">Did you mistakenly upload the wrong logo? Please <a class="" id="change_signature_logo" data-img="'.$signature_image_without_analytics.'" data-id="'.$GLOBALS['logo_id'].'" data-kt-modal-toggle="#changeLogoModel">click here.</a></div>';
					}
					$GLOBALS['signature_process'] .= '</div>
						</div></div>';
				}
				if($GLOBALS['plan_type'] == 'FREE'){
					$GLOBALS['signature_process'] ='<div class="col-xl-4 col-lg-6 col-md-6 col-12">
							<div class="sin_dashboard_box sin_process">
								<div class="logo_right"><img src="%%DEFINE_IMAGE_LINK%%/images/main-logo.png" alt="">
									<div class="animation_img"><img src="%%DEFINE_IMAGE_LINK%%/images/line-animation1.gif" alt=""></div>
								</div>
								<div class="progress_details_left">
								<h2>'.$GLOBALS['USERNAME'].'!</h2>
								<h3>Your Free Trial period will end in '.$GLOBALS['freeperiod_dayleft'].' day!</h3>
								<p>kindly renew your account before end.</p>
								<div class="review_btn"><a href="#" data-bs-toggle="modal" data-kt-modal-toggle="#upgradeFromFreeTrialPopup" class="btn">Animated Plan</a></div>
								</div>
							</div>
						</div>';
				}

				// if($GLOBALS['current_department_id']){
				// 	$signature_logo_department = $GLOBALS['DB']->row("SELECT * FROM `signature_logo` WHERE user_id = ? AND department_id = ?",array($GLOBALS['USERID'],$GLOBALS['current_department_id']));
				// 	if($signature_logo_department){
				// 		$GLOBALS['signature_process'] ='<div class="col-xl-4 col-lg-6 col-md-6 col-12">
				// 				<div class="sin_dashboard_box sin_process">
				// 					<div class="logo_right">
				// 						<img src="'.$GLOBALS['UPLOAD_LINK'].'/signature/'.$signature_logo_department['user_id'].'/'.$signature_logo_department['logo'].'" alt="">
				// 						<div class="animation_img"><lottie-player autoplay loop mode="normal" src="'.$GLOBALS['ROOT_LINK'].'/images/line-animation.json"></lottie-player></div>
				// 					</div>
				// 					<h2>'.$GLOBALS['USERNAME'].'!</h2>
				// 					<h3>Your Logo Animation require payment</h3>';
				// 		$GLOBALS['signature_process'] .= '<div class="change_logo_btn">Did you want your department logo to be animated? Please <a href="'.$GLOBALS['departmentpayment'].'?department_id='.$GLOBALS['current_department_id'].'" >click here.</a></div>';

				// 		$GLOBALS['signature_process'] .= '</div>
				// 			</div>';
				// 	}
				// 	else{
				// 		$GLOBALS['signature_process'] = "";
				// 	}
				// }
				$GLOBALS['master_inner_extra_class'] = "";

				if($GLOBALS['signature_process'] == ""){
					$GLOBALS['master_inner_extra_class'] = "master-without-process";
				}

	}

	public function getUserSignature($signature_id=''){
		 $GLOBASL['signature_list'] ='';
		 if($userid !=""){ $GLOBALS['USERID'] = $userid; $this->getSignatureLogo();}
		 $user = $GLOBALS['DB']->row("SELECT * FROM `registerusers` WHERE user_id = ? ",array($GLOBALS['USERID']));
		 if($signature_id!=''){
			$signature_lists = $GLOBALS['DB']->query("SELECT SG.*,SL.* FROM `signature` SG LEFT JOIN signature_layout SL ON SG.layout_id = SL.layout_id WHERE SG.signature_status = 1 AND SG.signature_id = ? LIMIT 0,1",array($signature_id));
		 }else{ 
			$totalplansignature = $this->getTotalPlanSignature();
			$total_sigcreated = $this->getTotalSignatureCreate();
			if($GLOBALS['current_department_id']){
				// check for created signature count with purchase plan count
				if($totalplansignature != $total_sigcreated){ 
					$signature_lists = $GLOBALS['DB']->query("SELECT SG.*,SL.* FROM `signature` SG LEFT JOIN signature_layout SL ON SG.layout_id = SL.layout_id WHERE SG.signature_status = 1 AND SG.user_id = ? AND SG.department_id = ? GROUP BY SG.signature_id ORDER BY FIELD(SG.signature_master,1) DESC,signature_id ASC LIMIT 0,".$totalplansignature."",array($GLOBALS['USERID'],$GLOBALS['current_department_id']));
				}else{
					$signature_lists = $GLOBALS['DB']->query("SELECT SG.*,SL.* FROM `signature` SG LEFT JOIN signature_layout SL ON SG.layout_id = SL.layout_id WHERE SG.signature_status = 1 AND SG.user_id = ? AND SG.department_id = ? GROUP BY SG.signature_id ORDER BY FIELD(SG.signature_master,1) DESC,signature_id DESC",array($GLOBALS['USERID'],$GLOBALS['current_department_id']));
				}
			}elseif($_REQUEST['department_id']){
				// check for created signature count with purchase plan count
				if($totalplansignature != $total_sigcreated){ 
					$signature_lists = $GLOBALS['DB']->query("SELECT SG.*,SL.* FROM `signature` SG LEFT JOIN signature_layout SL ON SG.layout_id = SL.layout_id WHERE SG.signature_status = 1 AND SG.user_id = ? AND SG.department_id = ? GROUP BY SG.signature_id ORDER BY FIELD(SG.signature_master,1) DESC,signature_id ASC LIMIT 0,".$totalplansignature."",array($GLOBALS['USERID'],$_REQUEST['department_id']));
				}else{
					$signature_lists = $GLOBALS['DB']->query("SELECT SG.*,SL.* FROM `signature` SG LEFT JOIN signature_layout SL ON SG.layout_id = SL.layout_id WHERE SG.signature_status = 1 AND SG.user_id = ? AND SG.department_id = ? GROUP BY SG.signature_id ORDER BY FIELD(SG.signature_master,1) DESC,signature_id DESC",array($GLOBALS['USERID'],$_REQUEST['department_id']));
				}
			}else{
				// check for created signature count with purchase plan count
				if($totalplansignature != $total_sigcreated){ 
					$signature_lists = $GLOBALS['DB']->query("SELECT SG.*,SL.* FROM `signature` SG LEFT JOIN signature_layout SL ON SG.layout_id = SL.layout_id WHERE SG.signature_status = 1 AND SG.user_id = ? GROUP BY SG.signature_id ORDER BY FIELD(SG.signature_master,1) DESC,signature_id ASC LIMIT 0,".$totalplansignature."",array($GLOBALS['USERID']));
				}else{
					$signature_lists = $GLOBALS['DB']->query("SELECT SG.*,SL.* FROM `signature` SG LEFT JOIN signature_layout SL ON SG.layout_id = SL.layout_id WHERE SG.signature_status = 1 AND SG.user_id = ? GROUP BY SG.signature_id ORDER BY FIELD(SG.signature_master,1) DESC,signature_id DESC",array($GLOBALS['USERID']));
				}
			}
			
			
		 }

		$GLOBALS['signature_count'] =0;
		foreach($signature_lists as $sRow){
			$signature_id = $sRow['signature_id'];
			$this->getlayoutStyle('get',$sRow['signature_style']);
			// code for setting signature profile size if NULL or NAN END
			if(isset($GLOBALS['signature_profilesize'])){
				if(is_null($GLOBALS['signature_profilesize']) || $GLOBALS['signature_profilesize']=='NaN'){
					$GLOBALS['signature_profilesize'] = $this->getSignatureSpecificLayoutProfileSize($sRow['layout_id']);
				}
			}
			// code for setting signature profile size if NULL or NAN END
			$customfields = $this->getCustomField($signature_id);
			$GLOBALS['signature_socialdesign'] = $sRow['signature_socialdesign'];
			$GLOBALS['signature_btndesign'] = $sRow['signature_btndesign'];
			$GLOBALS['signature_marketbtndesign'] = $sRow['signature_marketbtndesign'];
			$GLOBALS['signature_custombtntext'] =  $sRow['signature_custombtntext'];
			$GLOBALS['signature_link'] =  $sRow['signature_link'] != "" ? addhttp($sRow['signature_link']) : 'javascript:void(0);';
			$dateToday = date('Y-m-d');
			$userIp = $GLOBALS['CLA_INDEX']->getUserIP();
			$sigLogoLinkAnalytics = $GLOBALS['DB']->row("SELECT * FROM `registerusers_analytics` WHERE url = ? AND user_id = ? AND date = ? AND signature_id = ? AND analytic_type='logoclick' AND user_ip = ?",array($sRow['signature_link'],$GLOBALS['USERID'],$dateToday,$sRow['signature_id'],$userIp));
			if($sigLogoLinkAnalytics){
				$GLOBALS['signature_link'] = $GLOBALS['ROOT_LINK'].'/r/'.$sigLogoLinkAnalytics['id'];
			}else{
				$existingData = $GLOBALS['DB']->row("SELECT * FROM `registerusers_analytics` WHERE url = ? AND user_id = ? AND signature_id = ? AND analytic_type='logoclick' LIMIT 0,1",array($sRow['signature_link'],$GLOBALS['USERID'],$sRow['signature_id']));
				if($existingData){
					$data['user_id'] = $existingData['user_id'];
					$data['signature_id'] = $existingData['signature_id'];
					$data['url'] = $existingData['url'];
					$data['analytic_type'] = $existingData['analytic_type'];
					$data['impressions'] = $data['clicks'] = $data['mobile_clicks'] = $data['desktop_clicks'] = $data['tablet_clicks'] = $data['windows_clicks'] = $data['macos_clicks'] = $data['linux_clicks'] = $data['ios_clicks'] = $data['android_clicks'] =  0;
					$data['date'] = $dateToday;
					$ipInformation = $GLOBALS['CLA_INDEX']->getUserLocation();
					$data['user_ip'] = $ipInformation['ip'];
					$data['location'] = json_encode($ipInformation['location'], true);
					$GLOBALS['DB']->insert('registerusers_analytics', $data);
					$sigLogoLinkAnalytics = $GLOBALS['DB']->row("SELECT * FROM `registerusers_analytics` WHERE url = ? AND user_id = ? AND date = ? AND signature_id = ? AND analytic_type='logoclick' LIMIT 0,1",array($sRow['signature_link'],$GLOBALS['USERID'],$dateToday,$sRow['signature_id']));
					if($sigLogoLinkAnalytics){
						$GLOBALS['signature_link'] = $GLOBALS['ROOT_LINK'].'/r/'.$sigLogoLinkAnalytics['id'];
					}
				}
			}
			$GLOBALS['profile_image_size'] = $sRow['profile_image_size'];

			if(is_null($GLOBALS['signature_profilesize'])){
				$GLOBALS['signature_profilesize'] = $GLOBALS['profile_image_size'];
			}
			// For Profile Animation Gif

			$profileAnimationArr = $GLOBALS["signature_profileanimation1"];
			$gifName = $GLOBALS['signature_profileanimation1_signature_profileanimation_gif_name'];
			if(is_null($gifName) || $gifName == ""){
				$signature_profileanimation_gif_src = $GLOBALS['UPLOAD_LINK'] . "/signature/gifs/giphyy-1.gif";
			}else{
				$signature_profileanimation_gif_src = $GLOBALS['UPLOAD_LINK'] . "/signature/gifs/" . $gifName;
			}
			$GLOBALS['signature_profileanimation_gif_src'] =  $signature_profileanimation_gif_src;
			$GLOBALS['signature_profileanimation_gif_zindex'] = $GLOBALS['signature_profileanimation1_signature_profileanimation'] == 1 ? "1" : "-1";
			$GLOBALS['signature_profileanimation_maxheight'] = $GLOBALS['signature_profileanimation1_signature_profileanimation'] == 1 ? "0" : "inherit";
			$GLOBALS['signature_profileanimation_gif_display'] = $GLOBALS['signature_profileanimation1_signature_profileanimation'] == 1 ? "block" : "none";
			$GLOBALS['signature_profileanimationsize'] = $GLOBALS['signature_profileanimation1_signature_profileanimation'] == 1 ? $GLOBALS['signature_profilesize'] : 0;

			$GLOBALS['signature_firstname'] = '<span class="layout_firstname" style="font-weight:'.$GLOBALS['firstname_bold'].'; font-style:'.$GLOBALS['firstname_italic'].'; color:'.$GLOBALS['firstname_color'].'; font-size:'.$GLOBALS['firstname_fontsize'].'; white-space: nowrap;">'.$sRow['signature_firstname'].'</span>';
			$GLOBALS['signature_company'] = '<span class="layout_company" style="font-weight:'.$GLOBALS['company_bold'].'; font-style:'.$GLOBALS['company_italic'].'; color:'.$GLOBALS['company_color'].'; font-size:'.$GLOBALS['company_fontsize'].';">'.$sRow['signature_company'].'</span>';
			$GLOBALS['signature_jobtitle'] = '<span class="layout_jobtitle" style="font-weight:'.$GLOBALS['jobtitle_bold'].'; font-style:'.$GLOBALS['jobtitle_italic'].'; color:'.$GLOBALS['jobtitle_color'].'; font-size:'.$GLOBALS['jobtitle_fontsize'].';">'.$sRow['signature_jobtitle'].'</span>';



				if($sRow['signature_profile'] !=""){
					$GLOBALS['signature_profile'] = $GLOBALS['UPLOAD_LINK'].'/signature/profile/'.$sRow['signature_profile'];

					$filePath = 'upload-beta/signature/profile/'.$GLOBALS['USERID'].'/'.$sRow['signature_profile'];
					if (file_exists($filePath)) {
						$GLOBALS['signature_profile'] = $GLOBALS['UPLOAD_LINK'].'/signature/profile/'.$GLOBALS['USERID'].'/'.$sRow['signature_profile'];
					}
				}else{
					$GLOBALS['signature_profile'] = $GLOBALS['IMAGE_LINK'].'/images/profile-img1.png';
				}

				$stylesArr = unserialize($sRow['signature_style']);
				if($stylesArr['signature_profileanimation1']){
					if($stylesArr['signature_profileanimation1']['signature_profileanimation']){
						if($stylesArr['signature_profileanimation1']['signature_profileanimation_gif'] == '1'){
							$filename = $sRow['signature_profile'];
							$basename = pathinfo($filename, PATHINFO_FILENAME);
							$newFilename = $basename . '-square.gif';
							$signature_profileanimation_new_gif = $newFilename;
						}else if($stylesArr['signature_profileanimation1']['signature_profileanimation_gif'] == '2'){
							$filename = $sRow['signature_profile'];
							$basename = pathinfo($filename, PATHINFO_FILENAME);
							$newFilename = $basename . '-circle.gif';
							$signature_profileanimation_new_gif = $newFilename;
						}else{
							$filename = $sRow['signature_profile'];
							$basename = pathinfo($filename, PATHINFO_FILENAME);
							$newFilename = $basename . '-square.gif';
							$signature_profileanimation_new_gif = $newFilename;
						}
						$filePath = 'upload-beta/signature/profile/'.$GLOBALS['USERID'].'/'.$signature_profileanimation_new_gif;
						if (file_exists($filePath)) {
							$GLOBALS['signature_profile'] = $GLOBALS['UPLOAD_LINK'].'/signature/profile/'.$GLOBALS['USERID'].'/'.$signature_profileanimation_new_gif;
						}else{
							$filePath = 'upload-beta/signature/profile/'.$signature_profileanimation_new_gif;
							if (file_exists($filePath)) {
								$GLOBALS['signature_profile'] = $GLOBALS['UPLOAD_LINK'].'/signature/profile/'.$signature_profileanimation_new_gif;
							}else{
								$filePath = 'upload-beta/signature/profile/'.$GLOBALS['USERID'].'/'.$sRow['signature_profile'];
								if (file_exists($filePath)) {
									$GLOBALS['signature_profile'] = $GLOBALS['UPLOAD_LINK'].'/signature/profile/'.$GLOBALS['USERID'].'/'.$sRow['signature_profile'];
								}else{
									$GLOBALS['signature_profile'] = $GLOBALS['UPLOAD_LINK'].'/signature/profile/'.$sRow['signature_profile'];
								}
							}
						}
					}
				}
				/*
				$dateToday = date('Y-m-d');
				$sigProfileAnalytics = $GLOBALS['DB']->row("SELECT * FROM `registerusers_analytics` WHERE url = ? AND user_id = ? AND date = ? LIMIT 0,1",array($sRow['signature_profile'],$GLOBALS['USERID'],$dateToday));
				if($sigProfileAnalytics){
					$GLOBALS['signature_profile'] = $GLOBALS['ROOT_LINK'].'/r/'.$sigProfileAnalytics['id'].'/profile';
				}else{
					$existingData = $GLOBALS['DB']->row("SELECT * FROM `registerusers_analytics` WHERE url = ? AND user_id = ? LIMIT 0,1",array($sRow['signature_profile'],$GLOBALS['USERID']));
					if($existingData){
						$data['user_id'] = $existingData['user_id'];
						$data['signature_id'] = $existingData['signature_id'];
						$data['url'] = $existingData['url'];
						$data['analytic_type'] = $existingData['analytic_type'];
						$data['impressions'] = $data['clicks'] = $data['mobile_clicks'] = $data['desktop_clicks'] = $data['tablet_clicks'] = $data['windows_clicks'] = $data['macos_clicks'] = $data['linux_clicks'] = $data['ios_clicks'] = $data['android_clicks'] =  0;
						$data['date'] = $dateToday;
						$GLOBALS['DB']->insert('registerusers_analytics', $data);
						$sigProfileAnalytics = $GLOBALS['DB']->row("SELECT * FROM `registerusers_analytics` WHERE url = ? AND user_id = ? AND date = ? LIMIT 0,1",array($sRow['signature_profile'],$GLOBALS['USERID'],$dateToday));
						if($sigProfileAnalytics){
							$GLOBALS['signature_profile'] = $GLOBALS['ROOT_LINK'].'/r/'.$sigProfileAnalytics['id'].'/profile';
						}
					}
				}
				*/
				
				if($sRow['signature_banner'] !=""){
					$GLOBALS['signature_banner'] = $GLOBALS['UPLOAD_LINK'].'/signature/banner/'.$sRow['signature_banner'];
				}else{
					$GLOBALS['signature_banner'] = '';
				}
				$dateToday = date('Y-m-d');
				$userIp = $GLOBALS['CLA_INDEX']->getUserIP();
				$sigBannerAnalytics = $GLOBALS['DB']->row("SELECT * FROM `registerusers_analytics` WHERE url = ? AND user_id = ? AND signature_id = ? AND date = ? AND analytic_type='banner' AND user_ip = ? LIMIT 0,1",array($sRow['signature_banner'],$GLOBALS['USERID'],$sRow['signature_id'],$dateToday,$userIp));
				if($sigBannerAnalytics){
					$GLOBALS['signature_banner'] = $GLOBALS['ROOT_LINK'].'/r/'.$sigBannerAnalytics['id'].'/banner';
				}else{
					$existingData = $GLOBALS['DB']->row("SELECT * FROM `registerusers_analytics` WHERE url = ? AND user_id = ? AND signature_id = ? AND analytic_type='banner' LIMIT 0,1",array($sRow['signature_banner'],$GLOBALS['USERID'],$sRow['signature_id']));
					if($existingData){
						$data['user_id'] = $existingData['user_id'];
						$data['signature_id'] = $existingData['signature_id'];
						$data['url'] = $existingData['url'];
						$data['analytic_type'] = $existingData['analytic_type'];
						$data['impressions'] = $data['clicks'] = $data['mobile_clicks'] = $data['desktop_clicks'] = $data['tablet_clicks'] = $data['windows_clicks'] = $data['macos_clicks'] = $data['linux_clicks'] = $data['ios_clicks'] = $data['android_clicks'] =  0;
						$data['date'] = $dateToday;
						$ipInformation = $GLOBALS['CLA_INDEX']->getUserLocation();
						$data['user_ip'] = $ipInformation['ip'];
						$data['location'] = json_encode($ipInformation['location'], true);
						$GLOBALS['DB']->insert('registerusers_analytics', $data);
						$sigBannerAnalytics = $GLOBALS['DB']->row("SELECT * FROM `registerusers_analytics` WHERE url = ? AND user_id = ? AND signature_id = ? AND date = ? AND analytic_type='banner' LIMIT 0,1",array($sRow['signature_banner'],$GLOBALS['USERID'],$sRow['signature_id'],$dateToday));
						if($sigBannerAnalytics){
							$GLOBALS['signature_banner'] = $GLOBALS['ROOT_LINK'].'/r/'.$sigBannerAnalytics['id'].'/banner';
						}
					}
				}
				$GLOBALS['signature_bannerlink'] = $sRow['signature_bannerlink'] != "" ? addhttp($sRow['signature_bannerlink']) : 'javascript:void(0);';						
				
				$dateToday = date('Y-m-d');
				$userIp = $GLOBALS['CLA_INDEX']->getUserIP();
				$sigBannerClickAnalytics = $GLOBALS['DB']->row("SELECT * FROM `registerusers_analytics` WHERE url = ? AND user_id = ? AND date = ? AND signature_id = ? AND analytic_type='bannerclick' AND user_ip = ? LIMIT 0,1",array($sRow['signature_bannerlink'],$GLOBALS['USERID'],$dateToday,$sRow['signature_id'],$userIp));
				if($sigBannerClickAnalytics){
					$GLOBALS['signature_bannerlink'] = $GLOBALS['ROOT_LINK'].'/r/'.$sigBannerClickAnalytics['id'];
				}else{
					$existingData = $GLOBALS['DB']->row("SELECT * FROM `registerusers_analytics` WHERE url = ? AND user_id = ? AND signature_id = ? AND analytic_type='bannerclick' LIMIT 0,1",array($sRow['signature_bannerlink'],$GLOBALS['USERID'],$sRow['signature_id']));
					if($existingData){
						$data['user_id'] = $existingData['user_id'];
						$data['signature_id'] = $existingData['signature_id'];
						$data['url'] = $existingData['url'];
						$data['analytic_type'] = $existingData['analytic_type'];
						$data['impressions'] = $data['clicks'] = $data['mobile_clicks'] = $data['desktop_clicks'] = $data['tablet_clicks'] = $data['windows_clicks'] = $data['macos_clicks'] = $data['linux_clicks'] = $data['ios_clicks'] = $data['android_clicks'] =  0;
						$data['date'] = $dateToday;
						$ipInformation = $GLOBALS['CLA_INDEX']->getUserLocation();
						$data['user_ip'] = $ipInformation['ip'];
						$data['location'] = json_encode($ipInformation['location'], true);
						$GLOBALS['DB']->insert('registerusers_analytics', $data);
						$sigBannerClickAnalytics = $GLOBALS['DB']->row("SELECT * FROM `registerusers_analytics` WHERE url = ? AND user_id = ? AND date = ? AND signature_id = ? AND analytic_type='bannerclick' LIMIT 0,1",array($sRow['signature_bannerlink'],$GLOBALS['USERID'],$dateToday,$sRow['signature_id']));
						if($sigBannerClickAnalytics){
							$GLOBALS['signature_bannerlink'] = $GLOBALS['ROOT_LINK'].'/r/'.$sigBannerClickAnalytics['id'];
						}
					}
				}
				if($GLOBALS['current_department_id'] != '0'){
					
					$bannerCampaign = $GLOBALS['DB']->row("SELECT * FROM banner_campaign WHERE user_id=? AND department_id LIKE ? AND is_paused='false' AND campaign_status!='draft' AND start_date <= NOW() AND NOW() <= end_date LIMIT 0,1",array($GLOBALS['USERID'],'%' . $GLOBALS['current_department_id'] . '%'));
					if(is_array($bannerCampaign) && $bannerCampaign['start_date'] <= date('Y-m-d H:i:s') && date('Y-m-d H:i:s') <= $bannerCampaign['end_date'] && $bannerCampaign['campaign_status'] != 'canceled'){
						$GLOBALS['signature_banner'] = $GLOBALS['UPLOAD_LINK']."/bannercampaign/".$bannerCampaign['banner_name'];
						$dateToday = date('Y-m-d');
						$userIp = $GLOBALS['CLA_INDEX']->getUserIP();
						$sigBannerAnalytics = $GLOBALS['DB']->row("SELECT * FROM `registerusers_analytics` WHERE url = ? AND user_id = ? AND date = ? AND signature_id = ? AND user_ip = ? LIMIT 0,1",array($bannerCampaign['banner_name'],$GLOBALS['USERID'],$dateToday,$sRow['signature_id'],$userIp));
						if($sigBannerAnalytics){
							$GLOBALS['signature_banner'] = $GLOBALS['ROOT_LINK'].'/r/'.$sigBannerAnalytics['id'].'/banner';
						}else{
							$existingData = $GLOBALS['DB']->row("SELECT * FROM `registerusers_analytics` WHERE url = ? AND user_id = ? AND signature_id = ? AND analytic_type='banner' LIMIT 0,1",array($bannerCampaign['banner_name'],$GLOBALS['USERID'],$sRow['signature_id']));
							if($existingData){
								$data['user_id'] = $existingData['user_id'];
								$data['signature_id'] = $existingData['signature_id'];
								$data['url'] = $existingData['url'];
								$data['analytic_type'] = $existingData['analytic_type'];
								$data['impressions'] = $data['clicks'] = $data['mobile_clicks'] = $data['desktop_clicks'] = $data['tablet_clicks'] = $data['windows_clicks'] = $data['macos_clicks'] = $data['linux_clicks'] = $data['ios_clicks'] = $data['android_clicks'] =  0;
								$data['date'] = $dateToday;
								$ipInformation = $GLOBALS['CLA_INDEX']->getUserLocation();
								$data['user_ip'] = $ipInformation['ip'];
								$data['location'] = json_encode($ipInformation['location'], true);
								$GLOBALS['DB']->insert('registerusers_analytics', $data);
								$sigBannerAnalytics = $GLOBALS['DB']->row("SELECT * FROM `registerusers_analytics` WHERE url = ? AND user_id = ? AND signature_id = ? AND date = ? LIMIT 0,1",array($bannerCampaign['banner_name'],$GLOBALS['USERID'],$sRow['signature_id'],$dateToday));
								if($sigBannerAnalytics){
									$GLOBALS['signature_banner'] = $GLOBALS['ROOT_LINK'].'/r/'.$sigBannerAnalytics['id'].'/banner';
								}
							}
						}

						$GLOBALS['signature_bannerlink'] = 'https://'.$bannerCampaign['banner_link'];
$sigBannerClickAnalytics = $GLOBALS['DB']->row("SELECT * FROM `registerusers_analytics` WHERE url = ? AND user_id = ? AND date = ? AND signature_id = ? AND analytic_type='bannerclick' AND user_ip = ? LIMIT 0,1",array($bannerCampaign['banner_link'],$GLOBALS['USERID'],$dateToday,$sRow['signature_id'],$userIp));
						if($sigBannerClickAnalytics){
							$GLOBALS['signature_bannerlink'] = $GLOBALS['ROOT_LINK'].'/r/'.$sigBannerClickAnalytics['id'];
						}else{
							$existingData = $GLOBALS['DB']->row("SELECT * FROM `registerusers_analytics` WHERE url = ? AND user_id = ? AND signature_id = ? AND analytic_type='bannerclick' LIMIT 0,1",array($sRow['signature_bannerlink'],$GLOBALS['USERID'],$sRow['signature_id']));
							if($existingData){
								$data['user_id'] = $existingData['user_id'];
								$data['signature_id'] = $existingData['signature_id'];
								$data['url'] = $existingData['url'];
								$data['analytic_type'] = $existingData['analytic_type'];
								$data['impressions'] = $data['clicks'] = $data['mobile_clicks'] = $data['desktop_clicks'] = $data['tablet_clicks'] = $data['windows_clicks'] = $data['macos_clicks'] = $data['linux_clicks'] = $data['ios_clicks'] = $data['android_clicks'] =  0;
								$data['date'] = $dateToday;
								$ipInformation = $GLOBALS['CLA_INDEX']->getUserLocation();
								$data['user_ip'] = $ipInformation['ip'];
								$data['location'] = json_encode($ipInformation['location'], true);
								$GLOBALS['DB']->insert('registerusers_analytics', $data);
								$sigBannerClickAnalytics = $GLOBALS['DB']->row("SELECT * FROM `registerusers_analytics` WHERE url = ? AND user_id = ? AND date = ? AND signature_id = ? AND analytic_type='bannerclick' LIMIT 0,1",array($bannerCampaign['banner_link'],$GLOBALS['USERID'],$dateToday,$sRow['signature_id']));
								if($sigBannerClickAnalytics){
									$GLOBALS['signature_bannerlink'] = $GLOBALS['ROOT_LINK'].'/r/'.$sigBannerClickAnalytics['id'];
								}
							}
						}
						$GLOBALS['signature_banner_display'] = "block"; 
						$GLOBALS['signature_bannersize'] = $bannerCampaign['banner_size'];
						$GLOBALS['signature_bannershape'] = $bannerCampaign['banner_shape'];
					}
				}

				$root_link = $GLOBALS['ROOT_LINK'];
				$usesignature_link = $GLOBALS['linkModuleUsesignature'].'/'.$signature_id;
				$editsignature_link =$GLOBALS['linkModuleEditsignature'].'/'.$signature_id;
				$sharesignature_link = $GLOBALS['linkModuleUsesignature'].'/install?uuid='.base64_encode($signature_id).'&u='.base64_encode($GLOBALS['USERID']);
				if($GLOBALS['current_department_id'] != '0'){
					$sharesignature_link = $GLOBALS['linkModuleUsesignature'].'/install?department_id='.$GLOBALS['current_department_id'].'&uuid='.base64_encode($signature_id).'&u='.base64_encode($GLOBALS['USERID']);
				}

				$cusbtnlink = $GLOBALS['IMAGE_LINK'].'/images/custome/'.$GLOBALS['signature_btndesign'];
				$cusbtnlink = $GLOBALS['IMAGE_LINK'].'/images/custome/'.$GLOBALS['signature_btndesign'];
				if($sRow['signature_custombtn'] == 'custome' && $GLOBALS['signature_custombtntext']!=""){
					if($sRow['signature_btndesign'] == 1){
						$cbtnstyle ="background: #f1f1f1; color:#333;";
					}else if($sRow['signature_btndesign'] == 2){
						$cbtnstyle ="background: #ffffff; color:#333; border:1px solid #000; border-radius','2px;";
					}else if($sRow['signature_btndesign'] == 3){
						$cbtnstyle ="background: #000000; color:#fff;";
					}else if($sRow['signature_btndesign'] == 4){
						$cbtnstyle ="background: none; color:#333;";
					}
					$sRow['signature_custombtnlink'] = $sRow['signature_custombtnlink'] =="" ? '#' : $sRow['signature_custombtnlink'];
					$userIp = $GLOBALS['CLA_INDEX']->getUserIP();
					$sigCustombtnLinkAnalytics = $GLOBALS['DB']->row("SELECT * FROM `registerusers_analytics` WHERE url = ? AND user_id = ? AND signature_id = ? AND date = ? AND user_ip = ? LIMIT 0,1",array($sRow['signature_custombtnlink'],$GLOBALS['USERID'],$sRow['signature_id'],$dateToday,$userIp));
					if($sigCustombtnLinkAnalytics){
						$sRow['signature_custombtnlink'] = $GLOBALS['ROOT_LINK'].'/r/'.$sigCustombtnLinkAnalytics['id'];
					}else{
						$existingData = $GLOBALS['DB']->row("SELECT * FROM `registerusers_analytics` WHERE url = ? AND user_id = ? AND signature_id = ? AND analytic_type='custombtnlink' LIMIT 0,1",array($sRow['signature_custombtnlink'],$GLOBALS['USERID'],$sRow['signature_id']));
						if($existingData){
							$data['user_id'] = $existingData['user_id'];
							$data['signature_id'] = $existingData['signature_id'];
							$data['url'] = $existingData['url'];
							$data['analytic_type'] = $existingData['analytic_type'];
							$data['impressions'] = $data['clicks'] = $data['mobile_clicks'] = $data['desktop_clicks'] = $data['tablet_clicks'] = $data['windows_clicks'] = $data['macos_clicks'] = $data['linux_clicks'] = $data['ios_clicks'] = $data['android_clicks'] =  0;
							$data['date'] = $dateToday;
							$ipInformation = $GLOBALS['CLA_INDEX']->getUserLocation();
							$data['user_ip'] = $ipInformation['ip'];
							$data['location'] = json_encode($ipInformation['location'], true);
							$GLOBALS['DB']->insert('registerusers_analytics', $data);
							$sigCustombtnLinkAnalytics = $GLOBALS['DB']->row("SELECT * FROM `registerusers_analytics` WHERE url = ? AND user_id = ? AND signature_id = ? AND date = ? LIMIT 0,1",array($sRow['signature_custombtnlink'],$GLOBALS['USERID'],$sRow['signature_id'],$dateToday));
							if($sigCustombtnLinkAnalytics){
								$sRow['signature_custombtnlink'] = $GLOBALS['ROOT_LINK'].'/r/'.$sigCustombtnLinkAnalytics['id'];
							}
						}
					}
					$GLOBALS['signature_customebtn'] = '<td class="layout-custombtn imagetopngClass" data-image-name="custombtn" style="display:flex;margin:0.1px;"><a href="'.addhttp($sRow['signature_custombtnlink']).'" target="_blank" style="'.$cbtnstyle.' text-align:center; padding:4px 15px; text-align:center; text-decoration:none; font-size:12px; line-height:14px; display:inline-block;">'.$GLOBALS['signature_custombtntext'].'</a></td>';
				}else{
						if($sRow['signature_custombtnanimation'] == 1){
							$cusbtnlink = $GLOBALS['IMAGE_LINK'].'/images/custome/animation/'.$sRow['signature_btndesign'];
						}else{
							$sRow['signature_custombtn'] = str_replace('.gif','.png',$sRow['signature_custombtn']);
							$cusbtnlink = $GLOBALS['IMAGE_LINK'].'/images/custome/static/'.$sRow['signature_btndesign'];
						}

						if($sRow['signature_custombtn'] != ""){
							$custome_btnimage = $cusbtnlink.'/'.$sRow['signature_custombtn'];
							$btnsize = $GLOBALS['signature_custombtnsize'] !="" ? $GLOBALS['signature_custombtnsize'] : 86;
							$userIp = $GLOBALS['CLA_INDEX']->getUserIP();
							$sigCustombtnLinkAnalytics = $GLOBALS['DB']->row("SELECT * FROM `registerusers_analytics` WHERE url = ? AND user_id = ? AND signature_id = ? AND date = ? AND user_ip = ? LIMIT 0,1",array($sRow['signature_custombtnlink'],$GLOBALS['USERID'],$sRow['signature_id'],$dateToday,$userIp));
							if($sigCustombtnLinkAnalytics){
								$sRow['signature_custombtnlink'] = $GLOBALS['ROOT_LINK'].'/r/'.$sigCustombtnLinkAnalytics['id'];
							}else{
								$existingData = $GLOBALS['DB']->row("SELECT * FROM `registerusers_analytics` WHERE url = ? AND user_id = ? AND signature_id = ? AND analytic_type='custombtnlink' LIMIT 0,1",array($sRow['signature_custombtnlink'],$GLOBALS['USERID'],$sRow['signature_id']));
								if($existingData){
									$data['user_id'] = $existingData['user_id'];
									$data['signature_id'] = $existingData['signature_id'];
									$data['url'] = $existingData['url'];
									$data['analytic_type'] = $existingData['analytic_type'];
									$data['impressions'] = $data['clicks'] = $data['mobile_clicks'] = $data['desktop_clicks'] = $data['tablet_clicks'] = $data['windows_clicks'] = $data['macos_clicks'] = $data['linux_clicks'] = $data['ios_clicks'] = $data['android_clicks'] =  0;
									$data['date'] = $dateToday;
									$ipInformation = $GLOBALS['CLA_INDEX']->getUserLocation();
									$data['user_ip'] = $ipInformation['ip'];
									$data['location'] = json_encode($ipInformation['location'], true);
									$GLOBALS['DB']->insert('registerusers_analytics', $data);
									$sigCustombtnLinkAnalytics = $GLOBALS['DB']->row("SELECT * FROM `registerusers_analytics` WHERE url = ? AND user_id = ? AND signature_id = ? AND date = ? LIMIT 0,1",array($sRow['signature_custombtnlink'],$GLOBALS['USERID'],$sRow['signature_id'],$dateToday));
									if($sigCustombtnLinkAnalytics){
										$sRow['signature_custombtnlink'] = $GLOBALS['ROOT_LINK'].'/r/'.$sigCustombtnLinkAnalytics['id'];
									}
								}
							}
							$GLOBALS['signature_customebtn'] ='<td class="layout-custombtn" style="margin:0.1px;"><a href="'.addhttp($sRow['signature_custombtnlink']).'" target="_blank"><img alt="" src="'.$custome_btnimage.'" width="'.$btnsize.'" class="scusbtn" style="display:block;" /></a></td>';
						}else{
							$GLOBALS['signature_customebtn'] ='';
						}
				}

				// CTA btn layout
				for ($cta = 1; $cta <= 3; $cta++) {
					if($sRow['signature_ctabtnlink'.$cta] != "" && $sRow['signature_ctabtnname'.$cta] != ""){
						$shape = $GLOBALS['signature_ctabtn'.$cta.'_shape'];
						$bgcolor = $GLOBALS['signature_ctabtn'.$cta.'_bgcolor'];
						$font = $GLOBALS['signature_ctabtn'.$cta.'_size'];
						$display = $GLOBALS['signature_ctabtn'.$cta.'_display'] == 1 ? 'inline-flex' : 'none';
						if($GLOBALS['signature_ctabtn'.$cta.'_icon'] != ""){
							$GLOBALS['signature_ctabtn'.$cta.'_icon'] = str_replace(".svg",".png",$GLOBALS['signature_ctabtn'.$cta.'_icon']);
							$btnicon = $GLOBALS['ROOT_LINK'].'/images/buttonicon/'.$GLOBALS['signature_ctabtn'.$cta.'_icon'];
							$ctabtnicon = '<td align="left" valign="middle" width="18" style="padding-right:10px;"><img src="'.$btnicon.'" width="18" /></td>';
						}else{
							$ctabtnicon = '';
						}
						$ctabtnlink = addhttp($sRow['signature_ctabtnlink'.$cta]);
						$userIp = $GLOBALS['CLA_INDEX']->getUserIP();
						$cta_analytic_type = "ctabtnlink".$cta;
						$sigCtabtnlinkAnalytics = $GLOBALS['DB']->row("SELECT * FROM `registerusers_analytics` WHERE url = ? AND user_id = ? AND signature_id = ? AND date = ? AND user_ip = ? AND analytic_type = ? LIMIT 0,1",array($sRow['signature_ctabtnlink'.$cta],$GLOBALS['USERID'],$sRow['signature_id'],$dateToday,$userIp,$cta_analytic_type));
						if($sigCtabtnlinkAnalytics){
							$ctabtnlink = $GLOBALS['ROOT_LINK'].'/r/'.$sigCtabtnlinkAnalytics['id'];
						}else{
							$existingData = $GLOBALS['DB']->row("SELECT * FROM `registerusers_analytics` WHERE url = ? AND user_id = ? AND signature_id = ? AND analytic_type = ? LIMIT 0,1",array($sRow['signature_ctabtnlink'.$cta],$GLOBALS['USERID'],$sRow['signature_id'],$cta_analytic_type));
							if($existingData){
								$data['user_id'] = $existingData['user_id'];
								$data['signature_id'] = $existingData['signature_id'];
								$data['url'] = $existingData['url'];
								$data['analytic_type'] = $existingData['analytic_type'];
								$data['impressions'] = $data['clicks'] = $data['mobile_clicks'] = $data['desktop_clicks'] = $data['tablet_clicks'] = $data['windows_clicks'] = $data['macos_clicks'] = $data['linux_clicks'] = $data['ios_clicks'] = $data['android_clicks'] =  0;
								$data['date'] = $dateToday;
								$ipInformation = $GLOBALS['CLA_INDEX']->getUserLocation();
								$data['user_ip'] = $ipInformation['ip'];
								$data['location'] = json_encode($ipInformation['location'], true);
								$GLOBALS['DB']->insert('registerusers_analytics', $data);
								$sigCtabtnlinkAnalytics = $GLOBALS['DB']->row("SELECT * FROM `registerusers_analytics` WHERE url = ? AND user_id = ? AND signature_id = ? AND date = ? AND analytic_type = ? LIMIT 0,1",array($sRow['signature_ctabtnlink'.$cta],$GLOBALS['USERID'],$sRow['signature_id'],$dateToday,$cta_analytic_type));
								if($sigCtabtnlinkAnalytics){
									$ctabtnlink = $GLOBALS['ROOT_LINK'].'/r/'.$sigCtabtnlinkAnalytics['id'];
								}
							}
						}
						$GLOBALS['signature_ctabtn'.$cta] = '<td style="border-collapse:collapse; padding:10px 5px 0 0; display:'.$display.';margin:0.1px;"><table border="0"  cellspacing="0" cellpadding="0"><tr><td class="imagetopngClass" data-image-name="ctabtn'.$cta.'" align="center" bgcolor="'.$bgcolor.'" style=" border-collapse:collapse;  background-color:'.$bgcolor.'; padding:4px 15px; line-height:10px; border-radius:'.$shape.';line-height: 0"><a href="'.$ctabtnlink.'" style="border-radius:'.$shape.'; background-color:'.$bgcolor.'; color:#ffffff; font-size:'.$font.'; display:'.$display.'; align-items:center; justify-content: center; text-decoration:none;"><table border="0" cellspacing="0" cellpadding="0"><tr>'.$ctabtnicon.'<td align="left" valign="middle" style="color:#ffffff;"><span style="text-decoration:none; color:#ffffff; line-height:18px; font-size:'.$font.';">'.$sRow['signature_ctabtnname'.$cta].'</span></td></tr></table></a></td></tr></table></td>';
					}else{
						$GLOBALS['signature_ctabtn'.$cta] ='';
					}
				}

				$iconsize = $GLOBALS['signature_socialsize'] !="" ? $GLOBALS['signature_socialsize'] : 30;
				$sociallink = $GLOBALS['IMAGE_LINK'].'/images/social/'.$GLOBALS['signature_socialdesign'];
				$GLOBALS['social_icons_arr'] = array(array('iconname'=>'web','label'=>'Website'),array('iconname'=>'insta','label'=>'Instagram'),array('iconname'=>'linkedin','label'=>'Linkdin'),array('iconname'=>'facebook','label'=>'Facebook'),array('iconname'=>'tiktok','label'=>'Tiktok'),array('iconname'=>'youtube','label'=>'Youtube'),array('iconname'=>'twitter','label'=>'Twitter'),array('iconname'=>'vimeo','label'=>'Vimeo'),array('iconname'=>'pintrest','label'=>'Pintrest'),array('iconname'=>'google','label'=>'Google'),array('iconname'=>'yelp','label'=>'Yelp'),array('iconname'=>'zillow','label'=>'Zillow'),array('iconname'=>'airbnb','label'=>'Airbnb'),array('iconname'=>'whatsapp','label'=>'Whatsapp'),array('iconname'=>'discord','label'=>'Discord'),array('iconname'=>'imbd','label'=>'imbd'),array('iconname'=>'ebay','label'=>'eBay'),array('iconname'=>'spotify','label'=>'Spotify'),array('iconname'=>'amazon','label'=>'Amazon'),array('iconname'=>'clendly','label'=>'Clendly'),array('iconname'=>'wechat','label'=>'Wechat'),array('iconname'=>'apple','label'=>'Apple'),array('iconname'=>'snapchat','label'=>'Snapchat'),array('iconname'=>'reddit','label'=>'Reddit'),array('iconname'=>'shopify','label'=>'Shopify'),array('iconname'=>'threads','label'=>'Threads'),array('iconname'=>'venmo','label'=>'Venmo'),array('iconname'=>'zelle','label'=>'Zelle'));

				$GLOBALS['marketplace_btn_arr'] = array(array('iconname'=>'appstorebtn','label'=>'App Store'),array('iconname'=>'playstorebtn','label'=>'Google Play Store'));
				$GLOBALS['signature_socialicons'] =''; $GLOBALS['signature_socialiconsv'] ='';
				foreach($GLOBALS['social_icons_arr'] as $icons){
					$iconname =  'signature_'.$icons['iconname'];
					$GLOBALS[$iconname] = $sRow[$iconname];
					$GLOBALS[$iconname.'_chk'] = $sRow[$iconname] != "" ? 'checked' : "";

					$iconsize = $GLOBALS['signature_socialsize'] !="" ? $GLOBALS['signature_socialsize'] : 30;
					if($sRow['signature_socialanimation'] == 1){
						$socialimg = $GLOBALS['IMAGE_LINK'].'/images/social/animation/'.$GLOBALS['signature_socialdesign'].'/'.$icons['iconname'].'-icon.gif';
					}else{
						$socialimg = $GLOBALS['IMAGE_LINK'].'/images/social/static/'.$GLOBALS['signature_socialdesign'].'/'.$icons['iconname'].'-icon.png';
					}
					$GLOBALS[$iconname.'_icon'] = $sRow[$iconname] !="" ? '<td style="padding:0 4px 0 0;margin:0.1px;" class="layout-'.$icons['iconname'].'-icon sicon"><a href="'.addhttp($GLOBALS[$iconname]).'" target="_blank"><img alt="" src="'.$socialimg.'" width="'.$iconsize.'" style="display:block;" /></a></td>' : '';
					$GLOBALS[$iconname.'_iconv'] = $sRow[$iconname] !="" ? '<tr><td style="padding:5px 0 0 0;margin:0.1px;" class="layout-'.$icons['iconname'].'-icon sicon"><a href="'.addhttp($GLOBALS[$iconname]).'" target="_blank"><img alt="" src="'.$socialimg.'" width="'.$iconsize.'" style="display:block;" /></a></td></tr>' : '';
					$userIp = $GLOBALS['CLA_INDEX']->getUserIP();
					$sigSocialLinkAnalytics = $GLOBALS['DB']->row("SELECT * FROM `registerusers_analytics` WHERE url = ? AND user_id = ? AND signature_id = ? AND date = ? AND user_ip = ? AND analytic_type = ? LIMIT 0,1",array(addhttp($GLOBALS[$iconname]),$GLOBALS['USERID'],$sRow['signature_id'],$dateToday,$userIp,$icons['iconname']));
					if($sigSocialLinkAnalytics){
						$analticsLink= $GLOBALS['ROOT_LINK'].'/r/'.$sigSocialLinkAnalytics['id'];
						$GLOBALS[$iconname.'_icon'] = $sRow[$iconname] !="" ? '<td style="padding:0 4px 0 0;margin:0.1px;" class="layout-'.$icons['iconname'].'-icon sicon"><a href="'.$analticsLink.'" target="_blank"><img alt="" src="'.$socialimg.'" width="'.$iconsize.'" style="display:block;" /></a></td>' : '';
						$GLOBALS[$iconname.'_iconv'] = $sRow[$iconname] !="" ? '<tr><td style="padding:5px 0 0 0;margin:0.1px;" class="layout-'.$icons['iconname'].'-icon sicon"><a href="'.$analticsLink.'" target="_blank"><img alt="" src="'.$socialimg.'" width="'.$iconsize.'" style="display:block;" /></a></td></tr>' : '';
					}else{
						$existingData = $GLOBALS['DB']->row("SELECT * FROM `registerusers_analytics` WHERE url = ? AND user_id = ? AND signature_id = ? AND analytic_type = ? LIMIT 0,1",array(addhttp($GLOBALS[$iconname]),$GLOBALS['USERID'],$sRow['signature_id'],$icons['iconname']));
						if($existingData){
							$data['user_id'] = $existingData['user_id'];
							$data['signature_id'] = $existingData['signature_id'];
							$data['url'] = $existingData['url'];
							$data['analytic_type'] = $existingData['analytic_type'];
							$data['impressions'] = $data['clicks'] = $data['mobile_clicks'] = $data['desktop_clicks'] = $data['tablet_clicks'] = $data['windows_clicks'] = $data['macos_clicks'] = $data['linux_clicks'] = $data['ios_clicks'] = $data['android_clicks'] =  0;
							$data['date'] = $dateToday;
							$ipInformation = $GLOBALS['CLA_INDEX']->getUserLocation();
							$data['user_ip'] = $ipInformation['ip'];
							$data['location'] = json_encode($ipInformation['location'], true);
							$GLOBALS['DB']->insert('registerusers_analytics', $data);
							$sigSocialLinkAnalytics = $GLOBALS['DB']->row("SELECT * FROM `registerusers_analytics` WHERE url = ? AND user_id = ? AND signature_id = ? AND date = ? AND analytic_type = ? LIMIT 0,1",array(addhttp($GLOBALS[$iconname]),$GLOBALS['USERID'],$sRow['signature_id'],$dateToday,$icons['iconname']));
							if($sigSocialLinkAnalytics){
								$analticsLink= $GLOBALS['ROOT_LINK'].'/r/'.$sigSocialLinkAnalytics['id'];
								$GLOBALS[$iconname.'_icon'] = $sRow[$iconname] !="" ? '<td style="padding:0 4px 0 0;margin:0.1px;" class="layout-'.$icons['iconname'].'-icon sicon"><a href="'.$analticsLink.'" target="_blank"><img alt="" src="'.$socialimg.'" width="'.$iconsize.'" style="display:block;" /></a></td>' : '';
								$GLOBALS[$iconname.'_iconv'] = $sRow[$iconname] !="" ? '<tr><td style="padding:5px 0 0 0;margin:0.1px;" class="layout-'.$icons['iconname'].'-icon sicon"><a href="'.$analticsLink.'" target="_blank"><img alt="" src="'.$socialimg.'" width="'.$iconsize.'" style="display:block;" /></a></td></tr>' : '';
							}
						}
					}
					$GLOBALS['signature_socialicons'] .= $GLOBALS[$iconname.'_icon'];
					$GLOBALS['signature_socialiconsv'] .= $GLOBALS[$iconname.'_iconv'];
				}

				//Hide custom cta button if no social icon selected and if layout_custom_with_social flag true
				if($GLOBALS['signature_socialiconsv'] == ''){
					if($sRow['layout_custom_with_social'] == '1' && $GLOBALS['signature_customebtn'] != ''){
						$GLOBALS['signature_social_border_display'] = 'compact';
						$GLOBALS['signature_between_gap_display'] = 'revert';
					}else{
						$GLOBALS['signature_social_border_display'] = 'none';
						$GLOBALS['signature_between_gap_display'] = 'none';
					}
				}else{
					$GLOBALS['signature_social_border_display'] = 'compact';
					$GLOBALS['signature_between_gap_display'] = 'revert';
				}

				// Remove padding from signature divider if it's off and layout_divider_padding_remove flag true
				if($sRow['layout_divider_padding_remove'] == '1'){
					if($GLOBALS['signature_divider'] == 1){
						$GLOBALS['signature_dividerpadding'] = '0 0 0 15px';
					} else{
						$GLOBALS['signature_dividerpadding'] = '0';
					}
				}else{
					$GLOBALS['signature_dividerpadding'] = '0 0 0 15px';
				}

				$GLOBALS['signature_marketplacebtns'] ='';
				$analticsMarketplaceButtons = '';
				foreach($GLOBALS['marketplace_btn_arr'] as $mbtn){
					$btnname =  'signature_'.$mbtn['iconname'];
					$GLOBALS[$btnname] = $sRow[$btnname];
					$GLOBALS[$btnname.'_chk'] = $sRow[$btnname] != "" ? 'checked' : "";

					$btnsize = $GLOBALS['signature_marketbtnsize'] !="" ? $GLOBALS['signature_marketbtnsize'] : 80;

					if($sRow['signature_marketbtnanimation'] == 1){
						$marketplaceimg = $GLOBALS['IMAGE_LINK'].'/images/marketplace/animation/'.$GLOBALS['signature_marketbtndesign'].'/'.$mbtn['iconname'].'-btn.gif';
					}else{
						$marketplaceimg = $GLOBALS['IMAGE_LINK'].'/images/marketplace/static/'.$GLOBALS['signature_marketbtndesign'].'/'.$mbtn['iconname'].'-btn.png';
					}
					$GLOBALS[$btnname.'_btn'] = $sRow[$btnname] !="" ? '<td style="padding:10px 4px 0 0;margin:0.1px;" class="layout-'.$mbtn['iconname'].'-btn mbtn"><a href="'.addhttp($GLOBALS[$btnname]).'" target="_blank"><img alt="" src="'.$marketplaceimg.'" width="'.$btnsize.'" /></a></td>' : '';

					$GLOBALS['signature_marketplacebtns'] .= $GLOBALS[$btnname.'_btn'];

					$sigMarketplaceLinkAnalytics = $GLOBALS['DB']->row("SELECT * FROM `registerusers_analytics` WHERE url = ? AND user_id = ? AND signature_id = ? AND date = ? AND user_ip = ? AND analytic_type = ? LIMIT 0,1",array(addhttp($GLOBALS[$btnname]),$GLOBALS['USERID'],$sRow['signature_id'],$dateToday,$userIp,$mbtn['iconname']));
					if($sigMarketplaceLinkAnalytics){
						$analticsLink= $GLOBALS['ROOT_LINK'].'/r/'.$sigMarketplaceLinkAnalytics['id'];
						$GLOBALS[$btnname.'_btn'] = $sRow[$btnname] !="" ? '<td style="padding:10px 4px 0 0;margin:0.1px;" class="layout-'.$mbtn['iconname'].'-btn mbtn"><a href="'.$analticsLink.'" target="_blank"><img alt="" src="'.$marketplaceimg.'" width="'.$btnsize.'" /></a></td>' : '';
						$analticsMarketplaceButtons .= $GLOBALS[$btnname.'_btn'];
					}else{
						$existingData = $GLOBALS['DB']->row("SELECT * FROM `registerusers_analytics` WHERE url = ? AND user_id = ? AND signature_id = ? AND analytic_type = ? LIMIT 0,1",array(addhttp($GLOBALS[$btnname]),$GLOBALS['USERID'],$sRow['signature_id'],$mbtn['iconname']));
						if($existingData){
							$data['user_id'] = $existingData['user_id'];
							$data['signature_id'] = $existingData['signature_id'];
							$data['url'] = $existingData['url'];
							$data['analytic_type'] = $existingData['analytic_type'];
							$data['impressions'] = $data['clicks'] = $data['mobile_clicks'] = $data['desktop_clicks'] = $data['tablet_clicks'] = $data['windows_clicks'] = $data['macos_clicks'] = $data['linux_clicks'] = $data['ios_clicks'] = $data['android_clicks'] =  0;
							$data['date'] = $dateToday;
							$ipInformation = $GLOBALS['CLA_INDEX']->getUserLocation();
							$data['user_ip'] = $ipInformation['ip'];
							$data['location'] = json_encode($ipInformation['location'], true);
							$GLOBALS['DB']->insert('registerusers_analytics', $data);
							$sigMarketplaceLinkAnalytics = $GLOBALS['DB']->row("SELECT * FROM `registerusers_analytics` WHERE url = ? AND user_id = ? AND signature_id = ? AND date = ? AND analytic_type = ? LIMIT 0,1",array(addhttp($GLOBALS[$btnname]),$GLOBALS['USERID'],$sRow['signature_id'],$dateToday,$mbtn['iconname']));
							if($sigMarketplaceLinkAnalytics){
								$analticsLink= $GLOBALS['ROOT_LINK'].'/r/'.$sigMarketplaceLinkAnalytics['id'];
								$GLOBALS[$btnname.'_btn'] = $sRow[$btnname] !="" ? '<td style="padding:10px 4px 0 0;margin:0.1px;" class="layout-'.$mbtn['iconname'].'-btn mbtn"><a href="'.$analticsLink.'" target="_blank"><img alt="" src="'.$marketplaceimg.'" width="'.$btnsize.'" /></a></td>' : '';
								$analticsMarketplaceButtons .= $GLOBALS[$btnname.'_btn'];
							}
						}
					}
				}
				if($analticsMarketplaceButtons != ""){
					$GLOBALS['signature_marketplacebtns'] = $analticsMarketplaceButtons;
				}

				$duplicate_btnstyle = $GLOBALS['plan_signaturelimit'] == 1 ? 'inline' : 'none';
				$master_linkstyle = $sRow['signature_master'] == 1 ? 'none' : 'inline';
				$master_btn = $sRow['signature_master'] == 1 ? '<span class="kt-badge kt-badge-primary bg-gradient">Master</span>' : '';
				$delete_chk = $sRow['signature_master'] == 0 ? '<input type="checkbox" name="bulkdelete[]" value="'.$signature_id.'" class="kt-checkbox del_chekbox" title="Delete">' : '';

				$intROw = $GLOBALS['DB']->row("SELECT count(*) connected FROM registerusers_token WHERE user_id = ? AND token_platform = 0",array($GLOBALS['USERID']));
				$msconnected = $intROw['connected'];
				$isMsConnected = false;
				if($msconnected > 0){
					$isMsConnected =true;
				}else{
					$isMsConnected =false;
				}

				$intROwGsuite = $GLOBALS['DB']->row("SELECT count(*) connected FROM registerusers_token WHERE user_id = ? AND token_platform = 1",array($GLOBALS['USERID']));
				$gsuiteconnected = $intROwGsuite['connected'];
				$isGSuiteConnected = false;
				if($gsuiteconnected > 0){
					$isGSuiteConnected =true;
				}else{
					$isGSuiteConnected =false;
				}
				if($sRow['signature_master'] == 1){
				$GLOBALS['master_signature'] = '<div class="inline-block mx-auto masterSignature relative">
				 		 <div class="size-11 rounded-full p-[2px] bg-gradient absolute top-0 -translate-y-1/2 -translate-x-1/2 left-1/2 z-[1]">
							<div class="size-full rounded-full bg-white flex items-center justify-center">
								<img src="'.$GLOBALS['IMAGE_LINK'].'/images/crown.svg" alt="">
							</div>
						 </div>
					<div class="top_button_check_box !hidden">
				        <div class="installed_pending_btn">';
							$GLOBALS['master_signature'] .= $master_btn;
							if($isMsConnected && $sRow['signature_importplatform'] == '1' && $sRow['is_deploy']){
							$GLOBALS['master_signature'] .= '<span class="installed">Installed</span>';
							} else if($isMsConnected && $sRow['signature_importplatform'] == '1' ){
							$GLOBALS['master_signature'] .= '<span class="pending">Pending</span>';
							}
							if($isGSuiteConnected && $sRow['signature_importplatform'] == '2' && $sRow['is_deploy']){
							$GLOBALS['master_signature'] .= '<span class="installed">Installed</span>';
							} else if($isGSuiteConnected && $sRow['signature_importplatform'] == '2' ){
							$GLOBALS['master_signature'] .= '<span class="pending">Pending</span>';
							}

						$GLOBALS['master_signature'] .= '</div>
							
						<div class="right_side_check">
							'.$delete_chk.'
							<i class="text-xxl text-gray-600 px-1 far fa-ellipsis-v cursor-pointer" data-kt-collapse="#myDIV'.$signature_id.'master"></i>
							<div id="myDIV'.$signature_id.'master" class="absolute inset-0 bg-white/90 hidden z-[2]">
								<i class="fal fa-times-circle cursor-pointer absolute top-2 right-2" data-kt-collapse="#myDIV'.$signature_id.'master"></i>
								<div class="flex flex-col gap-3 items-center justify-center h-full">
									<div class="flex gap-2">';
										if($GLOBALS['current_department_id'] != '0'){
										$GLOBALS['master_signature'] .= '<a class="kt-btn kt-btn-icon kt-btn-primary kt-btn-outline" href="'.$editsignature_link.'?department_id='.$GLOBALS['current_department_id'].'" data-toggle="tooltip" data-placement="top" title="Edit">
											<i class="hgi hgi-stroke hgi-pencil-edit-01"></i>
										</a>';
										}else{
										$GLOBALS['master_signature'] .= '<a  class="kt-btn kt-btn-icon kt-btn-primary kt-btn-outline" href="'.$editsignature_link.'" data-toggle="tooltip" data-placement="top" title="Edit">
											<i class="hgi hgi-stroke hgi-pencil-edit-01"></i>
										</a>';
										}

										$GLOBALS['master_signature'] .= '<a href="javascript:void(0);"  class="kt-btn kt-btn-icon kt-btn-primary kt-btn-outline ajaxaction" data-id="'.$signature_id.'" data-action="duplicate" data-toggle="tooltip" data-placement="top" title="Duplicate">
											<i class="hgi hgi-stroke hgi-copy-02"></i>
										</a>
										<a href="javascript:void(0);"  class="kt-btn kt-btn-icon kt-btn-primary kt-btn-outline ajaxaction" data-id="'.$signature_id.'" data-action="remove-master" data-toggle="tooltip" data-placement="top" title="Remove Master">
											<i class="hgi hgi-stroke hgi-user"></i>
										</a>
										<a  class="kt-btn kt-btn-icon kt-btn-primary kt-btn-outline" href="javascript:void(0);" id="Share-signature" data-url="'.$sharesignature_link.'" data-id="'.$signature_id.'" data-email="'.$GLOBALS['sigshare_email1'].'" data-bs-toggle="modal" data-bs-target="#shareModal" data-toggle="tooltip" data-placement="top" title="Share">
											<i class="hgi hgi-stroke hgi-sent"></i>
										</a>
										<a  class="kt-btn kt-btn-icon kt-btn-primary kt-btn-outline delete ajaxaction" href="javascript:void(0);" data-id="'.$signature_id.'" data-action="delete" data-toggle="tooltip" data-placement="top" title="Delete">
											<i class="hgi hgi-stroke hgi-delete-02"></i>
										</a>
									</div>';

									if($GLOBALS['current_department_id'] != '0'){
										$GLOBALS['master_signature'] .= '<div class="text-center">
										<a href="'.$usesignature_link.'?department_id='.$GLOBALS['current_department_id'].'"  class="kt-btn kt-btn-primary">
											<i class="hgi hgi-stroke hgi-sharp hgi-mail-edit-01"></i> Use Signature
										</a></div>';
									}else{
										$GLOBALS['master_signature'] .= '<div class="text-center">
										<a href="'.$usesignature_link.'" class="kt-btn kt-btn-primary">
											<i class="hgi hgi-stroke hgi-sharp hgi-mail-edit-01"></i> Use Signature
										</a></div>';
									}
								$GLOBALS['master_signature'] .= '</div>
							</div>
						</div>	
					</div>
					
					<div class="flex justify-center border border-gray-200 p-3 rounded-lg bg-white"><div class="max-h-[190px] overflow-hidden">'.$GLOBALS['CLA_HTML']->addContent($sRow['layout_desc']).'</div></div>
					<div class="sigshadow_box"></div>
				</div>';

				}
				// $analyticsData = $GLOBALS['DB']->row("SELECT COALESCE(SUM(RA.clicks)) as clicks,COALESCE(SUM(RA.impressions)) as impressions FROM `registerusers_analytics` RA WHERE RA.analytic_type IN ('logo', 'logoclick') AND RA.signature_id = ? AND RA.user_id = ?",array($signature_id,$GLOBALS['USERID']));
				$analyticsDataClick = $GLOBALS['DB']->row("SELECT COALESCE(SUM(RA.clicks)) as clicks FROM `registerusers_analytics` RA WHERE RA.signature_id = ? AND RA.user_id = ?",array($signature_id,$GLOBALS['USERID']));
				$analyticsDataView = $GLOBALS['DB']->row("SELECT COALESCE(SUM(RA.impressions)) as impressions FROM `registerusers_analytics` RA WHERE RA.signature_id = ? AND RA.user_id = ?",array($signature_id,$GLOBALS['USERID']));
				$clicks = 0;
				$impressions = 0;
				if($analyticsDataClick){
					$clicks = isset($analyticsDataClick["clicks"]) ? (int)$analyticsDataClick["clicks"] : 0;
				}
				if($analyticsDataView){
					$impressions = isset($analyticsDataView["impressions"]) ? (int)$analyticsDataView["impressions"] : 0;
				}
				
				$signature_type = "normal";
				if($isMsConnected && $sRow['signature_importplatform'] == '1' && $sRow['is_deploy']){
					$signature_type = "installed";
				} else if($isMsConnected && $sRow['signature_importplatform'] == '1' ){	
					$signature_type = "pending";
				}
				if($isGSuiteConnected && $sRow['signature_importplatform'] == '2' && $sRow['is_deploy']){
					$signature_type = "installed";
				} else if($isGSuiteConnected && $sRow['signature_importplatform'] == '2' ){
					$signature_type = "installed";
				}
					$GLOBALS['signature_list'] .= '<div class="search-container all '.$signature_type.'">
						<div class="kt-card kt-card-accent h-full relative sin_dashboard_box">
							<div class="kt-card-header">
								<div class="top_left_side_list">
									<div class="flex items-center gap-1 text-lg">
										<i class="text-gray-500 hgi hgi-stroke hgi-cursor-pointer-01 text-xl leading-none"></i>
										<span>'.$clicks.'</span>
									</div>
									<div class="flex items-center gap-1 text-lg">
										<i class="text-gray-500 hgi hgi-stroke hgi-view text-xl leading-none"></i>
										<span>'.$impressions.'</span>
									</div>
								</div>
								<div class="flex gap-2">';
									$GLOBALS['signature_list'] .= $master_btn;
									if($isMsConnected && $sRow['signature_importplatform'] == '1' && $sRow['is_deploy']){
										$GLOBALS['signature_list'] .= '<span class="kt-badge kt-badge-primary">Installed</span>';
									} else if($isMsConnected && $sRow['signature_importplatform'] == '1' ){	
										$GLOBALS['signature_list'] .= '<span class="kt-badge kt-badge-warning">Pending</span>';
									}
									if($isGSuiteConnected && $sRow['signature_importplatform'] == '2' && $sRow['is_deploy']){
										$GLOBALS['signature_list'] .= '<span class="kt-badge kt-badge-primary">Installed</span>';
									} else if($isGSuiteConnected && $sRow['signature_importplatform'] == '2' ){
										$GLOBALS['signature_list'] .= '<span class="kt-badge kt-badge-warning">Pending</span>';
									}
										
									if($sRow['signature_master'] == 1){
										$GLOBALS['signature_list'] .= '<div class="flex gap-2">';
											if($GLOBALS['ISSUBUSER'] && $GLOBALS['manage_signatures'] == ""){
												$GLOBALS['signature_list'] .= '
												<i class="text-xxl text-gray-600 px-1 far fa-ellipsis-v cursor-pointer" data-kt-collapse="#myDIV'.$signature_id.'"></i>
												<div id="myDIV'.$signature_id.'" class="absolute inset-0 bg-white/90 hidden z-[2]">
													<i class="fal fa-times-circle cursor-pointer absolute top-2 right-2" data-kt-collapse="#myDIV'.$signature_id.'"></i>
													<div class="flex flex-col gap-3 items-center justify-center h-full">
														<div class="flex gap-2">';
															if($GLOBALS['current_department_id'] != '0'){
																$GLOBALS['signature_list'] .= '<a class="kt-btn kt-btn-icon kt-btn-primary kt-btn-outline" href="'.$usesignature_link.'?department_id='.$GLOBALS['current_department_id'].'">
																	<i class="hgi hgi-stroke hgi-sharp hgi-mail-edit-01"></i> Use Signature
																</a>';
															}else{
																$GLOBALS['signature_list'] .= '<a class="kt-btn kt-btn-icon kt-btn-primary kt-btn-outline" href="'.$usesignature_link.'">
																	<i class="hgi hgi-stroke hgi-sharp hgi-mail-edit-01"></i> Use Signature
																</a>';
															}
															$GLOBALS['signature_list'] .= '<a href="javascript:void(0);" class="kt-btn kt-btn-icon kt-btn-primary kt-btn-outline ajaxaction" data-id="'.$signature_id.'" data-action="remove-master" data-toggle="tooltip" data-placement="top" title="Remove Master">
																<img src="'.$GLOBALS['IMAGE_LINK'].'/images/remove-master-icon.svg" alt="">
															</a>
														</div>
													</div>
												</div>';
											}else{
												$GLOBALS['signature_list'] .= $delete_chk.'
												<i class="text-xxl text-gray-600 px-1 far fa-ellipsis-v cursor-pointer" data-kt-collapse="#myDIV'.$signature_id.'"></i>
												<div id="myDIV'.$signature_id.'" class="absolute inset-0 bg-white/90 hidden z-[2]">
													<i class="fal fa-times-circle cursor-pointer absolute top-2 right-2" data-kt-collapse="#myDIV'.$signature_id.'"></i>
													<div class="flex flex-col gap-3 items-center justify-center h-full">
														<div class="flex gap-2">';
															if($GLOBALS['current_department_id'] != '0'){
																$GLOBALS['signature_list'] .= '<a class="kt-btn kt-btn-primary kt-btn-outline kt-btn-icon" href="'.$editsignature_link.'?department_id='.$GLOBALS['current_department_id'].'" data-toggle="tooltip" data-placement="top" title="Edit">
																<i class="hgi hgi-stroke hgi-edit-02"></i>
																</a>';
															}else{
																$GLOBALS['signature_list'] .= '<a class="kt-btn kt-btn-primary kt-btn-outline kt-btn-icon" href="'.$editsignature_link.'" data-toggle="tooltip" data-placement="top" title="Edit">
																<i class="hgi hgi-stroke hgi-edit-02"></i>
																</a>';
															}
															$GLOBALS['signature_list'] .= '<a href="javascript:void(0);" class="ajaxaction kt-btn kt-btn-primary kt-btn-outline kt-btn-icon" data-id="'.$signature_id.'" data-action="remove-master" data-toggle="tooltip" data-placement="top" title="Remove Master">
															<i class="hgi hgi-stroke hgi-user-remove-01"></i>
															</a>';

															$GLOBALS['signature_list'] .= '<a href="javascript:void(0);" class="ajaxaction kt-btn kt-btn-primary kt-btn-outline kt-btn-icon" data-id="'.$signature_id.'" data-action="duplicate" data-toggle="tooltip" data-placement="top" title="Duplicate">
															<i class="hgi hgi-stroke hgi-copy-02"></i>
															</a>
															<a class="kt-btn kt-btn-primary kt-btn-outline kt-btn-icon" href="javascript:void(0);" id="Share-signature" data-url="'.$sharesignature_link.'" data-id="'.$signature_id.'" data-email="'.$GLOBALS['sigshare_email1'].'" data-bs-toggle="modal" data-bs-target="#shareModal" data-toggle="tooltip" data-placement="top" title="Share">
															<i class="hgi hgi-stroke hgi-sent"></i>
															</a>
															<a href="javascript:void(0);" class="delete ajaxaction kt-btn kt-btn-primary kt-btn-outline kt-btn-icon" data-id="'.$signature_id.'" data-action="delete" data-toggle="tooltip" data-placement="top" title="Delete">
															<i class="hgi hgi-stroke hgi-delete-02"></i>
															</a>
														</div>
														<div class="text-center">';
															if($GLOBALS['current_department_id'] != '0'){
																$GLOBALS['signature_list'] .= '<a href="'.$usesignature_link.'?department_id='.$GLOBALS['current_department_id'].'" class="kt-btn kt-btn-primary">
																<i class="hgi hgi-stroke hgi-sharp hgi-mail-edit-01"></i> Use Signature</a>';
															}else{
																$GLOBALS['signature_list'] .= '<a href="'.$usesignature_link.'" class="kt-btn kt-btn-primary"><i class="hgi hgi-stroke hgi-sharp hgi-mail-edit-01"></i> Use Signature</a>';
															}
														$GLOBALS['signature_list'] .= '</div>
													</div>
												</div>';
											}
										$GLOBALS['signature_list'] .= '</div>';
									}
									else{
										$GLOBALS['signature_list'] .= '<div class="flex gap-2">';
											if($GLOBALS['ISSUBUSER'] && $GLOBALS['manage_signatures'] == ""){
												$GLOBALS['signature_list'] .= '
												<i class="text-xxl text-gray-600 px-1 far fa-ellipsis-v cursor-pointer" data-kt-collapse="#myDIV'.$signature_id.'"></i>
												<div id="myDIV'.$signature_id.'" class="absolute inset-0 bg-white/70 hidden z-[2]">
													<i class="fal fa-times-circle cursor-pointer absolute top-2 right-2" data-kt-collapse="#myDIV'.$signature_id.'"></i>
													<div class="flex flex-col gap-3 items-center justify-center h-full">
														<div class="flex gap-2">
															<div class="text-center">
																<a href="'.$usesignature_link.'?department_id='.$GLOBALS['current_department_id'].'" class="kt-btn kt-btn-primary">
																	<i class="hgi hgi-stroke hgi-sharp hgi-mail-edit-01"></i> Use Signature
																</a>
															</div>
														</div>
													</div>
												</div>';
											}else{
											$GLOBALS['signature_list'] .= $delete_chk.'
												<i class="text-xxl text-gray-600 px-1 far fa-ellipsis-v cursor-pointer" data-kt-collapse="#myDIV'.$signature_id.'"></i>
												<div id="myDIV'.$signature_id.'" class="absolute inset-0 bg-white/90 hidden z-[2]">
													<i class="fal fa-times-circle cursor-pointer absolute top-2 right-2" data-kt-collapse="#myDIV'.$signature_id.'"></i>
													<div class="flex flex-col gap-3 items-center justify-center h-full">
														<div class="flex gap-2">
															<a class="kt-btn kt-btn-icon kt-btn-primary kt-btn-outline" href="'.$editsignature_link.'?department_id='.$GLOBALS['current_department_id'].'" data-toggle="tooltip" data-placement="top" title="Edit">
																<i class="hgi hgi-stroke hgi-pencil-edit-01"></i>
															</a>
															<a class="kt-btn kt-btn-icon kt-btn-primary kt-btn-outline ajaxaction" href="javascript:void(0);" data-id="'.$signature_id.'" data-action="duplicate" data-toggle="tooltip" data-placement="top" title="Duplicate">
																<i class="hgi hgi-stroke hgi-copy-02"></i>
															</a>
															<span style="display:'.$master_linkstyle.'">
																<a class="kt-btn kt-btn-icon kt-btn-primary kt-btn-outline ajaxaction" href="javascript:void(0);" data-id="'.$signature_id.'" data-action="master" data-toggle="tooltip" data-placement="top" title="Master">
																	<i class="hgi hgi-stroke hgi-user"></i>
																</a>
															</span>
															<a class="kt-btn kt-btn-icon kt-btn-primary kt-btn-outline" href="javascript:void(0);" id="Share-signature" data-url="'.$sharesignature_link.'" data-id="'.$signature_id.'" data-email="'.$GLOBALS['sigshare_email1'].'" data-bs-toggle="modal" data-bs-target="#shareModal" data-toggle="tooltip" data-placement="top" title="Share">
																<i class="hgi hgi-stroke hgi-sent"></i>
															</a>
															<a class="kt-btn kt-btn-icon kt-btn-primary kt-btn-outline delete ajaxaction" href="javascript:void(0);" data-id="'.$signature_id.'" data-action="delete" data-toggle="tooltip" data-placement="top" title="Delete">
																<i class="hgi hgi-stroke hgi-delete-02"></i>
															</a>
														</div>
														<div class="text-center">
															<a href="'.$usesignature_link.'?department_id='.$GLOBALS['current_department_id'].'" class="kt-btn kt-btn-primary">
																<i class="hgi hgi-stroke hgi-sharp hgi-mail-edit-01"></i> Use Signature
															</a>
														</div>
													</div>
												</div>';
											}
										$GLOBALS['signature_list'] .= '</div>';
									}

								$GLOBALS['signature_list'] .= '</div>
							</div>
							<div class="kt-card-content">
								<div class="max-h-[190px] overflow-hidden">'
									.$GLOBALS['CLA_HTML']->addContent($sRow['layout_desc']).'
									<div class="sigshadow_box"></div>
								</div>
							</div>
						</div>
					</div>';
				

				$GLOBALS['signature_count']++;
		}
		if($GLOBALS['plan_signaturelimit'] == 1){
			$GLOBALS['signature_create_upgrade'] ='<div class="border-2 border-dashed border-gray-300 p-5 rounded-lg flex items-center justify-center">
						<a class="kt-btn kt-btn-primary" href="'.$GLOBALS['linkModuleNewsignature'].'">
						<i class="hgi hgi-stroke hgi-plus-sign-circle"></i>Create New Signature
						</a>
                    </div>';
		}else{
			$GLOBALS['signature_create_upgrade'] ='<div class="flex flex-col gap-4 items-center justify-center border-2 border-dashed border-gray-300 rounded-2xl">
  							<button class="kt-btn kt-btn-icon kt-btn-primary kt-btn-outline" onclick=redirectUrlWithAjax("'.$GLOBALS['linkModuleNewsignature'].'")>
								<i class="hgi hgi-stroke hgi-add-01"></i>
							</button>
							<p>Upgrade to create more signatures</p>
							<a class="kt-btn kt-btn-primary upgrade" href="%%DEFINE_billing%%">
								<i class="hgi hgi-stroke hgi-circle-arrow-up-02"></i> Upgrade
							</a>
                        </div>';
		}


		if($GLOBALS['current_department_id']){
			
            if($GLOBALS['plan_signaturelimit'] == 1){
				$GLOBALS['signature_create_upgrade'] ='<div class="flex flex-col gap-4 items-center justify-center border border-dashed border-gray-300 rounded-2xl">
  							<a href="'.$GLOBALS['newsignatureenterprise'].'?department_id='.$GLOBALS['current_department_id'].'">
							<span>
								<img src="%%DEFINE_IMAGE_LINK%%/images/new-adddepartment-icon.svg" alt="">
							</span>
							Create New Signature
							</a>
                    </div>';
			}else{
				$GLOBALS['signature_create_upgrade'] ='<div class="flex flex-col gap-4 items-center justify-center border border-dashed border-primary rounded-2xl bg-primary/5">
	  							<button class="kt-btn kt-btn-icon kt-btn-primary kt-btn-outline" onclick=redirectUrlWithAjax("'.$GLOBALS['linkModuleNewsignature'].'")>
									<i class="hgi hgi-stroke hgi-add-01"></i>
								</button>
								<p>Upgrade to create more signatures</p>
								<a class="kt-btn kt-btn-primary upgrade" href="%%DEFINE_billing%%">
									<i class="hgi hgi-stroke hgi-circle-arrow-up-02"></i> Upgrade
								</a>
	                    </div>';
			}
		}
		if($GLOBALS['manage_signatures'] == ""){
			$GLOBALS['signature_create_upgrade'] = "";
		}

		if($GLOBALS['master_signature'] == ''){
			$GLOBALS['master_signature'] = '<div class="create_new_signature_bg">
							<img src="%%DEFINE_IMAGE_LINK%%/images/plus-master-icon.svg" alt=""><br />
  							<span>Please Select the Master<br /> Signature</span>
                        </div>';
		}
		return $GLOBALS['CLA_HTML']->addContent($sRow['layout_desc']); // for use signature
	}

	public function getCustomField($signature_id){ // this function is different for edit and add
		$fields = $GLOBALS['DB']->query("SELECT * FROM signature_customfield WHERE signature_id= ? ORDER BY field_order ASC",array($signature_id));
		$field_count =0; $email_count =0;$phone_count=0; $text_count=0; $fax_count=0; $website_count=0; $address_count=0; $hyperlink_count=0; $disclaimer_count=0;
		$fieldspans = array('email','phone','text','fax','website','address','hyperlink','disclaimer');
		foreach($fieldspans as $span){
			for($i=1; $i <= 5; $i++){
				$GLOBALS['signature_cf_'.$span.'t'.$i]  = '';
				$GLOBALS['signature_cf_'.$span.$i] ='';
			}
		}
		$GLOBALS['signature_cf_email1'] ='';
		if(is_array($fields)){
			foreach($fields as $field){
				$fieldtype = $field['field_type'];
				$fieldlabelval = $field['field_label'];
				$fieldvalue = $field['field_value'];
				$fieldfontsize = $field['field_fontsize'];
				$fieldfontweight = $field['field_fontweight'];
				$fieldfontstyle = $field['field_fontstyle'];
				$fieldcolor = $field['field_color'];
				$fieldorder = $field['field_order'];
				
				$dateToday = date('Y-m-d');
				$userIp = $GLOBALS['CLA_INDEX']->getUserIP();

				${$fieldtype.'_count'} ++;
				$fieldno  = ${$fieldtype.'_count'};
				if($fieldtype == 'hyperlink'){
					$GLOBALS['signature_cf_'.$fieldtype.'t'.$fieldno] = '';
					$GLOBALS['signature_cf_'.$fieldtype.$fieldno] = '<a href="'.addhttp($fieldvalue).'" style="text-decoration:underline; font-weight:'.$fieldfontweight.'; font-style:'.$fieldfontstyle.'; color:'.$fieldcolor.'; font-size:'.$fieldfontsize.';">'.$fieldlabelval.'</a>';

				}else if($fieldtype == 'email'){
					$GLOBALS['signature_cf_'.$fieldtype.'t'.$fieldno] = '<span style="font-weight:'.$GLOBALS['label_bold'].'; font-style:'.$GLOBALS['label_italic'].'; color:'.$GLOBALS['label_color'].'; font-size:'.$GLOBALS['label_fontsize'].';">'.$fieldlabelval.'</span>';

					$analyticsFieldvalueEmail = "";
					$sigEmailAnalytics = $GLOBALS['DB']->row("SELECT * FROM `registerusers_analytics` WHERE url = ? AND user_id = ? AND signature_id = ? AND date = ? AND analytic_type='email' AND user_ip = ? LIMIT 0,1",array($fieldvalue,$GLOBALS['USERID'],$signature_id,$dateToday,$userIp));
					if($sigEmailAnalytics){
						$analyticsFieldvalueEmail = $GLOBALS['ROOT_LINK'].'/r/'.$sigEmailAnalytics['id'].'/email';
					}else{
						$existingData = $GLOBALS['DB']->row("SELECT * FROM `registerusers_analytics` WHERE url = ? AND user_id = ? AND signature_id = ? AND analytic_type='email' LIMIT 0,1",array($fieldvalue,$GLOBALS['USERID'],$signature_id));
						if($existingData){
							$data['user_id'] = $existingData['user_id'];
							$data['signature_id'] = $existingData['signature_id'];
							$data['url'] = $existingData['url'];
							$data['analytic_type'] = $existingData['analytic_type'];
							$data['impressions'] = $data['clicks'] = $data['mobile_clicks'] = $data['desktop_clicks'] = $data['tablet_clicks'] = $data['windows_clicks'] = $data['macos_clicks'] = $data['linux_clicks'] = $data['ios_clicks'] = $data['android_clicks'] =  0;
							$data['date'] = $dateToday;
							$ipInformation = $GLOBALS['CLA_INDEX']->getUserLocation();
							$data['user_ip'] = $ipInformation['ip'];
							$data['location'] = json_encode($ipInformation['location'], true);
							$GLOBALS['DB']->insert('registerusers_analytics', $data);
							$sigEmailAnalytics = $GLOBALS['DB']->row("SELECT * FROM `registerusers_analytics` WHERE url = ? AND user_id = ? AND signature_id = ? AND date = ? AND analytic_type='email' LIMIT 0,1",array($fieldvalue,$GLOBALS['USERID'],$signature_id,$dateToday));
							if($sigEmailAnalytics){
								$analyticsFieldvalueEmail = $GLOBALS['ROOT_LINK'].'/r/'.$sigEmailAnalytics['id'].'/email';
							}
						}
					}
					if($analyticsFieldvalueEmail) {
						$GLOBALS['signature_cf_'.$fieldtype.$fieldno] = '<a href="'.$analyticsFieldvalueEmail.'" style="font-weight:'.$fieldfontweight.'; font-style:'.$fieldfontstyle.'; color:'.$fieldcolor.'; font-size:'.$fieldfontsize.'; text-decoration:none;">'.$fieldvalue.'</a>';
					}else{
						$GLOBALS['signature_cf_'.$fieldtype.$fieldno] = '<a href="mailto:'.$fieldvalue.'" style="font-weight:'.$fieldfontweight.'; font-style:'.$fieldfontstyle.'; color:'.$fieldcolor.'; font-size:'.$fieldfontsize.'; text-decoration:none;">'.$fieldvalue.'</a>';
					}
					$GLOBALS['sigshare_email'.$fieldno] = $fieldvalue;
				}else if($fieldtype == 'website'){
					$GLOBALS['signature_cf_'.$fieldtype.'t'.$fieldno] = '<span style="font-weight:'.$GLOBALS['label_bold'].'; font-style:'.$GLOBALS['label_italic'].'; color:'.$GLOBALS['label_color'].'; font-size:'.$GLOBALS['label_fontsize'].';">'.$fieldlabelval.'</span>';
					$GLOBALS['signature_cf_'.$fieldtype.$fieldno] = '<a href="'.addhttp($fieldvalue).'" style="font-weight:'.$fieldfontweight.'; font-style:'.$fieldfontstyle.'; color:'.$fieldcolor.'; font-size:'.$fieldfontsize.'; text-decoration:none;">'.$fieldvalue.'</a>';
				}else if($fieldtype == 'phone'){
					$GLOBALS['signature_cf_'.$fieldtype.'t'.$fieldno] = '<span style="font-weight:'.$GLOBALS['label_bold'].'; font-style:'.$GLOBALS['label_italic'].'; color:'.$GLOBALS['label_color'].'; font-size:'.$GLOBALS['label_fontsize'].';">'.$fieldlabelval.'</span>';

					$analyticsFieldvaluePhone = "";
					$sigPhoneAnalytics = $GLOBALS['DB']->row("SELECT * FROM `registerusers_analytics` WHERE url = ? AND user_id = ? AND signature_id = ? AND date = ? AND analytic_type='phone' AND user_ip = ? LIMIT 0,1",array($fieldvalue,$GLOBALS['USERID'],$signature_id,$dateToday,$userIp));
					if($sigPhoneAnalytics){
						$analyticsFieldvaluePhone = $GLOBALS['ROOT_LINK'].'/r/'.$sigPhoneAnalytics['id'].'/phone';
					}else{
						$existingData = $GLOBALS['DB']->row("SELECT * FROM `registerusers_analytics` WHERE url = ? AND user_id = ? AND signature_id = ? AND analytic_type='phone' LIMIT 0,1",array($fieldvalue,$GLOBALS['USERID'],$signature_id));
						if($existingData){
							$data['user_id'] = $existingData['user_id'];
							$data['signature_id'] = $existingData['signature_id'];
							$data['url'] = $existingData['url'];
							$data['analytic_type'] = $existingData['analytic_type'];
							$data['impressions'] = $data['clicks'] = $data['mobile_clicks'] = $data['desktop_clicks'] = $data['tablet_clicks'] = $data['windows_clicks'] = $data['macos_clicks'] = $data['linux_clicks'] = $data['ios_clicks'] = $data['android_clicks'] =  0;
							$data['date'] = $dateToday;
							$ipInformation = $GLOBALS['CLA_INDEX']->getUserLocation();
							$data['user_ip'] = $ipInformation['ip'];
							$data['location'] = json_encode($ipInformation['location'], true);
							$GLOBALS['DB']->insert('registerusers_analytics', $data);
							$sigPhoneAnalytics = $GLOBALS['DB']->row("SELECT * FROM `registerusers_analytics` WHERE url = ? AND user_id = ? AND signature_id = ? AND date = ? AND analytic_type='phone' LIMIT 0,1",array($fieldvalue,$GLOBALS['USERID'],$signature_id,$dateToday));
							if($sigPhoneAnalytics){
								$analyticsFieldvaluePhone = $GLOBALS['ROOT_LINK'].'/r/'.$sigPhoneAnalytics['id'].'/phone';
							}
						}
					}
					if($analyticsFieldvaluePhone){
						$GLOBALS['signature_cf_'.$fieldtype.$fieldno] = '<a href="'.$analyticsFieldvaluePhone.'" style="font-weight:'.$fieldfontweight.'; font-style:'.$fieldfontstyle.'; color:'.$fieldcolor.'; font-size:'.$fieldfontsize.'; text-decoration:none;">'.$fieldvalue.'</a>';
					}else{
						$GLOBALS['signature_cf_'.$fieldtype.$fieldno] = '<a href="tel:'.$fieldvalue.'" style="font-weight:'.$fieldfontweight.'; font-style:'.$fieldfontstyle.'; color:'.$fieldcolor.'; font-size:'.$fieldfontsize.'; text-decoration:none;">'.$fieldvalue.'</a>';
					}

				}else{
					$GLOBALS['signature_cf_'.$fieldtype.'t'.$fieldno] = '<span style="font-weight:'.$GLOBALS['label_bold'].'; font-style:'.$GLOBALS['label_italic'].'; color:'.$GLOBALS['label_color'].'; font-size:'.$GLOBALS['label_fontsize'].';">'.$fieldlabelval.'</span>';
					$GLOBALS['signature_cf_'.$fieldtype.$fieldno] = '<span style="font-weight:'.$fieldfontweight.'; font-style:'.$fieldfontstyle.'; color:'.$fieldcolor.'; font-size:'.$fieldfontsize.';">'.$fieldvalue.'</span>';
				}

			}

			$field_count++;
		}


	}


	private function actionSignature($action,$id){
		$return_result = array();
		if($action == 'duplicate'){
			if($GLOBALS['plan_signaturelimit'] == 1){
				$result = $GLOBALS['DB']->row("SELECT * FROM signature  WHERE signature_id= ?",array($id));
				unset($result['signature_id']); //Remove ID from array
				unset($result['signature_master']);
				/*$qrystr = " INSERT INTO signature";
				$qrystr .= " ( " .implode(", ",array_keys($result)).") ";
				$qrystr .= " VALUES ('".implode("', '",array_values($result)). "')";
				$result = $GLOBALS['DB']->query($qrystr);*/
				$result = $GLOBALS['DB']->insert("signature",$result);
				if($result){
					$dubplicate_id = $GLOBALS['DB']->lastInsertId();
					$fields = $GLOBALS['DB']->query("SELECT * FROM signature_customfield WHERE signature_id= ? ORDER BY field_order ASC",array($id));
					if(is_array($fields)){
							foreach($fields as $field){
								$fielddata = array("signature_id"=>$dubplicate_id,"field_type"=>$field['field_type'],"field_label"=>$field['field_label'],"field_value"=>$field['field_value'],"field_fontsize"=>$field['field_fontsize'],"field_fontweight"=>$field['field_fontweight'],"field_fontstyle"=>$field['field_fontstyle'],"field_color"=>$field['field_color'],"field_order"=>$field['field_order']);
								$GLOBALS['DB']->insert("signature_customfield",$fielddata);
							}
					}
				}
				if($dubplicate_id){
					$this->getUserSignature($dubplicate_id);
					$return_result = array('error'=>0,'msg'=>'Success');
				}else{
					$return_result = array('error'=>1,'msg'=>'Somthing wrong try again');
				}
			}else{
					$return_result = array('error'=>1,'msg'=>'You have exceeded your plan limit');
			}
		}

		if($action == 'delete'){
			$delete = $GLOBALS['DB']->query("DELETE FROM signature WHERE `signature_id` = ?",array($id));
			if($delete){
				$GLOBALS['DB']->query("DELETE FROM `signature_customfield` WHERE `signature_id` = ?",array($id));
				$return_result = array('error'=>0,'msg'=>'Success');
			}else{
				$return_result = array('error'=>1,'msg'=>'Somthing wrong try again','signature'=>'');
			}
		}

		if($action == 'bulkdelete'){
			if($_POST['id']){
				$id = $_POST['id'];
				$delete = $GLOBALS['DB']->query("DELETE FROM signature WHERE `signature_id` IN (".$id.")");
				if($delete){
					$GLOBALS['DB']->query("DELETE FROM `signature_customfield` WHERE `signature_id` IN (".$id.")");
					$return_result = array('error'=>0,'msg'=>'Success');
				}else{
					$return_result = array('error'=>1,'msg'=>'Somthing wrong try again','signature'=>'');
				}
			}else{
				$return_result = array('error'=>0,'msg'=>'Somthing wrong try again','signature'=>'');
			}
		}

		if($action == 'status'){
			$update = $GLOBALS['DB']->update("signature_logo",array('logo_process'=>2,'logo_change_process'=>2),array('user_id'=>$GLOBALS['USERID']));
			if($update){
				$signature_logo = $GLOBALS['DB']->row("SELECT * FROM `signature_logo` WHERE user_id = ? ",array($GLOBALS['USERID']));
				if($signature_logo){
					if($signature_logo['logo_process'] == 2){
						$GLOBALS['DB']->update("registerusers_analytics",array('url'=>$signature_logo['logo_animation']),array('user_id'=>$GLOBALS['USERID'],'analytic_type'=>'logo'));
					}
				}
				$return_result = array('error'=>0,'msg'=>'Success');
			}else{
				$return_result = array('error'=>1,'msg'=>'Somthing wrong try again','signature'=>'');
			}
		}

		if($action == 'share'){
			$send_mail = false;
			$to = $_POST['share_email'];
			$shareurl = $_POST['share_url'];
			$GLOBALS['SHARE_SIGURL'] = $_POST['share_url'];
			$message= _getEmailTemplate('share_signature'); 	// send mail
			if(filter_var($to, FILTER_VALIDATE_EMAIL)){
				$send_mail = _SendMail($to,'',$GLOBALS['EMAIL_SUBJECT'],$message);
			}
			if($send_mail == true){
				$return_result = array('error'=>0,'msg'=>'Success');
			}else{
				$return_result = array('error'=>1,'msg'=>'Somthing wrong try again');
			}
		}
		
		if($action == 'master'){
			$user = $GLOBALS['DB']->row("SELECT * FROM `registerusers` WHERE user_id = ? ",array($GLOBALS['USERID']));
			if($user['user_type'] == "enterprise"){
				$department_id = $_POST['department_id'];
				$update = $GLOBALS['DB']->update("signature",array('signature_master'=>0),array('user_id'=>$GLOBALS['USERID'],'department_id'=>$department_id));
			}else{
				$update = $GLOBALS['DB']->update("signature",array('signature_master'=>0),array('user_id'=>$GLOBALS['USERID']));
			}
			$update = $GLOBALS['DB']->update("signature",array('signature_master'=>1),array('signature_id'=>$id));
			if($update){
				$return_result = array('error'=>0,'msg'=>'Success');
			}else{
				$return_result = array('error'=>1,'msg'=>'Somthing wrong try again','signature'=>'');
			}
		}

		if($action == 'remove-master'){
			$user = $GLOBALS['DB']->row("SELECT * FROM `registerusers` WHERE user_id = ? ",array($GLOBALS['USERID']));
			if($user['user_type'] == "enterprise"){
				$department_id = $_POST['department_id'];
				$update = $GLOBALS['DB']->update("signature",array('signature_master'=>0),array('user_id'=>$GLOBALS['USERID'],'department_id'=>$department_id));
			}else{
				$update = $GLOBALS['DB']->update("signature",array('signature_master'=>0),array('user_id'=>$GLOBALS['USERID']));
			}
			if($update){
				$return_result = array('error'=>0,'msg'=>'Success');
			}else{
				$return_result = array('error'=>1,'msg'=>'Somthing wrong try again','signature'=>'');
			}
		}
		return json_encode($return_result);
	}

	private function feedbackSignature($feedback,$id){
		if($feedback !="" && is_numeric($GLOBALS['USERID'])){

			$reason = $_POST["reason"];
			$logo_id = $_POST["logo_id"];
			$logoData = ['feedback' => $feedback,'user_id'=>$GLOBALS["USERID"], 'signature_logo_id' => $id, 'feedback_date' => date("d-m-Y")];
			$where = array('user_id'=>$GLOBALS['USERID']);
			$addfeedback = $GLOBALS['DB']->insert("signature_logo_feedback",$logoData);

			
			if($addfeedback){
				$update = $GLOBALS['DB']->update("signature_logo",array('logo_process'=>3),array('user_id'=>$GLOBALS['USERID']));
				$message= _getEmailTemplate('revision_request');
				$send_mail = _SendMail($GLOBALS['USEREMAIL'],'',$GLOBALS['EMAIL_SUBJECT'],$message);
				$return_result = array('error'=>0,'msg'=>'Success');
			}else{
				$return_result = array('error'=>1,'msg'=>$addfeedback);
			}
		}else{
			$return_result = array('error'=>1,'msg'=>'Somthing wrong try again');
		}
		return json_encode($return_result);
	}

	public function getlayoutStyle($action,$style=''){
		if($action == 'add'){
			if($_POST){
				$field_array = array('firstname','jobtitle','company');

				$stylearr['signature_fontfamily'] =  $_POST['signature_fontfamily'];
				$stylearr['signature_lineheight'] = $_POST['signature_lineheight'] == "" ? "14px" : $_POST['signature_lineheight'];
				$stylearr['signature_socialsize'] = $_POST['signature_socialsize'] == "" ? "30" : $_POST['signature_socialsize'];
				$stylearr['signature_custombtnsize'] = $_POST['signature_custombtnsize'] == "" ? "30" : $_POST['signature_custombtnsize'];
				$stylearr['signature_logosize'] = $_POST['signature_logosize'] == "" ? "100" : $_POST['signature_logosize'];
				$stylearr['profile_disply'] = $_POST['profile_disply'] == 1 ? "block" : "none";
				$stylearr['profile_container_disply'] = $_POST['profile_disply'] == 1 ? "revert" : "none";
				$stylearr['verified_display'] = $_POST['signature_verified'] == 1 ? "block" : "none";

				foreach($field_array as $field){
					$bold = $_POST['signature_'.$field.'_bold'] == 1 ? 'bold' : 'normal';
					$italic = $_POST['signature_'.$field.'_italic'] == 1 ? 'italic' : 'normal';
					$color = $_POST['signature_'.$field.'_color'] == "" ? "#8b8b8b" : $_POST['signature_'.$field.'_color'];
					$fontsize = $_POST['signature_'.$field.'_fontsize'] == "" ? "14px" : $_POST['signature_'.$field.'_fontsize'];
					$stylearr[$field] = array('bold'=>$bold,'italic'=>$italic,'color'=>$color,'fontsize'=>$fontsize,'fontfamily'=>$fontfamily,'lineheight'=>$lineheight);
				}
				return $stylearr;
			}else{
				return false;
			}
		}else{
			if($style){
				$GLOBALS['signature_logosize'] = 100;
				$GLOBALS['verified_display'] = 'none';
				$GLOBALS['signature_borderwidth'] = '0px';
				$GLOBALS['signature_dividerwidth'] = '1px';
				$GLOBALS['signature_bordercolor'] = '#E2E2E2'; $GLOBALS['signature_dividercolor'] = '#000000';
				$styles = unserialize($style);
				foreach($styles as $key1=>$cs){
					// signature css
					$GLOBALS[$key1] = $cs;
					$GLOBALS[$key1.'_'.$cs.'_sel'] = 'selected';
					// field css
					if(is_array($cs)){
						foreach($cs as $key => $value){
							$GLOBALS[$key1.'_'.$key] = $value;
							if($key != 'color'){
								if($value == 'bold' || $value =='italic'){
									$GLOBALS[$key1.'_'.$key.'_sel'] = 'checked';
								}else{
									$GLOBALS[$key1.'_'.$key.'_sel'] = '';
								}
							}
							if($key == 'fontsize'){
								$GLOBALS[$key1.'_'.$key.'_sel_'.$value] = 'selected';
							}
						}
					}
				}
				$GLOBALS['signature_borderwidth']  = $GLOBALS['signature_border'] == 0 ? '0px' : $GLOBALS['signature_borderwidth'];
				$GLOBALS['signature_dividerwidth']  = $GLOBALS['signature_divider'] == 0 ? '0px' : $GLOBALS['signature_dividerwidth'];
				$GLOBALS['signature_bordercolor'] =  $GLOBALS['signature_border'] == 0 ? '#ffffff' : $GLOBALS['signature_bordercolor'];
				$GLOBALS['signature_dividercolor'] =  $GLOBALS['signature_divider'] == 0 ? '#ffffff' : $GLOBALS['signature_dividercolor'];
				$GLOBALS['signature_borderpadding'] =  $GLOBALS['signature_border'] == 0 ? '0px' : '25px';
				$GLOBALS['profile_disply'] = $GLOBALS['profile_disply'] == "inline" ? "block" : "none";
				$GLOBALS['verified_display'] = $GLOBALS['verified_display'] == "inline" ? "block" : "none";
			}else{
				return false;
			}

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

	public function getTotalPlanSignature(){
		$totalRow = $GLOBALS['DB']->row("SELECT `plan_signaturelimit` as totalplansignature FROM `registerusers_subscription` WHERE `user_id` = ?",array($GLOBALS['USERID']));
		return $totalRow['totalplansignature'];
	}

	public function getTotalSignatureCreate(){
		$user = $GLOBALS['DB']->row("SELECT * FROM `registerusers` WHERE user_id = ? ",array($GLOBALS['USERID']));
		$totalRow = $GLOBALS['DB']->row("SELECT count(`signature_id`) as totalsignature FROM `signature` WHERE `user_id` = ?",array($GLOBALS['USERID']));
		return $totalRow['totalsignature'];
	}

	public function getTotalSignatureCreateByDepartment($departement_id){
		$totalRow = $GLOBALS['DB']->row("SELECT count(`signature_id`) as totalsignature FROM `signature` WHERE `user_id` = ?  AND `department_id` = ?",array($GLOBALS['USERID'],$departement_id));
		return $totalRow['totalsignature'];
	}

	// code for setting signature profile size if NULL or NAN START
	public function getSignatureSpecificLayoutProfileSize($layout_id) {
		$row = $GLOBALS['DB']->row("SELECT * FROM `signature_layout` WHERE layout_id= ?",array($layout_id));
		if(!isset($row['profile_image_size'])){	
			return 20; // returning 20px if layout specific profile size not set
		}else{
			if(is_null($row['profile_image_size']) || $row['profile_image_size']=='NaN'){
				return 20; // returning 20px if layout specific profile size is NULL,NAN
			}else{
				return $row['profile_image_size'];
			}
		}
	}
	// code for setting signature profile size if NULL or NAN END

}

?>



