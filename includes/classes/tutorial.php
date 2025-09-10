<?php
class CIT_TUTORIAL
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
		
		$this->getPage();
		$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/tutorial.html');	
		$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');			
		$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');
		$GLOBALS['SIDEBAR'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.sidebar.html');
		$GLOBALS['CLA_HTML']->display();
		RemoveMessageInfo();
		exit();	
		
	}
	
	public function getUserSignature($signature_id=''){
		 $GLOBASL['signature_list'] ='';
		 if($signature_id!=''){
			 $signature_lists = $GLOBALS['DB']->query("SELECT SG.*,SL.* FROM `signature` SG LEFT JOIN signature_layout SL ON SG.layout_id = SL.layout_id  WHERE SG.signature_status = 1 AND SG.signature_id = ? LIMIT 0,1",array($signature_id));
		 }else{
			$signature_lists = $GLOBALS['DB']->query("SELECT SG.*,SL.* FROM `signature` SG LEFT JOIN signature_layout SL ON SG.layout_id = SL.layout_id  WHERE SG.signature_status = 1 AND SG.user_id = ? ORDER BY signature_id DESC",array($GLOBALS['USERID']));
		 }
		$GLOBALS['signature_count'] =0;
		foreach($signature_lists as $sRow){
			if($sRow['signature_process'] == 0){
				$signature_id = $sRow['signature_id'];
				$GLOBALS['signature_firstname'] = $sRow['signature_firstname'];
				$GLOBALS['signature_lastname'] = $sRow['signature_lastname'];
				$GLOBALS['signature_email'] = $sRow['signature_email'];
				$GLOBALS['signature_phone'] = $sRow['signature_phone'];
				$GLOBALS['signature_company'] = $sRow['signature_company'];
				$GLOBALS['signature_jobtitle'] = $sRow['signature_jobtitle'];
				$GLOBALS['signature_address'] = $sRow['signature_address'];
				$signature_img = $GLOBALS['UPLOAD_LINK'].'/signature/'.$GLOBALS['USERID'].'/'.$sRow['signature_image'];
				$GLOBALS['signature_image'] = '<img alt="" src="'.$signature_img.'" width="100" />';
				$root_link = $GLOBALS['IMAGE_LINK'];
				$GLOBALS['signature_website'] = '<a href="'.$sRow['signature_website'].'"><img alt="" src="'.$GLOBALS['IMAGE_LINK'].'/images/social-icon1.png" width="20" /></a>';
				$GLOBALS['signature_fb'] = '<a href="'.$sRow['signature_fb'].'"><img alt="" src="'.$root_link.'/images/social-icon1.png" width="20" /></a>';
				$GLOBALS['signature_insta'] = '<a href="'.$sRow['signature_insta'].'"><img alt="" src="'.$root_link.'/images/social-icon1.png" width="20" /></a>';
				$GLOBALS['signature_gmail'] = '<a href="'.$sRow['signature_gmail'].'"><img alt="" src="'.$root_link.'/images/social-icon1.png" width="20" /></a>';
				$GLOBALS['signature_website'] = '<a href="'.$sRow['signature_linkdin'].'"><img alt="" src="'.$root_link.'/images/social-icon1.png" width="20" /></a>';
				$GLOBALS['signature_pintrest'] = '<a href="'.$sRow['signature_pintrest'].'"><img alt="" src="'.$root_link.'/images/social-icon1.png" width="20" /></a>';
				$GLOBALS['signature_twitter'] = '<a href="'.$sRow['signature_twitter'].'"><img alt="" src="'.$root_link.'/images/social-icon1.png" width="20" /></a>';
				$GLOBALS['signature_telegram'] = '<a href="'.$sRow['signature_telegram'].'"><img alt="" src="'.$root_link.'/images/social-icon1.png" width="20" /></a>';
				$GLOBALS['signature_snapchat'] = '<a href="'.$sRow['signature_snapchat'].'"><img alt="" src="'.$root_link.'/images/social-icon1.png" width="20" /></a>';
				$GLOBALS['signature_whatsapp'] = '<a href="'.$sRow['signature_whatsapp'].'"><img alt="" src="'.$root_link.'/images/social-icon1.png" width="20" /></a>';
				
				 $GLOBALS['signature_list'] .= '<div class="col-md-6 col-12"><div class="sin_dashboard_box">
				 								<div class="dot_icon"><a href="javascript:void(0);" onclick="myFunction('.$signature_id.')"><img src="'.$root_link.'/images/dot-icon.svg" alt=""></a></div><div id="myDIV'.$signature_id.'" class="menu_open" style="display:none;">
                                 <ul>
                                 <li><a href="#"><img src="'.$root_link.'/images/edit-signature.svg" alt=""> Edit Signature</a></li>
                                 <li><a href="javascript:void(0);" class="ajaxaction" data-id="'.$signature_id.'" data-action="duplicate"><img src="'.$root_link.'/images/duplicate.svg" alt=""> Duplicate</a></li>
                                 <li><a href="#"><img src="'.$root_link.'/images/use-signature.svg" alt=""> Use Signature</a></li>
                                 <li><a href="#"><img src="'.$root_link.'/images/share-signature.svg" alt=""> Share Signature</a></li>
                                 <li><a href="javascript:void(0);" class="delete ajaxaction" data-id="'.$signature_id.'" data-action="delete"><img src="'.$root_link.'/images/delete.svg" alt=""> Delete</a></li>
                                 </ul>
                                </div>
				 '.$GLOBALS['CLA_HTML']->addContent($sRow['layout_desc']).'</div></div>';
			}else{
				$GLOBALS['signature_list'] .='<div class="col-lg-6 col-md-6 col-12">
                    	<div class="sin_dashboard_box">
                        	<h2>'.$GLOBALS['USERNAME'].'!</h2>
                            <h3>Your logo animation is in process</h3>
                            <p>Thank you for your patience.</p>
                            <div class="progress_days_box">
                            <div class="progress">
                              <div class="progress-bar w-50" role="progressbar" aria-valuenow="50" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <span>1/2 Days</span>
                            </div>
                             
                        </div>
                    </div>';
			}
				$GLOBALS['signature_count']++;
		}
	}
	
	private function actionSignature($action,$id){
		$return_result = array();
		if($action == 'duplicate'){
			$dubplicate_id = $GLOBALS['DB']->query("INSERT INTO signature SELECT 0,`user_id`,`layout_id`,`signature_process`,`signature_image`,`signature_firstname`,`signature_lastname`,`signature_email`,`signature_phone`,`signature_company`,`signature_jobtitle`,`signature_address`,`signature_website`,`signature_fb`,`signature_insta`,`signature_gmail`,`signature_youtube`,`signature_linkdin`,`signature_pintrest`,`signature_twitter`,`signature_telegram`,`signature_snapchat`,`signature_whatsapp`,`signature_status`,`signature_updated`,`signature_created` FROM signature WHERE `signature_id` =$id");
		  	$dubplicate_id = $GLOBALS['DB']->lastInsertId();
			if($dubplicate_id){
				$this->getUserSignature($dubplicate_id);
				$return_result = array('error'=>0,'msg'=>'Success');
			}else{
				$return_result = array('error'=>1,'msg'=>'Somthing wrong try again');
			}
		}
		
		if($action == 'delete'){
			$delete = $GLOBALS['DB']->query("DELETE FROM signature WHERE `signature_id` = ?",array($id));
			if($delete){
				$return_result = array('error'=>0,'msg'=>'Success');
			}else{
				$return_result = array('error'=>1,'msg'=>'Somthing wrong try again','signature'=>'');
			}
		}
		return json_encode($return_result);
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