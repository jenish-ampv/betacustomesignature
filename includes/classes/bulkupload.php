<?php
class CIT_BULKUPLOAD
{
	public function __construct(){}
	
	public function displayPage(){
		
		AddMessageInfo();
		$GLOBALS['success_popup'] =0;
		//$refid = $_REQUEST['refid'];
		$GLOBALS['bulkerrorcls'] ='d-none'; 
		$GLOBALS['bulkuploadcls'] = 'd-none'; 
		
		$conuRow = $GLOBALS['DB']->row("SELECT count(signature_id) as mastersig FROM signature WHERE signature_master =1 AND user_id = ?",array($GLOBALS['USERID']));
		
		if($conuRow['mastersig'] ==1){
			$GLOBALS['bulkuploadcls'] = '';
		}else{
			$GLOBALS['bulkerrorcls'] ='';
		}
		
		// bulkshare siganture email send
		if($_POST['bulkshare']=="_bulksahre"){

			if($_POST['total_share'] > 1){
				for($i=1; $i < $_POST['total_share']; $i++){				
					$send_mail = false;
					$to = $_POST['share_email'.$i];
					$shareurl = $_POST['share_url'.$i];
					$GLOBALS['SHARE_SIGURL'] = $_POST['share_url'.$i];
					$message= _getEmailTemplate('share_signature'); 	// send mail
					if(filter_var($to, FILTER_VALIDATE_EMAIL)){
						$send_mail = _SendMail($to,'',$GLOBALS['EMAIL_SUBJECT'],$message);
					}
					$result = array('error'=>0,'msg'=>'<div class="alert alert-success"><strong>Success! </strong>Signature share success</div>');
					$response = json_encode($result);
				}
			}else{
				$result = array('error'=>1,'msg'=>'<div class="alert alert-danger" id="wrong"><strong> Failure! </strong>somthing wrong try again</div>');
				$response =  json_encode($result);
			}
			echo $response; exit;
		}
		
		
		// get signature share list
		if($_REQUEST['success'] == "1"){
			if(isset($_SESSION['import_items']) && is_array($_SESSION['import_items'])){
				$GLOBALS['success_popup'] =1; $GLOBALS['signature_sharelists']='';
				$import_list = implode(",",$_SESSION['import_items']);
				$shareLists = $GLOBALS['DB']->query("SELECT S.signature_id,S.signature_firstname,CF.field_value FROM signature S LEFT JOIN signature_customfield CF ON S.signature_id = CF.signature_id AND CF.field_type = 'email' WHERE S.signature_id IN(".$import_list.") GROUP BY S.signature_id");
				$sigcount =1;
				foreach($shareLists as $shareList){
					if($shareList['field_value'] !=""){
				 	$signature_id = $shareList['signature_id'];
					$signature_name = $shareList['signature_firstname'];
				 	$share_link = $GLOBALS['linkModuleUsesignature'].'/install?uuid='.base64_encode($signature_id).'&u='.base64_encode($GLOBALS['USERID']);
					$sharename = $shareList['signature_firstname'];
						$GLOBALS['signature_sharelists'] .='<tr>
							<td>'.$signature_name.'</p></td>
							<td>
								<input type="hidden" class="kt-input share_url" id="share_url" name="share_url'.$sigcount.'"  value="'.$share_link.'">
								<input type="text" class="kt-input share_email" id="share_email" name="share_email'.$sigcount.'" placeholder="Email Address" required="required" value="'.$shareList['field_value'].'">
							</td>
						</tr>';
					$sigcount++; }
				}
				$GLOBALS['signature_sharelists'] .='<input type="hidden" name="total_share" value="'.$sigcount.'">';
			}
		}
		
		
		
		// download bulk upload csv file
		if($_REQUEST['download'] == "1"){

			$data = $GLOBALS['DB']->row("SELECT count(signature_id) as no,signature_id,signature_firstname as signature_name, signature_company,signature_jobtitle,signature_website,signature_web,signature_facebook,signature_insta,signature_google,signature_youtube,signature_linkedin,signature_pintrest,signature_twitter,signature_clendly,signature_ebay,signature_imbd,signature_tiktok,signature_vimeo,signature_yelp,signature_zillow FROM signature WHERE signature_master= 1 AND user_id =?",array($GLOBALS['USERID']));
			if($data['no'] != ""){
				$customfields = $GLOBALS['DB']->query("SELECT `field_type`,`field_value` FROM `signature_customfield` WHERE `signature_id`=?",array($data['signature_id']));
				if(count($customfields) > 0){
					$emailc =1; $phonec=1; $textc=1; $faxc=1; $websitec=1; $addressc =1; $hyperlinkc=1; $disclaimerc =1; $field=[];
					foreach($customfields as $customfield){
						$chk = $customfield['field_type'].${$customfield['field_type'].'c'};
						if(array_key_exists($chk,$field)){
							${$customfield['field_type'].'c'} ++;
						}
						$field_type = $customfield['field_type'];
						$field[$field_type.${$customfield['field_type'].'c'}] = $customfield['field_value'];
					 $fieldno++; }
				}
				unset($data['signature_id']); // remove from csv file
				
				$data  = array_merge($data,$field);
				$data = array_filter($data);
				$header = array_keys($data);
				$content = array_values($data);
				$content= array($content);
				header('Content-Type: text/csv; charset=utf-8');
				header('Content-Disposition: attachment; filename=esignature_team.csv');
				ob_end_clean();
				$output = fopen( 'php://output', 'w' );
				fputcsv( $output, $header );
				foreach($content as $data_item ){
					 fputcsv( $output, $data_item );
				}
				fclose( $output );
				exit;
			}else{
				return false;
			}
		}
		
		if($_FILES['csv_file']){								  
								  
			if($_FILES['csv_file']['type'] == 'text/csv' || $_FILES['csv_file']['type'] =='application/csv' || $_FILES['csv_file']['type']=='application/vnd.ms-excel' && $_FILES["file"]["error"] == 0){
				$file = $_FILES['csv_file']['tmp_name'];
				$handle = fopen($file, "r");
				
				$fp = file($file);
				$row_count = (count($fp) -1);
				
				if($row_count > $GLOBALS['total_sigleftlimit'] || $row_count > 500){ // check limit of plan 
					$_SESSION[GetSession('Error')] = '<div class="alert alert-info">You have exceeded your plan limit. Please upgrade a plan or remove signature.</div>';
					GetFrontRedirectUrl(GetUrl(array('module'=>$_REQUEST['module']))); exit;
				}
				$refSigRow = $GLOBALS['DB']->row("SELECT * FROM signature WHERE signature_master=1 AND user_id= ?",array($GLOBALS['USERID']));
				if($refSigRow['signature_id'] != ""){
					// get default field from ref signature
					$user_id = $refSigRow['user_id'];
					$layout_id = $refSigRow['layout_id'];
					$signature_style = $refSigRow['signature_style'];
					$signature_socialdesign = $refSigRow['signature_socialdesign'];
					$signature_btndesign = $refSigRow['signature_btndesign'];
					$signature_marketbtndesign = $refSigRow['signature_marketbtndesign'];
					$signature_socialanimation = $refSigRow['signature_socialanimation'];
					$signature_custombtnanimation = $refSigRow['signature_custombtnanimation'];
					$signature_marketbtnanimation = $refSigRow['signature_marketbtnanimation'];
					$signature_status = $refSigRow['signature_status'];
					$signature_custombtn = $refSigRow['signature_custombtn'];
					$signature_custombtnlink = $refSigRow['signature_custombtnlink'];
					$signature_custombtntext = $refSigRow['signature_custombtntext'];
					$signature_link = $refSigRow['signature_link'];
					$signature_banner = $refSigRow['signature_banner'];
					$signature_bannerlink = $refSigRow['signature_bannerlink'];
					
					$signature_ctabtnname1 = $refSigRow['signature_ctabtnname1'];
					$signature_ctabtnlink1 = $refSigRow['signature_ctabtnlink1'];
					$signature_ctabtnname2 = $refSigRow['signature_ctabtnname2'];
					$signature_ctabtnlink2 = $refSigRow['signature_ctabtnlink2'];
					$signature_ctabtnname3 = $refSigRow['signature_ctabtnname3'];
					$signature_ctabtnlink3 = $refSigRow['signature_ctabtnlink3'];
					
					$iscustom_field = array('email','phone','text','fax','website','address','hyperlink','disclaimer');
									
					 $insert_count =0; $line = false;
					 while(($filesop = fgetcsv($handle, 1000, ",")) !== false){	
						 if($line == false){ 
							if(trim($filesop[0]) != 'no' || trim($filesop[1]) != 'signature_name'){
								$_SESSION[GetSession('Error')] = '<div class="alert alert-danger">File format not valid to import.</div>';
								GetFrontRedirectUrl(GetUrl(array('module'=>$_REQUEST['module']))); exit;
							}
							$signature_fields = array_diff($filesop,$iscustom_field);
						    $custom_fields = array_intersect($filesop,$iscustom_field);
							$variables = $filesop;
						 }else{	
							$f =0;
							foreach($variables as $field){ ${$field} = $filesop[$f]; $f++;}  // store field variables
							
							if($signature_name !="" || $signature_company !="" || $signature_jobtitle !=""){
								$data =array('user_id'=>$GLOBALS['USERID'],'layout_id'=>$layout_id,'signature_profile'=>$signature_profile,'signature_firstname'=>$signature_name,'signature_company'=>$signature_company,'signature_jobtitle'=>$signature_jobtitle,'signature_socialdesign'=>$signature_socialdesign,'signature_btndesign'=>$signature_btndesign,'signature_custombtn'=>$signature_custombtn,'signature_custombtntext'=>$signature_custombtntext, 'signature_custombtnlink'=>addhttp($signature_custombtnlink),'signature_web'=>addhttp($signature_web),'signature_facebook'=>addhttp($signature_facebook), 'signature_insta'=>addhttp($signature_insta),'signature_google'=>addhttp($signature_google),'signature_youtube'=>addhttp($signature_youtube),'signature_linkedin'=>addhttp($signature_linkedin),'signature_pintrest'=>addhttp($signature_pintrest),'signature_twitter'=>addhttp($signature_twitter),'signature_clendly'=>addhttp($signature_clendly),'signature_ebay'=>addhttp($signature_ebay),'signature_imbd'=>addhttp($signature_imbd),'signature_tiktok'=>addhttp($signature_tiktok),'signature_vimeo'=>addhttp($signature_vimeo),'signature_yelp'=>addhttp($signature_yelp),'signature_zillow'=>addhttp($signature_zillow),'signature_snapchat'=>addhttp($signature_snapchat),'signature_reddit'=>addhttp($signature_reddit),'signature_wechat'=>addhttp($signature_wechat),'signature_airbnb'=>addhttp($signature_airbnb),'signature_amazon'=>addhttp($signature_amazon),'signature_discord'=>addhttp($signature_discord),'signature_spotify'=>addhttp($signature_spotify),'signature_apple'=>addhttp($signature_apple),'signature_whatsapp'=>addhttp($signature_whatsapp),'signature_shopify'=>addhttp($signature_shopify),'signature_threads'=>addhttp($signature_threads),'signature_venmo'=>addhttp($signature_venmo),'signature_zelle'=>addhttp($signature_zelle),'signature_link'=>addhttp($signature_link),'signature_banner'=>$signature_banner,'signature_bannerlink'=>$signature_bannerlink,'signature_ctabtnname1'=>$signature_ctabtnname1,'signature_ctabtnlink1'=>$signature_ctabtnlink1,'signature_ctabtnname2'=>$signature_ctabtnname2,'signature_ctabtnlink2'=>$signature_ctabtnlink2,'signature_ctabtnname3'=>$signature_ctabtnname3,'signature_ctabtnlink3'=>$signature_ctabtnlink3,'signature_style'=>$signature_style,'signature_appstorebtn'=>addhttp($signature_appstorebtn),'signature_playstorebtn'=>addhttp($signature_playstorebtn),'signature_amazonbtn'=>addhttp($signature_amazonbtn),'signature_ebaybtn'=>addhttp($signature_ebaybtn),'signature_socialanimation'=>$signature_socialanimation,'signature_custombtnanimation'=>$signature_custombtnanimation,'signature_marketbtnanimation'=>$signature_marketbtnanimation);
							   $addSignature = $GLOBALS['DB']->insert("signature",$data);
							   if($addSignature > 0){
								  $customfieldRows = $GLOBALS['DB']->query("SELECT * FROM `signature_customfield` WHERE `signature_id` = ?",array($refSigRow['signature_id']));
								  $fcount = 0;
								  $emailc=1; $phonec=1; $textc=1; $faxc=1; $websitec=1; $addressc=1; $hyperlinkc=1; $disclaimerc=1;
								  foreach($customfieldRows as $key =>$fieldvalue){

									 if($fieldvalue['field_type']== "email"){
										 $fieldvalue['field_type'].$emailc;
										 $fieldval = ${$fieldvalue['field_type'].$emailc};
										$emailc++;
									 }
									 
									 if($fieldvalue['field_type']== "phone"){
										 $fieldvalue['field_type'].$phonec;
										 $fieldval = ${$fieldvalue['field_type'].$phonec};
										$phonec++;
									 }
									 
									 if($fieldvalue['field_type']== "text"){
										 $fieldvalue['field_type'].$textc;
										 $fieldval = ${$fieldvalue['field_type'].$textc};
										$textc++;
									 }
									 
									 if($fieldvalue['field_type']== "fax"){
										 $fieldvalue['field_type'].$faxc;
										 $fieldval = ${$fieldvalue['field_type'].$faxc};
										$faxc++;
									 }
									 if($fieldvalue['field_type']== "website"){
										 $fieldvalue['field_type'].$websitec;
										 $fieldval = ${$fieldvalue['field_type'].$websitec};
										$websitec++;
									 }
									 
									 if($fieldvalue['field_type']== "address"){
										 $fieldvalue['field_type'].$addressc;
										 $fieldval = ${$fieldvalue['field_type'].$addressc};
										$addressc++;
									 }
									 if($fieldvalue['field_type']== "hyperlink"){
										 $fieldvalue['field_type'].$hyperlinkc;
										 $fieldval = ${$fieldvalue['field_type'].$hyperlinkc};
										$hyperlinkc++;
									 }
									 
									 if($fieldvalue['field_type']== "disclaimer"){
										 $fieldvalue['field_type'].$disclaimerc;
										 $fieldval = ${$fieldvalue['field_type'].$disclaimerc};
										$disclaimerc++;
									 }
									 
									 if(trim($fieldval) != ""){
									 	$fielddata = array("signature_id"=>$addSignature,"field_type"=>$fieldvalue['field_type'],"field_label"=>$fieldvalue['field_label'],"field_value"=>$fieldval,"field_fontsize"=>$fieldvalue['field_fontsize'],"field_fontweight"=>$fieldvalue['field_fontweight'],"field_fontstyle"=>$fieldvalue['field_fontstyle'],"field_color"=>$fieldvalue['field_color'],"field_order"=>$fieldvalue['field_order']);
									 	$GLOBALS['DB']->insert("signature_customfield",$fielddata);
									 }
									 //echo '<pre>';
									// print_r( $fielddata ); 
									  $fieldval ='';
									$fcount++;							
								  }
								  $import_item[] = $addSignature;
							   }
						  }
						 }
						$line = true;
						 
					 $insert_count++;	

					 }
					 
					 $_SESSION['import_items']=  $import_item;
					$_SESSION[GetSession('Success')] = '<div class="alert alert-success">File has been imported successfully.</div>';
					 $redirect = GetUrl(array('module'=>$_REQUEST['module'])).'?success=1';
					 GetFrontRedirectUrl($redirect); exit;
				}else{
					$_SESSION[GetSession('Error')] = '<div class="alert alert-info">please select master signature.</div>';
					GetFrontRedirectUrl(GetUrl(array('module'=>$_REQUEST['module']))); exit;
				}
			}else {
				$_SESSION[GetSession('Error')] = '<div class="alert alert-info">Please select a valid .csv file.</div>';
				GetFrontRedirectUrl(GetUrl(array('module'=>$_REQUEST['module']))); exit;
			}
		}


		
		$this->getPage();
		
		$GLOBALS['samplecsv_download'] =GetUrl(array('module'=>$_REQUEST['module'])).'?download=1';
		$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/bulkupload.html');	
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

	
	
}

?>