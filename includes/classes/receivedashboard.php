<?php
class CIT_RECEIVEDASHBOARD
{

	public function __construct()
	{

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
		if (!is_dir(GetConfig('SITE_UPLOAD_PATH') . "/signature/".$GLOBALS['USERID'])) {
			if (!mkdir(GetConfig('SITE_UPLOAD_PATH')."/signature/".$GLOBALS['USERID'])) {
				$error = 1;
				die("\"temp\" folder not created. Permission problem.......");
			}
		}

		// create a signature store directory
		if (!is_dir(GetConfig('SITE_UPLOAD_PATH') . "/signature/complete/".$insert_id)) {
			if (!mkdir(GetConfig('SITE_UPLOAD_PATH')."/signature/complete/".$insert_id)) {
				die("\"temp\" folder not created. Permission problem.......");
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
					if(is_array(getimagesize($location))){
						$src = $GLOBALS['UPLOAD_LINK'].'/signature/'.$GLOBALS['USERID'].'/'.$filename ;
						$data =array('user_image'=>$filename); $where =array('user_id'=>$GLOBALS['USERID']);
					}
					$return_arr = array("name" => $filename,"displayname" => $displayname, "size" => $filesize, "src"=> $src, "error"=>0);
					$logoData = ['changed_logo' => $filename, 'logo_change_process'=>1];
					$where = array('user_id'=>$GLOBALS['USERID']);
					$GLOBALS['DB']->update("signature_logo",$logoData,$where);
				}
			}else{
				$return_arr = array("error" =>1, "msg"=>"please upload valid jpg, jpeg, png gif or svg image");
			}
			echo json_encode($return_arr); exit;
		}

		// if($GLOBALS['signature_count'] == 0){ // redirect welcome page
		// 	$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/welcome.html');
		// 	$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');
		// }else{
		// 	$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/dashboard.html');
		// 	$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');
		// }
		// $GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');
		// $GLOBALS['SIDEBAR'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.sidebar.html');
		$GLOBALS['CLA_HTML']->display();
		RemoveMessageInfo();
		exit();

	}

	public function getUserSigProcess(){
			$signature_logo = $GLOBALS['DB']->row("SELECT * FROM `signature_logo` WHERE user_id = ? ",array($GLOBALS['USERID']));
			$GLOBALS['signature_logo_id'] = $signature_logo['id'];
			if($GLOBALS['logo_process'] == 1){
					$GLOBALS['signature_process'] ='<div class="col-lg-4 col-md-6 col-12">
							<div class="sin_dashboard_box sin_process">
								<h2>'.$GLOBALS['USERNAME'].'!</h2>
								<h3>Your logo animation is ready!</h3>
								<p>kindly review it.</p>
								<div class="review_btn"><a class="btn btn-primary" id="reviewLogo" data-img="'.$GLOBALS['signature_image'].'" data-id="'.$GLOBALS['logo_id'].'" data-bs-toggle="modal" data-bs-target="#reviewModal">Review Logo</a></div>';
						if($signature_logo['logo_change_process'] != 2){
							$GLOBALS['signature_process'] .= '<div class="change_logo_btn">Did you mistakenly upload the wrong logo? Please <a class="text-primary cursor-pointer" id="change_signature_logo" data-img="'.$GLOBALS['signature_image'].'" data-id="'.$GLOBALS['logo_id'].'" data-bs-toggle="modal" data-bs-target="#changeLogoModel">click here.</a></div>';
						}
					$GLOBALS['signature_process'] .= '</div>
					                               </div>';
				}else if($GLOBALS['logo_process'] == 0 || $GLOBALS['logo_process'] == 3 ){
					$GLOBALS['signature_process'] ='<div class="col-lg-4 col-md-6 col-12">
							<div class="sin_dashboard_box sin_process">
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
						$GLOBALS['signature_process'] .= '<div class="change_logo_btn">Did you mistakenly upload the wrong logo? Please <a class="text-primary cursor-pointer" id="change_signature_logo" data-img="'.$GLOBALS['signature_image'].'" data-id="'.$GLOBALS['logo_id'].'" data-bs-toggle="modal" data-bs-target="#changeLogoModel">click here.</a></div>';
					}
					$GLOBALS['signature_process'] .= '</div>
						</div>';
				}
				elseif ($signature_logo['logo_change_process'] == 0 || $signature_logo['logo_change_process'] == 1) {
					$GLOBALS['signature_process'] ='<div class="col-lg-4 col-md-6 col-12">
							<div class="sin_dashboard_box sin_process">
								<h2>'.$GLOBALS['USERNAME'].'!</h2>
								<h3>Your Logo is Under Review</h3>';
					if($signature_logo['logo_change_process'] != 2){
						$GLOBALS['signature_process'] .= '<div class="change_logo_btn">Did you mistakenly upload the wrong logo? Please <a class="text-primary cursor-pointer" id="change_signature_logo" data-img="'.$GLOBALS['signature_image'].'" data-id="'.$GLOBALS['logo_id'].'" data-bs-toggle="modal" data-bs-target="#changeLogoModel">click here.</a></div>';
					}
					$GLOBALS['signature_process'] .= '</div>
						</div>';
				}
				elseif ($signature_logo['logo_change_process'] == 3) {
					$GLOBALS['signature_process'] ='<div class="col-lg-4 col-md-6 col-12">
							<div class="sin_dashboard_box sin_process">
								<h2>Your logo is not matching with our guideline.</h2>
								<ul>
								<li>'.$signature_logo['change_logo_reject_reason'].'</li>
								</ul>
								<div class="review_btn"><a class="btn btn-primary" id="change_signature_logo" data-url="'.$GLOBALS['UrlRewriteBase'].'dashboard" data-bs-toggle="modal" data-bs-target="#changeLogoModel">Reupload Logo<img src="'.$GLOBALS['IMAGE_LINK'].'/images/arrow-right.svg" alt=""></a></div></div>
						</div>';
				}
				elseif ($signature_logo['logo_change_process'] == 4 ) {
					$GLOBALS['signature_process'] ='<div class="col-lg-4 col-md-6 col-12">
							<div class="sin_dashboard_box sin_process">
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
						$GLOBALS['signature_process'] .= '<div class="change_logo_btn">Did you mistakenly upload the wrong logo? Please <a class="text-primary cursor-pointer" id="change_signature_logo" data-img="'.$GLOBALS['signature_image'].'" data-id="'.$GLOBALS['logo_id'].'" data-bs-toggle="modal" data-bs-target="#changeLogoModel">click here.</a></div>';
					}
					$GLOBALS['signature_process'] .= '</div>
						</div>';
				}
				elseif( $GLOBALS['logo_process'] == 4 ){
					$GLOBALS['signature_process'] ='<div class="col-lg-4 col-md-6 col-12">
							<div class="sin_dashboard_box sin_process">
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
						$GLOBALS['signature_process'] .= '<div class="change_logo_btn">Did you mistakenly upload the wrong logo? Please <a class="text-primary cursor-pointer" id="change_signature_logo" data-img="'.$GLOBALS['signature_image'].'" data-id="'.$GLOBALS['logo_id'].'" data-bs-toggle="modal" data-bs-target="#changeLogoModel">click here.</a></div>';
					}
					$GLOBALS['signature_process'] .= '</div>
						</div>';
				}
				if($GLOBALS['plan_type'] == 'FREE'){
					$GLOBALS['signature_process'] ='<div class="col-lg-4 col-md-6 col-12">
							<div class="sin_dashboard_box sin_process">
								<h2>'.$GLOBALS['USERNAME'].'!</h2>
								<h3>Your Free Trial period will end in '.$GLOBALS['freeperiod_dayleft'].' day!</h3>
								<p>kindly renew your account before end.</p>
								<div class="review_btn"><a class="btn btn-primary" href="'.$GLOBALS['billing'].'?action=freetrial">Animated Logo <img src="'.$GLOBALS['IMAGE_LINK'].'/images/arrow-right.svg" alt=""></a></div>

							</div>
						</div>';
				}
	}

	public function getUserSignature($signature_id=''){
		 $GLOBASL['signature_list'] ='';
		 if($userid !=""){ $GLOBALS['USERID'] = $userid; $this->getSignatureLogo();}
		 if($signature_id!=''){
			 $signature_lists = $GLOBALS['DB']->query("SELECT SG.*,SL.* FROM `signature` SG LEFT JOIN signature_layout SL ON SG.layout_id = SL.layout_id  WHERE SG.signature_status = 1 AND SG.signature_id = ? LIMIT 0,1",array($signature_id));
		 }else{
			 $totalplansignature = $this->getTotalPlanSignature();
			 // check for created signature count with purchase plan count
			 if($totalplansignature != $GLOBALS['total_sigcreated']){ 
				 $signature_lists = $GLOBALS['DB']->query("SELECT SG.*,SL.* FROM `signature` SG LEFT JOIN signature_layout SL ON SG.layout_id = SL.layout_id  WHERE SG.signature_status = 1 AND SG.user_id = ? ORDER BY FIELD(SG.signature_master,1) DESC,signature_id ASC LIMIT 0,".$totalplansignature."",array($GLOBALS['USERID']));
			 }else{
				 $signature_lists = $GLOBALS['DB']->query("SELECT SG.*,SL.* FROM `signature` SG LEFT JOIN signature_layout SL ON SG.layout_id = SL.layout_id  WHERE SG.signature_status = 1 AND SG.user_id = ? ORDER BY FIELD(SG.signature_master,1) DESC,signature_id DESC",array($GLOBALS['USERID']));
			 }
			
		 }
		$GLOBALS['signature_count'] =0;
		foreach($signature_lists as $sRow){
			$signature_id = $sRow['signature_id'];
			$this->getlayoutStyle('get',$sRow['signature_style']);
			$customfields = $this->getCustomField($signature_id);
			$GLOBALS['signature_socialdesign'] = $sRow['signature_socialdesign'];
			$GLOBALS['signature_btndesign'] = $sRow['signature_btndesign'];
			$GLOBALS['signature_marketbtndesign'] = $sRow['signature_marketbtndesign'];
			$GLOBALS['signature_custombtntext'] =  $sRow['signature_custombtntext'];
			$GLOBALS['signature_link'] =  $sRow['signature_link'] != "" ? addhttp($sRow['signature_link']) : 'javascript:void(0);';
			$dateToday = date('Y-m-d');
			$userIp = $GLOBALS['CLA_INDEX']->getUserIP();
			$sigLogoLinkAnalytics = $GLOBALS['DB']->row("SELECT * FROM `registerusers_analytics` WHERE url = ? AND user_id = ? AND date = ? AND signature_id = ? AND analytic_type='logoclick' AND user_ip = ? LIMIT 0,1",array($sRow['signature_link'],$GLOBALS['USERID'],$dateToday,$sRow['signature_id'],$userIp));
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
			$GLOBALS['signature_bannerlink'] = $sRow['signature_bannerlink'] != "" ? addhttp($sRow['signature_bannerlink']) : 'javascript:void(0);';

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

			$GLOBALS['signature_firstname'] = '<span class="layout_firstname" style="font-weight:'.$GLOBALS['firstname_bold'].'; font-style:'.$GLOBALS['firstname_italic'].'; color:'.$GLOBALS['firstname_color'].'; font-size:'.$GLOBALS['firstname_fontsize'].';">'.$sRow['signature_firstname'].'</span>';
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
				/*
				$dateToday = date('Y-m-d');
				$sigProfileAnalytics = $GLOBALS['DB']->row("SELECT * FROM `registerusers_analytics` WHERE url = ? AND user_id = ? AND date = ? LIMIT 0,1",array($sRow['signature_profile'],$GLOBALS['USERID']));
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
						$sigProfileAnalytics = $GLOBALS['DB']->row("SELECT * FROM `registerusers_analytics` WHERE url = ? AND user_id = ? AND date = ? LIMIT 0,1",array($sRow['signature_profile'],$GLOBALS['USERID']));
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
					$GLOBALS['banner_display'] = 'none';
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
				$dateToday = date('Y-m-d');
				$userIp = $GLOBALS['CLA_INDEX']->getUserIP();
				$sigBannerClickAnalytics = $GLOBALS['DB']->row("SELECT * FROM `registerusers_analytics` WHERE url = ? AND user_id = ? AND date = ? AND user_ip = ? AND analytic_type='bannerclick' LIMIT 0,1",array($sRow['signature_bannerlink'],$GLOBALS['USERID'],$dateToday,$userIp));
				if($sigBannerClickAnalytics){
					$GLOBALS['signature_bannerlink'] = $GLOBALS['ROOT_LINK'].'/r/'.$sigBannerClickAnalytics['id'];
				}else{
					$existingData = $GLOBALS['DB']->row("SELECT * FROM `registerusers_analytics` WHERE url = ? AND user_id = ? AND analytic_type='bannerclick' LIMIT 0,1",array($sRow['signature_bannerlink'],$GLOBALS['USERID']));
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
						$sigBannerClickAnalytics = $GLOBALS['DB']->row("SELECT * FROM `registerusers_analytics` WHERE url = ? AND user_id = ? AND date = ? AND analytic_type='bannerclick' LIMIT 0,1",array($sRow['signature_bannerlink'],$GLOBALS['USERID'],$dateToday));
						if($sigBannerClickAnalytics){
							$GLOBALS['signature_bannerlink'] = $GLOBALS['ROOT_LINK'].'/r/'.$sigBannerClickAnalytics['id'];
						}
					}
				}
				$root_link = $GLOBALS['ROOT_LINK'];
				$upload_link = $GLOBALS['UPLOAD_LINK'];
				$usesignature_link = $GLOBALS['linkModuleUsesignature'].'/'.$signature_id;
				$editsignature_link =$GLOBALS['linkModuleEditsignature'].'/'.$signature_id;
				$sharesignature_link = $GLOBALS['linkModuleUsesignature'].'/install?uuid='.base64_encode($signature_id).'&u='.base64_encode($GLOBALS['USERID']);
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
					// $GLOBALS['signature_customebtn'] = '<td class="layout-custombtn" style="display:flex;"><a href="'.addhttp($sRow['signature_custombtnlink']).'" target="_blank" style="'.$cbtnstyle.' text-align:center; padding:4px 15px; text-align:center; text-decoration:none; font-size:12px; line-height:14px; display:inline-block;">'.$GLOBALS['signature_custombtntext'].'</a></td>';
					$pathToImage = $upload_link."/htmltoimage/".$GLOBALS["USERID"]."/".$GLOBALS["SIGNATUREID"]."/custombtn.png";
					$imgSrc = "";
					if (!file_exists($pathToImage)) {
						$imgSrc = $pathToImage."?".rand(1000,9999);
					}
					list($width, $height) = getimagesize($imgSrc); 
					$imageWidth = $width/3;
					$imageHeight = $height/3;
					$GLOBALS['signature_customebtn'] = '<td class="layout-custombtn" style="display:flex;"><a href="'.addhttp($sRow['signature_custombtnlink']).'" target="_blank" ><img src="'.$imgSrc.'" style="width:'.$imageWidth.'px;height:'.$imageHeight.'px;"></a></td>';

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
							$GLOBALS['signature_customebtn'] ='<td class="layout-custombtn"><a href="'.addhttp($sRow['signature_custombtnlink']).'" target="_blank"><img alt="" src="'.$custome_btnimage.'" width="'.$btnsize.'" class="scusbtn" style="display:block;" /></a></td>';
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
							$btnicon = $GLOBALS['IMAGE_LINK'].'/images/buttonicon/'.$GLOBALS['signature_ctabtn'.$cta.'_icon'];
							$ctabtnicon = '<td align="left" valign="middle" width="18" style="padding-right:10px;"><img src="'.$btnicon.'" width="18" /></td>';
						}else{
							$ctabtnicon = '';
						}
						$ctabtnlink = addhttp($sRow['signature_ctabtnlink'.$cta]);
						// $GLOBALS['signature_ctabtn'.$cta] = '<td style="border-collapse:collapse; padding:10px 5px 0 0; display:'.$display.';"><table border="0"  cellspacing="0" cellpadding="0"><tr><td class="imagetopngClass" data-image-name="ctabtn'.$cta.'" align="center" bgcolor="'.$bgcolor.'" style=" border-collapse:collapse;  background-color:'.$bgcolor.'; padding:4px 15px; line-height:10px; border-radius:'.$shape.';line-height: 0"><a href="'.$ctabtnlink.'" style="border-radius:'.$shape.'; background-color:'.$bgcolor.'; color:#ffffff; font-size:'.$font.'; display:'.$display.'; align-items:center; justify-content: center; text-decoration:none;"><table border="0" cellspacing="0" cellpadding="0"><tr>'.$ctabtnicon.'<td align="left" valign="middle" style="color:#ffffff;"><span style="text-decoration:none; color:#ffffff; line-height:18px; font-size:'.$font.';">'.$sRow['signature_ctabtnname'.$cta].'</span></td></tr></table></a></td></tr></table></td>';
						$pathToImage = $upload_link."/htmltoimage/".$GLOBALS["USERID"]."/".$GLOBALS["SIGNATUREID"]."/ctabtn".$cta.".png";
						$imgSrc = "";
						if (!file_exists($pathToImage)) {
							$imgSrc = $pathToImage."?".rand(1000,9999);
						}
						list($width, $height) = getimagesize($imgSrc); 
						$imageWidth = $width/3;
						$imageHeight = $height/3;
						$GLOBALS['signature_ctabtn'.$cta] = '<td style="border-collapse:collapse; padding:10px 5px 0 0; display:'.$display.';"><table border="0" cellspacing="0" cellpadding="0"><tr><td class="imagetopngClass"><a href="'.$ctabtnlink.'" ><img src="'.$imgSrc.'" style="width:'.$imageWidth.'px;height:'.$imageHeight.'px;"></a></td></tr></table></td>';
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
					$GLOBALS[$iconname.'_icon'] = $sRow[$iconname] !="" ? '<td style="padding:0 4px 0 0" class="layout-'.$icons['iconname'].'-icon sicon"><a href="'.addhttp($GLOBALS[$iconname]).'" target="_blank"><img alt="" src="'.$socialimg.'" width="'.$iconsize.'" style="display:block;" /></a></td>' : '';
					$GLOBALS['signature_socialicons'] .= $GLOBALS[$iconname.'_icon'];

					$GLOBALS[$iconname.'_iconv'] = $sRow[$iconname] !="" ? '<tr><td style="padding:5px 0 0 0" class="layout-'.$icons['iconname'].'-icon sicon"><a href="'.addhttp($GLOBALS[$iconname]).'" target="_blank"><img alt="" src="'.$socialimg.'" width="'.$iconsize.'" style="display:block;" /></a></td></tr>' : '';
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
					$GLOBALS[$btnname.'_btn'] = $sRow[$btnname] !="" ? '<td style="padding:10px 4px 0 0;" class="layout-'.$mbtn['iconname'].'-btn mbtn"><a href="'.addhttp($GLOBALS[$btnname]).'" target="_blank"><img alt="" src="'.$marketplaceimg.'" width="'.$btnsize.'" /></a></td>' : '';

					$GLOBALS['signature_marketplacebtns'] .= $GLOBALS[$btnname.'_btn'];
				}

				$GLOBALS['signature_marketplacebtns'] ='';
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
					$GLOBALS[$btnname.'_btn'] = $sRow[$btnname] !="" ? '<td style="padding:10px 4px 0 0;" class="layout-'.$mbtn['iconname'].'-btn mbtn"><a href="'.addhttp($GLOBALS[$btnname]).'" target="_blank"><img alt="" src="'.$marketplaceimg.'" width="'.$btnsize.'" /></a></td>' : '';

					$GLOBALS['signature_marketplacebtns'] .= $GLOBALS[$btnname.'_btn'];
				}

				$duplicate_btnstyle = $GLOBALS['plan_signaturelimit'] == 1 ? 'inline' : 'none';
				$master_linkstyle = $sRow['signature_master'] == 1 ? 'none' : 'inline';
				$master_btn = $sRow['signature_master'] == 1 ? '<span class="master">Master</span>' : '';
				$delete_chk = $sRow['signature_master'] == 0 ? '<input type="checkbox" name="bulkdelete[]" value="'.$signature_id.'" class="del_chekbox" title="Delete">' : '';
				 $GLOBALS['signature_list'] .= '<div class="col-lg-4 col-md-6 col-12 search-container"><div class="sin_dashboard_box">'.$delete_chk.$master_btn.'
				 								<div class="dot_icon"><a href="javascript:void(0);" onclick="myFunction('.$signature_id.')"><img src="'.$GLOBALS['IMAGE_LINK'].'/images/dot-icon.svg" alt=""></a></div><div id="myDIV'.$signature_id.'" class="menu_open" style="display:none;">
                                 <ul>
                                 <li><a href="'.$editsignature_link.'"><img src="'.$GLOBALS['IMAGE_LINK'].'/images/edit-signature.svg" alt=""> Edit Signature</a></li>
                                 <li><a href="javascript:void(0);" class="ajaxaction" data-id="'.$signature_id.'" data-action="duplicate"><img src="'.$GLOBALS['IMAGE_LINK'].'/images/duplicate.svg" alt=""> Duplicate</a></li>
								  <li style="display:'.$master_linkstyle.'"><a href="javascript:void(0);" class="ajaxaction" data-id="'.$signature_id.'" data-action="master"><img src="'.$GLOBALS['IMAGE_LINK'].'/images/master.svg" alt="">Set as Master</a></li>
                                 <li><a href="'.$usesignature_link.'"><i class="hgi hgi-stroke hgi-sharp hgi-mail-edit-01"></i> Use Signature</a></li>
								 <li><a  href="javascript:void(0);" id="Share-signature" data-url="'.$sharesignature_link.'" data-id="'.$signature_id.'" data-email="'.$GLOBALS['sigshare_email1'].'" data-kt-modal-toggle="#shareModal"><img src="'.$GLOBALS['IMAGE_LINK'].'/images/share-signature.svg" alt=""> Share Signature</a></li>
                                 <li><a href="javascript:void(0);" class="delete ajaxaction" data-id="'.$signature_id.'" data-action="delete"><img src="'.$GLOBALS['IMAGE_LINK'].'/images/delete.svg" alt=""> Delete</a></li>
                                 </ul>
                                </div>'.$GLOBALS['CLA_HTML']->addContent($sRow['layout_desc']).'<div class="sigshadow_box"></div></div></div>';

				$GLOBALS['signature_count']++;
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

				${$fieldtype.'_count'} ++;
				$fieldno  = ${$fieldtype.'_count'};
				if($fieldtype == 'hyperlink'){
					$GLOBALS['signature_cf_'.$fieldtype.'t'.$fieldno] = '';
					$GLOBALS['signature_cf_'.$fieldtype.$fieldno] = '<a href="'.addhttp($fieldvalue).'" style="text-decoration:underline; font-weight:'.$fieldfontweight.'; font-style:'.$fieldfontstyle.'; color:'.$fieldcolor.'; font-size:'.$fieldfontsize.';">'.$fieldlabelval.'</a>';

				}else if($fieldtype == 'email'){
					$GLOBALS['signature_cf_'.$fieldtype.'t'.$fieldno] = '<span style="font-weight:'.$GLOBALS['label_bold'].'; font-style:'.$GLOBALS['label_italic'].'; color:'.$GLOBALS['label_color'].'; font-size:'.$GLOBALS['label_fontsize'].';">'.$fieldlabelval.'</span>';
					$GLOBALS['signature_cf_'.$fieldtype.$fieldno] = '<a href="mailto:'.$fieldvalue.'" style="font-weight:'.$fieldfontweight.'; font-style:'.$fieldfontstyle.'; color:'.$fieldcolor.'; font-size:'.$fieldfontsize.'; text-decoration:none;">'.$fieldvalue.'</a>';
					$GLOBALS['sigshare_email'.$fieldno] = $fieldvalue;
				}else if($fieldtype == 'website'){
				$GLOBALS['signature_cf_'.$fieldtype.'t'.$fieldno] = '<span style="font-weight:'.$GLOBALS['label_bold'].'; font-style:'.$GLOBALS['label_italic'].'; color:'.$GLOBALS['label_color'].'; font-size:'.$GLOBALS['label_fontsize'].';">'.$fieldlabelval.'</span>';
					$GLOBALS['signature_cf_'.$fieldtype.$fieldno] = '<a href="'.addhttp($fieldvalue).'" style="font-weight:'.$fieldfontweight.'; font-style:'.$fieldfontstyle.'; color:'.$fieldcolor.'; font-size:'.$fieldfontsize.'; text-decoration:none;">'.$fieldvalue.'</a>';
				}else if($fieldtype == 'phone'){
				$GLOBALS['signature_cf_'.$fieldtype.'t'.$fieldno] = '<span style="font-weight:'.$GLOBALS['label_bold'].'; font-style:'.$GLOBALS['label_italic'].'; color:'.$GLOBALS['label_color'].'; font-size:'.$GLOBALS['label_fontsize'].';">'.$fieldlabelval.'</span>';
					$GLOBALS['signature_cf_'.$fieldtype.$fieldno] = '<a href="tel:'.$fieldvalue.'" style="font-weight:'.$fieldfontweight.'; font-style:'.$fieldfontstyle.'; color:'.$fieldcolor.'; font-size:'.$fieldfontsize.'; text-decoration:none;">'.$fieldvalue.'</a>';
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
			$update = $GLOBALS['DB']->update("signature",array('signature_master'=>0),array('user_id'=>$GLOBALS['USERID']));
			$update = $GLOBALS['DB']->update("signature",array('signature_master'=>1),array('signature_id'=>$id));
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
			$data = array('signature_id'=>$id,'user_id'=>$GLOBALS['USERID'],'feedback_comment'=>$feedback);
			$addfeedback = $GLOBALS['DB']->insert("signature_feedback",$data);
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

}

?>
