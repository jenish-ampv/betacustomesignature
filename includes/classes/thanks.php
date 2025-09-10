<?php
class CIT_THANKS
{
	
	public function __construct()
	{	

	}
	
	public function displayPage(){
		AddMessageInfo();	 $GLOABLS['runsignupscript'] =0;
		if(isset($_REQUEST['category_id'])){
			$action = trim($_REQUEST['category_id']);
		} else {
			$action = '';
		}
		
		if(isset($_REQUEST['customer_id']) && is_numeric($_REQUEST['customer_id'])){
			
				$userRow = $GLOBALS['DB']->row("select RU.*,SU.customer_id FROM `registerusers` RU LEFT JOIN  registerusers_subscription SU ON RU.user_id = SU.user_id  WHERE RU.user_id =".$_REQUEST['customer_id']);
				if($userRow){
					$GLOBALS['customer_refid'] = $userRow['customer_id'];
					$GLOBALS['customer_refemail'] = $userRow['user_email'];
					
					
					 $GLOBALS['runsignupscript'] = 1;
				}
		}
		$this->getPage();
		$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/thanks.html');	
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