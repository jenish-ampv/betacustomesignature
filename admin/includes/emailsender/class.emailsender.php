<?php
class CIT_EMAILSENDER
{
	private $count;
	private $result;
	private $category;
	private $emailsender_id ='';
	public function __construct(){	
		$GLOBALS['ModuleName'] = 'Email Sender';								
		if(isset($_REQUEST['page'])){			
			$GLOBALS['PageLink'] = '&page='.$_REQUEST['page'];
			if($_REQUEST['page'] === '0'){
				GetAdminRedirectUrl();
			}
		} else {
			$GLOBALS['PageLink'] = '';
		}		
	}
	public function displayPage(){		
		$this->emailsender();
	}
	private function emailsender(){					
		AddMessageInfo();	
		$row  = $GLOBALS['DB']->row("SELECT * FROM `emailsender` where site_id = ?",array($GLOBALS['SITE_ID']));	
		$count = 0;
		if(isset($_POST['emailsender_from'])){													
			if($row['emailsender_id'] == ""){
				$data =array('site_id'=>$GLOBALS['SITE_ID'],'contact_name'=>$_POST['contact_name'],'contact_email'=>$_POST['contact_email'], 'sales_name'=>$_POST['sales_name'], 'sales_email'=>$_POST['sales_email'], 'support_name'=>$_POST['support_name'],'support_email'=>$_POST['support_email'],'custome_name'=>$_POST['custome_name'],'custome_email'=>$_POST['custome_email'], 'emailsender_from'=>$_POST['emailsender_from']);
				$query = $GLOBALS['DB']->insert("emailsender",$data);
				if($query){
					$_SESSION['Success'] .= '<div class="alert alert-success">Emailsender inserted successfully</div>';
				} else {
					$_SESSION['Error'] .= '<div class="alert alert-danger">An error occurred while you trying to added emailsender, please try again.</div>';
				}	
			} else {
				$data = array('contact_name'=>$_POST['contact_name'],'contact_email'=>$_POST['contact_email'],'sales_name'=>$_POST['sales_name'],'sales_email'=>$_POST['sales_email'],'support_name'=>$_POST['support_name'],'support_email'=>$_POST['support_email'],'custome_name'=>$_POST['custome_name'],'custome_email'=>$_POST['custome_email'], 'emailsender_from'=>$_POST['emailsender_from']);
				$where = array('emailsender_id'=>$row['emailsender_id']);
				$query = $GLOBALS['DB']->update("emailsender",$data,$where);
			
				if($query){
					$_SESSION['Success'] .= '<div class="alert alert-success">Emailsender updated successfully</div>';
				} else {
					$_SESSION['Error'] .= '<div class="alert alert-danger">An error occurred while you trying to update emailsender, please try again.</div>';
				}
			}			
			GetAdminRedirectUrl();			
		}
		$count++;	
		
		$GLOBALS['ContactName'] = $row['contact_name'];
		$GLOBALS['ContactEmail'] = $row['contact_email'];
		$GLOBALS['SalesName'] = $row['sales_name'];
		$GLOBALS['SalesEmail'] = $row['sales_email'];
		$GLOBALS['SupportName'] = $row['support_name'];
		$GLOBALS['SupportEmail'] = $row['support_email'];
		$GLOBALS['CustomeName'] = $row['custome_name'];
		$GLOBALS['CustomeEmail'] = $row['custome_email'];
		$GLOBALS['Emailsenderfrom'] = $row['emailsender_from'];
		
		$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/'.$GLOBALS['Module'].'/emailsender.html');	
		$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');			
		$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');						
		$GLOBALS['LEFT'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.left.html');			
		$GLOBALS['CLA_HTML']->display();
		RemoveMessageInfo();	
	}			
}