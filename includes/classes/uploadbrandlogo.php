<?php
// require_once($GLOBALS['BASE_LINK'].'/'.GetConfig('CLASSES').'/dashboard.php');   // code is for saving signature html(to use in deploy)
class CIT_UPLOADBRANDLOGO
{

	public function __construct()
	{
		if(!isset($_SESSION[GetSession('user_id')])){
			GetFrontRedirectUrl(GetUrl(array('module'=>'signin')));
		}

		if(isset($_REQUEST['department_id'])){
			$GLOBALS['current_department_id_add_sign'] = $_REQUEST['department_id'];
		}else{
			$GLOBALS['current_department_id_add_sign'] = 0;
		}
	}

	public function displayPage(){
		AddMessageInfo();
		if(isset($_REQUEST['category_id'])){
			$action = trim($_REQUEST['category_id']);
		} else {
			$action = '';
		}
		if($_REQUEST['category_id'] == 'uploadlogo'){

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
					// $src = $GLOBALS['IMAGE_LINK']."/images/img-icon.svg";
					// checking file is image or not
					$result = $GLOBALS['S3Client']->putObject(array( // upload image s3bucket
						'Bucket'=>$GLOBALS['BUCKETNAME'],
						'Key' =>  'upload-beta/signature/'.$GLOBALS['USERID'].'/'.$filename,
						'SourceFile' => $location,
						'StorageClass' => 'REDUCED_REDUNDANCY',
						'ACL'   => 'public-read'
					));

					$src = $GLOBALS['UPLOAD_LINK'].'/signature/'.$GLOBALS['USERID'].'/'.$filename;
					$GLOBALS['DB']->update("registerusers",array('user_uploadlimit'=>0),array('user_id'=>$GLOBALS['USERID']));
					$_SESSION[GetSession('user_uploadlimit')] =0; 	// update logo upload limit for user
					$GLOBALS['signature_image_name_value'] = $filename;
					$return_arr = array("name" => $filename,"displayname" => $displayname, "size" => $filesize, "src"=> $src, "error"=>0);
				}
			}else{
				$return_arr = array("error" =>1, "msg"=>"please upload valid png or svg image");
			}
			echo json_encode($return_arr); exit;
		}

		if($_REQUEST['category_id'] == 'savelogo'){
			$GLOBALS['DB']->insert("signature_logo",array('user_id'=>$GLOBALS['USERID'],'logo'=>$_POST['signature_image']));
			$return_arr = array("error" =>0, "msg"=>"Logo uploaded successfully.");
			$GLOBALS['DB']->update("registerusers",array('user_uploadlimit'=>0),array('user_id'=>$GLOBALS['USERID']));
			$_SESSION[GetSession('user_uploadlimit')] =0;
			echo json_encode($return_arr); exit;
		}

		if($GLOBALS['USERUPLOADLIMIT'] == 0){
			$GLOBALS['activetabclass2'] ='active';
		}else{
			$GLOBALS['activetabclass1'] ='active';
		}


		$this->getPage();
		
		$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/uploadbrandlogo.html');
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

	
	public function getPage(){
		$row = $GLOBALS['DB']->row("SELECT * FROM `pages` WHERE `seourl`= ? LIMIT 0,1",array($_REQUEST['module'],0));
		$GLOBALS['MetaTitle'] =  $row['metatitle'] !="" ? $row['metatitle'] : $GLOBALS['SITE_TITLE'];
		$GLOBALS['Metakeywords'] = $row['metakeywords'];
		$GLOBALS['Metadescription'] =  $row['metadescription'];
		$GLOBALS['PageId'] = $row['id'];
		$GLOBALS['PageName'] = $row['name'];
		$GLOBALS['PageDesc'] = $GLOBALS['CLA_HTML']->addContent($row['desc']);
	}

}

?>
