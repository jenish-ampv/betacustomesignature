<?php
class CIT_ANALYTICSURLSCRIPT
{
	public function __construct()
	{	
        
	}
	
	public function displayPage(){
        try {
            // $subscriptionUser['customer_id'] = '45';
            // $subscriptionUser['user_email'] = 'jenish@gmail.com';
            $todayDate = date('Y-m-d'); // Get current date/time
            $timestamp = date('Y-m-d H:i:s'); // Get current date/time

            $pathToFolder = $GLOBALS['UPLOAD_LINK']."/log";
            if (!file_exists($pathToFolder)) {
                mkdir($pathToFolder, 0777, true);
            }
            $pathToFolder = $GLOBALS['UPLOAD_LINK']."/log/analytics";
            if (!file_exists($pathToFolder)) {
                mkdir($pathToFolder, 0777, true);
            }

            $logFile = GetConfig('SITE_UPLOAD_PATH').'/log/analytics/updatedUserData.log';
            

            

            $signatures = $GLOBALS['DB']->query("SELECT * FROM `signature` WHERE `signature_id`!=0 ");

            foreach ($signatures as $key => $signature) {
                if($signature['signature_id']){
                    foreach ($signature as $fieldKey => $fieldValue) {
                        if($fieldValue != ""){
                            $analytic_type = $this->isLinkAnalyticsMonitored($fieldKey);
                            if($analytic_type !== FALSE){ // check for field in in monitored array

                                $row = $GLOBALS['DB']->row("SELECT * FROM `registerusers_analytics` WHERE `user_id` = ? AND `signature_id` = ? AND `analytic_type` = ? AND `date` = ? LIMIT 0,1",array($signature['user_id'],$signature['signature_id'],$analytic_type,$todayDate));	
                                
                                if($row === FALSE){
                                    $ipInformation = $this->getUserLocation();
                                    $data =array('user_id'=>$signature['user_id'],'signature_id'=>$signature['signature_id'],'url'=>$fieldValue, 'analytic_type'=>$analytic_type,'date'=>$todayDate,'user_ip'=>$ipInformation['ip'],'location'=>json_encode($ipInformation['location'], true));
                                    $addedId = $GLOBALS['DB']->insert("registerusers_analytics",$data);
                                }else{
                                    $ipInformation = $this->getUserLocation();
                                    $data = array('user_id'=>$signature['user_id'],'signature_id'=>$signature['signature_id'],'url'=>$fieldValue, 'analytic_type'=>$analytic_type,'date'=>$todayDate,'user_ip'=>$ipInformation['ip'],'location'=>json_encode($ipInformation['location'], true),'user_agent'=>$_SERVER['HTTP_USER_AGENT']);
                                    $addedId = $row['id'];
                                    $GLOBALS['DB']->update("registerusers_analytics",$data,array('id'=>$addedId));
                                }
                            }
                            // if(in_array($fieldKey, ['signature_profile','signature_banner'])){
                            if(in_array($fieldKey, ['signature_banner'])){
                                $analytic_type = str_replace("signature_", "", $fieldKey);
                                $row = $GLOBALS['DB']->row("SELECT * FROM `registerusers_analytics` WHERE `user_id` = ? AND `signature_id` = ? AND `analytic_type` = ? AND `date` = ? LIMIT 0,1",array($signature['user_id'],$signature['signature_id'],$analytic_type,$todayDate));	
                                
                                if($row === FALSE){
                                    $ipInformation = $this->getUserLocation();
                                    $data =array('user_id'=>$signature['user_id'],'signature_id'=>$signature['signature_id'],'url'=>$fieldValue, 'analytic_type'=>$analytic_type,'date'=>$todayDate,'user_ip'=>$ipInformation['ip'],'location'=>json_encode($ipInformation['location'], true));
                                    $addedId = $GLOBALS['DB']->insert("registerusers_analytics",$data);
                                }else{
                                    $ipInformation = $this->getUserLocation();
                                    $data = array('user_id'=>$signature['user_id'],'signature_id'=>$signature['signature_id'],'url'=>$fieldValue, 'analytic_type'=>$analytic_type,'date'=>$todayDate,'user_ip'=>$ipInformation['ip'],'location'=>json_encode($ipInformation['location'], true),'user_agent'=>$_SERVER['HTTP_USER_AGENT']);
                                    $addedId = $row['id'];
                                    $GLOBALS['DB']->update("registerusers_analytics",$data,array('id'=>$addedId));
                                }
                            }
                            
                        }
                    }
                    $analytic_type = "logo";
                    $row = $GLOBALS['DB']->row("SELECT * FROM `registerusers_analytics` WHERE `user_id` = ? AND `signature_id` = ? AND `analytic_type` = ? AND `date` = ? LIMIT 0,1",array($signature['user_id'],$signature['signature_id'],$analytic_type,$todayDate));	
                    $userLogo = $GLOBALS['DB']->row("SELECT * FROM `signature_logo` WHERE `user_id` = ? LIMIT 0,1",array($signature['user_id']));	
                    if($userLogo !== FALSE){
                        $urlValue = "";
                        if($userLogo['logo_process'] == '2'){
                            $urlValue = $userLogo['logo_animation'];
                        }else{
                            $urlValue = $userLogo['logo'];
                        }
                        if($row === FALSE){
                            $ipInformation = $this->getUserLocation();
                            $data =array('user_id'=>$signature['user_id'],'signature_id'=>$signature['signature_id'],'url'=>$urlValue, 'analytic_type'=>$analytic_type,'date'=>$todayDate,'user_ip'=>$ipInformation['ip'],'location'=>json_encode($ipInformation['location'], true));
                            $addedId = $GLOBALS['DB']->insert("registerusers_analytics",$data);
                        }else{
                            $ipInformation = $this->getUserLocation();
                            $data = array('user_id'=>$signature['user_id'],'signature_id'=>$signature['signature_id'],'url'=>$urlValue, 'analytic_type'=>$analytic_type,'date'=>$todayDate,'user_ip'=>$ipInformation['ip'],'location'=>json_encode($ipInformation['location'], true));
                            $addedId = $row['id'];
                            $GLOBALS['DB']->update("registerusers_analytics",$data,array('id'=>$addedId));
                        }
                    }

                    $analytic_type = "logoclick";
                    $row = $GLOBALS['DB']->row("SELECT * FROM `registerusers_analytics` WHERE `user_id` = ? AND `signature_id` = ? AND `analytic_type` = ? AND `date` = ? LIMIT 0,1",array($signature['user_id'],$signature['signature_id'],$analytic_type,$todayDate));	
                    $urlValue = $signature['signature_link'];
                    if($urlValue != ""){
                        if($row === FALSE){
                            $ipInformation = $this->getUserLocation();
                            $data =array('user_id'=>$signature['user_id'],'signature_id'=>$signature['signature_id'],'url'=>$urlValue, 'analytic_type'=>$analytic_type,'date'=>$todayDate,'user_ip'=>$ipInformation['ip'],'location'=>json_encode($ipInformation['location'], true));
                            $addedId = $GLOBALS['DB']->insert("registerusers_analytics",$data);
                        }else{
                            $ipInformation = $this->getUserLocation();
                            $data = array('user_id'=>$signature['user_id'],'signature_id'=>$signature['signature_id'],'url'=>$urlValue, 'analytic_type'=>$analytic_type,'date'=>$todayDate,'user_ip'=>$ipInformation['ip'],'location'=>json_encode($ipInformation['location'], true));
                            $addedId = $row['id'];
                            $GLOBALS['DB']->update("registerusers_analytics",$data,array('id'=>$addedId));
                        }
                    }
                }

                       
                $message = " TimeStamp -- $timestamp \n signature_id : ".$signature['signature_id']." \n user_id : ".$signature['user_id']." \n-------------------------------\n"; // Log a message
                

                $handle = fopen($logFile, 'a');
                if ($handle) {
                    fwrite($handle, $message); // Write to the log file
                    fclose($handle);// Close the log file
                } else {
                    echo "Unable to open log file!";
                }$handle = fopen($logFile, 'a');
            
            }
            echo('Links are updated and log file generated at '.$logFile);
            
        }catch(Exception $e) {
            echo $response = $e->getMessage();
            $response = json_decode($response,true);
            // GetFrontRedirectUrl(GetUrl(array('module'=>'dashboard')));
        }
        
	}
	
    public function isLinkAnalyticsMonitored($value) {
        $analyticLinksMonitorArray = array(
            'custombtnlink' => 'signature_custombtnlink',
            'web' => 'signature_web',
            'facebook' => 'signature_facebook',
            'insta' => 'signature_insta',
            'google' => 'signature_google',
            'youtube' => 'signature_youtube',
            'linkedin' => 'signature_linkedin',
            'pintrest' => 'signature_pintrest',
            'twitter' => 'signature_twitter',
            'clendly' => 'signature_clendly',
            'ebay' => 'signature_ebay',
            'imbd' => 'signature_imbd',
            'tiktok' => 'signature_tiktok',
            'vimeo' => 'signature_vimeo',
            'yelp' => 'signature_yelp',
            'zillow' => 'signature_zillow',
            'snapchat' => 'signature_snapchat',
            'reddit' => 'signature_reddit',
            'wechat' => 'signature_wechat',
            'airbnb' => 'signature_airbnb',
            'amazon' => 'signature_amazon',
            'discord' => 'signature_discord',
            'spotify' => 'signature_spotify',
            'apple' => 'signature_apple',
            'whatsapp' => 'signature_whatsapp',
            'shopify' => 'signature_shopify',
            'threads' => 'signature_threads',
            'venmo' => 'signature_venmo',
            'zelle' => 'signature_zelle',
            'appstorebtn' => 'signature_appstorebtn',
            'playstorebtn' => 'signature_playstorebtn',
            'bannerclick' => 'signature_bannerlink',
            'ctabtnlink1' => 'signature_ctabtnlink1',
            'ctabtnlink2' => 'signature_ctabtnlink2',
            'ctabtnlink3' => 'signature_ctabtnlink3'
        );

        $key = array_search($value, $analyticLinksMonitorArray);
        if($key === FALSE)
        {
           return false;
        }
        else
        {
            return $key;
        }
    }
    public function getUserLocation($ip = null) {
        // Use given IP or detect automatically
        if (!$ip) {
            if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
            } else {
                $ip = $_SERVER['REMOTE_ADDR'];
            }
        }

        // TEMP: Override for local testing
        if ($ip === '127.0.0.1' || $ip === '::1') {
            $ip = '164.92.90.31'; // Replace with your own IP if needed
        }

        $response = @file_get_contents("http://ip-api.com/json/{$ip}?fields=status,country,regionName,city");

        if ($response !== false) {
            $data = json_decode($response, true);
            if ($data['status'] === 'success') {
                return ['ip'=>$ip, 'location' => [
							'country' => $data['country'],
							'region' => $data['regionName'],
							'city' => $data['city']
						]
					];
            }
        }
		return ['ip'=>$ip, 'location' => [
				'country' => 'Unknown',
				'region' => 'Unknown',
				'city' => 'Unknown'
				]
			];
    }
}

?>