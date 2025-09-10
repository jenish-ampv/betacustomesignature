<?php

class CIT_CMSPAGE
{
	private $result;
	private $id ='';
	
	public function displayPage(){
		
		  
		if(isset($_REQUEST['id'])){
			$id = trim($_REQUEST['id']);
		} else {
			$id = '';
		}

		
		
		
		if(!is_numeric($id)){
			header(sprintf('Location: %s',GetConfig('SITE_URL')));
			JavascriptHeader(sprintf("%s",GetConfig('SITE_URL')));
  	    }
		  
		
		$row = $GLOBALS['DB']->row("SELECT * FROM `pages` WHERE `id`= ? ORDER BY `id` ASC LIMIT 0,1",array($id));


		// seo content
		if($row['metatitle'] != ''){
			$GLOBALS['MetaTitle'] = $row['metatitle'];
		}
		$GLOBALS['Metakeywords'] = $row['metakeywords'];
		$GLOBALS['Metadescription'] = $row['metadescription'];
		// seo content	
		$GLOBALS['PageName'] = $row['name'];	
		$GLOBALS['PageDesc'] = $row['desc'];
		
		$prowrow = $GLOBALS['DB']->row("SELECT * FROM `pages` WHERE `id`= ? ORDER BY `id` ASC LIMIT 0,1",array($row['parentid']));

		$GLOBALS['Parentid'] = $prow['PageName'];
		
		$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/cmspage.html');	
		$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');			
		$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');		
		//$GLOBALS['LEFT'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.left.html');
								
		$GLOBALS['PageDesc'] = $GLOBALS['CLA_HTML']->addContent($row['desc']);
		$GLOBALS['CLA_HTML']->display();
		exit();	
	}	
}
?>