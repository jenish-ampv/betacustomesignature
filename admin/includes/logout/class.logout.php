<?php
class CIT_LOGOUT
{
	public function displayPage(){		
		if(isset($_REQUEST['action'])){
			if($_REQUEST['action'] == 'expire'){
				$GLOBALS['CLA_SESSION']->sessionExpire();
			}
		} else {
			$GLOBALS['CLA_SESSION']->logoutAdmin();
		}
		exit;
	}
}