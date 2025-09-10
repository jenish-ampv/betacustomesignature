<?php
require_once($GLOBALS['BASE_LINK'].'/'.GetConfig('CLASSES').'/integrations.php');
require_once($GLOBALS['BASE_LINK'].'/'.GetConfig('CLASSES').'/dashboard.php');
class CIT_PROCESSPENDINGIMPORTSIGNATURE
{
	
	public function __construct()
	{	
		$GLOBALS['integrations'] = GetClass('CIT_INTEGRATIONS');
		$GLOBALS['SIGNATURE'] = GetClass('CIT_DASHBOARD');
		if(isset($_REQUEST['department_id'])){
			$GLOBALS['current_department_id'] = $_REQUEST['department_id'];
		}else{
			$GLOBALS['current_department_id'] = 0;
		}
	}
	
	public function displayPage(){
		
		AddMessageInfo();
        $import_items = $GLOBALS['DB']->query("SELECT * FROM signature_import_process_data WHERE `status`=0 AND user_id = ?",array($GLOBALS['USERID'])); //status 0 = pending
		$GLOBALS['total_imported_signature'] = sizeof($import_items);
		foreach ($import_items as $key => $import_item) {
			$signature_id = $import_item['signature_id'];
    		$signatureData = $GLOBALS['DB']->row("SELECT * FROM signature WHERE `signature_id`=? AND user_id = ?",array($signature_id,$GLOBALS['USERID']));
    		if(isset($signatureData['department_id'])){
    			if($signatureData['department_id'] != $GLOBALS['current_department_id']){
    				return false;
    			}
    		}

			$GLOBALS['signature_outlook_imported'] .= '<div class="signature_preview_container" data-signature-id="'.$signature_id.'">';
			$GLOBALS['signature_outlook_imported'] .= $GLOBALS['SIGNATURE']->getUserSignature($signature_id);
			$sigSaveHtmlUrl = GetUrl(array('module'=>'azuread','category_id'=>'saveSignatureHtml','id'=>$signature_id));
			$GLOBALS['signature_outlook_imported'] .= '</div>';
			$GLOBALS['signature_outlook_imported'] .= '<input type="hidden" id="signature_save_html_url_'.$signature_id.'" value="'.$sigSaveHtmlUrl.'" />';
		}
		$GLOBALS['link_redirect'] = GetUrl(array('module'=>'dashboard')).'?department_id='.$GLOBALS['current_department_id'];
		$GLOBALS['link_copy_cta_btn_url'] = GetUrl(array('module'=>'azuread','category_id'=>'copyCtaButton'));   // code is for saving signature html(to use in deploy) 
        $GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');
		$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/signaturepreview.html');
		$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');
		return $GLOBALS['CLA_HTML']->display();
		RemoveMessageInfo();
		exit();	
		
	}	

	


}

?>