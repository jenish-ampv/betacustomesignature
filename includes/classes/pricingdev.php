<?php
class CIT_PRICINGDEV
{
	
	public function __construct()
	{	

	}
	
	public function displayPage(){
		AddMessageInfo();	
		if(isset($_REQUEST['category_id'])){
			$action = trim($_REQUEST['category_id']);
		} else {
			$action = '';
		}


		
		$this->getPage();
		$this->getPlanDetail();
		$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/pricingdev.html');	
		$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');			
		$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');
		$GLOBALS['SIDEBAR'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.sidebar.html');
		$GLOBALS['CLA_HTML']->display();
		RemoveMessageInfo();
		exit();	
		
	}

	public function getPlanDetail($plan_id='',$unit='',$getunit =1){
		$planRows = $GLOBALS['DB']->query("SELECT * FROM `plan`  WHERE `plan_status` =1");
		foreach($planRows as $planRow){
			$plan_id = $planRow['plan_id'];
			$planname = strtolower($planRow['plan_name']);
			$plantype = strtolower($planRow['plan_type']);
			if($getunit == 1){
				$unitRows = $GLOBALS['DB']->query("SELECT * FROM plan_unit WHERE plan_id = ? ORDER BY plan_unit ASC",array($plan_id));
				foreach($unitRows as $unit){
					if($planname == 'basic' && $plantype == 'quarter'){
						$basic_quarter_arr[$unit['plan_unit']] = intval($unit['plan_unitprice']);
						$basic_quarter_arrspl[$unit['plan_unit']] = intval($unit['plan_unitsplprice']);
					}
					if($planname == 'pro' && $plantype == 'quarter'){
						$pro_quarter_arr[$unit['plan_unit']] = intval($unit['plan_unitprice']);
						$pro_quarter_arrspl[$unit['plan_unit']] = intval($unit['plan_unitsplprice']);
					}
					if($planname == 'basic' && $plantype == 'year'){
						$basic_year_arr[$unit['plan_unit']] = intval($unit['plan_unitprice']);
						$basic_year_arrspl[$unit['plan_unit']] = intval($unit['plan_unitsplprice']);
					}
					if($planname == 'pro' && $plantype == 'year'){
						$pro_year_arr[$unit['plan_unit']] = intval($unit['plan_unitprice']);
						$pro_year_arrspl[$unit['plan_unit']] = intval($unit['plan_unitsplprice']);
					}
				}
			}
		}
		//$GLOBALS['basic_month_unit'] =  json_encode(array(1=>10,5=>15,10=>25,15=>35,20=>45,25=>50,30=>55,35=>60,40=>65,45=>70,50=>75)); 
		//$GLOBALS['pro_month_unit'] =  json_encode(array(1=>15,5=>20,10=>30,15=>40,20=>50,25=>55,30=>60,35=>65,40=>70,45=>75,50=>80)); 
		
		$GLOBALS['basic_quarter_unit'] = json_encode($basic_quarter_arr);
		$GLOBALS['basic_quarter_unitspl'] = json_encode($basic_quarter_arrspl);
		$GLOBALS['pro_quarter_unit'] = json_encode($pro_quarter_arr);
		$GLOBALS['pro_quarter_unitspl'] = json_encode($pro_quarter_arrspl);
		$GLOBALS['basic_year_unit'] = json_encode($basic_year_arr);
		$GLOBALS['basic_year_unitspl'] = json_encode($basic_year_arrspl);
		$GLOBALS['pro_year_unit'] = json_encode($pro_year_arr);
		$GLOBALS['pro_year_unitspl'] = json_encode($pro_year_arrspl);
		return false;
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