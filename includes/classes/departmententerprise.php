<?php
// require_once($GLOBALS['BASE_LINK'].'/'.GetConfig('CLASSES').'/dashboard.php');   // code is for saving signature html(to use in deploy)
class CIT_DEPARTMENTENTERPRISE
{

	public function __construct()
	{
		if(!isset($_SESSION[GetSession('user_id')])){
			GetFrontRedirectUrl(GetUrl(array('module'=>'signin')));
		}
	}

	public function displayPage(){
		$this->getPage();
		if($_REQUEST['category_id'] == "editDepartment"){
			$department = $GLOBALS['DB']->row("SELECT * FROM `registerusers_departments` WHERE user_id = ? AND department_id = ? ",array($GLOBALS['USERID'],$_REQUEST['department_id']));
			$GLOBALS['department_id'] = $department['department_id'];
			$GLOBALS['department_name'] = $department['department_name'];
			
			$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/editdepartmententerprise.html');
			$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');
			$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');
			$GLOBALS['SIDEBAR'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.sidebar.html');
			$GLOBALS['CLA_HTML']->display();
			RemoveMessageInfo();
			exit();
		}
		if($_REQUEST['category_id'] == "deleteDepartment"){
			$department_id = $_REQUEST['department_id'];
			$delete = $GLOBALS['DB']->query("DELETE FROM registerusers_departments WHERE `department_id` = ?",array($department_id));
			if($delete){
				$GLOBALS['DB']->query("DELETE FROM `signature` WHERE `department_id` = ?",array($department_id));
				$GLOBALS['DB']->query("DELETE FROM `signature_logo` WHERE `department_id` = ?",array($department_id));
				$return_result = array('error'=>0,'msg'=>'Success');
			}else{
				$return_result = array('error'=>1,'msg'=>'Somthing wrong try again','signature'=>'');
			}
			
			GetFrontRedirectUrl($GLOBALS['moduleLinkDashboard']);
		}

		if($_POST['edit_department'] && isset($_POST['edit_department'])){
			if($_POST['department_name'] !=""){
				$department_id = $_REQUEST['department_id'];
				$rowExist = $GLOBALS['DB']->row("SELECT * FROM `registerusers_departments` WHERE `department_name`= ? AND `user_id`=? LIMIT 0,1",array(strtolower($_POST['department_name']),$GLOBALS['USERID']));
				if($rowExist){
					if($department_id == $rowExist['department_id']){
						$return_arrs = array('error'=>0);
					}else{
						$return_arrs = array('error'=>1,'msg'=>'A division with this name already exists. Please choose a different name.');
					}
				}else{
					$data = array('department_name'=>$_POST['department_name']);
					$where = array('department_id'=>$_POST['department_id']); 
					$addDepartment = $GLOBALS['DB']->update("registerusers_departments",$data,$where);
					
					$return_arrs = array('error'=>0,'msg'=>'Division Updated success');
				}
			}else{
				$return_arrs = array('error'=>1,'msg'=>'please fill all required field');
			}
			echo json_encode($return_arrs); exit;
		}

		if($_POST['department_name'] && isset($_POST['department_name'])){
			if($_POST['department_name'] !=""){
				$rowExist = $GLOBALS['DB']->row("SELECT * FROM `registerusers_departments` WHERE `department_name`= ? AND `user_id`=? LIMIT 0,1",array(strtolower($_POST['department_name']),$GLOBALS['USERID']));
				if($rowExist){
					$return_arrs = array('error'=>1,'msg'=>'A division with this name already exists. Please choose a different name.');
				}else{
					$data = array('user_id'=>$GLOBALS['USERID'],'department_name'=>$_POST['department_name']);
					$addDepartment = $GLOBALS['DB']->insert("registerusers_departments",$data);
					if($_POST['signature_department_image'] !=""){
						$logo_name = $_POST["signature_department_image"];
						$department_id = $addDepartment;
						$GLOBALS['DB']->insert("signature_logo",array('user_id'=>$GLOBALS['USERID'],'department_id'=>$department_id,'logo'=>$logo_name));
					}
					$return_arrs = array('error'=>0,'msg'=>'Division add success');
				}
				

			}else{
				$return_arrs = array('error'=>1,'msg'=>'please fill all required field');
			}
			echo json_encode($return_arrs); exit;
		}

		if($_REQUEST['category_id'] == "update-positions"){
			$data = json_decode(file_get_contents('php://input'), true);

			if($data['positions'] !=""){

				foreach ($data['positions'] as $key => $position) {
					$data = array('position'=>$position['position']);
					$where = array('department_id'=>$position['id']); 
					$updateDepartment = $GLOBALS['DB']->update("registerusers_departments",$data,$where);
				}
				$return_arrs = array('error'=>0,'msg'=>'Division Position Updated success');
			}else{
				$return_arrs = array('error'=>1,'msg'=>'please fill all required field');
			}
			echo json_encode($return_arrs); exit;
		}


		$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/newdepartmententerprise.html');
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
