<?php
// ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
require_once($GLOBALS['BASE_LINK'].'/'.GetConfig('CLASSES').'/dashboard.php');

class CIT_BANNERCAMPAIGN
{
   

	public function __construct()
	{
		$GLOBALS['SIGNATURE'] = GetClass('CIT_DASHBOARD');
	}

	public function displayPage(){
		AddMessageInfo();
        if(isset($_REQUEST['category_id'])){
			$action = trim($_REQUEST['category_id']);
		} else {
			$action = '';
		}
        if($action == "addBanner"){
            $this->addBanner();
        }
        if($action == "saveBanner"){
            $this->saveBanner();
        }
        if($action == "duplicateBanner"){
            $this->duplicateBanner();
        }
        if($action == "startBanner"){
            $this->startBanner();
        }
        if($action == "pauseBanner"){
            $this->pauseBanner();
        }
        if($action == "resumeBanner"){
            $this->resumeBanner();
        }
        if($action == "cancelBanner"){
            $this->cancelBanner();
        }
        if($action == "deleteBanner"){
            $this->deleteBanner();
        }
        if($action == "getDepartmentSignature"){
            $this->getDepartmentSignature();
        }


        if($_FILES['banner']['name'] !=""){

			$filename = $_FILES['banner']['name'];
			$filesize = $_FILES['banner']['size'];
			$displayname = $filename;
			$valid_extensions = array('png', 'svg','jpeg','jpg');
			$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

			if(in_array($ext, $valid_extensions)) {
				//$location = "upload-beta/".$filename;
				$filename = time().'-'.$GLOBALS['USERID'].'.'.$ext;
				$bannerCampaignPath =  GetConfig('SITE_UPLOAD_PATH').'/bannercampaign/';
				$location =  GetConfig('SITE_UPLOAD_PATH').'/bannercampaign/'.$filename ;
				if (!is_dir($bannerCampaignPath)) {
    				mkdir($bannerCampaignPath, 0755, true);
    			}
				$return_arr = array();
				if(move_uploaded_file($_FILES['banner']['tmp_name'],$location)){
					$result = $GLOBALS['S3Client']->putObject(array( // upload image s3bucket
						'Bucket'=>$GLOBALS['BUCKETNAME'],
						'Key' =>  'upload-beta/bannercampaign/'.$filename,
						'SourceFile' => $location,
						'StorageClass' => 'REDUCED_REDUNDANCY',
						'ACL'   => 'public-read'
					));
					if(is_array(getimagesize($location))){
						$src = $GLOBALS['UPLOAD_LINK'].'/bannercampaign/'.$filename;

						$data =array('user_image'=>$filename); $where =array('user_id'=>$GLOBALS['USERID']);
					}
					$return_arr = array("name" => $filename,"displayname" => $displayname, "size" => $filesize, "src"=> $src, "error"=>0);
				}
			}else{
				$return_arr = array("error" =>1, "msg"=>"please upload valid jpg, jpeg, png or svg image");
			}
			echo json_encode($return_arr); exit;
		}
        
        $manageBanners = $GLOBALS['DB']->query("select RU.*,BC.* FROM `banner_campaign` BC LEFT JOIN  registerusers RU ON RU.user_id = BC.user_id WHERE BC.user_id=?;",array($GLOBALS['USERID']));
        $GLOBALS['bannerCampaignTableBody'] = "";
		foreach ($manageBanners as $row) {
			$campaign_status = "";
			if(isset($row['campaign_status'])){
				$fieldtype = $row['campaign_status'];
				if($fieldtype != 'canceled'){
					if($row['start_date'] > date('Y-m-d H:i:s')){
						$fieldtype = 'scheduled';
					}else if(date('Y-m-d H:i:s') > $row['end_date']){
						$fieldtype = 'completed';
					}
					else if($row['is_paused'] == 'true'){
						$fieldtype = 'pause';
					}
				}
				$cancelButtonVisibility = '';
				if($fieldtype == 'canceled' || !in_array($fieldtype,['scheduled','active','pause'])){
					$cancelButtonVisibility = 'style="display: none;"';
				}

				switch($fieldtype){
					case 'scheduled':
					 	$campaign_status = "<span class='kt-badge kt-badge-warning'>Scheduled</span>";
					 	break;
					case 'active':
					 	$campaign_status = "<span class='kt-badge kt-badge-success'>Active</span>";
					 	break;
					case 'completed':
					 	$campaign_status = "<span class='kt-badge kt-badge-info'>Completed</span>";
					 	break;
					case 'canceled':
					 	$campaign_status = "<span class='kt-badge kt-badge-destructive'>Canceled</span>";
					 	break;
					case 'pause':
					 	$campaign_status = "<span class='kt-badge kt-badge-primary'>Pause</span>";
					 	break;
				 	case 'draft':
					 	$campaign_status = "<span class='kt-badge kt-badge-secondary'>draft</span>";
					 	break;
					default:
					 	$campaign_status = "<span class='kt-badge kt-badge-mono'>".$row['campaign_status']."</span>";
					 	break;
				}
			}
    		$department_name = "";
    		$departmentIdsString = $row['department_id']; 
			$ids = explode(',', $departmentIdsString); // Now $ids is an array
			$ids = array_map('intval', $ids); // Ensures all elements are integers
			$placeholders = implode(',', array_fill(0, count($ids), '?'));
			$sql = "SELECT GROUP_CONCAT(department_name SEPARATOR ', ') AS department_name FROM registerusers_departments WHERE department_id IN ($placeholders)";
			$banner = $GLOBALS['DB']->row($sql, $ids);
			if($banner){
        		$department_name .= $banner['department_name'];
			}
            $GLOBALS['bannerCampaignTableBody'] .= "<tr class='allcampaigns ".$fieldtype."'>
                <td>".$row['campaign_name']."</td>
                <td>".$campaign_status."</td>
                <td>".$row['start_date']."</td>
                <td>".$row['end_date']."</td>
                <td>".$department_name."</td>
                <td>
					<span class='campain-table-img'>
                    	<img style='width:150px;height:50px;' src='".$GLOBALS['UPLOAD_LINK']."/bannercampaign/".$row['banner_name']."'/>
					</span>
                </td>
                <td>
                	<div class='signature_manager_top'>
                		<div class='campaign_option_btns flex gap-1 items-center'>
							<a class='kt-btn kt-btn-primary kt-btn-icon' data-action='Duplicate' data-kt-tooltip='true' data-kt-tooltip-placement='top' href='".$GLOBALS['bannercampaign']."/addBanner/".$row['banner_id']."' ><i class='hgi hgi-stroke hgi-copy-02'></i><span data-kt-tooltip-content='true' class='kt-tooltip'>Duplicate</span></a>";
							if($fieldtype == 'pause'){
								$GLOBALS['bannerCampaignTableBody'] .= "<a class='kt-btn kt-btn-primary kt-btn-icon' data-action='Resume' data-kt-tooltip='true' data-kt-tooltip-placement='top' onclick='bannerAction(this);' data-url='".$GLOBALS['bannercampaign']."/resumeBanner/".$row['banner_id']."'><i class='hgi hgi-solid hgi-sharp hgi-play'></i><span data-kt-tooltip-content='true' class='kt-tooltip'>Resume</span></a>";
							}
							else if($fieldtype == 'active'){
								$GLOBALS['bannerCampaignTableBody'] .= "<a class='kt-btn kt-btn-primary kt-btn-icon' data-action='Pause' data-kt-tooltip='true' data-kt-tooltip-placement='top' onclick='bannerAction(this);' data-url='".$GLOBALS['bannercampaign']."/pauseBanner/".$row['banner_id']."'><i class='hgi hgi-stroke hgi-pause-circle'></i><span data-kt-tooltip-content='true' class='kt-tooltip'>Pause</span></a>";
							}
							if($fieldtype == 'scheduled' || $fieldtype == 'draft'){
								$GLOBALS['bannerCampaignTableBody'] .= "<a class='kt-btn kt-btn-primary kt-btn-icon' data-action='Edit' data-kt-tooltip='true' data-kt-tooltip-placement='top' href='".$GLOBALS['bannercampaign']."/addBanner/".$row['banner_id']."/edit'><i class='hgi hgi-stroke hgi-pencil-edit-02'></i><span data-kt-tooltip-content='true' class='kt-tooltip'>Edit</span></a>";
							}
							if($fieldtype == 'draft'){
								$GLOBALS['bannerCampaignTableBody'] .= "<a class='kt-btn kt-btn-primary kt-btn-icon' data-action='start' data-kt-tooltip='true' data-kt-tooltip-placement='top' onclick='bannerAction(this);' data-url='".$GLOBALS['bannercampaign']."/startBanner/".$row['banner_id']."'><i class='hgi hgi-stroke hgi-start-up-02'></i><span data-kt-tooltip-content='true' class='kt-tooltip'>Start Campaign</span></a>";
							}
							$GLOBALS['bannerCampaignTableBody'] .= "<a class='kt-btn kt-btn-primary kt-btn-icon' data-action='Cancel' data-kt-tooltip='true' data-kt-tooltip-placement='top' onclick='bannerAction(this);' data-url='".$GLOBALS['bannercampaign']."/cancelBanner/".$row['banner_id']."' ".$cancelButtonVisibility."' ><i class='hgi hgi-stroke hgi-cancel-circle'></i><span data-kt-tooltip-content='true' class='kt-tooltip'>Cancel</span></a></a>
							<a href='javascript:void(0);' class='kt-btn kt-btn-destructive kt-btn-icon' data-action='Delete' data-kt-tooltip='true' data-kt-tooltip-placement='top' onclick='bannerAction(this);' data-url='".$GLOBALS['bannercampaign']."/deleteBanner/".$row['banner_id']."' ><i class='hgi hgi-stroke hgi-delete-02'></i><span data-kt-tooltip-content='true' class='kt-tooltip'>Delete</span></a>
						</div>
					</div>
                </td>
                </tr>";
                // <img src='".$GLOBALS['IMAGE_LINK']."/images/dots-menu.svg'/>
        }
        $GLOBALS['bannerCampaignTableBody'] .= '<tr id="no_banner_row"><td class="text-center pb-0 pt-5" colspan="9">No Campaign Found</td></tr>';
        $departments = $GLOBALS['DB']->query("select * FROM `registerusers_departments` WHERE user_id=? ",array($GLOBALS['USERID']));
        foreach ($departments as $department) {
            $GLOBALS['department_list'] .= "<div class='flex items-center gap-2'>
                       <input type='checkbox' class='kt-checkbox kt-checkbox-sm department-checkbox' name='department_list' id='department_".$department['department_id']."' value='".$department['department_id']."'>
					   <label for='department_".$department['department_id']."' class='kt-label'>".$department['department_name']."</label>
                    </div>
                ";
        }
            
        $GLOBALS['bannercampaign_datatable'] = GetUrl(array('module'=>'bannercampaign','category_id' => 'datatableregdata'));
        $GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/bannercampaign.html');	
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

    public function addBanner(){
    	$GLOBALS['bannersize'] = '200';
    	if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])){
    		$bannerId = $_REQUEST['id'];
    		$banner = $GLOBALS['DB']->row("select * FROM `banner_campaign` WHERE banner_id=? ",array($bannerId));
    		$GLOBALS['BANNER_CAMPAIGN_IMAGE'] = $GLOBALS['IMAGE_LINK'].'/images/img-icon.svg';
    		$GLOBALS['BANNER_CAMPAIGN_IMAGE_SECTION'] = 'section_disabled';
    		if($banner){
	        	$GLOBALS['campaign_name'] = $banner['campaign_name'];
	        	$GLOBALS['bannershape_'.$banner['banner_shape'].'_checked'] = "checked";
	        	$GLOBALS['bannersize'] = $banner['banner_size'];
	        	$GLOBALS['bannerlink'] = $banner['banner_link'];
	        	$GLOBALS['BANNER_CAMPAIGN_IMAGE'] = $GLOBALS['UPLOAD_LINK'].'/bannercampaign/'.$banner['banner_name'];
	        	$GLOBALS['BANNER_CAMPAIGN_IMAGE_NAME'] = $banner['banner_name'];
	        	$GLOBALS['BANNER_CAMPAIGN_IMAGE_SECTION'] = '';
    		}
    		$departments = $GLOBALS['DB']->query("select * FROM `registerusers_departments` WHERE user_id=? ",array($GLOBALS['USERID']));
	    	$GLOBALS['department_list'] = "";
			$GLOBALS['department_list'] .= "<div class='flex items-center gap-2'>
				<input type='checkbox' class='kt-checkbox kt-checkbox-sm' id='banner_department_select_all'>
				<label for='banner_department_select_all' class='kt-label'>Select All</label>
				</div>";
	        foreach ($departments as $department) {
	        	$totalRowData = $GLOBALS['DB']->row("SELECT count(`signature_id`) as totalsignature FROM `signature` WHERE `user_id` = ?  AND `department_id` = ?",array($GLOBALS['USERID'],$department['department_id']));
	        	$totalRow = $totalRowData['totalsignature'];
	        	if (isset($banner['department_id']) && str_contains($banner['department_id'],$department['department_id'])) {
	        		$GLOBALS['department_list'] .= "<div class='flex items-center gap-2'>
	                       <input type='checkbox' class='kt-checkbox kt-checkbox-sm department-checkbox selected-checkbox' name='department_list' id='department_".$department['department_id']."' value='".$department['department_id']."'>
						   <label for='department_".$department['department_id']."' class='kt-label'>".$department['department_name']."(".$totalRow.")</label>
	                    </div>
	                ";
				} else {
					$GLOBALS['department_list'] .= "<div class='flex items-center gap-2'>
	                       <input type='checkbox' class='kt-checkbox kt-checkbox-sm department-checkbox' name='department_list' id='department_".$department['department_id']."' value='".$department['department_id']."'>
						   <label for='department_".$department['department_id']."' class='kt-label'>".$department['department_name']."(".$totalRow.")</label>
	                    </div>
	                ";
				}
	            
	        }
        	$GLOBALS['EDIT_BANNER'] = false;
        	$GLOBALS['EDIT_BANNER_ID'] = '0';
        	$GLOBALS['BANNER_CAMPAIGN_START_DATE'] = "";
    		$GLOBALS['BANNER_CAMPAIGN_END_DATE'] = "";

	        if(isset($_REQUEST['subid'])){
	        	$GLOBALS['EDIT_BANNER'] = true;
        		$GLOBALS['EDIT_BANNER_ID'] = $bannerId;
        		$GLOBALS['BANNER_CAMPAIGN_START_DATE'] = $banner['start_date'];
        		$GLOBALS['BANNER_CAMPAIGN_END_DATE'] = $banner['end_date'];

	        }
	        $GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/addbannercampaign.html');	
	        $GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');
	        $GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');
	        $GLOBALS['SIDEBAR'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.sidebar.html');
	        $GLOBALS['CLA_HTML']->display();
	        RemoveMessageInfo();
	        exit();	
    	}else{
    		$departments = $GLOBALS['DB']->query("select * FROM `registerusers_departments` WHERE user_id=? ",array($GLOBALS['USERID']));
	    	$GLOBALS['department_list'] = "";
			$GLOBALS['department_list'] .= "<div class='flex items-center gap-2'>
				<input type='checkbox' class='kt-checkbox kt-checkbox-sm' id='banner_department_select_all'>
				<label for='banner_department_select_all' class='kt-label'>Select All</label>
				</div>";
	        foreach ($departments as $department) {
	        	$totalRowData = $GLOBALS['DB']->row("SELECT count(`signature_id`) as totalsignature FROM `signature` WHERE `user_id` = ?  AND `department_id` = ?",array($GLOBALS['USERID'],$department['department_id']));
	        	$totalRow = $totalRowData['totalsignature'];
	            $GLOBALS['department_list'] .= "<div class='flex items-center gap-2'>
	                       <input type='checkbox' class='kt-checkbox kt-checkbox-sm department-checkbox' name='department_list' id='department_".$department['department_id']."' value='".$department['department_id']."'>
						   <label for='department_".$department['department_id']."' class='kt-label'>".$department['department_name']."(".$totalRow.")</label>
	                    </div>
	                ";
	        }
			$GLOBALS['bannercampaign_signature'] = $GLOBALS['SIGNATURE']->getUserSignature();
	        $GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/addbannercampaign.html');	
	        $GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');
	        $GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');
	        $GLOBALS['SIDEBAR'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.sidebar.html');
	        $GLOBALS['CLA_HTML']->display();
	        RemoveMessageInfo();
	        exit();	
    	}
    	
	}

	public function saveBanner(){
        $postdata = $_POST;
        if(isset($postdata['signature_banner']) && $postdata['signature_banner'] != ""){
        	$banner_name = $postdata['signature_banner'];
        }else{
        	$banner_name = '';
        }
        $dateRange = $postdata['date_range'];

		// Split the string by the hyphen and trim any surrounding whitespace
		$dateParts = explode(' - ', $dateRange);

		// Separate the start and end dates
		$startDate = trim($dateParts[0]);
		$endDate = trim($dateParts[1]);
		$data = array('user_id'=>$GLOBALS['USERID'],'department_id'=>$postdata['department_list'],'campaign_name'=>$postdata['campaign_name'],'campaign_status'=>'draft','start_date'=>$startDate,'end_date'=>$endDate,'banner_name'=>$banner_name,'banner_shape'=>$postdata['bannershape'],'banner_size'=>$postdata['bannersize'],'banner_link'=>$postdata['bannerlink'],'is_paused'=>'false');
		$departmentSignatures = explode(',', $postdata['department_list']);
		$placeholders = implode(',', array_fill(0, count($departmentSignatures), '?'));
		$sql = "SELECT signature_id, department_id, user_id FROM `signature` WHERE department_id IN ($placeholders)";
		$signatures = $GLOBALS['DB']->query($sql, $departmentSignatures);
		$dateToday = date('Y-m-d');
		foreach ($signatures as $signature) {
			$dataAnalyticsBannerCampaign['user_id'] = $signature['user_id'];
			$dataAnalyticsBannerCampaign['signature_id'] = $signature['signature_id'];
			$dataAnalyticsBannerCampaign['url'] = $banner_name;
			$dataAnalyticsBannerCampaign['analytic_type'] = 'banner';
			$dataAnalyticsBannerCampaign['impressions'] = $dadataAnalyticsBannerCampaigna['clicks'] = $dataAnalyticsBannerCampaign['mobile_clicks'] = $dataAnalyticsBannerCampaign['desktop_clicks'] = $dataAnalyticsBannerCampaign['tablet_clicks'] = $dataAnalyticsBannerCampaign['windows_clicks'] = $dataAnalyticsBannerCampaign['macos_clicks'] = $dataAnalyticsBannerCampaign['linux_clicks'] = $dataAnalyticsBannerCampaign['ios_clicks'] = $dataAnalyticsBannerCampaign['android_clicks'] =  0;
			$dataAnalyticsBannerCampaign['date'] = $dateToday;
			$ipInformation = $GLOBALS['CLA_INDEX']->getUserLocation();
			$dataAnalyticsBannerCampaign['user_ip'] = $ipInformation['ip'];
			$dataAnalyticsBannerCampaign['location'] = json_encode($ipInformation['location'], true);
			$GLOBALS['DB']->insert('registerusers_analytics', $dataAnalyticsBannerCampaign);

			$dataAnalyticsBannerCampaignLink['user_id'] = $signature['user_id'];
			$dataAnalyticsBannerCampaignLink['signature_id'] = $signature['signature_id'];
			$dataAnalyticsBannerCampaignLink['url'] = $postdata['bannerlink'];
			$dataAnalyticsBannerCampaignLink['analytic_type'] = 'bannerclick';
			$dataAnalyticsBannerCampaignLink['impressions'] = $dadataAnalyticsBannerCampaignLinka['clicks'] = $dataAnalyticsBannerCampaignLink['mobile_clicks'] = $dataAnalyticsBannerCampaignLink['desktop_clicks'] = $dataAnalyticsBannerCampaignLink['tablet_clicks'] = $dataAnalyticsBannerCampaignLink['windows_clicks'] = $dataAnalyticsBannerCampaignLink['macos_clicks'] = $dataAnalyticsBannerCampaignLink['linux_clicks'] = $dataAnalyticsBannerCampaignLink['ios_clicks'] = $dataAnalyticsBannerCampaignLink['android_clicks'] =  0;
			$dataAnalyticsBannerCampaignLink['date'] = $dateToday;
			$ipInformation = $GLOBALS['CLA_INDEX']->getUserLocation();
			$dataAnalyticsBannerCampaignLink['user_ip'] = $ipInformation['ip'];
			$dataAnalyticsBannerCampaignLink['location'] = json_encode($ipInformation['location'], true);
			$GLOBALS['DB']->insert('registerusers_analytics', $dataAnalyticsBannerCampaignLink);
		}
		if($postdata['edit_banner']){
			$bannerId = $postdata['edit_banner_id'];
			$where = array('banner_id'=>$bannerId);
			$updateBanner = $GLOBALS['DB']->update("banner_campaign",$data,$where);
	        $return_arrs = array('error'=>0,'msg'=>'Banner updated successfully');
		} else {
	        $addDepartment = $GLOBALS['DB']->insert("banner_campaign",$data);
	        $return_arrs = array('error'=>0,'msg'=>'Banner added successfully');
		}
		echo json_encode($return_arrs); exit;
	}

	public function duplicateBanner(){
		$bannerId = 0;
		if(isset($_REQUEST['id']) && $_REQUEST['id'] !=''){
        	$bannerId = $_REQUEST['id'];
		}
        $banner = $GLOBALS['DB']->row("select * FROM `banner_campaign` WHERE banner_id=? ",array($bannerId));
        
        if($banner){
        	$data = array('user_id'=>$banner['user_id'],'department_id'=>$banner['department_id'],'campaign_name'=>$banner['campaign_name'],'campaign_status'=>$banner['campaign_status'],'start_date'=>$banner['start_date'],'end_date'=>$banner['end_date'],'banner_name'=>$banner['banner_name'],'banner_shape'=>$banner['banner_shape'],'banner_size'=>$banner['banner_size'],'banner_link'=>$banner['banner_link'],'is_paused'=>$banner['is_paused']);
	        $addDepartment = $GLOBALS['DB']->insert("banner_campaign",$data);
	        $return_arrs = array('error'=>0,'msg'=>'Banner duplicated successfully');

        }else{
	        $return_arrs = array('error'=>1,'msg'=>'Something went wrong, please try again');
        }
		
		echo json_encode($return_arrs); exit;
	}

	public function startBanner(){
		$bannerId = 0;
		if(isset($_REQUEST['id']) && $_REQUEST['id'] !=''){
        	$bannerId = $_REQUEST['id'];
		}
		$banner = $GLOBALS['DB']->row("select * FROM `banner_campaign` WHERE banner_id=? ",array($bannerId));
		if($banner){
			$departmentstr = $banner['department_id'];
			$departments = explode(',', $departmentstr);
    		$isAnyOtherActivated = false;
			foreach ($departments as $department) {
				$activeBanner = $GLOBALS['DB']->row("SELECT * FROM `banner_campaign` WHERE campaign_status='active' AND is_paused='false' AND department_id LIKE ?",array('%' . $department . '%'));
				if($activeBanner){
	        		$isAnyOtherActivated = true;
				}
			}
			if($isAnyOtherActivated){
				$return_arrs = array('error'=>1,'msg'=>'A banner campaign is already active for this division. Please pause or stop the currently active campaign before activating a new one.');
			}else{
				$data = array('campaign_status'=>'active');
				$where = array('banner_id'=>$bannerId);
				$updateBanner = $GLOBALS['DB']->update("banner_campaign",$data,$where);
				if($updateBanner){
		        	$return_arrs = array('error'=>0,'msg'=>'Banner activated successfully');
		        }else{
			        $return_arrs = array('error'=>1,'msg'=>'Something went wrong, please try again');
		        }
			}
			echo json_encode($return_arrs); exit;
		}else{
			$return_arrs = array('error'=>1,'msg'=>'Something went wrong, please try again');
			echo json_encode($return_arrs); exit;
		}
	}

	public function pauseBanner(){
		$bannerId = 0;
		if(isset($_REQUEST['id']) && $_REQUEST['id'] !=''){
        	$bannerId = $_REQUEST['id'];
		}
    	$data = array('is_paused'=>'true');
		$where = array('banner_id'=>$bannerId);
		$updateBanner = $GLOBALS['DB']->update("banner_campaign",$data,$where);
		if($updateBanner){
        	$return_arrs = array('error'=>0,'msg'=>'Banner paused successfully');
        }else{
	        $return_arrs = array('error'=>1,'msg'=>'Something went wrong, please try again');
        }
		
		echo json_encode($return_arrs); exit;
	}

	public function resumeBanner(){
		$bannerId = 0;
		if(isset($_REQUEST['id']) && $_REQUEST['id'] !=''){
        	$bannerId = $_REQUEST['id'];
		}
		$banner = $GLOBALS['DB']->row("select * FROM `banner_campaign` WHERE banner_id=? ",array($bannerId));
		if($banner){
			$departmentstr = $banner['department_id'];
			$departments = explode(',', $departmentstr);
    		$isAnyOtherActivated = false;
			foreach ($departments as $department) {
				$activeBanner = $GLOBALS['DB']->row("SELECT * FROM `banner_campaign` WHERE campaign_status='active' AND is_paused='false' AND department_id LIKE ?",array('%' . $department . '%'));
				if($activeBanner){
	        		$isAnyOtherActivated = true;
				}
			}
			if($isAnyOtherActivated){
				$return_arrs = array('error'=>1,'msg'=>'A banner campaign is already active for this division. Please pause or stop the currently active campaign before activating a new one.');
			}else{
				$data = array('is_paused'=>'false');
				$where = array('banner_id'=>$bannerId);
				$updateBanner = $GLOBALS['DB']->update("banner_campaign",$data,$where);
				if($updateBanner){
		        	$return_arrs = array('error'=>0,'msg'=>'Banner resumed successfully');
		        }else{
			        $return_arrs = array('error'=>1,'msg'=>'Something went wrong, please try again');
		        }
			}
			echo json_encode($return_arrs); exit;
		}else{
			$return_arrs = array('error'=>1,'msg'=>'Something went wrong, please try again');
			echo json_encode($return_arrs); exit;
		}
    	
	}

	public function cancelBanner(){
		$bannerId = 0;
		if(isset($_REQUEST['id']) && $_REQUEST['id'] !=''){
        	$bannerId = $_REQUEST['id'];
		}
    	$data = array('campaign_status'=>'canceled');
		$where = array('banner_id'=>$bannerId);
		$updateBanner = $GLOBALS['DB']->update("banner_campaign",$data,$where);
		if($updateBanner){
        	$return_arrs = array('error'=>0,'msg'=>'Banner canceled successfully');
        }else{
	        $return_arrs = array('error'=>1,'msg'=>'Something went wrong, please try again');
        }
		
		echo json_encode($return_arrs); exit;
	}

	public function deleteBanner(){
		$bannerId = 0;
		if(isset($_REQUEST['id']) && $_REQUEST['id'] !=''){
        	$bannerId = $_REQUEST['id'];
		}
        if($bannerId != 0){
        	$banner = $GLOBALS['DB']->row("DELETE FROM `banner_campaign` WHERE banner_id=? ",array($bannerId));
	        $return_arrs = array('error'=>0,'msg'=>'Banner deleted successfully');
        }else{
	        $return_arrs = array('error'=>1,'msg'=>'Something went wrong, please try again');
        }
		
		echo json_encode($return_arrs); exit;
	}

	public function getDepartmentSignature(){
		$departmentId = 0;
		if(isset($_REQUEST['department_id']) && $_REQUEST['department_id'] !=''){
        	$departmentId = $_REQUEST['department_id'];
		}
        if($departmentId != 0){
        	$signature_id = $GLOBALS['DB']->row("SELECT `signature_id` FROM `signature` WHERE department_id=? AND user_id=? AND signature_master=1 ",array($departmentId,$GLOBALS['USERID']));
        	$signature = "";
        	if($signature_id){
				$signature = $GLOBALS['SIGNATURE']->getUserSignature($signature_id['signature_id']);
        	}else{
        		$signature_id = $GLOBALS['DB']->row("SELECT `signature_id` FROM `signature` WHERE department_id=? AND user_id=?",array($departmentId,$GLOBALS['USERID']));
        		if($signature_id){
					$signature = $GLOBALS['SIGNATURE']->getUserSignature($signature_id['signature_id']);
				}else{
					$signature_id = $GLOBALS['DB']->row("SELECT `signature_id` FROM `signature` WHERE user_id=? ORDER BY `signature_id` ASC ",array($GLOBALS['USERID']));
					$signature= $GLOBALS['SIGNATURE']->getUserSignature($signature_id['signature_id']);
				}
        	}

        }else{
	        $signature_id = $GLOBALS['DB']->row("SELECT `signature_id` FROM `signature` WHERE user_id=?",array($GLOBALS['USERID']));
			$signature= $GLOBALS['SIGNATURE']->getUserSignature($signature_id['signature_id']);
        }
        $return_arrs = array('error'=>0,'signature'=>$signature);
		echo json_encode($return_arrs); exit;
	}
	

}

?>



