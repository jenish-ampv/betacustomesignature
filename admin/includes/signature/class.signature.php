<?php
require_once(GetConfig('SITE_BASE_PATH').'/admin/includes/display/Image.php');
require_once(GetConfig('SITE_BASE_PATH').'/lib/s3bucket/s3bucketinit.php');
$GLOBALS['user_type']= $_SESSION[GetSession('AdminType')];


class CIT_SIGNATURE{
	private $count;
	private $result;
	private $user;
	private $id ='';
	public function __construct(){
		$GLOBALS['ModuleName'] = 'Signature';

	}
	public function displayPage(){

		if(isset($_REQUEST['action'])){
			$action = trim($_REQUEST['action']);
		} else {
			$action = '';
		}
		switch($action){
			case "view":
				$this->viewSignature();
				break;
			case "processstatus":
				$this->statusProcessSignature();
				break;
			case "datatableregdata";
				$this->datatableSignature();
				break;
			case "viewlogo";
				$this->viewChangeLogoSignature();
				break;
			case "viewdepartmentlogo";
				$this->viewDepartmentLogoSignature();
				break;
			case "datatableregdatadepartment";
				$this->datatableDepartmentSignature();
				break;
			default:
				$this->Signature();
				break;
		}
	}

	private function statusProcessSignature(){
			ini_set('display_errors', 1);
		ini_set('display_startup_errors', 1);
		error_reporting(E_ALL);

		if(isset($_REQUEST['id'])){
			$id = $_REQUEST['id'];
		} else {
			$id =  '';
		}

		if(is_numeric($id)){
			if($_REQUEST['status'] == 1){  // check logo is uploaded
				$sRow = $GLOBALS['DB']->row("select * FROM `signature_logo` SL LEFT JOIN registerusers RU ON SL.user_id = RU.user_id  WHERE SL.id = ?",array($id));
				$signature_imgchk = GetConfig('SITE_UPLOAD_PATH').'/signature/complete/'.$sRow['user_id'].'/'.$sRow['logo_animation'];
				if (file_exists($signature_imgchk) && !is_file($signature_imgchk)) {
					$_SESSION['Error'] = '<div class="alert alert-danger">please upload signature logo image for status complete.</div>';
					GetAdminRedirectUrl(GetAdminUrl(array('module'=>$_REQUEST['module']),0));
				}

				//$revRow = $GLOBALS['DB']->row("SELECT * FROM signature_feedback WHERE signature_id = ? AND user_id=?",array($id,$sRow['user_id']));

				if($sRow['logo_process'] == 3){ // send logo revision complete mail
					$message= _getEmailTemplate('revision_completed');
					_SendMail($sRow['user_email'],'',$GLOBALS['EMAIL_SUBJECT'],$message);
					$addResult = $GLOBALS['DB']->update('signature_logo',array('logo_process' => 2, 'logo_change_process' => 2),array('id'=>$sRow['id']));
				}else{ // send logo animation complete mail
					$message= _getEmailTemplate('animation_ready');
					_SendMail($sRow['user_email'],'',$GLOBALS['EMAIL_SUBJECT'],$message);
					$addResult = $GLOBALS['DB']->update('signature_logo',array('logo_process' => 1, 'logo_change_process' => 1),array('id'=>$sRow['id']));
				}

			}
			//$addResult = $GLOBALS['DB']->update('signature',array('signature_process' => $_REQUEST['status']),array('signature_id'=>$_REQUEST['id']));

			if($addResult){
				$_SESSION['Success'] = '<div class="gap-8 py-5 px-4 pl-11 border-l-9 border-green-600 rounded-xl relative bg-white bg-gradient-to-r from-[#00B71B]/12 to-[#00B71B]/0 shadow-lg">Process changed successfully</div>';
			} else {
				$_SESSION['Error'] = '<div class="alert alert-danger">An error occurred while you trying to change status, please try again.</div>';
			}
			GetAdminRedirectUrl();
		} else {
			$_SESSION['Error'] = '<div class="alert alert-danger">Record not valid.</div>';
			GetAdminRedirectUrl(GetAdminUrl(array('module'=>$_REQUEST['module']),0));
		}
	}


	private function statusSignature(){
		if(isset($_REQUEST['id'])){
			$signature_id = $_REQUEST['id'];
		} else {
			$signature_id =  '';
		}

		if(is_numeric($signature_id)){

			$addResult = $GLOBALS['DB']->update('signature',array('signature_status' => $_REQUEST['status']),array('signature_id'=>$_REQUEST['id']));

			if($addResult){
				$_SESSION['Success'] = '<div class="gap-8 py-5 px-4 pl-11 border-l-9 border-green-600 rounded-xl relative bg-white bg-gradient-to-r from-[#00B71B]/12 to-[#00B71B]/0 shadow-lg">Status changed successfully</div>';
			} else {
				$_SESSION['Error'] = '<div class="alert alert-danger">An error occurred while you trying to change status, please try again.</div>';
			}
			GetAdminRedirectUrl();
		} else {
			$_SESSION['Error'] = '<div class="alert alert-danger">Record not valid.</div>';
			GetAdminRedirectUrl(GetAdminUrl(array('module'=>$_REQUEST['module']),0));
		}
	}

	private function editSignature(){

		if(isset($_REQUEST['id'])){
			$signature_id = $_REQUEST['id'];
		} else {
			$signature_id =  '';
		}

		$row = $GLOBALS['DB']->row("SELECT * FROM `signature` WHERE signature_id= ?",array($signature_id));

		$GLOBALS['layout_name'] = $row['layout_name'];
		$GLOBALS['layout_desc'] = $row['layout_desc'];
		$GLOBALS['layout_image'] =$GLOBALS['UPLOAD_LINK'].'/layout/'.$row['layout_image'];

		if($_POST['submit']){
			if($_POST['layout_name'] != '' && $_POST['layout_desc'] != '' ){

				if($_FILES['layout_image']['name'] != ''){
					$imageSourcefile = $_FILES['layout_image'];
					if($imageSourcefile['name'] != ''){
						$objImage = GetClass('Image');
						$imageDestination = GetConfig('SITE_UPLOAD_PATH').'/layout/'.$signature_id;
						$image = $objImage->imageUpload($imageSourcefile,$imageDestination,'image');
						$image =$image['name'];
					}
				} else {
					$image = $row['layout_image'];
				}
				  $data = array('layout_name'=>$_POST['layout_name'],'layout_desc'=>$_POST['layout_desc'],'layout_image'=>$image);
				  $where = array('signature_id'=>$signature_id);
				  $addResult = $GLOBALS['DB']->update("signature",$data,$where);
					if($addResult){
						$_SESSION['Success'] = '<div class="gap-8 py-5 px-4 pl-11 border-l-9 border-green-600 rounded-xl relative bg-white bg-gradient-to-r from-[#00B71B]/12 to-[#00B71B]/0 shadow-lg" role="alert">layout Add successfully</div>';
						GetAdminRedirectUrl(GetAdminUrl(array('module'=>'signature')));
					} else {
						$_SESSION['Error'] = '<div class="alert alert-danger" role="alert">An error occurred while you trying to add layout, please try again.</div>';
					}
			}else{
				$_SESSION['Error'] = '<div class="alert alert-danger" role="alert">Please fill all required Field</div>';
			}
		}

		AddMessageInfo();
	  	$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/'.$GLOBALS['Module'].'/signature.edit.html');
		$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');
		$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');
		$GLOBALS['LEFT'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.left.html');
		$GLOBALS['CLA_HTML']->display();
		RemoveMessageInfo();
	}

	private function addSignature(){


		$GLOBALS['layout_name'] = $_POST['layout_name'];
		$GLOBALS['layout_desc'] = $_POST['layout_desc'];



		if($_POST['submit']){

			$rowAI = $GLOBALS['DB']->AutoIncrement("signature");
			$objImage = GetClass('Image');
			$image ='';
			$imageSourcefile = $_FILES['layout_image'];
			if($imageSourcefile['name'] != ''){
				 $imageDestination = GetConfig('SITE_UPLOAD_PATH').'/layout/'.$rowAI['Auto_increment'];
				$image = $objImage->imageUploadResize($imageSourcefile,$imageDestination,'image');
			}
			if($_POST['layout_name'] != '' && $_POST['layout_desc'] != '' ){
				  $data = array('layout_name'=>$_POST['layout_name'],'layout_desc'=>$_POST['layout_desc'],'layout_image'=>$image);
				  $addResult = $GLOBALS['DB']->insert("signature",$data);
					if($addResult){
						$_SESSION['Success'] = '<div class="gap-8 py-5 px-4 pl-11 border-l-9 border-green-600 rounded-xl relative bg-white bg-gradient-to-r from-[#00B71B]/12 to-[#00B71B]/0 shadow-lg" role="alert">layout Add successfully</div>';
						GetAdminRedirectUrl(GetAdminUrl(array('module'=>'signature','action'=>'add')));
					} else {
						$_SESSION['Error'] = '<div class="alert alert-danger" role="alert">An error occurred while you trying to add layout, please try again.</div>';
					}
			}else{
				$_SESSION['Error'] = '<div class="alert alert-danger" role="alert">Please fill all required Field</div>';
			}
		}



		AddMessageInfo();
	  	$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/'.$GLOBALS['Module'].'/signature.add.html');
		$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');
		$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');
		$GLOBALS['LEFT'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.left.html');
		$GLOBALS['CLA_HTML']->display();
		RemoveMessageInfo();
	}

	private function deleteSignature(){
		if(isset($_REQUEST['id'])){
			$id = $_REQUEST['id'];
		} else {
			$id =  '';
		}
		if(is_numeric($id)){
			$addResult = $GLOBALS['DB']->query("DELETE FROM signature WHERE signature_id = ?", array($id));
			if($addResult){
				$_SESSION['Success'] = '<div class="gap-8 py-5 px-4 pl-11 border-l-9 border-green-600 rounded-xl relative bg-white bg-gradient-to-r from-[#00B71B]/12 to-[#00B71B]/0 shadow-lg">Register User deleted successfully</div>';
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

	private function viewSignature(){
		if(isset($_REQUEST['id'])){
			$id = $_REQUEST['id'];
		} else {
			$id =  '';
		}

		if(is_numeric($id)){
			$sRow = $GLOBALS['DB']->row("select * FROM `signature_logo` SL LEFT JOIN registerusers RU ON SL.user_id = RU.user_id  WHERE SL.id = ?",array($id));

				// UPLOAD SIGNATURE IMAGE
				if (!is_dir(GetConfig('SITE_UPLOAD_PATH') . "/signature/complete/".$sRow['user_id'])) {
					if (!mkdir(GetConfig('SITE_UPLOAD_PATH')."/signature/complete/".$sRow['user_id'])) {
						die("\"temp\" folder not created. Permission problem.......");
					}
				}

				if(isset($_POST['submit'])){
					if($_FILES['signature_image']['name'] !=""){
						$objImage = GetClass('Image');
						$imageSourcefile = $_FILES['signature_image'];
						$imageDestination = GetConfig('SITE_UPLOAD_PATH').'/signature/complete/'.$sRow['user_id'].'/'.$sRow['user_id'];
						$image = $objImage->imageUpload($imageSourcefile,$imageDestination,'image');
						$imagename = $image['name'];
						if($imagename){
							$location = GetConfig('SITE_UPLOAD_PATH').'/signature/complete/'.$sRow['user_id'].'/'.$imagename;
							$result = $GLOBALS['S3Client']->putObject(array( // upload image s3bucket
								'Bucket'=>$GLOBALS['BUCKETNAME'],
								'Key' =>  'upload-beta/signature/complete/'.$sRow['user_id'].'/'.$imagename,
								'SourceFile' => $location,
								'StorageClass' => 'REDUCED_REDUNDANCY',
								'ACL'   => 'public-read'
							));

							$update = $GLOBALS['DB']->update("signature_logo",array('logo_animation'=>$imagename),array('id'=>$id));
							if($update){
								$_SESSION['Success'] = '<div class="gap-8 py-5 px-4 pl-11 border-l-9 border-green-600 rounded-xl relative bg-white bg-gradient-to-r from-[#00B71B]/12 to-[#00B71B]/0 shadow-lg">signature logo upload successfully</div>';
							}else{
								$_SESSION['Error'] = '<div class="alert alert-danger">somthing wrong try again record not updated.</div>';
							}
						}else{
							$_SESSION['Error'] = '<div class="alert alert-danger">somthing wrong try again logo not upload.</div>';
						}
					}
					else{
						$_SESSION['Error'] = '<div class="alert alert-danger">please select signature image.</div>';

					}
					$redUrl = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'view','id'=>$id)).'?id='.time();
					GetAdminRedirectUrl($redUrl);
				}


				$rand_number = rand(100000,999999);
				$signature_imgchk = GetConfig('SITE_UPLOAD_PATH').'/signature/complete/'.$sRow['user_id'].'/'.$sRow['logo_animation'];
				if (file_exists($signature_imgchk) && is_file($signature_imgchk)) {
					$uploadcomplete =1;
					$signature_img = $GLOBALS['UPLOAD_LINK'].'/signature/complete/'.$sRow['user_id'].'/'.$sRow['logo_animation'];
				}else{
					$uploadcomplete =0;
					$signature_img = $GLOBALS['UPLOAD_LINK'].'/signature/'.$sRow['user_id'].'/'.$sRow['logo'];
				}
				$GLOBALS['signature_image'] = $signature_img.'?id='.$rand_number;
				$root_link = $GLOBALS['ROOT_LINK'];




				 if($uploadcomplete == 1 && $sRow['signature_process'] != 2){
					 $complete_link = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'processstatus','id'=>$id,'status'=>'1'));
					 $GLOBALS['completebtn'] ='<a href="'.$complete_link.'" title="Approve" class="btn btn-primary has-ripple">Approve</a>';
				 }else{
					  $GLOBALS['completebtn'] ='';
				 }
				AddMessageInfo();
				$this->getSignatureLogoFeedback($sRow['user_id'],$id);
				$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/'.$GLOBALS['Module'].'/signature.view.html');
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

	private function getSignatureLogoFeedback($userid,$id){
		$GLOBALS['FeedbackList'] ='';
		$feedbacks = $GLOBALS['DB']->query("SELECT * FROM signature_logo_feedback WHERE signature_logo_id = ? AND user_id= ?",array($id,$userid));
		foreach($feedbacks as $feedbackRow){

			if($row['feedback_status']){
				$GLOBALS['StatusImage'] = $GLOBALS['ADMIN_LINK'].'/images/status_on.png';
				$GLOBALS['StatusLink'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'status','id'=>$feedbackRow['feedback_id'],'status'=>'0'));
				$GLOBALS['Statusclass'] = 'c-gr f-22 feather icon-toggle-right';
			} else {
				$GLOBALS['StatusImage'] =  $GLOBALS['ADMIN_LINK'].'/images/status_off.png';
				$GLOBALS['StatusLink'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'status','id'=>$feedbackRow['feedback_id'],'status'=>'1'));
				$GLOBALS['Statusclass'] = 'c-rd f-22 feather icon-toggle-left';
			}

			$GLOBALS['FeedbackList'] .= '<tr>
			<td>'.$feedbackRow['feedback_id'].'</td>
			<td>'.$feedbackRow['feedback'].'</td>
			<td>'.$feedbackRow['feedback_date'].'</td>
			</tr>';
		}
	}

	private function getSignatureFeedback($userid,$id){
		$GLOBALS['FeedbackList'] ='';
		$feedbacks = $GLOBALS['DB']->query("SELECT * FROM signature_feedback WHERE signature_id = ? AND user_id= ?",array($id,$userid));
		foreach($feedbacks as $feedbackRow){

			if($row['feedback_status']){
				$GLOBALS['StatusImage'] = $GLOBALS['ADMIN_LINK'].'/images/status_on.png';
				$GLOBALS['StatusLink'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'status','id'=>$feedbackRow['feedback_id'],'status'=>'0'));
				$GLOBALS['Statusclass'] = 'c-gr f-22 feather icon-toggle-right';
			} else {
				$GLOBALS['StatusImage'] =  $GLOBALS['ADMIN_LINK'].'/images/status_off.png';
				$GLOBALS['StatusLink'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'status','id'=>$feedbackRow['feedback_id'],'status'=>'1'));
				$GLOBALS['Statusclass'] = 'c-rd f-22 feather icon-toggle-left';
			}

			$GLOBALS['FeedbackList'] .= '<tr>
			<td>'.$feedbackRow['feedback_id'].'</td>
			<td>'.$feedbackRow['feedback_comment'].'</td>
			<td>'.$feedbackRow['feedback_created'].'</td>
			</tr>';
		}
	}


	private function datatableSignature(){
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
			$searchQueryForTotalData = " WHERE (RU.user_id like '%".$searchValue."%' or RU.user_firstname like '%".$searchValue."%' or RU.user_email like '%".$searchValue."%')";
		}
		if($_POST['filter_val'] != ''){
			if($_POST['filter_val'] == 10){
				$searchQuery = " AND SL.logo_process =0 AND SU.free_trial=1 OR SU.plan_cancel=1";
			}else if($_POST['filter_val'] == 0){
				$searchQuery = " AND SL.logo_process =0 AND SU.free_trial=0 AND SU.plan_cancel = 0";
			}else{
				$searchQuery = " AND SL.logo_process =".$_POST['filter_val'];
			}
		}

		if($_POST['filter_user_type_val'] != ''){
			$searchQuery = " AND RU.user_type='".$_POST['filter_user_type_val']."' ";
		}

		if($searchQueryForTotalData){
			$records = $GLOBALS['DB']->row("select count(*) as allcount FROM `signature_logo` SL LEFT JOIN registerusers RU ON SL.user_id = RU.user_id" .$searchQueryForTotalData." AND SL.department_id=0");
		}else{
			$records = $GLOBALS['DB']->row("select count(*) as allcount FROM `signature_logo` SL LEFT JOIN registerusers RU ON SL.user_id = RU.user_id WHERE SL.department_id=0");
		}
		$totalRecordwithFilter = $records['allcount'];

		$registeruserRecords = $GLOBALS['DB']->query("SELECT *,SU.subscription_id,SU.free_trial FROM `signature_logo` SL LEFT JOIN registerusers RU ON SL.user_id = RU.user_id LEFT JOIN  registerusers_subscription SU ON RU.user_id = SU.user_id WHERE SL.user_id !=0". $searchQuery." order by ".$columnName." ".$columnSortOrder." limit ".$row.",".$rowperpage);

		$data = array();
		$count=1;

		foreach ($registeruserRecords as $row) {
			$reguser[$count]['GetCreatdDate'] = GetDateFormat($row['layout_created']);
			// if($row['logo_process'] !=2){
				$signature_imgchk = GetConfig('SITE_UPLOAD_PATH').'/signature/complete/'.$row['user_id'].'/'.$row['logo_animation'];
				if (file_exists($signature_imgchk) && is_file($signature_imgchk)) {
					$signatre_image = $GLOBALS['UPLOAD_LINK'].'/signature/complete/'.$row['user_id'].'/'.$row['logo_animation'];
				}else{
					$signatre_image = $GLOBALS['UPLOAD_LINK'].'/signature/'.$row['user_id'].'/'.$row['logo'];
				}

			// }else{
			// 	$signatre_image = $GLOBALS['UPLOAD_LINK'].'/signature/'.$row['user_id'].'/'.$row['logo'];
			// 	$download_link = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'download','id'=>$row['signature_id']));
			// }

			if($row['free_trial'] == 1){
				$userlable ='<label class="badge badge-light-primary" style="cursor:pointer;">Free</label>';
			}else{
				$userlable ='';
			}

			$GLOBALS['on_process'] = 'return jsConfirm("","Process")';
			$GLOBALS['on_complete'] = 'return jsConfirm("","Complete")';

			if($row['free_trial'] != 1 && $row['plan_cancel'] != 1){
				if($row['logo_process'] == 2){
					//$status_link = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'processstatus','id'=>$row['id'],'status'=>'0'));
					$status_link = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'view','id'=>$row['id']));
					$status = '<a href="'.$status_link.'" title="Click Here to Processing" onclick="'.$GLOBALS['on_process'].'"><label class="badge badge-light-success" style="cursor:pointer;">Complete</label></a>';
				}else if($row['logo_process'] == 1){
					$status_link = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'view','id'=>$row['id']));
					$status = '<a href="'.$status_link.'" title="Click here to Complete" onclick="'.$GLOBALS['on_complete'].'"><label class="badge badge-light-primary" style="cursor:pointer;">Processing</label></a>';
				}else if($row['logo_process'] == 3){
					$status_link = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'view','id'=>$row['id']));
					$status = '<a href="'.$status_link.'" title="Click here to Complete" onclick="'.$GLOBALS['on_complete'].'"><label class="badge badge-light-danger" style="cursor:pointer;">Revision Requested</label></a>';
				}else{
					//$status_link = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'processstatus','id'=>$row['signature_id'],'status'=>'1'));
					$status_link = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'view','id'=>$row['id']));
					$status = '<a href="'.$status_link.'" title="Click here to Complete" onclick="'.$GLOBALS['on_complete'].'"><label class="badge badge-light-warning" style="cursor:pointer;">Pending</label></a>';
				}
			}else if($row['plan_cancel'] == 1){
				$status = '<label class="badge badge-light-danger">Free Trial - Canceled</label>';
			}else{
				$status = '<label class="badge badge-light-danger">Pending - Free Trial</label>';
			}

			if($row['logo_change_process'] == 2){
				// $statusChangeLogo = '<a title="Click Here to Processing" onclick="'.$GLOBALS['on_process'].'"><label class="badge badge-light-success" style="cursor:pointer;">Complete</label></a>';
				$statusChangeLogo = '<a title="Click Here to Processing"><label class="badge badge-light-success" style="cursor:pointer;">Complete</label></a>';
			}else if($row['logo_change_process'] == 1){
				// $statusChangeLogo = '<a title="Click here to Complete" onclick="'.$GLOBALS['on_complete'].'"><label class="badge badge-light-primary" style="cursor:pointer;">Processing</label></a>';
				$statusChangeLogo = '<a title="Click here to Complete"><label class="badge badge-light-primary" style="cursor:pointer;">Processing</label></a>';
			}else if($row['logo_change_process'] == 3){
				// $statusChangeLogo = '<a title="Click here to Complete" onclick="'.$GLOBALS['on_complete'].'"><label class="badge badge-light-danger" style="cursor:pointer;">Revision Requested</label></a>';
				$statusChangeLogo = '<a title="Click here to Complete"><label class="badge badge-light-danger" style="cursor:pointer;">Revision Requested</label></a>';
			}else{
				// $statusChangeLogo = '<a title="Click here to Complete" onclick="'.$GLOBALS['on_complete'].'"><label class="badge badge-light-warning" style="cursor:pointer;">Pending</label></a>';
				$statusChangeLogo = '<a title="Click here to Complete"><label class="badge badge-light-warning" style="cursor:pointer;">Pending</label></a>';
			}

			if(is_null($sRow['changed_logo'])){
				$signatre_changed_logochk = GetConfig('SITE_UPLOAD_PATH').'/signature/'.$row['user_id'].'/'.$row['changed_logo'];
				if (file_exists($signatre_changed_logochk) && is_file($signatre_changed_logochk)) {
					$signatre_changed_logo = $GLOBALS['UPLOAD_LINK'].'/signature/'.$row['user_id'].'/'.$row['changed_logo'];
					$changed_logo = '<a href="'.$signatre_changed_logo.'" target="_blank" title="Download Logo" class="f-20 feather icon-download"><img src="'.$signatre_changed_logo.'" width="75"></a>';
				}else{
					$changed_logo = '<span> No logo </span>';
				}

			}else{
				$changed_logo = '<span> No logo </span>';
			}
			$GLOBALS['li_edit'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'edit','id'=>$row['id']));
			$GLOBALS['li_view'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'view','id'=>$row['id']));
			$GLOBALS['li_delete'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'delete','id'=>$row['id']));
			$GLOBALS['li_view_logo'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'viewlogo','id'=>$row['id']));
			// $GLOBALS['li_view_department_logo'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'viewdepartmentlogo','id'=>$row['user_id']));
			$GLOBALS['on_delete'] = 'return jsConfirm("","delete")';
			if($row['department_id'] == '0'){
				if($row['user_type'] == 'enterprise'){
					$data[] = array(
						"logo_process"=>$status,
						"logo_change_process"=>$statusChangeLogo,
						"id"=>$row['id'],
						"logo"=>"<a href='".$signatre_image."' target='_blank' title='Download Logo' class='f-20 feather icon-download'><img src='".$signatre_image."' width='75'></a>",
						"changed_logo"=>$changed_logo,
						"user_name"=>$row['user_firstname'].' <label class="badge badge-light-success" style="cursor:pointer;"> '.$row['user_type'].'</label> '.$userlable.'<br>'.$row['user_email'],
						"logo_created"=>GetDateFormat($row['logo_created']),
						"logo_updated"=>GetDateFormat($row['logo_updated']),
						"action"=>"<a href='".$GLOBALS['li_view']."' class='f-20 feather icon-eye' title='View'>&nbsp;</a><a href='".$GLOBALS['li_view_logo']."' class='f-20 feather icon-edit' title='View'>&nbsp;</a>"
					);
				}else{
					$data[] = array(
						"logo_process"=>$status,
						"logo_change_process"=>$statusChangeLogo,
						"id"=>$row['id'],
						"logo"=>"<a href='".$signatre_image."' target='_blank' title='Download Logo' class='f-20 feather icon-download'><img src='".$signatre_image."' width='75'></a>",
						"changed_logo"=>$changed_logo,
						"user_name"=>$row['user_firstname'].' '.$userlable.'<br>'.$row['user_email'],
						"logo_created"=>GetDateFormat($row['logo_created']),
						"logo_updated"=>GetDateFormat($row['logo_updated']),
						"action"=>"<a href='".$GLOBALS['li_view']."' class='f-20 feather icon-eye' title='View'>&nbsp;</a><a href='".$GLOBALS['li_view_logo']."' class='f-20 feather icon-edit' title='View'>&nbsp;</a>"
					);
				}
				
				$count++;
			}
			
		}
		$response = array(
			"draw" => intval($draw),
			"iTotalDisplayRecords" => $totalRecordwithFilter,
			"aaData" => $data
		);
		echo json_encode($response);
	}

	private function Signature(){

		$GLOBALS['li_getuserdata'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'datatableregdata'));
		$GLOBALS['li_export'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'export'));
		$GLOBALS['Totaluser'] = $count-1;
		AddMessageInfo();
		$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/'.$GLOBALS['Module'].'/signature.html');
		$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');
		$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');
		$GLOBALS['LEFT'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.left.html');
		$GLOBALS['CLA_HTML']->SetLoop('FEEDBACK',$reguser);
		$GLOBALS['CLA_HTML']->display();
		RemoveMessageInfo();
	}
	private function viewChangeLogoSignature(){
		if(isset($_REQUEST['id'])){
			$id = $_REQUEST['id'];
		} else {
			$id =  '';
		}
		if(is_numeric($id)){
			$sRow = $GLOBALS['DB']->row("select * FROM `signature_logo` SL LEFT JOIN registerusers RU ON SL.user_id = RU.user_id  WHERE SL.id = ?",array($id));
			$rand_number = rand(100000,999999);
			if(is_null($sRow['changed_logo'])){
				$signature_change_image_src = $GLOBALS['UPLOAD_LINK'].'/signature/'.$sRow['user_id'].'/'.$sRow['logo'];
				$GLOBALS['signature_change_image'] = "<img src='".$signature_change_image_src."?id=".$rand_number."' alt='Logo'/>";
			}else{
				$signature_change_image_src = $GLOBALS['UPLOAD_LINK'].'/signature/'.$sRow['user_id'].'/'.$sRow['changed_logo'];
				$GLOBALS['signature_change_image'] = "<img src='".$signature_change_image_src."?id=".$rand_number."' alt='Logo'/>";
			}
			$GLOBALS['change_logo_reason'] = $sRow['change_logo_reason'];

				if(isset($_POST['submit'])){
					if($_POST['logo_change_process'] !=""){
						$updateDataArray = [];
						if($_POST['change_logo_reject_reason'] !=""){
							if($_POST['logo_change_process'] == 3){
								$updateDataArray = array('logo_change_process'=>$_POST['logo_change_process'],'logo_process'=>'4','change_logo_reject_reason'=>$_POST['change_logo_reject_reason']); // logo_process reject(4 status)
								
							}else{
								$updateDataArray = array('logo_change_process'=>$_POST['logo_change_process'],'change_logo_reject_reason'=>$_POST['change_logo_reject_reason']);
							}
						}
						else{
							if($_POST['logo_change_process'] == 3){
								$updateDataArray = array('logo_change_process'=>$_POST['logo_change_process'],'logo_process'=>'4'); // logo_process reject(4 status)
							}else{
								$updateDataArray = array('logo_change_process'=>$_POST['logo_change_process']);
							}
						}
						$GLOBALS['DB']->update("signature_logo",$updateDataArray,array('id'=>$id));
						$_SESSION['Success'] = '<div class="gap-8 py-5 px-4 pl-11 border-l-9 border-green-600 rounded-xl relative bg-white bg-gradient-to-r from-[#00B71B]/12 to-[#00B71B]/0 shadow-lg">signature logo change proocess status updated successfully</div>';
					}
					else{
						$_SESSION['Error'] = '<div class="alert alert-danger">please select signature image.</div>';

					}
					
					// Create Asana Task API part START	
					if($_POST['logo_change_process'] == 4){
						$userData = $GLOBALS['DB']->row("select user_firstname, user_lastname, user_id, user_email FROM `registerusers` WHERE user_id = ?",array($sRow['user_id']));
						$logoId = $sRow['id'];
						$taskName = $logoId ." & ". $userData['user_firstname'] . " ". $userData['user_lastname'];
						$todayDate = date("Y-m-d");
						$dueDate = date("Y-m-d", strtotime("$todayDate +2 days"));
						
						$dueDateDay = new DateTime($dueDate);
						if($dueDateDay->format('l') == "Saturday"){
							$dueDate = date("Y-m-d", strtotime("$dueDate +2 days"));
						}elseif ($dueDateDay->format('l') == "Sunday") {
							$dueDate = date("Y-m-d", strtotime("$dueDate +2 days"));
						}
						
						// Get plan details
						$planDetails = $GLOBALS['DB']->row("SELECT P.plan_name,P.plan_type,RUS.plan_signaturelimit FROM `registerusers_subscription` as RUS LEFT JOIN `plan` as P ON P.plan_id=RUS.plan_id WHERE RUS.user_id=?",array($sRow['user_id']));

						$planName = "";
						$totalSignature = "";
						if($planDetails){
							if(isset($planDetails['plan_name']) && isset($planDetails['plan_type']) && isset($planDetails['plan_signaturelimit'])){
								$planName = ucfirst($planDetails['plan_name'])." ".ucfirst($planDetails['plan_type'])."ly"; // managed string to look like Basic Quartly
								$totalSignature = $planDetails['plan_signaturelimit'];
							}
						}
						
						// Create Task API START
						$curl = curl_init();
						curl_setopt_array($curl, array(
							CURLOPT_URL => 'https://app.asana.com/api/1.0/tasks',
							CURLOPT_RETURNTRANSFER => true,
							CURLOPT_ENCODING => '',
							CURLOPT_MAXREDIRS => 10,
							CURLOPT_TIMEOUT => 0,
							CURLOPT_FOLLOWLOCATION => true,
							CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
							CURLOPT_CUSTOMREQUEST => 'POST',
							CURLOPT_POSTFIELDS =>'{
								"data":
								{
									"name": "'.$taskName.'",
									"workspace": "1206253456547590",
									"assignee": "1206775942015442",
									"start_on": "'.$todayDate.'",
									"due_at": "'.$dueDate.'T18:29:00Z",
									"resource_subtype": "default_task",
									"projects": [
							      "1206775299870546"
							    ],
									"custom_fields": {
							      "1206887581291567":"'.$userData['user_id'].'", 
							      "1206887581291572":"'.$userData['user_firstname'].' '.$userData['user_lastname'].'", 
							      "1206887581291574":"'.$userData['user_email'].'",
							      "1206979965046785":"'.$logoId.'",
							      "1207413932967929":"'.$totalSignature.'",
							      "1207480586324570":"'.$planName.'"
							    }
								}
							}	',
							CURLOPT_HTTPHEADER => array(
								'accept: application/json',
								'authorization: Bearer 2/1206820072662878/1206887262231335:a4697fab3f5c77c3fd0fd55528db96b5',
								'content-type: application/json'
							),
						));
						$response = curl_exec($curl);
						
						$taskResult = json_decode($response);
						$createdTaskId = "0";
						if(property_exists($taskResult,'data') && property_exists($taskResult->data,'gid')){
								$createdTaskId = $taskResult->data->gid;
						}
						curl_close($curl);
						// Create Task API END
						
						// Add attachments to created API START
						if($createdTaskId == "0"){
							die("Something went wrong while creating task.");
						}
						
						$path = "";
						if(is_null($sRow['changed_logo'])){
							$path = GetConfig('SITE_UPLOAD_PATH').'/signature/'.$userData['user_id']."/".$sRow['logo'];
						}else{
							$path = GetConfig('SITE_UPLOAD_PATH').'/signature/'.$userData['user_id']."/".$sRow['changed_logo'];
						}
						if(file_exists($path)){
							$type = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $path);
							$asanaFilename = $sRow['changed_logo'];
							
							$curl = curl_init();
							curl_setopt_array($curl, array(
								CURLOPT_URL => 'https://app.asana.com/api/1.0/attachments',
								CURLOPT_RETURNTRANSFER => true,
								CURLOPT_ENCODING => '',
								CURLOPT_MAXREDIRS => 10,
								CURLOPT_TIMEOUT => 0,
								CURLOPT_FOLLOWLOCATION => true,
								CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
								CURLOPT_CUSTOMREQUEST => 'POST',
								CURLOPT_POSTFIELDS => array('file' => new \CURLFile($path, $type, $asanaFilename), 'resource_subtype' => 'asana', 'name' => $fileName,'parent' => $createdTaskId),
								CURLOPT_HTTPHEADER => array(
									'accept: application/json',
									'authorization: Bearer 2/1206820072662878/1206887262231335:a4697fab3f5c77c3fd0fd55528db96b5',
									'content-type: multipart/form-data'
								),
							));
							$response = curl_exec($curl);
							curl_close($curl);
						}
							
						// Add attachments to created API END
					}
					if($_POST['logo_change_process'] == 3){
						$_SESSION['Success'] = '<div class="gap-8 py-5 px-4 pl-11 border-l-9 border-green-600 rounded-xl relative bg-white bg-gradient-to-r from-[#00B71B]/12 to-[#00B71B]/0 shadow-lg">Logo has been rejected and feedback assign to client</div>';
					}
					$redUrl = GetAdminUrl(array('module'=>$_REQUEST['module']));
					GetAdminRedirectUrl($redUrl);
				}
				// Create Asana Task API part END`	
				
				$feedbackData = $GLOBALS['DB']->query("select * FROM `signature_logo_feedback` WHERE signature_logo_id = ?",array($id));
				$feedbackStr = "<div>";
				foreach ($feedbackData as $feedback) {
					$feedbackStr .= "<div class='date'>
					 	<span>".$feedback['feedback_date']."</span>
						 <p>".$feedback['feedback']." </p>
					</div>";
				}
				$feedbackStr .= "</div>";
				$GLOBALS['feedback'] = $feedbackStr;
				AddMessageInfo();
				$this->getSignatureFeedback($sRow['user_id'],$id);
				$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/'.$GLOBALS['Module'].'/signature.viewlogo.html');
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

	private function viewDepartmentLogoSignature(){
		$GLOBALS['li_getuserdata'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'datatableregdatadepartment','id'=>$_REQUEST['id']));
		$GLOBALS['li_export'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'export'));
		$GLOBALS['Totaluser'] = $count-1;
		AddMessageInfo();
		$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/'.$GLOBALS['Module'].'/signature.html');
		$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');
		$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');
		$GLOBALS['LEFT'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.left.html');
		$GLOBALS['CLA_HTML']->SetLoop('FEEDBACK',$reguser);
		$GLOBALS['CLA_HTML']->display();
		RemoveMessageInfo();
	}

	private function datatableDepartmentSignature(){
		$draw = $_POST['draw'];
		$row = $_POST['start'];
		$rowperpage = $_POST['length'];
		$columnIndex = $_POST['order'][0]['column'];
		$columnName = $_POST['columns'][$columnIndex]['data'];
		$columnSortOrder = $_POST['order'][0]['dir'];
		$searchValue = $_POST['search']['value'];
		$filter_val = $_POST['filter_val'];
		$searchQuery = " ";
		$user_id = $_REQUEST['id'];
		if($searchValue != ''){
			$searchQuery = " AND (RU.user_id like '%".$searchValue."%' or RU.user_firstname like '%".$searchValue."%' or RU.user_email like '%".$searchValue."%')";
			$searchQueryForTotalData = " WHERE (RU.user_id like '%".$searchValue."%' or RU.user_firstname like '%".$searchValue."%' or RU.user_email like '%".$searchValue."%')";
		}
		if($_POST['filter_val'] != ''){
			if($_POST['filter_val'] == 10){
				$searchQuery = " AND SL.logo_process =0 AND SU.free_trial=1 OR SU.plan_cancel=1";
			}else if($_POST['filter_val'] == 0){
				$searchQuery = " AND SL.logo_process =0 AND SU.free_trial=0 AND SU.plan_cancel = 0";
			}else{
				$searchQuery = " AND SL.logo_process =".$_POST['filter_val'];
			}
		}

		if($searchQueryForTotalData){
			$records = $GLOBALS['DB']->row("select count(*) as allcount FROM `signature_logo` SL LEFT JOIN registerusers RU ON SL.user_id = RU.user_id" .$searchQueryForTotalData." AND SL.user_id=".$user_id." ");
		}else{
			$records = $GLOBALS['DB']->row("select count(*) as allcount FROM `signature_logo` SL LEFT JOIN registerusers RU ON SL.user_id = RU.user_id WHERE SL.user_id=".$user_id." ");
		}
		$totalRecordwithFilter = $records['allcount'];

		$registeruserRecords = $GLOBALS['DB']->query("SELECT *,SU.subscription_id,SU.free_trial FROM `signature_logo` SL LEFT JOIN registerusers RU ON SL.user_id = RU.user_id LEFT JOIN  registerusers_subscription SU ON RU.user_id = SU.user_id WHERE SL.user_id=".$user_id." ". $searchQuery." order by ".$columnName." ".$columnSortOrder." limit ".$row.",".$rowperpage);


		$data = array();
		$count=1;

		foreach ($registeruserRecords as $row) {
			$reguser[$count]['GetCreatdDate'] = GetDateFormat($row['layout_created']);
			// if($row['logo_process'] !=2){
				$signature_imgchk = GetConfig('SITE_UPLOAD_PATH').'/signature/complete/'.$row['user_id'].'/'.$row['logo_animation'];
				if (file_exists($signature_imgchk) && is_file($signature_imgchk)) {
					$signatre_image = $GLOBALS['UPLOAD_LINK'].'/signature/complete/'.$row['user_id'].'/'.$row['logo_animation'];
				}else{
					$signatre_image = $GLOBALS['UPLOAD_LINK'].'/signature/'.$row['user_id'].'/'.$row['logo'];
				}

			// }else{
			// 	$signatre_image = $GLOBALS['UPLOAD_LINK'].'/signature/'.$row['user_id'].'/'.$row['logo'];
			// 	$download_link = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'download','id'=>$row['signature_id']));
			// }

			if($row['free_trial'] == 1){
				$userlable ='<label class="badge badge-light-primary" style="cursor:pointer;">Free</label>';
			}else{
				$userlable ='';
			}

			$GLOBALS['on_process'] = 'return jsConfirm("","Process")';
			$GLOBALS['on_complete'] = 'return jsConfirm("","Complete")';

			if($row['free_trial'] != 1 && $row['plan_cancel'] != 1){
				if($row['logo_process'] == 2){
					//$status_link = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'processstatus','id'=>$row['id'],'status'=>'0'));
					$status_link = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'view','id'=>$row['id']));
					$status = '<a href="'.$status_link.'" title="Click Here to Processing" onclick="'.$GLOBALS['on_process'].'"><label class="badge badge-light-success" style="cursor:pointer;">Complete</label></a>';
				}else if($row['logo_process'] == 1){
					$status_link = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'view','id'=>$row['id']));
					$status = '<a href="'.$status_link.'" title="Click here to Complete" onclick="'.$GLOBALS['on_complete'].'"><label class="badge badge-light-primary" style="cursor:pointer;">Processing</label></a>';
				}else if($row['logo_process'] == 3){
					$status_link = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'view','id'=>$row['id']));
					$status = '<a href="'.$status_link.'" title="Click here to Complete" onclick="'.$GLOBALS['on_complete'].'"><label class="badge badge-light-danger" style="cursor:pointer;">Revision Requested</label></a>';
				}else{
					//$status_link = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'processstatus','id'=>$row['signature_id'],'status'=>'1'));
					$status_link = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'view','id'=>$row['id']));
					$status = '<a href="'.$status_link.'" title="Click here to Complete" onclick="'.$GLOBALS['on_complete'].'"><label class="badge badge-light-warning" style="cursor:pointer;">Pending</label></a>';
				}
			}else if($row['plan_cancel'] == 1){
				$status = '<label class="badge badge-light-danger">Free Trial - Canceled</label>';
			}else{
				$status = '<label class="badge badge-light-danger">Pending - Free Trial</label>';
			}

			if($row['logo_change_process'] == 2){
				// $statusChangeLogo = '<a title="Click Here to Processing" onclick="'.$GLOBALS['on_process'].'"><label class="badge badge-light-success" style="cursor:pointer;">Complete</label></a>';
				$statusChangeLogo = '<a title="Click Here to Processing"><label class="badge badge-light-success" style="cursor:pointer;">Complete</label></a>';
			}else if($row['logo_change_process'] == 1){
				// $statusChangeLogo = '<a title="Click here to Complete" onclick="'.$GLOBALS['on_complete'].'"><label class="badge badge-light-primary" style="cursor:pointer;">Processing</label></a>';
				$statusChangeLogo = '<a title="Click here to Complete"><label class="badge badge-light-primary" style="cursor:pointer;">Processing</label></a>';
			}else if($row['logo_change_process'] == 3){
				// $statusChangeLogo = '<a title="Click here to Complete" onclick="'.$GLOBALS['on_complete'].'"><label class="badge badge-light-danger" style="cursor:pointer;">Revision Requested</label></a>';
				$statusChangeLogo = '<a title="Click here to Complete"><label class="badge badge-light-danger" style="cursor:pointer;">Revision Requested</label></a>';
			}else{
				// $statusChangeLogo = '<a title="Click here to Complete" onclick="'.$GLOBALS['on_complete'].'"><label class="badge badge-light-warning" style="cursor:pointer;">Pending</label></a>';
				$statusChangeLogo = '<a title="Click here to Complete"><label class="badge badge-light-warning" style="cursor:pointer;">Pending</label></a>';
			}

			if(is_null($sRow['changed_logo'])){
				$signatre_changed_logochk = GetConfig('SITE_UPLOAD_PATH').'/signature/'.$row['user_id'].'/'.$row['changed_logo'];
				if (file_exists($signatre_changed_logochk) && is_file($signatre_changed_logochk)) {
					$signatre_changed_logo = $GLOBALS['UPLOAD_LINK'].'/signature/'.$row['user_id'].'/'.$row['changed_logo'];
					$changed_logo = '<a href="'.$signatre_changed_logo.'" target="_blank" title="Download Logo" class="f-20 feather icon-download"><img src="'.$signatre_changed_logo.'" width="75"></a>';
				}else{
					$changed_logo = '<span> No logo </span>';
				}

			}else{
				$changed_logo = '<span> No logo </span>';
			}
			$GLOBALS['li_edit'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'edit','id'=>$row['id']));
			$GLOBALS['li_view'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'view','id'=>$row['id']));
			$GLOBALS['li_delete'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'delete','id'=>$row['id']));
			$GLOBALS['li_view_logo'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'viewlogo','id'=>$row['id']));
			// $GLOBALS['li_view_department_logo'] = GetAdminUrl(array('module'=>$_REQUEST['module'],'action'=>'viewdepartmentlogo','id'=>$row['id']));
			$GLOBALS['on_delete'] = 'return jsConfirm("","delete")';
			$data[] = array(
				"logo_process"=>$status,
				"logo_change_process"=>$statusChangeLogo,
				"id"=>$row['id'],
				"logo"=>"<a href='".$signatre_image."' target='_blank' title='Download Logo' class='f-20 feather icon-download'><img src='".$signatre_image."' width='75'></a>",
				"changed_logo"=>$changed_logo,
				"user_name"=>$row['user_firstname'].' '.$userlable.'<br>'.$row['user_email'],
				"logo_created"=>GetDateFormat($row['logo_created']),
				"logo_updated"=>GetDateFormat($row['logo_updated']),
				"action"=>"<a href='".$GLOBALS['li_view']."' class='f-20 feather icon-eye' title='View'>&nbsp;</a><a href='".$GLOBALS['li_view_logo']."' class='f-20 feather icon-edit' title='View'>&nbsp;</a>"
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


}
