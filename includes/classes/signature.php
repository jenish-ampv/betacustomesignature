<?php
class CIT_SIGNATURE
{
	
	public function __construct()
	{	

	}
	
	public function displayPage(){
		if(isset($_REQUEST['category_id'])){
			$action = trim($_REQUEST['category_id']);
		} else {
			$action = '';
		}
		switch($action){
			case "add":
				$this->addSignature();
				break;			
			default:
				$this->viewSignature();
				break;			
		}
		
	}
	public function addSignature(){}
	
	
	public function viewSignature(){
		
		//view signature
		AddMessageInfo();	
		$GLOBALS['PageName'] = 'Signature Manager';
		$GLOBALS['MetaTitle'] = $GLOBALS['SITE_TITLE'];
		$GLOBALS['Metakeywords'] = $GLOBALS['SITE_TITLE'];
		$GLOBALS['Metadescription'] = $GLOBALS['SITE_TITLE'];
		
		$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/signature.html');	
		$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');			
		$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');
		$GLOBALS['SIDEBAR'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.sidebar.html');
		//$GLOBALS['CLA_HTML']->SetLoop('SIGNATURE',$signature);
		$GLOBALS['CLA_HTML']->display();
		RemoveMessageInfo();		
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