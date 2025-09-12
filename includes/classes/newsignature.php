<?php
// require_once($GLOBALS['BASE_LINK'].'/'.GetConfig('CLASSES').'/dashboard.php');   // code is for saving signature html(to use in deploy)
class CIT_NEWSIGNATURE
{

	public function __construct()
	{
		if( $GLOBALS['manage_signatures'] != "manage_signatures"){
			GetFrontRedirectUrl(GetUrl(array('module'=>'dashboard')));
		}
		
		if(!isset($_SESSION[GetSession('user_id')])){
			GetFrontRedirectUrl(GetUrl(array('module'=>'signin')));
		}

		if($GLOBALS['plan_signaturelimit'] == 0){
			$return_arrs = array('error'=>1,'msg'=>'You have exceeded your plan limit');
			echo json_encode($return_arrs); exit;
			// GetFrontRedirectUrl(GetUrl(array('module'=>'dashboard')));
		}
		// $GLOBALS['SIGNATURE'] = GetClass('CIT_DASHBOARD');  // code is for saving signature html(to use in deploy)

		if(isset($_REQUEST['department_id'])){
			$GLOBALS['current_department_id_add_sign'] = $_REQUEST['department_id'];
		}else{
			$GLOBALS['current_department_id_add_sign'] = 0;
		}
	}

	public function displayPage(){

		AddMessageInfo();
		
		if(isset($_REQUEST['generateAndSaveGIF']) && $_REQUEST['generateAndSaveGIF']){
			$outputGifName = $_POST['gif_name'];
			$imagePath = $_POST['image_path_name'];
			$pattern = '#(upload-beta/.*)$#i';
			$imageFolderPath = '';
			if (preg_match($pattern, $imagePath, $m)) {
				$imageFolderPath = $m[1]; // everything starting from upload-beta
			}
			$imageName = $_POST['image_name'];
			$outputGifPath = str_replace($imageName, '', $imageFolderPath);
			$outputGifPath = $outputGifPath.$outputGifName;
			$shape = $_POST['shape'];

			$cmd = "cd animation2 && node render.js $outputGifPath $imageFolderPath $shape 2>&1";
			$output = [];
			$return_var = 0;

			exec($cmd, $output, $return_var);

			if($return_var == 0){
				$targetDir = "upload-beta/signature/profile/".$GLOBALS['USERID']."/";
				$fileName = $outputGifName;
			    $targetFile = $targetDir . $fileName;
				$location = GetConfig('SITE_UPLOAD_PATH').'/signature/profile/'.$GLOBALS['USERID'].'/'.$fileName ;
				$keyName = 'upload-beta/signature/profile/'.$GLOBALS['USERID'].'/'.$fileName;
				if (!file_exists($location)) {
					$location = GetConfig('SITE_UPLOAD_PATH').'/signature/profile/'.$fileName ;
					$keyName = 'upload-beta/signature/profile/'.$fileName;
				}
				$result = $GLOBALS['S3Client']->putObject(array( // upload image s3bucket
					'Bucket'=>$GLOBALS['BUCKETNAME'],
					'Key' =>  $keyName,
					'SourceFile' => $location,
					'StorageClass' => 'REDUCED_REDUNDANCY',
					'ACL'   => 'public-read'
				));
				$return_arr = array("error" =>0,"msg"=>"GIF generated successfully","gif_name"=>$outputGifName);
				echo json_encode($return_arr); exit;
			}else{
				$return_arr = array("error" =>1,"msg"=>"There is some error while generating GIF", "result" => $output);
				echo json_encode($return_arr); exit;
			}
		}

		if(isset($_REQUEST['category_id'])){
			$action = trim($_REQUEST['category_id']);
		} else {
			$action = '';
		}

		// code is for saving signature html(to use in deploy) START

		// if(isset($_REQUEST['category_id']) && $_REQUEST['category_id'] == 'saveSignatureHtml'){
		// 	$this->saveSignatureHtml();
		// 	exit();
		// }
	
		// code is for saving signature html(to use in deploy) END

		if($_POST['profileCropped'] !=""){
			$data = $_POST['profileCropped'];
			list($type, $data) = explode(';', $data);
			list(, $data)      = explode(',', $data);
			$data = base64_decode($data);
			$imageName = ($_POST['signature_profile'] !="") ? $_POST['signature_profile'] : 'image';
			file_put_contents("upload-beta/signature/profile/".$GLOBALS['USERID'].'/'.$imageName, $data);
			$location =  GetConfig('SITE_UPLOAD_PATH').'/signature/profile/'.$GLOBALS['USERID'].'/'.$imageName ;
			$result = $GLOBALS['S3Client']->putObject(array( // upload image s3bucket
				'Bucket'=>$GLOBALS['BUCKETNAME'],
				'Key' =>  'upload-beta/signature/profile/'.$GLOBALS['USERID'].'/'.$imageName,
				'SourceFile' => $location,
				'StorageClass' => 'REDUCED_REDUNDANCY',
				'ACL'   => 'public-read'
			));
		}

		if($_POST['saveCroppedImage'] !=""){
			$data = $_POST['saveCroppedImage'];
			list($type, $data) = explode(';', $data);
			list(, $data)      = explode(',', $data);
			$data = base64_decode($data);
			$imageName = time().'-'.$GLOBALS['USERID'].'.png';
			file_put_contents("upload-beta/signature/profile/".$GLOBALS['USERID'].'/'.$imageName, $data); 
			$location =  GetConfig('SITE_UPLOAD_PATH').'/signature/profile/'.$GLOBALS['USERID'].'/'.$imageName ;
			$targetDir = 'upload-beta/signature/profile/'.$GLOBALS['USERID'].'/';
			if (!is_dir($targetDir)) {
				mkdir($targetDir, 0755, true);
			}
			$result = $GLOBALS['S3Client']->putObject(array( // upload image s3bucket
				'Bucket'=>$GLOBALS['BUCKETNAME'],
				'Key' =>  'upload-beta/signature/profile/'.$GLOBALS['USERID'].'/'.$imageName,
				'SourceFile' => $location,
				'StorageClass' => 'REDUCED_REDUNDANCY',
				'ACL'   => 'public-read'
			));
			


			$inputJson = $GLOBALS['ROOT_LINK'].'/images/Profile_pic_circle.json';
			$imagePath = GetConfig('SITE_UPLOAD_PATH').'/signature/profile/'.$GLOBALS['USERID'].'/'.$imageName ;
			$outputJson = GetConfig('SITE_UPLOAD_PATH').'/signature/profile/output_updated_circle.json';

			// === LOAD JSON ===
			$lottie = json_decode(file_get_contents($inputJson), true);
			if (!$lottie || !isset($lottie['assets'])) {
			    // die("❌ Invalid Lottie JSON.\n");
			}

			// === PREPARE NEW IMAGE ===
			$imageMime = 'png';
			$imageData = base64_encode(file_get_contents($imagePath));
			$imageBase64 = 'data:' . $imageMime . ';base64,' . $imageData;

			// === FIND & REPLACE THE EXISTING IMAGE ASSET ===
			$found = false;
			foreach ($lottie['assets'] as &$asset) {
			    if (isset($asset['p']) && strpos($asset['p'], 'base64') !== false) {
			        $asset['p'] = $imageBase64;
			        $asset['e'] = 1;
			        $found = true;
			        break;
			    }
			}

			if (!$found) {
			    // die("❌ No embedded image found in assets.\n");
			}

			// === SAVE MODIFIED JSON ===
			file_put_contents($outputJson, json_encode($lottie, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));


			$inputJson = $GLOBALS['ROOT_LINK'].'/images/Profile_pic_square.json';
			$imagePath = GetConfig('SITE_UPLOAD_PATH').'/signature/profile/'.$GLOBALS['USERID'].'/'.$imageName ;
			$outputJson = GetConfig('SITE_UPLOAD_PATH').'/signature/profile/output_updated_square.json';

			// === LOAD JSON ===
			$lottie = json_decode(file_get_contents($inputJson), true);
			if (!$lottie || !isset($lottie['assets'])) {
			    // die("❌ Invalid Lottie JSON.\n");
			}

			// === PREPARE NEW IMAGE ===
			$imageMime = 'png';
			$imageData = base64_encode(file_get_contents($imagePath));
			$imageBase64 = 'data:' . $imageMime . ';base64,' . $imageData;

			// === FIND & REPLACE THE EXISTING IMAGE ASSET ===
			$found = false;
			foreach ($lottie['assets'] as &$asset) {
			    if (isset($asset['p']) && strpos($asset['p'], 'base64') !== false) {
			        $asset['p'] = $imageBase64;
			        $asset['e'] = 1;
			        $found = true;
			        break;
			    }
			}

			if (!$found) {
			    // die("❌ No embedded image found in assets.\n");
			}

			// === SAVE MODIFIED JSON ===
			file_put_contents($outputJson, json_encode($lottie, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

			$circleJsonName = $GLOBALS['ROOT_LINK'].'/upload-beta/signature/profile/output_updated_circle.json';
			$squareJsonName = $GLOBALS['ROOT_LINK'].'/upload-beta/signature/profile/output_updated_square.json';
			$return_arr = array("error" =>0,"user_id"=>$GLOBALS['USERID'],"img"=>$imageName,'circleJsonName'=>$circleJsonName,'squareJsonName'=>$squareJsonName);

			echo json_encode($return_arr); exit;
		}

		if($_POST['saveProfileGif'] == "true"){
			if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
			    $targetDir = "upload-beta/signature/profile/".$GLOBALS["USERID"]."/";

			    if (!is_dir($targetDir)) {
			        mkdir($targetDir, 0755, true);
			    }

			    $tmpName = $_FILES['file']['tmp_name'];
			    $fileName = $_POST['gif_name'];
			    $targetFile = $targetDir . $fileName;

			    if (move_uploaded_file($tmpName, $targetFile)) {
			        http_response_code(200);
			        echo "Upload successful";
					$location = GetConfig('SITE_UPLOAD_PATH').'/signature/profile/'.$GLOBALS['USERID'].'/'.$fileName ;
					$result = $GLOBALS['S3Client']->putObject(array( // upload image s3bucket
						'Bucket'=>$GLOBALS['BUCKETNAME'],
						'Key' =>  'upload-beta/signature/profile/'.$GLOBALS['USERID'].'/'.$fileName,
						'SourceFile' => $location,
						'StorageClass' => 'REDUCED_REDUNDANCY',
						'ACL'   => 'public-read'
					));
			    } else {
			        http_response_code(500);
			        echo "Failed to move uploaded file.";
			    }
			} else {
			    http_response_code(400);
			    echo "No file uploaded or upload error.";
			}
		}

		if($_FILES['profile']['name'] !=""){

			$filename = $_FILES['profile']['name'];
			$filesize = $_FILES['profile']['size'];
			$displayname = $filename;
			$valid_extensions = array('png','jpeg','jpg');
			$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
			$uploadedfile = $_FILES['profile']['tmp_name'];
			list($width,$height)=getimagesize($uploadedfile);

			if(in_array($ext, $valid_extensions)) {
				//$location = "upload-beta/".$filename;
				$filename = time().'-'.$GLOBALS['USERID'].'.'.$ext;
				$location =  GetConfig('SITE_UPLOAD_PATH').'/signature/profile/'.$GLOBALS['USERID'].'/'.$filename ;
				$targetDir = 'upload-beta/signature/profile/'.$GLOBALS['USERID'].'/';
				if (!is_dir($targetDir)) {
					mkdir($targetDir, 0755, true);
			    }
				$return_arr = array();
				if(move_uploaded_file($_FILES['profile']['tmp_name'],$location)){
					$result = $GLOBALS['S3Client']->putObject(array( // upload image s3bucket
						'Bucket'=>$GLOBALS['BUCKETNAME'],
						'Key' =>  'upload-beta/signature/profile/'.$GLOBALS['USERID'].'/'.$filename,
						'SourceFile' => $location,
						'StorageClass' => 'REDUCED_REDUNDANCY',
						'ACL'   => 'public-read'
					));
					// if(is_array(getimagesize($location))){
						$src = $GLOBALS['UPLOAD_LINK'].'/signature/profile/'.$GLOBALS['USERID'].'/'.$filename;

						$data =array('user_image'=>$filename); $where =array('user_id'=>$GLOBALS['USERID']);
					// }
					$return_arr = array("name" => $filename,"displayname" => $displayname, "size" => $filesize, "src"=> $src, "error"=>0);
				}
			}else{
				$return_arr = array("error" =>1, "msg"=>"please upload valid jpg, jpeg or png image");
			}
			// if($height > $width){
			// 	$return_arr = array("error" =>1, "msg"=>"the image height should not exceed the image width.");
			// }

			echo json_encode($return_arr); exit;
		}

		if($_FILES['banner']['name'] !=""){

			$filename = $_FILES['banner']['name'];
			$filesize = $_FILES['banner']['size'];
			$displayname = $filename;
			$valid_extensions = array('png','jpeg','jpg');
			$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

			if(in_array($ext, $valid_extensions)) {
				//$location = "upload-beta/".$filename;
				$filename = time().'-'.$GLOBALS['USERID'].'.'.$ext;
				$location =  GetConfig('SITE_UPLOAD_PATH').'/signature/banner/'.$filename ;
				$return_arr = array();
				if(move_uploaded_file($_FILES['banner']['tmp_name'],$location)){
					$result = $GLOBALS['S3Client']->putObject(array( // upload image s3bucket
						'Bucket'=>$GLOBALS['BUCKETNAME'],
						'Key' =>  'upload-beta/signature/banner/'.$filename,
						'SourceFile' => $location,
						'StorageClass' => 'REDUCED_REDUNDANCY',
						'ACL'   => 'public-read'
					));
					// if(is_array(getimagesize($location))){
						$src = $GLOBALS['UPLOAD_LINK'].'/signature/banner/'.$filename;

						$data =array('user_image'=>$filename); $where =array('user_id'=>$GLOBALS['USERID']);
					// }
					$return_arr = array("name" => $filename,"displayname" => $displayname, "size" => $filesize, "src"=> $src, "error"=>0);
				}
			}else{
				$return_arr = array("error" =>1, "msg"=>"please upload valid jpg, jpeg or png image");
			}
			echo json_encode($return_arr); exit;
		}



		if($_REQUEST['category_id'] == 'uploadimg'){

			$filename = $_FILES['file']['name'];
			$filesize = $_FILES['file']['size'];
			$displayname = $filename;
			$valid_extensions = array('png', 'svg');
			$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
			if(in_array($ext, $valid_extensions)) {
				//$location = "upload-beta/".$filename;
				$filename = $GLOBALS['USERID'].'.'.$ext;
				$location =  GetConfig('SITE_UPLOAD_PATH').'/signature/'.$GLOBALS['USERID'].'/'.$filename ;
				if (!file_exists(GetConfig('SITE_UPLOAD_PATH').'/signature/'.$GLOBALS['USERID'])) {
					mkdir(GetConfig('SITE_UPLOAD_PATH').'/signature/'.$GLOBALS['USERID'], 0777, true);
				}
				$return_arr = array();
				if(move_uploaded_file($_FILES['file']['tmp_name'],$location)){
					// $src = $GLOBALS['ROOT_LINK']."/images/img-icon.svg";
					// checking file is image or not
					$result = $GLOBALS['S3Client']->putObject(array( // upload image s3bucket
						'Bucket'=>$GLOBALS['BUCKETNAME'],
						'Key' =>  'upload-beta/signature/'.$GLOBALS['USERID'].'/'.$filename,
						'SourceFile' => $location,
						'StorageClass' => 'REDUCED_REDUNDANCY',
						'ACL'   => 'public-read'
					));

					$src = $GLOBALS['UPLOAD_LINK'].'/signature/'.$GLOBALS['USERID'].'/'.$filename;
					$GLOBALS['signature_image_name_value'] = $filename;
					$return_arr = array("name" => $filename,"displayname" => $displayname, "size" => $filesize, "src"=> $src, "error"=>0);
				}
			}else{
				$return_arr = array("error" =>1, "msg"=>"please upload valid png or svg image");
			}
			echo json_encode($return_arr); exit;
		}

		// Upload Profile image start
		if($_REQUEST['category_id'] == 'uploadprofileimg'){
			$filename = $_FILES['profileImage']['name'];
			$filesize = $_FILES['profileImage']['size'];
			$displayname = $filename;
			$valid_extensions = array('png','jpeg','jpg');
			$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
			
			if(in_array($ext, $valid_extensions)) {
				//$location = "upload-beta/".$filename;
				$filename = $GLOBALS['USERID'].'.'.$ext;
				$location =  GetConfig('SITE_UPLOAD_PATH').'/profile/'.$filename ;
				$return_arr = array();
				if(move_uploaded_file($_FILES['profileImage']['tmp_name'],$location)){
					$result = $GLOBALS['S3Client']->putObject(array( // upload image s3bucket
						'Bucket'=>$GLOBALS['BUCKETNAME'],
						'Key' =>  'upload-beta/signature/'.$GLOBALS['USERID'].'/'.$filename,
						'SourceFile' => $location,
						'StorageClass' => 'REDUCED_REDUNDANCY',
						'ACL'   => 'public-read'
					));
					// if(is_array(getimagesize($location))){
						$src = $GLOBALS['UPLOAD_LINK'].'/profile/'.$filename;
						
						$data =array('user_image'=>$filename); $where =array('user_id'=>$GLOBALS['USERID']); 
						$update = $GLOBALS['DB']->update("registerusers",$data,$where);
					// }
					$return_arr = array("name" => $filename,"displayname" => $displayname, "size" => $filesize, "src"=> $src, "error"=>0);
				}
			}else{
				$return_arr = array("error" =>1, "msg"=>"please upload valid jpg, jpeg or png image");
			}
			echo json_encode($return_arr); exit;
		}
		// Upload Profile image end

		if($_POST['layout_id'] && !isset($_POST['loadlayout'])){
			$pattern1 = '/^([\da-z.-]+)\.([a-z.]{2,6})([\/\w.-]*)*\/*/';
			$pattern2 = '/^(https?:\/\/)?([\da-z.-]+)\.([a-z.]{2,6})([\/\w.-]*)*\/*/';
			$logoLink = $_POST['signature_link'];

			if(!is_null($logoLink) && $logoLink != ""){
				if(!preg_match($pattern1, $logoLink)){
					if(!preg_match($pattern2, $logoLink)){
						$return_arrs = array('error'=>1,'msg'=>'please enter valid url for LOGO');
						echo json_encode($return_arrs); exit;
					}
				}
			}
			$ctabtnlink1 = strtolower($_POST['signature_ctabtnlink1']);
			if(!is_null($ctabtnlink1) && $ctabtnlink1 != ""){
				if(!preg_match($pattern1, $ctabtnlink1)){
					if(!preg_match($pattern2, $ctabtnlink1)){
						$return_arrs = array('error'=>1,'msg'=>'please enter valid url for Cta button 1');
						echo json_encode($return_arrs); exit;
					}
				}
			}
			$ctabtnlink2 = strtolower($_POST['signature_ctabtnlink2']);
			if(!is_null($ctabtnlink2) && $ctabtnlink2 != ""){
				if(!preg_match($pattern1, $ctabtnlink2)){
					if(!preg_match($pattern2, $ctabtnlink2)){
						$return_arrs = array('error'=>1,'msg'=>'please enter valid url for Cta button 2');
						echo json_encode($return_arrs); exit;
					}
				}
			}
			$ctabtnlink3 = strtolower($_POST['signature_ctabtnlink3']);
			if(!is_null($ctabtnlink3) && $ctabtnlink3 != ""){
				if(!preg_match($pattern1, $ctabtnlink3)){
					if(!preg_match($pattern2, $ctabtnlink3)){
						$return_arrs = array('error'=>1,'msg'=>'please enter valid url for Cta button 3');
						echo json_encode($return_arrs); exit;
					}
				}
			}

			if($_POST['signature_firstname'] !=""){

				$this->getSignatureLogo($GLOBALS['USERUPLOADLIMIT']); // get logo from logo limit
				$style = $this->getlayoutStyle('add');
				$signature_style = serialize($style);
				if($_POST['current_department'] == ""){
					$data = array('user_id'=>$GLOBALS['USERID'],'department_name'=>'Division #1');
					$addDepartment = $GLOBALS['DB']->insert("registerusers_departments",$data);
					$_POST['current_department'] = $addDepartment;
				}
				$data =array('user_id'=>$GLOBALS['USERID'],'layout_id'=>$_POST['layout_id'],'department_id'=>$_POST['current_department'],'signature_profile'=>$_POST['signature_profile'],'signature_firstname'=>$_POST['signature_firstname'],'signature_company'=>$_POST['signature_company'],'signature_jobtitle'=>$_POST['signature_jobtitle'],'signature_socialdesign'=>$_POST['signature_socialdesign'],'signature_btndesign'=>$_POST['signature_btndesign'],'signature_marketbtndesign'=>$_POST['signature_marketbtndesign'],'signature_custombtn'=>$_POST['signature_custombtn'],'signature_custombtntext'=>$_POST['signature_custombtntext'], 'signature_custombtnlink'=>addhttp($_POST['signature_custombtnlink']),'signature_web'=>addhttp($_POST['signature_web']),'signature_facebook'=>addhttp($_POST['signature_facebook']), 'signature_insta'=>addhttp($_POST['signature_insta']),'signature_google'=>addhttp($_POST['signature_google']),'signature_youtube'=>addhttp($_POST['signature_youtube']),'signature_linkedin'=>addhttp($_POST['signature_linkedin']),'signature_pintrest'=>addhttp($_POST['signature_pintrest']),'signature_twitter'=>addhttp($_POST['signature_twitter']),'signature_clendly'=>addhttp($_POST['signature_clendly']),'signature_ebay'=>addhttp($_POST['signature_ebay']),'signature_imbd'=>addhttp($_POST['signature_imbd']),'signature_tiktok'=>addhttp($_POST['signature_tiktok']),'signature_vimeo'=>addhttp($_POST['signature_vimeo']),'signature_yelp'=>addhttp($_POST['signature_yelp']),'signature_zillow'=>addhttp($_POST['signature_zillow']),'signature_snapchat'=>addhttp($_POST['signature_snapchat']),'signature_reddit'=>addhttp($_POST['signature_reddit']),'signature_wechat'=>addhttp($_POST['signature_wechat']),'signature_airbnb'=>addhttp($_POST['signature_airbnb']),'signature_amazon'=>addhttp($_POST['signature_amazon']),'signature_discord'=>addhttp($_POST['signature_discord']),'signature_spotify'=>addhttp($_POST['signature_spotify']),'signature_apple'=>addhttp($_POST['signature_apple']),'signature_whatsapp'=>addhttp($_POST['signature_whatsapp']),'signature_shopify'=>addhttp($_POST['signature_shopify']),'signature_threads'=>addhttp($_POST['signature_threads']),'signature_venmo'=>addhttp($_POST['signature_venmo']),'signature_zelle'=>addhttp($_POST['signature_zelle']),'signature_link'=>$_POST['signature_link'],'signature_banner'=>$_POST['signature_banner'],'signature_bannerlink'=>$_POST['signature_bannerlink'],'signature_ctabtnname1'=>$_POST['signature_ctabtnname1'],'signature_ctabtnlink1'=>$_POST['signature_ctabtnlink1'],'signature_ctabtnname2'=>$_POST['signature_ctabtnname2'],'signature_ctabtnlink2'=>$_POST['signature_ctabtnlink2'],'signature_ctabtnname3'=>$_POST['signature_ctabtnname3'],'signature_ctabtnlink3'=>$_POST['signature_ctabtnlink3'],'signature_style'=>$signature_style,'signature_appstorebtn'=>addhttp($_POST['signature_appstorebtn']),'signature_playstorebtn'=>addhttp($_POST['signature_playstorebtn']),'signature_amazonbtn'=>addhttp($_POST['signature_amazonbtn']),'signature_ebaybtn'=>addhttp($_POST['signature_ebaybtn']),'signature_socialanimation'=>$_POST['signature_socialanimation'],'signature_custombtnanimation'=>$_POST['signature_custombtnanimation'],'signature_marketbtnanimation'=>$_POST['signature_marketbtnanimation']);
				if(isset($_REQUEST['signature_type']) && $_REQUEST['signature_type'] == 'master'){
					$update = $GLOBALS['DB']->update("signature",array('signature_master'=>0),array('user_id'=>$GLOBALS['USERID']));
					$data['signature_master'] = 1;
				}
				$addSignature = $GLOBALS['DB']->insert("signature",$data);
				if($addSignature){
					$todayDate = date('Y-m-d');
					$ipInformation = $this->getUserLocation();
					// for logo view
					$logoRow = $GLOBALS['DB']->row("SELECT * FROM signature_logo WHERE user_id = ? LIMIT 0,1",array($GLOBALS['USERID']));
					if(is_array($logoRow)){
						$logoName = $logoRow['logo'];
						if(($logoRow['logo_process'] == 2) && ($logoRow['logo_change_process'] == 2)){
							$logoName = $logoRow['logo_animation'];
						}
						$dataLogoAnalytics =array('user_id'=>$GLOBALS['USERID'],'signature_id'=>$addSignature,'url'=>$logoName,'analytic_type'=>'logo','date'=>$todayDate,'user_ip'=>$ipInformation['ip'],'location'=>json_encode($ipInformation['location'], true));
						$addAnalytics = $GLOBALS['DB']->insert("registerusers_analytics",$dataLogoAnalytics);
					}
					// for logo view

					// for logo click
					if($_POST['signature_link'] != ""){
						$dataLogoAnalytics =array('user_id'=>$GLOBALS['USERID'],'signature_id'=>$addSignature,'url'=>$_POST['signature_link'],'analytic_type'=>'logoclick','date'=>$todayDate,'user_ip'=>$ipInformation['ip'],'location'=>json_encode($ipInformation['location'], true));
						$addAnalytics = $GLOBALS['DB']->insert("registerusers_analytics",$dataLogoAnalytics);
					}
					// for logo click

					// for banner view
					if($_POST['signature_banner'] != ""){
						$dataBannerAnalytics =array('user_id'=>$GLOBALS['USERID'],'signature_id'=>$addSignature,'url'=>$_POST['signature_banner'],'analytic_type'=>'banner','date'=>$todayDate,'user_ip'=>$ipInformation['ip'],'location'=>json_encode($ipInformation['location'], true));
						$addAnalytics = $GLOBALS['DB']->insert("registerusers_analytics",$dataBannerAnalytics);
					}
					// for banner view

					// for banner click
					if($_POST['signature_bannerlink'] != ""){
						$dataBannerAnalytics =array('user_id'=>$GLOBALS['USERID'],'signature_id'=>$addSignature,'url'=>$_POST['signature_bannerlink'],'analytic_type'=>'bannerclick','date'=>$todayDate,'user_ip'=>$ipInformation['ip'],'location'=>json_encode($ipInformation['location'], true));
						$addAnalytics = $GLOBALS['DB']->insert("registerusers_analytics",$dataBannerAnalytics);
					}
					// for banner click

					// for social icons click
					$GLOBALS['social_icons_arr'] = array(array('iconname'=>'web','label'=>'Website'),array('iconname'=>'insta','label'=>'Instagram'),array('iconname'=>'linkedin','label'=>'Linkdin'),array('iconname'=>'facebook','label'=>'Facebook'),array('iconname'=>'tiktok','label'=>'Tiktok'),array('iconname'=>'youtube','label'=>'Youtube'),array('iconname'=>'twitter','label'=>'Twitter'),array('iconname'=>'vimeo','label'=>'Vimeo'),array('iconname'=>'pintrest','label'=>'Pintrest'),array('iconname'=>'google','label'=>'Google'),array('iconname'=>'yelp','label'=>'Yelp'),array('iconname'=>'zillow','label'=>'Zillow'),array('iconname'=>'airbnb','label'=>'Airbnb'),array('iconname'=>'whatsapp','label'=>'Whatsapp'),array('iconname'=>'discord','label'=>'Discord'),array('iconname'=>'imbd','label'=>'imbd'),array('iconname'=>'ebay','label'=>'eBay'),array('iconname'=>'spotify','label'=>'Spotify'),array('iconname'=>'amazon','label'=>'Amazon'),array('iconname'=>'clendly','label'=>'Clendly'),array('iconname'=>'wechat','label'=>'Wechat'),array('iconname'=>'apple','label'=>'Apple'),array('iconname'=>'snapchat','label'=>'Snapchat'),array('iconname'=>'reddit','label'=>'Reddit'),array('iconname'=>'shopify','label'=>'Shopify'),array('iconname'=>'threads','label'=>'Threads'),array('iconname'=>'venmo','label'=>'Venmo'),array('iconname'=>'zelle','label'=>'Zelle'));
					
					foreach($GLOBALS['social_icons_arr'] as $icons){
						$iconname =  'signature_'.$icons['iconname'];
						if($_POST[$iconname] != ''){
							$dataSocialAnalytics =array('user_id'=>$GLOBALS['USERID'],'signature_id'=>$addSignature,'url'=>$_POST[$iconname],'analytic_type'=>$icons['iconname'],'date'=>$todayDate,'user_ip'=>$ipInformation['ip'],'location'=>json_encode($ipInformation['location'], true));
							$addAnalytics = $GLOBALS['DB']->insert("registerusers_analytics",$dataSocialAnalytics);
						}
					}
					// for social icons click

					// for custombtnlink button click
					if($_POST['signature_custombtnlink'] != ""){
						$dataCustombtnlinkAnalytics =array('user_id'=>$GLOBALS['USERID'],'signature_id'=>$addSignature,'url'=>$_POST['signature_custombtnlink'],'analytic_type'=>'custombtnlink','date'=>$todayDate,'user_ip'=>$ipInformation['ip'],'location'=>json_encode($ipInformation['location'], true));
						$addAnalytics = $GLOBALS['DB']->insert("registerusers_analytics",$dataCustombtnlinkAnalytics);
					}
					// for custombtnlink button click
					
					// for cta button click
					for ($cta = 1; $cta <= 3; $cta++) {
						if($_POST['signature_ctabtnlink'.$cta] != ""){
							$dataCTAButtonAnalytics =array('user_id'=>$GLOBALS['USERID'],'signature_id'=>$addSignature,'url'=>$_POST['signature_ctabtnlink'.$cta],'analytic_type'=>'ctabtnlink'.$cta,'date'=>$todayDate,'user_ip'=>$ipInformation['ip'],'location'=>json_encode($ipInformation['location'], true));
							$addAnalytics = $GLOBALS['DB']->insert("registerusers_analytics",$dataCTAButtonAnalytics);
						}
					}
					// for cta button click

					// for marketplace button click
					if($_POST['signature_appstorebtn'] != ""){
						$dataCustombtnlinkAnalytics =array('user_id'=>$GLOBALS['USERID'],'signature_id'=>$addSignature,'url'=>$_POST['signature_appstorebtn'],'analytic_type'=>'appstorebtn','date'=>$todayDate,'user_ip'=>$ipInformation['ip'],'location'=>json_encode($ipInformation['location'], true));
						$addAnalytics = $GLOBALS['DB']->insert("registerusers_analytics",$dataCustombtnlinkAnalytics);
					}
					if($_POST['signature_playstorebtn'] != ""){
						$dataCustombtnlinkAnalytics =array('user_id'=>$GLOBALS['USERID'],'signature_id'=>$addSignature,'url'=>$_POST['signature_playstorebtn'],'analytic_type'=>'playstorebtn','date'=>$todayDate,'user_ip'=>$ipInformation['ip'],'location'=>json_encode($ipInformation['location'], true));
						$addAnalytics = $GLOBALS['DB']->insert("registerusers_analytics",$dataCustombtnlinkAnalytics);
					}
					// for marketplace button click


					// for email and phone click
					$fieldArray = $_POST['custom_fieldtype'];
					if(count($fieldArray)> 0 && $addSignature > 0){
						$fcount =0;
						foreach($fieldArray as $fieldtype){
							if(in_array($fieldtype,['email','phone'])){
								if(trim($_POST['custom_field'][$fcount]) !=""){
									$dataFieldtypeAnalytics =array('user_id'=>$GLOBALS['USERID'],'signature_id'=>$addSignature,'url'=>$_POST['custom_field'][$fcount],'analytic_type'=>$fieldtype,'date'=>$todayDate,'user_ip'=>$ipInformation['ip'],'location'=>json_encode($ipInformation['location'], true));
									$addAnalytics = $GLOBALS['DB']->insert("registerusers_analytics",$dataFieldtypeAnalytics);
								}
							}
							$fcount++;
						}
					}
					// for email and phone click


					// add custom fields
					$fieldArray = $_POST['custom_fieldtype'];
					if(count($fieldArray)> 0 && $addSignature > 0){
						$fcount =0;
						foreach($fieldArray as $fieldtype){
							$fontweigth = $_POST['field_fontweight'][$fcount] == 1 ? 'bold':'normal';
							$fontstyle = $_POST['field_fontstyle'][$fcount] == 1 ? 'italic':'normal';
							if(trim($_POST['custom_field'][$fcount]) !=""){
								$fielddata = array("signature_id"=>$addSignature,"field_type"=>$fieldtype,"field_label"=>$_POST['field_label'][$fcount],"field_value"=>$_POST['custom_field'][$fcount],"field_fontsize"=>$_POST['field_fontsize'][$fcount],"field_fontweight"=>$fontweigth,"field_fontstyle"=>$fontstyle,"field_color"=>$_POST['field_color'][$fcount],"field_order"=>$fcount);
								$GLOBALS['DB']->insert("signature_customfield",$fielddata);
							}
							$fcount++;
						}
					}

					if($GLOBALS['USERUPLOADLIMIT'] > 0){
						// if($_POST['current_department']){
							// $GLOBALS['DB']->insert("signature_logo",array('user_id'=>$GLOBALS['USERID'],'logo'=>$GLOBALS['newsignature_img'],'logo_animation'=>$GLOBALS['newsignature_cimg'],'department_id'=>$_POST['current_department']));
						// }else{
							$GLOBALS['DB']->insert("signature_logo",array('user_id'=>$GLOBALS['USERID'],'logo'=>$GLOBALS['newsignature_img'],'logo_animation'=>$GLOBALS['newsignature_cimg']));
						// }
						
						// $dataLogoAnalytics =array('user_id'=>$GLOBALS['USERID'],'signature_id'=>0,'url'=>$GLOBALS['newsignature_img'],'analytic_type'=>'logo');
						// $addAnalytics = $GLOBALS['DB']->insert("registerusers_analytics",$dataLogoAnalytics);
						// $GLOBALS['DB']->update("signature_logo",array('logo'=>$addAnalytics),array('user_id'=>$GLOBALS['USERID']));
						$GLOBALS['DB']->update("registerusers",array('user_uploadlimit'=>0),array('user_id'=>$GLOBALS['USERID']));
						$_SESSION[GetSession('user_uploadlimit')] =0; 	// update logo upload limit for user
						if($GLOBALS['FREETRIAL'] == 0){
							$message= _getEmailTemplate('animation_process'); 	// send mail
							$send_mail = _SendMail($GLOBALS['USEREMAIL'],'',$GLOBALS['EMAIL_SUBJECT'],$message);
						}

					}
				}
				$return_arrs = array('error'=>0,'msg'=>'Signature add success');
				// $return_arrs = array('error'=>0,'msg'=>'Signature update success','added_signature_id' => $addSignature, 'signature_after_save' => $GLOBALS['SIGNATURE']->getUserSignature($addSignature));   // code is for saving signature html(to use in deploy) 

			}else{
				$return_arrs = array('error'=>1,'msg'=>'please fill all required field');
			}
			echo json_encode($return_arrs); exit;
		}

		if($_REQUEST['category_id'] =='done'){

			$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/signature-done.html');
			$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');
			$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');
			$GLOBALS['SIDEBAR'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.sidebar.html');
			$GLOBALS['CLA_HTML']->display();
			RemoveMessageInfo();
			exit();

		}

		if($GLOBALS['USERUPLOADLIMIT'] == 0){
			$GLOBALS['activetabclass2'] ='active';
		}else{
			$GLOBALS['activetabclass1'] ='active';
		}

		$GLOBALS['social_icons_arr'] = array(array('iconname'=>'web','label'=>'Website'),array('iconname'=>'insta','label'=>'Instagram'),array('iconname'=>'linkedin','label'=>'Linkdin'),array('iconname'=>'facebook','label'=>'Facebook'),array('iconname'=>'tiktok','label'=>'Tiktok'),array('iconname'=>'youtube','label'=>'Youtube'),array('iconname'=>'twitter','label'=>'Twitter'),array('iconname'=>'vimeo','label'=>'Vimeo'),array('iconname'=>'pintrest','label'=>'Pintrest'),array('iconname'=>'google','label'=>'Google'),array('iconname'=>'yelp','label'=>'Yelp'),array('iconname'=>'zillow','label'=>'Zillow'),array('iconname'=>'airbnb','label'=>'Airbnb'),array('iconname'=>'whatsapp','label'=>'Whatsapp'),array('iconname'=>'discord','label'=>'Discord'),array('iconname'=>'imbd','label'=>'imbd'),array('iconname'=>'ebay','label'=>'eBay'),array('iconname'=>'spotify','label'=>'Spotify'),array('iconname'=>'amazon','label'=>'Amazon'),array('iconname'=>'clendly','label'=>'Clendly'),array('iconname'=>'wechat','label'=>'Wechat'),array('iconname'=>'apple','label'=>'Apple'),array('iconname'=>'snapchat','label'=>'Snapchat'),array('iconname'=>'reddit','label'=>'Reddit'),array('iconname'=>'shopify','label'=>'Shopify'),array('iconname'=>'threads','label'=>'Threads'),array('iconname'=>'venmo','label'=>'Venmo'),array('iconname'=>'zelle','label'=>'Zelle'));

		$GLOBALS['marketplace_btn_arr'] = array(array('iconname'=>'appstorebtn','label'=>'App Store'),array('iconname'=>'playstorebtn','label'=>'Google Play Store'));

		$this->getPage();
		$this->getLayout(0);
		if($_REQUEST['siganature_type'] == 'master'){
			$GLOBALS['signatureTypeParam'] = '?signature_type=master';
		}else{
			$GLOBALS['signatureTypeParam'] = '';
		}

		if($GLOBALS['current_department_id_add_sign'] != '0'){
			$bannerCampaign = $GLOBALS['DB']->row("SELECT * FROM banner_campaign WHERE user_id=? AND department_id LIKE ? AND is_paused='false' AND campaign_status NOT IN ('canceled', 'draft') AND start_date <= NOW() AND NOW() <= end_date LIMIT 0,1",array($GLOBALS['USERID'],'%' . $GLOBALS['current_department_id_add_sign'] . '%'));
			if(is_array($bannerCampaign) && $bannerCampaign['start_date'] <= date('Y-m-d H:i:s') && date('Y-m-d H:i:s') <= $bannerCampaign['end_date'] && $bannerCampaign['campaign_status'] != 'canceled'){
				$GLOBALS['is_banner_campaign'] = 'section_disabled';
				$GLOBALS['signature_banner'] = $GLOBALS['UPLOAD_LINK']."/bannercampaign/".$bannerCampaign['banner_name'];
			}else{
				$GLOBALS['is_banner_campaign'] = '';
			}
		}
		else{
			$GLOBALS['is_banner_campaign'] = '';
		}

		// Assign global CLA_HTML object to a local variable
		$html = $GLOBALS['CLA_HTML'];

		// Load the main template (signature editor sidebar)
		$html->addMain($GLOBALS['WWW_TPL'] . '/signatureeditorsidebar.html');

		// Add common page components
		$GLOBALS['HEADER']  = $html->addSub($GLOBALS['WWW_TPL'] . '/page.header.html');
		$GLOBALS['FOOTER']  = $html->addSub($GLOBALS['WWW_TPL'] . '/page.footer.html');
		$GLOBALS['SIDEBAR'] = $html->addSub($GLOBALS['WWW_TPL'] . '/page.sidebar.html');

		// Set loops for dynamic content
		$html->SetLoop('SOCIALICONS', $GLOBALS['social_icons_arr']);
		$html->SetLoop('SOCIALICONSLINK', $GLOBALS['social_icons_arr']);
		$html->SetLoop('MARKETPLACEBTN', $GLOBALS['marketplace_btn_arr']);
		$html->SetLoop('MARKETPLACEBTNLINK', $GLOBALS['marketplace_btn_arr']);

		// Start output buffering
		ob_start();
		$html->display();
		$renderedHtml = ob_get_clean(); // Get the buffered output and assign to a variable

		// Assign it to the global variable without displaying it
		$GLOBALS['EDITOR_SIDEBAR_NEW_SIGNATURE'] = $renderedHtml;


		
		$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/newsignature.html');
		// $GLOBALS['sig_html_save_url'] = GetUrl(array('module'=>$_REQUEST['module'],'category_id'=>'saveSignatureHtml'));   // code is for saving signature html(to use in deploy) 
		$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');
		$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');
		$GLOBALS['SIDEBAR'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.sidebar.html');
		$GLOBALS['CLA_HTML']->SetLoop('SOCIALICONS',$GLOBALS['social_icons_arr']);
		$GLOBALS['CLA_HTML']->SetLoop('SOCIALICONSLINK',$GLOBALS['social_icons_arr']);
		$GLOBALS['CLA_HTML']->SetLoop('MARKETPLACEBTN',$GLOBALS['marketplace_btn_arr']);
		$GLOBALS['CLA_HTML']->SetLoop('MARKETPLACEBTNLINK',$GLOBALS['marketplace_btn_arr']);
		$GLOBALS['CLA_HTML']->display();
		RemoveMessageInfo();
		exit();

	}

	public function getLayout($signature_id){
		if($signature_id == 0){
			$masterSign = $GLOBALS['DB']->row("SELECT * FROM `signature` WHERE `user_id`=? AND`signature_master`=1",array($GLOBALS['USERID']));
			if(is_array($masterSign)){
				$signature_id = $masterSign['signature_id'];
			}
		}
		$this->getDemoSignature($signature_id);
		$layout_lists = $GLOBALS['DB']->query("SELECT * FROM `signature_layout` WHERE `layout_status`= 1 ORDER BY `index_id`");
		$GLOBALS['layout_list'] = '';
		$templet_no = 1;


		foreach($layout_lists as $layoutRow){
			$layout_id = $layoutRow['layout_id'];
			$layout_name = $layoutRow['layout_name'];
			$root_link = $GLOBALS['ROOT_LINK'];
			$GLOBALS['signature_firstname'] = '<span class="layout_firstname" style="font-weight:'.$GLOBALS['firstname_bold'].'; font-style:'.$GLOBALS['firstname_italic'].'; color:'.$GLOBALS['firstname_color'].'; font-size:'.$GLOBALS['firstname_fontsize'].';">'.$GLOBALS['signature_firstname_text'].'</span>';
			$GLOBALS['signature_company'] = '<span class="layout_company" style="font-weight:'.$GLOBALS['company_bold'].'; font-style:'.$GLOBALS['company_italic'].'; color:'.$GLOBALS['company_color'].'; font-size:'.$GLOBALS['company_fontsize'].';">'.$GLOBALS['signature_company_text'].'</span>';
			$GLOBALS['signature_jobtitle'] = '<span class="layout_jobtitle" style="font-weight:'.$GLOBALS['jobtitle_bold'].'; font-style:'.$GLOBALS['jobtitle_italic'].'; color:'.$GLOBALS['jobtitle_color'].'; font-size:'.$GLOBALS['jobtitle_fontsize'].';">'.$GLOBALS['signature_jobtitle_text'].'</span>';

				if($GLOBALS['signature_custombtnlink'] != "" && $GLOBALS['signature_custombtn'] != ""){
					$GLOBALS['cusbtnshow'] ='inline';
					$cusbtnlink = $GLOBALS['IMAGE_LINK'].'/images/custome/animation/'.$GLOBALS['signature_btndesign'];
					$btnsize = $GLOBALS['signature_custombtnsize'] !="" ? $GLOBALS['signature_custombtnsize'] : 80;
					if($GLOBALS['signature_custombtn'] == 'custome'){
						$GLOBALS['signature_customebtn'] = '<td class="layout-custombtn" style="display:flex;"><a href="'.addhttp($GLOBALS['signature_custombtnlink']).'" style="background: #f1f1f1; text-align:center; padding:4px 15px; color:#333; text-align:center; text-decoration:none; font-weight:bold; font-size:12px; line-height:14px; display:inline-block;">'.$GLOBALS['signature_custombtntext'].'</a></td>';
					}else{
						$custome_btnimage = $cusbtnlink.'/'.$GLOBALS['signature_custombtn'];
						$GLOBALS['signature_customebtn'] ='<td class="layout-custombtn"><a href="'.addhttp($GLOBALS['signature_custombtnlink']).'" target="_blank"><img alt="" src="'.$custome_btnimage.'" width="'.$btnsize.'" class="scusbtn" /></a></td>';
					}
				}else{
					$GLOBALS['cusbtnshow'] ='';
					$GLOBALS['signature_customebtn'] ='<td class="layout-custombtn"></td>';
				}

				// CTA btn layout
				for ($cta = 1; $cta <= 3; $cta++) {
					if($GLOBALS['signature_ctabtnlink'.$cta] != "" && $GLOBALS['signature_ctabtnname'.$cta] != ""){
						if($GLOBALS['signature_ctabtn'.$cta.'_icon'] !=""){
							$GLOBALS['signature_ctabtn'.$cta.'_icon'] = str_replace(".svg",".png",$GLOBALS['signature_ctabtn'.$cta.'_icon']);
							$btnicon = $GLOBALS['IMAGE_LINK'].'/images/buttonicon/'.$GLOBALS['signature_ctabtn'.$cta.'_icon'];
							$ctabtnicon = '<img src="'.$btnicon.'" width="18" style="margin-right:10px" />';
						}else{
							$ctabtnicon = '';
						}
						$shape = $GLOBALS['signature_ctabtn'.$cta.'_shape'] != "" ? $GLOBALS['signature_ctabtn'.$cta.'_shape'] : '0px';
						$bgcolor = $GLOBALS['signature_ctabtn'.$cta.'_bgcolor'] != "" ? $GLOBALS['signature_ctabtn'.$cta.'_bgcolor'] : '#000000';
						$font = $GLOBALS['signature_ctabtn'.$cta.'_size'] != "" ? $GLOBALS['signature_ctabtn'.$cta.'_size'] : '12px';
						$display = $GLOBALS['signature_ctabtn'.$cta.'_display'] == 1 ? 'inline-flex' : 'none';
						$GLOBALS['signature_ctabtn'.$cta] = '<td style="border-collapse:collapse; padding-right:5px;"><a href="javascript:void(0);" class="layout_ctabtn'.$cta.'" style="border-radius:'.$shape.'; background-color:'.$bgcolor.'; color:#ffffff; padding:4px 15px; font-size:'.$font.'; display:inline-flex; align-items:center; justify-content: center; text-decoration:none; margin:10px 0 0 0;">'.$ctabtnicon.' '.$GLOBALS['signature_ctabtnname'.$cta].'</a></td>';
					}else{
						// $GLOBALS['signature_ctabtn'.$cta] = '';
						$GLOBALS['signature_ctabtn'.$cta] = '<td style="border-collapse:collapse; padding-right:5px;"><a href="javascript:void(0);" class="layout_ctabtn'.$cta.' ctaeditpre'.$cta.'" style="border-radius:0px; background-color:rgb(0, 0, 0); color:#ffffff; padding:4px 15px; font-size:fontsizesel12px; display:none; align-items:center; justify-content: center; text-decoration:none; margin:10px 0 0 0;"></a></td>';
					}

				}


				$iconsize = $GLOBALS['signature_socialsize'] !="" ? $GLOBALS['signature_socialsize'] : 30;
				$sociallink = $GLOBALS['IMAGE_LINK'].'/images/social/'.$GLOBALS['signature_socialdesign'];
				$GLOBALS['signature_socialicons'] =''; $GLOBALS['signature_socialiconsv'] ='';  $GLOBALS['signature_marketplacebtns'] ='';

				foreach($GLOBALS['social_icons_arr'] as $icons){
					$iconname = $icons['iconname'];
					$GLOBALS['signature_socialicons'] .= $GLOBALS['signature_'.$iconname.'_icon'];
					$GLOBALS['signature_socialiconsv'] .= $GLOBALS['signature_'.$iconname.'_iconv'];
				}
				if($GLOBALS['signature_socialiconsv'] == ''){
					$GLOBALS['signature_social_border_display'] = 'none';
					$GLOBALS['signature_between_gap_display'] = 'none';
				}else{
					$GLOBALS['signature_social_border_display'] = 'compact';
					$GLOBALS['signature_between_gap_display'] = 'revert';
				}

				$GLOBALS['signature_marketplacebtns'] = $GLOBALS['signature_appstorebtn_btn'].$GLOBALS['signature_playstorebtn_btn'].$GLOBALS['signature_amazonbtn_btn'].$GLOBALS['signature_ebaybtn_btn'];

				if($GLOBALS['signature_layout'] == $layout_id){
					$GLOBALS['lauout_load'] = '<div class="sin_dashboard_box signature_layot"><input type="radio" name="layout_id" id="layout_id'.$layout_id.'" class="d-none imgbgchk" value="'.$layout_id.'" required="required" '.$selected.'><table class="signature_tbl_main" style="font-family:'.$GLOBALS['signature_fontfamily'].'; line-height:'.$GLOBALS['signature_lineheight'].';" width="454" cellspacing="0" cellpadding="0" border="0"><tr><td>'.$GLOBALS['CLA_HTML']->addContent($layoutRow['layout_desc']).'</td></tr></table></div>';
				}
				$selected = $GLOBALS['signature_layout'] == $layout_id ? 'checked="checked"' :'';
				$templateTitle = "<p>Template ".$templet_no."</p>";
				if($layout_id >= 13){
					$templateTitle = "<div class='flex items-center justify-between mb-3'>
					<p class='text-gray-400'>Template ".$templet_no."</p>
					<div class='px-2 py-1 bg-gradient text-xs text-white rounded-full'>New Layout</div>
					</div>";
				}
				$GLOBALS['signature_profilesize'] = $layoutRow['profile_image_size'];
				$GLOBALS['signature_profileanimationsize'] = $layoutRow['profile_image_size'];
				$GLOBALS['signature_logosize'] = "100";
				 $GLOBALS['layout_list'] .= $templateTitle.'<div class="sin_dashboard_box signature_layot">
				 <input type="radio" name="layout_id" id="layout_id'.$layout_id.'" class="d-none imgbgchk" value="'.$layout_id.'" required="required" '.$selected.' data-layout_divider_padding_remove="'.$layoutRow['layout_divider_padding_remove'].'">
				 <label class="layout_id" for="layout_id'.$layout_id.'">
				 <table class="signature_tbl_main" style="font-family:'.$GLOBALS['signature_fontfamily'].'; line-height:'.$GLOBALS['signature_lineheight'].';" width="454" cellspacing="0" cellpadding="0" border="0">
					<tr>
						<td>'.$GLOBALS['CLA_HTML']->addContent($layoutRow['layout_desc']).'</td>
					</tr>
				 </table>
				 <div class="tick_container">
					<div class="tick">
						<img src="'.$GLOBALS['IMAGE_LINK'].'/images/right-icon.png" alt="">
					</div>
				</div>
				<input type="hidden" id="profile_image_size'.$layout_id.'" value="'.$layoutRow['profile_image_size'].'"/>
				</label>
			</div>';
				$templet_no++;

		}


	}

	public function getDemoSignature($signature_id){
		if($signature_id == 0){
			$signature_lists = $GLOBALS['DB']->query("SELECT SG.*,SL.* FROM `signature` SG LEFT JOIN signature_layout SL ON SG.layout_id = SL.layout_id  WHERE SG.signature_status = 0 AND SG.signature_id = 0 AND SG.user_id = 0 LIMIT 0,1");
		}else{
			$signature_lists = $GLOBALS['DB']->query("SELECT SG.*,SL.* FROM `signature` SG LEFT JOIN signature_layout SL ON SG.layout_id = SL.layout_id  WHERE SG.signature_status = 1 AND SG.signature_id = ? AND SG.user_id = ? LIMIT 0,1",array($signature_id,$GLOBALS['USERID']));
		}
		if($signature_lists){
			foreach($signature_lists as $sRow){
			$this->getlayoutStyle('get',$sRow['signature_style']);
			$this->getCustomField($signature_id);
			$signature_id = $sRow['signature_id'];
			$GLOBALS['signature_layout'] = $sRow['layout_id'];
			$GLOBALS['signature_firstname_text'] = $GLOBALS['USERNAME'];
			$GLOBALS['signature_company_text'] = $GLOBALS['USERORGANIZATION'];
			$GLOBALS['signature_jobtitle_text'] = $sRow['signature_jobtitle'];
			$GLOBALS['signature_profile'] = $GLOBALS['IMAGE_LINK'].'/images/profile-img1.png';
			$GLOBALS['signature_banner'] = $GLOBALS['IMAGE_LINK'].'/images/banner-img1.png';
			$GLOBALS['profile_image_size'] = $sRow['profile_image_size'];


			$GLOBALS['signature_custombtn'] = $sRow['signature_custombtn'];
			$GLOBALS['signature_custombtnlink'] =  $sRow['signature_custombtnlink'];
			$GLOBALS['signature_custombtntext'] = $sRow['signature_custombtntext'];

			$GLOBALS['signature_socialdesign'] = $sRow['signature_socialdesign'];
			$GLOBALS['signature_btndesign'] = $sRow['signature_btndesign'];
			$GLOBALS['signature_marketbtndesign'] = $sRow['signature_marketbtndesign'];

			$GLOBALS['signature_website'] = $sRow['signature_website'];

			// cta button
			$GLOBALS['signature_ctabtnname1'] = $sRow['signature_ctabtnname1'];
			$GLOBALS['signature_ctabtnname2'] = $sRow['signature_ctabtnname2'];
			$GLOBALS['signature_ctabtnname3'] = $sRow['signature_ctabtnname3'];
			$GLOBALS['signature_ctabtnlink1'] = $sRow['signature_ctabtnlink1'];
			$GLOBALS['signature_ctabtnlink2'] = $sRow['signature_ctabtnlink2'];
			$GLOBALS['signature_ctabtnlink3'] = $sRow['signature_ctabtnlink3'];


			foreach($GLOBALS['social_icons_arr'] as $icons){
				$iconname =  'signature_'.$icons['iconname'];
				$GLOBALS[$iconname] = $sRow[$iconname];
				$GLOBALS[$iconname.'_chk'] = $sRow[$iconname] != "" ? 'checked' : "";
				$GLOBALS[$iconname.'_show'] = $sRow[$iconname] != "" ? 'show' : "none";

				$iconsize = $GLOBALS['signature_socialsize'] !="" ? $GLOBALS['signature_socialsize'] : 30;
				$socialimg = $GLOBALS['IMAGE_LINK'].'/images/social/animation/'.$GLOBALS['signature_socialdesign'].'/'.$icons['iconname'].'-icon.gif';
				$GLOBALS[$iconname.'_icon'] = $sRow[$iconname] !="" ? '<td class="layout-'.$icons['iconname'].'-icon sicon" style="padding:0 4px 0 0"><a href="'.addhttp($GLOBALS[$iconname]).'" target="_blank"><img alt="" src="'.$socialimg.'" width="'.$iconsize.'" /></a></td>' : '<td class="layout-'.$icons['iconname'].'-icon sicon" style="padding:0 4px 0 0; display:'.$GLOBALS[$iconname.'_show'].';"><a href="#" target="_blank"><img alt="" src="'.$socialimg.'" width="'.$iconsize.'" /></a></td>';

				$GLOBALS[$iconname.'_iconv'] = $sRow[$iconname] !="" ? '<tr><td class="layout-'.$icons['iconname'].'-icon sicon" style="padding:5px 0 0 0"><a href="'.addhttp($GLOBALS[$iconname]).'" target="_blank"><img alt="" src="'.$socialimg.'" width="'.$iconsize.'" /></a></td></tr>' : '<tr><td class="layout-'.$icons['iconname'].'-icon sicon" style="padding:5px 0 0 0; display:'.$GLOBALS[$iconname.'_show'].';"><a href="#" target="_blank"><img alt="" src="'.$socialimg.'" width="'.$iconsize.'" /></a></td></tr>';
			}

			foreach($GLOBALS['marketplace_btn_arr'] as $mbtn){
				$btnname =  'signature_'.$mbtn['iconname'];
				$GLOBALS[$btnname] = $sRow[$btnname];
				$GLOBALS[$btnname.'_chk'] = $sRow[$btnname] != "" ? 'checked' : "";
				$btnsize = $GLOBALS['signature_marketbtnsize'] !="" ? $GLOBALS['signature_marketbtnsize'] : 80;
				$GLOBALS['signature_marketbtndesign']=1;
				$GLOBALS['signature_marketbtnanimation'] = 1;
				if($GLOBALS['signature_marketbtnanimation'] == 1){
					$marketplaceimg = $GLOBALS['IMAGE_LINK'].'/images/marketplace/animation/'.$GLOBALS['signature_marketbtndesign'].'/'.$mbtn['iconname'].'-btn.gif';
				}else{
					$marketplaceimg = $GLOBALS['IMAGE_LINK'].'/images/marketplace/static/'.$GLOBALS['signature_marketbtndesign'].'/'.$mbtn['iconname'].'-btn.png';
				}
				$GLOBALS[$btnname.'_btn'] = $sRow[$btnname] !="" ? '<td style="padding:10px 4px 0 0;" class="layout-'.$mbtn['iconname'].'-btn mbtn"><a href="'.addhttp($GLOBALS[$btnname]).'" target="_blank"><img style="display : block;" alt="" src="'.$marketplaceimg.'" width="'.$btnsize.'" /></a></td>' : '<td style="display:none; padding:10px 4px 0 0;" class="layout-'.$mbtn['iconname'].'-btn mbtn"><a href="#" target="_blank"><img style="display : block;" alt="" src="'.$marketplaceimg.'" width="'.$btnsize.'"  /></a></td>';
			}
			$GLOBALS['cusbtnchk_'.$sRow['signature_custombtn']] = 'checked';
			$GLOBALS['signature_btndesign_sel'.$sRow['signature_btndesign']] = 'checked';
			$GLOBALS['signature_socialdesign_sel'.$sRow['signature_socialdesign']] = 'checked';
			$GLOBALS['signature_marketbtndesign_sel'.$sRow['signature_marketbtndesign']] = 'checked';
			$GLOBALS['signature_socialanimation_sel'.$sRow['signature_socialanimation']] = 'checked';
			$GLOBALS['signature_profileanimation_gif_sel1'] = 'checked';
			}


			return $sRow['signature_id'];
        }
        return false;
    }

	public function getlayoutStyle($action,$style=''){
		if($action == 'add'){
			if($_POST){
				$field_array = array('label','firstname','jobtitle','company');

				$stylearr['signature_fontfamily'] =  $_POST['signature_fontfamily'];
				$stylearr['signature_lineheight'] = $_POST['signature_lineheight'] == "" ? "14px" : $_POST['signature_lineheight'];
				$stylearr['signature_socialsize'] = $_POST['signature_socialsize'] == "" ? "30" : $_POST['signature_socialsize'];
				$stylearr['signature_custombtnsize'] = $_POST['signature_custombtnsize'] == "" ? "80" : $_POST['signature_custombtnsize'];
				$stylearr['signature_marketbtnsize'] = $_POST['signature_marketbtnsize'] == "" ? "80" : $_POST['signature_marketbtnsize'];
				$stylearr['signature_logosize'] = $_POST['signature_logosize'] == "" ? "100" : $_POST['signature_logosize'];
				$stylearr['signature_bannershape'] =  $_POST['signature_bannershape'] =="" ? "0px" : $_POST['signature_bannershape'];
				$stylearr['signature_bannersize'] = $_POST['signature_bannersize'] == "" ? "400" : $_POST['signature_bannersize'];
				$stylearr['signature_border'] = $_POST['signature_border'] ;
				$stylearr['signature_divider'] = $_POST['signature_divider'];
				$stylearr['signature_bordercolor'] = $_POST['signature_bordercolor'] ;
				$stylearr['signature_dividercolor'] = $_POST['signature_dividercolor'];
				$stylearr['signature_borderwidth'] = $_POST['signature_borderwidth'] == "" ? "0px" : $_POST['signature_borderwidth'].'px';
				$stylearr['signature_dividerwidth'] = $_POST['signature_dividerwidth'] == "" ? "1px" : $_POST['signature_dividerwidth'].'px';
				$stylearr['profile_disply'] = $_POST['profile_disply'] == 1 ? "inline" : "none";
				$stylearr['profile_container_disply'] = $_POST['profile_disply'] == 1 ? "revert" : "none";
				$stylearr['signature_profileshape'] =  $_POST['signature_profileshape'] =="" ? "0px" : $_POST['signature_profileshape'];
				$stylearr['signature_profilesize'] = $_POST['signature_profilesize'] == "" ? "50" : $_POST['signature_profilesize'];
				$stylearr['signature_banner_display'] = $_POST['signature_banner_display'] == 1 ? "inline" : "none";
				$stylearr['verified_display'] = $_POST['signature_verified'] == 1 ? "inline" : "none";
				$stylearr['signature_ctabtn1'] = array('size'=>$_POST['signature_ctabtnsize1'],'shape'=>$_POST['signature_ctabtnshape1'],'icon'=>$_POST['signature_ctabtnicon1'],'bgcolor'=>$_POST['signature_ctabtnbgcolor1'],'icon'=>$_POST['signature_ctabtnicon1'],'display'=>$_POST['signature_ctabtndisplay1']);
				$stylearr['signature_ctabtn2'] = array('size'=>$_POST['signature_ctabtnsize2'],'shape'=>$_POST['signature_ctabtnshape2'],'icon'=>$_POST['signature_ctabtnicon2'],'bgcolor'=>$_POST['signature_ctabtnbgcolor2'],'icon'=>$_POST['signature_ctabtnicon2'],'display'=>$_POST['signature_ctabtndisplay2']);
				$stylearr['signature_ctabtn3'] = array('size'=>$_POST['signature_ctabtnsize3'],'shape'=>$_POST['signature_ctabtnshape3'],'icon'=>$_POST['signature_ctabtnicon3'],'bgcolor'=>$_POST['signature_ctabtnbgcolor3'],'icon'=>$_POST['signature_ctabtnicon3'],'display'=>$_POST['signature_ctabtndisplay3']);

				// FOR image profile annimation
				$stylearr["signature_profileanimation1"] = array("signature_profileanimation" => $_POST['signature_profileanimation'], "signature_profileanimation_gif_name" => $_POST['signature_profileanimation_gif_name'],"signature_profileanimation_gif" => $_POST['signature_profileanimation_gif']);

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
				$GLOBALS['verified_display'] = 'checked'; $GLOBALS['verified_display_sel'] = 'checked'; $GLOBALS['signature_bannershape'] = '0px';  $GLOBALS['signature_bordercolor'] = '#E2E2E2'; $GLOBALS['signature_dividercolor'] = '#E2E2E2'; $GLOBALS['signature_profileanimation_gif_zindex'] = '-1';
				$GLOBALS['signature_profileanimation_maxheight'] = "inherit";
				$GLOBALS['signature_profileanimation_gif_display'] = "none"; $GLOBALS['signature_profileanimation_gif_src']= $GLOBALS['signature_profile'] = $GLOBALS['UPLOAD_LINK'].'/signature/gifs/giphyy-1.gif';$GLOBALS['signature_banner_display_sel'] = "";$GLOBALS['signature_profileshape'] = '0px';$GLOBALS['signature_banner_display'] = "none";  $GLOBALS['signature_borderpadding'] = "25px"; 						
				$GLOBALS['signature_dividerpadding'] = '0 0 0 15px';
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
			}else{
				return false;
			}

		}
	}

	public function getCustomField($signature_id){
		if($signature_id == 0){
			$fields = $GLOBALS['DB']->query("SELECT * FROM signature_customfield WHERE signature_id= ? AND field_id IN(1,2,3,4) ORDER BY field_order ASC LIMIT 0,4",array($signature_id));
		}else{
			$fields = $GLOBALS['DB']->query("SELECT * FROM signature_customfield WHERE signature_id= ? ORDER BY field_order ASC LIMIT 0,4",array($signature_id));
		}
		$field_count =0; $email_count =0;$phone_count=0; $text_count=0; $fax_count=0; $website_count=0; $address_count=0; $hyperlink_count=0; $disclaimer_count=0;
		$fieldvar ='';
		if(is_array($fields)){
			foreach($fields as $field){
				$fieldtype = $field['field_type'];
				$fieldlabel = ucfirst($fieldtype);
				$fieldlabelval = $field['field_label'];
				$fieldvalue = $field['field_value'];
				$fieldfontsize = $field['field_fontsize'];
				$fieldfontweight = $field['field_fontweight'];
				$fieldfontstyle = $field['field_fontstyle'];
				$fieldcolor = $field['field_color'];
				$fieldorder = $field['field_order'];

				${'fontsizesel'.$fieldfontsize} = 'selected';
				$fontweightchk = $fieldfontweight == 'bold' ? 'checked':'';
				$fonstylechk = $fieldfontstyle == 'italic' ? 'checked':'';
				switch($fieldtype){
					case 'email':
						$email_count++;
						$fieldid = 'e'.$email_count;
						$layout_class = 'layout_email'.$email_count;
						$layout_labelclass = 'layout_email_label'.$email_count;
						break;
					case 'phone':
						$phone_count++;
						$fieldid = 'p'.$phone_count;
						$layout_class = 'layout_phone'.$phone_count;
						$layout_labelclass = 'layout_phone_label'.$phone_count;
						break;
					case 'text':
						$text_count++;
						$fieldid = 't'.$text_count;
						$layout_class = 'layout_text'.$text_count;
						$layout_labelclass = 'layout_text_label'.$text_count;
						break;
					case 'fax':
						$fax_count++;
						$fieldid = 'f'.$fax_count;
						$layout_class = 'layout_fax'.$fax_count;
						$layout_labelclass = 'layout_fax_label'.$fax_count;
						 break;
					case 'website':
						$website_count++;
						$fieldid = 'w'.$website_count;
						$layout_class = 'layout_website'.$website_count;
						$layout_labelclass = 'layout_website_label'.$website_count;
						break;
					case 'address':
						$address_count++;
						$fieldid = 'a'.$address_count;
						$layout_class = 'layout_address'.$address_count;
						$layout_labelclass = 'layout_address_label'.$address_count;
						 break;
					case 'hyperlink':
						$hyperlink_count++;
						$fieldid = 'h'.$hyperlink_count;
						$layout_class = 'layout_hyperlink'.$hyperlink_count;
						$layout_labelclass = 'layout_hyperlink_label'.$hyperlink_count;
						break;
					case 'disclaimer':
						$disclaimer_count++;
						$fieldid = 'd'.$disclaimer_count;
						$layout_class = 'layout_disclaimer'.$disclaimer_count;
						$layout_labelclass = 'layout_disclaimer_label'.$disclaimer_count;
						 break;
					default:
					 	break;
				}

				$fieldno  = ${$fieldtype.'_count'};
				if($fieldtype == 'hyperlink'){
					$GLOBALS['signature_cf_'.$fieldtype.'t'.$fieldno] = '';
					$GLOBALS['signature_cf_'.$fieldtype.$fieldno] = '<span class="'.$layout_labelclass.' label" style="font-weight:'.$GLOBALS['label_bold'].'; font-style:'.$GLOBALS['label_italic'].'; color:'.$GLOBALS['label_bold'].'; font-size:'.$GLOBALS['label_fontsize'].';"></span><a href="'.addhttp($fieldvalue).'" target="_blank" class="'.$layout_class.' label" style="text-decoration:underline; font-weight:'.$fieldfontweight.'; font-style:'.$fieldfontstyle.'; color:'.$fieldcolor.'; font-size:'.$fieldfontsize.'; display:none;">'.$fieldlabelval.'</a>';

				}else{
					// $GLOBALS['signature_cf_'.$fieldtype.'t'.$fieldno] = '<span class="'.$layout_labelclass.' label" style="font-weight:'.$GLOBALS['label_bold'].'; font-style:'.$GLOBALS['label_italic'].'; color:'.$GLOBALS['label_bold'].'; font-size:'.$GLOBALS['label_fontsize'].';">'.$fieldlabelval.'</span>';
					$GLOBALS['signature_cf_'.$fieldtype.$fieldno] = '<span class="'.$layout_labelclass.' label" style="font-weight:'.$GLOBALS['label_bold'].'; font-style:'.$GLOBALS['label_italic'].'; color:'.$GLOBALS['label_bold'].'; font-size:'.$GLOBALS['label_fontsize'].';"></span><span class="'.$layout_class.'" style="font-weight:'.$fieldfontweight.'; font-style:'.$fieldfontstyle.'; color:'.$fieldcolor.'; font-size:'.$fieldfontsize.';">'.$fieldvalue.'</span>';
				}

				if($fieldtype == 'text'){ $fieldvar = 1; }
				$GLOBALS['signature_custom_fields'. $fieldvar].='<div class="inputbox"><div class="row"><div class="col-md-3"><div class="form-floating"><input type="text" class="form-control" name="field_label[]" id=""  placeholder="Title" data-class="'.$layout_labelclass.'"><label for="">Title</label></div></div><div class="col-md-5"><div class="form-floating"><input type="text" class="form-control" id="" name="custom_field[]" value="'.$fieldvalue.'" data-class="'.$layout_class.'"><label for="">'.$fieldlabel.'</label><input type="hidden" name="custom_fieldtype[]" value="'.$fieldtype.'"></div></div><div class="col-md-4"><div class="custome_icon"><input type="checkbox" name="field_fontweight['.$field_count.']" id="bold-icon-'.$fieldid.'" class="d-none imgbgchk style_bold" value="1" data-class="'.$layout_class.'" '.$fontweightchk.'><label for="bold-icon-'.$fieldid.'" class="iconcheckbox collapsed" data-bs-toggle="collapse" href="#bold" role="button" aria-expanded="false" aria-controls="bold"><img src="'.$GLOBALS['IMAGE_LINK'].'/images/bold-icon.svg" alt=""><div class="tick_container"><div class="tick bold"><img src="'.$GLOBALS['IMAGE_LINK'].'/images/bold-icon-hover.svg" alt=""></div></div></label></div><div class="custome_icon"><input type="checkbox" name="field_fontstyle['.$field_count.']" id="italic-icon-'.$fieldid.'" class="d-none imgbgchk style_italic" value="1" data-class="'.$layout_class.'" '.$fonstylechk.' ><label for="italic-icon-'.$fieldid.'" class="iconcheckbox collapsed" data-bs-toggle="collapse" href="#italic" role="button" aria-expanded="false" aria-controls="italic"><img src="'.$GLOBALS['IMAGE_LINK'].'/images/italic-icon.svg" alt=""><div class="tick_container"><div class="tick italic"><img src="'.$GLOBALS['IMAGE_LINK'].'/images/italic-icon-hover.svg" alt=""></div></div></label></div><div class="color_picker"><input type="color" name="field_color[]" class="form-control form-control-color" id="exampleColorInput" value="'.$fieldcolor.'" title="Choose your color" data-class="'.$layout_class.'"></div><div class="custome_icon"><select class="select_small_box" data-class="'.$layout_class.'" name="field_fontsize[]"><option value="10px" '.$fontsizesel10px.'>Small</option><option value="12px" '.$fontsizesel12px.'>Normal</option><option value="14px" '.$fontsizesel14px.'>Large</option><option value="16px" '.$fontsizesel16px.'>Huge</option></select></div><a href="javascript:void(0);" class="remove_cusfield" data-id="'.$fieldtype.'" data-number="'.$fieldno.'"><img src="'.$GLOBALS['IMAGE_LINK'].'/images/delete.svg" alt="Remove"></a></div></div></div>';

				$field_count++;

			}

		}

			$fieldspans = array('email','phone','text','fax','website','address','hyperlink','disclaimer');
			foreach($fieldspans as $span){
				$field_left = ${$span.'_count'};
				$fieldfontweight = 'normal'; $fieldfontstyle = 'normal'; $fieldcolor='#000000'; $fieldfontsize ='12px';
				if($field_left == 0){$leftcount =1;}else{ $leftcount =  (${$span.'_count'} +1); }
				for($i=$leftcount; $i <= 5; $i++){
					$layout_labelclass1 = 'layout_'.$span.'_label'.$i;
					$layout_class1 = 'layout_'.$span.$i;
					if($span == 'hyperlink'){
						$GLOBALS['signature_cf_'.$span.'t'.$i]  = '';
						$GLOBALS['signature_cf_'.$span.$i] = '<span class="'.$layout_labelclass1.' label" style="font-weight:'.$GLOBALS['label_bold'].'; font-style:'.$GLOBALS['label_italic'].'; color:'.$GLOBALS['label_bold'].'; font-size:'.$GLOBALS['label_fontsize'].';"></span><a href="#" class="'.$layout_class1.' label" style="font-weight:'.$fieldfontweight.'; font-style:'.$fieldfontstyle.'; color:'.$fieldcolor.'; font-size:'.$fieldfontsize.'; display:none;"></a>';
					}else{
						// $GLOBALS['signature_cf_'.$span.'t'.$i] = '<span class="'.$layout_labelclass1.' label" style="font-weight:'.$GLOBALS['label_bold'].'; font-style:'.$GLOBALS['label_italic'].'; color:'.$GLOBALS['label_color'].'; font-size:'.$GLOBALS['label_fontsize'].';"></span>';
						$GLOBALS['signature_cf_'.$span.$i] = '<span class="'.$layout_labelclass1.' label" style="font-weight:'.$GLOBALS['label_bold'].'; font-style:'.$GLOBALS['label_italic'].'; color:'.$GLOBALS['label_bold'].'; font-size:'.$GLOBALS['label_fontsize'].';"></span><span class="'.$layout_class1.'" style="font-weight:'.$fieldfontweight.'; font-style:'.$fieldfontstyle.'; color:'.$fieldcolor.'; font-size:'.$fieldfontsize.';"></span>';
					}
				}

			}

		$GLOBALS['signature_custom_fields'].= '<input id="fieldcount" type="hidden" value="'.$field_count.'" data-emailcount="'.($email_count+1).'" data-phonecount="'.($phone_count+1).'" data-textcount="'.($text_count+1).'" data-faxcount="'.($fax_count+1).'" data-websitecount="'.($website_count+1).'" data-addresscount="'.($address_count+1).'" data-hyperlinkcount="'.($hyperlink_count+1).'" data-disclaimercount="'.($disclaimer_count+1).'">';

	}


	public function getSignatureLogo($limit=0){
		if($limit == 0){
			$logoRow = $GLOBALS['DB']->row("SELECT * FROM signature_logo WHERE user_id = ? LIMIT 0,1",array($GLOBALS['USERID']));
			$GLOBALS['newsignature_img'] = $logoRow['logo'];
			$GLOBALS['newsignature_cimg']  = $logoRow['logo_animation'];
			$GLOBALS['newsignature_process']  = $logoRow['logo_process'];
		}else{
			$GLOBALS['newsignature_img'] = $_POST['signature_image'];
			$GLOBALS['newsignature_cimg']  = '';
			$GLOBALS['newsignature_process']  =0;
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

	// code is for saving signature html(to use in deploy) START

	// public function saveSignatureHtml() {
	// 	if($_POST['html']){
	// 		$html = $_POST['html'];
	// 		$signature_id = $_REQUEST['id'];
	// 		if($signature_id){
	// 				$data = array("outlook_html" => $html);
	// 			$where = array('signature_id'=>$signature_id);
	// 			$addSignature = $GLOBALS['DB']->update("signature",$data,$where);
	// 			if($addSignature){
	// 				$return_arrs = array('error'=>1,'msg'=>'Signature saved successfully');
					
	// 				echo json_encode($return_arrs); exit;
	// 			}else{
	// 				$return_arrs = array('error'=>1,'msg'=>'Something went wrong while saving signature html');
	// 				echo json_encode($return_arrs); exit;
	// 			}

	// 		}
	// 		else{
	// 			$return_arrs = array('error'=>1,'msg'=>'Something went wrong with signature, please try again');
	// 			echo json_encode($return_arrs); exit;
	// 		}
	// 	}else{
	// 		$return_arrs = array('error'=>1,'msg'=>'Something went wrong, please try again');
	// 		echo json_encode($return_arrs); exit;
	// 	}
	// }

	// code is for saving signature html(to use in deploy) END

	public function getUserLocation($ip = null) {
        // Use given IP or detect automatically
        if (!$ip) {
            if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
            } else {
                $ip = $_SERVER['REMOTE_ADDR'];
            }
        }

        // TEMP: Override for local testing
        if ($ip === '127.0.0.1' || $ip === '::1') {
            $ip = '164.92.90.31'; // Replace with your own IP if needed
        }

        $response = @file_get_contents("http://ip-api.com/json/{$ip}?fields=status,country,regionName,city");

        if ($response !== false) {
            $data = json_decode($response, true);
            if ($data['status'] === 'success') {
                return ['ip'=>$ip, 'location' => [
							'country' => $data['country'],
							'region' => $data['regionName'],
							'city' => $data['city']
						]
					];
            }
        }
		return ['ip'=>$ip, 'location' => [
				'country' => 'Unknown',
				'region' => 'Unknown',
				'city' => 'Unknown'
				]
			];
    }
}

?>
