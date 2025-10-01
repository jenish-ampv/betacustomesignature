<?php
// ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
class CIT_R
{
	public function __construct()
	{	
        
	}
	
	public function displayPage(){
        try {
            $userIp = $this->getUserIp();
            $userLocation = json_encode($this->getUserLocation());
            $redirectUrlId = $_REQUEST['category_id'];  //Redirect Url ID
            $analytics_type = $_REQUEST['id']; //Redirect Url analytics type
            /* 
            if($analytics_type == 'profile'){
                $rProfileData = $GLOBALS['DB']->row("SELECT * FROM `registerusers_analytics` WHERE `id`= ? ",array($redirectUrlId));
                $imagePath = '';
                if($rProfileData){
                    $dateToday = date('Y-m-d');
                    $profileName = $rProfileData['url'];
                    $newImpressions = $rProfileData['impressions']+1;
                    $data = array('impressions' => $newImpressions,'date'=>$dateToday, 'location'=>$userLocation);

                    $canUpdate = $GLOBALS['DB']->row("SELECT (NOW() > updated_at + INTERVAL 10 SECOND) AS can_update FROM registerusers_analytics WHERE id = ?", [$redirectUrlId]);
                    if ($canUpdate && $canUpdate['can_update']) {
                        if (!isset($_SERVER['HTTP_REFERER']) || strpos($_SERVER['HTTP_REFERER'], 'customesignature.com') === false){
                            // Check if today's entry exists
                            $existingEntry = $GLOBALS['DB']->row("SELECT * FROM registerusers_analytics WHERE id= ? AND date=?",array($redirectUrlId,$dateToday));

                            if ($existingEntry) {
                                // Update today's entry
                                $GLOBALS['DB']->update('registerusers_analytics',$data,array('id' => $redirectUrlId, 'date' => $dateToday));
                            } else {
                                $existingData = $GLOBALS['DB']->row("SELECT * FROM registerusers_analytics WHERE id= ?",array($redirectUrlId));
                                if($existingData){
                                    $existingEntrySameDate = $GLOBALS['DB']->row("SELECT * FROM registerusers_analytics WHERE user_id = ? AND date= ? AND url = ? AND analytic_type='profile'",array($existingData['user_id'],$dateToday,$existingData['url']));
                                    if($existingEntrySameDate){
                                        // Update today's entry
                                        $newData['user_id'] = $existingData['user_id'];
                                        $newData['signature_id'] = $existingData['signature_id'];
                                        $newData['url'] = $existingData['url'];
                                        $newData['analytic_type'] = $existingData['analytic_type'];
                                        $newData['impressions'] = $existingEntrySameDate['impressions']+1;
                                        $GLOBALS['DB']->update('registerusers_analytics',$newData,array('id' => $existingEntrySameDate['id']));
                                    }
                                    else{
                                        // Add new entry
                                        $data['user_id'] = $existingData['user_id'];
                                        $data['signature_id'] = $existingData['signature_id'];
                                        $data['url'] = $existingData['url'];
                                        $data['analytic_type'] = $existingData['analytic_type'];
                                        $data['impressions'] = 1;
                                        $GLOBALS['DB']->insert('registerusers_analytics', $data);
                                    }
                                    
                                }
                            }
                        }
                    } else {
                        // skip update; too soon
                    }
                    $imagePath = $GLOBALS['BUCKETBASEURL'].'/upload-beta/signature/profile/'.$profileName;
                    
                }else{
                    $imagePath = $GLOBALS['BUCKETBASEURL'].'/images/profile-img1.png';
                }

                // Try to get image content from remote URL
                $imageContent = @file_get_contents($imagePath);

                // Check if the image was successfully fetched
                if ($imageContent !== false) {
                    // Get image info from the image content
                    $imageInfo = @getimagesizefromstring($imageContent);

                    if ($imageInfo !== false) {
                        header_remove('Content-Type');
                        header('Content-Type: ' . $imageInfo['mime']);
                        header('Content-Length: ' . strlen($imageContent));

                        // Clear output buffer
                        if (ob_get_length()) {
                            ob_end_clean();
                        }

                        // Output the image content
                        echo $imageContent;
                    } else {
                        // Image data is invalid
                        header("HTTP/1.0 415 Unsupported Media Type");
                        echo "Invalid image data.";
                    }
                } else {
                    // Image not found or could not be fetched
                    header("HTTP/1.0 404 Not Found");
                    echo "Image not found!";
                }
            }
            else */
            if($analytics_type == 'logo'){
                $rLogoData = $GLOBALS['DB']->row("SELECT * FROM `registerusers_analytics` WHERE `id`= ? ",array($redirectUrlId));
                $imagePath = '';
                if($rLogoData){
                    $dateToday = date('Y-m-d');
                    $logoName = $rLogoData['url'];
                    $userId = $rLogoData['user_id'];
                    $newImpressions = $rLogoData['impressions']+1;
                    $data = array('impressions' => $newImpressions, 'date'=>$dateToday, 'location'=>$userLocation);
                    if($userIp == $rLogoData['ip']){
                        $canUpdate = $GLOBALS['DB']->row("SELECT (NOW() > updated_at + INTERVAL 10 SECOND) AS can_update FROM registerusers_analytics WHERE id = ?", [$redirectUrlId]); 
                        if ($canUpdate && $canUpdate['can_update']) {
                            if (!isset($_SERVER['HTTP_REFERER']) || strpos($_SERVER['HTTP_REFERER'], 'customesignature.com') === false){
                                // Check if today's entry exists
                                $existingEntry = $GLOBALS['DB']->row("SELECT * FROM registerusers_analytics WHERE id= ? AND date=?",array($redirectUrlId,$dateToday));

                                if ($existingEntry) {
                                    // Update today's entry
                                    $GLOBALS['DB']->update('registerusers_analytics',$data,array('id' => $redirectUrlId, 'date' => $dateToday));
                                } else {
                                    // Add new entry
                                    $existingData = $GLOBALS['DB']->row("SELECT * FROM registerusers_analytics WHERE id= ?",array($redirectUrlId));
                                    if($existingData){
                                        $existingEntrySameDate = $GLOBALS['DB']->row("SELECT * FROM registerusers_analytics WHERE user_id = ? AND date= ? AND url = ? AND analytic_type='logo'",array($existingData['user_id'],$dateToday,$existingData['url']));
                                        if($existingEntrySameDate){
                                            // Update today's entry
                                            $newData['user_id'] = $existingData['user_id'];
                                            $newData['signature_id'] = $existingData['signature_id'];
                                            $newData['url'] = $existingData['url'];
                                            $newData['analytic_type'] = $existingData['analytic_type'];
                                            $newData['impressions'] = $existingEntrySameDate['impressions']+1;
                                            $GLOBALS['DB']->update('registerusers_analytics',$newData,array('id' => $existingEntrySameDate['id']));
                                        }
                                        else{
                                            $data['user_id'] = $existingData['user_id'];
                                            $data['signature_id'] = $existingData['signature_id'];
                                            $data['url'] = $existingData['url'];
                                            $data['analytic_type'] = $existingData['analytic_type'];
                                            $data['impressions'] = 1;
                                            $data['date'] = $dateToday;
                                            $GLOBALS['DB']->insert('registerusers_analytics', $data);
                                        }
                                    }
                                }
                            }
                        } else {
                            // skip update; too soon
                        }
                    }else{
                        $canUpdate = $GLOBALS['DB']->row("SELECT (NOW() > updated_at + INTERVAL 10 SECOND) AS can_update FROM registerusers_analytics WHERE id = ?", [$redirectUrlId]); 
                        if ($canUpdate && $canUpdate['can_update']) {
                            if (!isset($_SERVER['HTTP_REFERER']) || strpos($_SERVER['HTTP_REFERER'], 'customesignature.com') === false){
                                $existingDataSameIP = $GLOBALS['DB']->row("SELECT * FROM registerusers_analytics WHERE date= ? AND user_ip=?",array($dateToday,$userIp));
                                if($existingDataSameIP){
                                    $GLOBALS['DB']->update('registerusers_analytics',$data,array('id' => $redirectUrlId, 'date' => $dateToday));
                                } 
                                else{
                                    $existingData = $GLOBALS['DB']->row("SELECT * FROM registerusers_analytics WHERE id= ?",array($redirectUrlId));
                                    if($existingData){
                                        // Update today's entry
                                        $newData['user_id'] = $existingData['user_id'];
                                        $newData['signature_id'] = $existingData['signature_id'];
                                        $newData['url'] = $existingData['url'];
                                        $newData['analytic_type'] = $existingData['analytic_type'];
                                        $newData['impressions'] = 1;
                                        $newData['date'] = $dateToday;
                                        $newData['user_ip'] = $userIp;
                                        $newData['location'] = $userLocation;
                                        $GLOBALS['DB']->insert('registerusers_analytics', $newData);
                                    }
                                }
                            }
                        }
                    }
                    $userLogo = $GLOBALS['DB']->row("SELECT * FROM `signature_logo` WHERE `user_id` = ? LIMIT 0,1",array($userId));	
                    if($userLogo !== FALSE){
                        if($userLogo['logo_process'] == '2'){
                            $imagePath = $GLOBALS['BUCKETBASEURL'].'/upload-beta/signature/complete/'.$userId.'/'.$logoName;
                        }else{
                            $imagePath = $GLOBALS['BUCKETBASEURL'].'/upload-beta/signature/'.$userId.'/'.$logoName;
                        }
                    }
                    
                    // if(!file_exists($imagePath)) {
                    //     $imagePath = $GLOBALS['BUCKETBASEURL'].'/upload-beta/signature/'.$userId.'/'.$logoName;
                    // }
                    
                }else{
                    $imagePath = $GLOBALS['BUCKETBASEURL'].'/images/profile-img1.png';
                }
                

                // Try to get image content from remote URL
                $imageContent = @file_get_contents($imagePath);

                // Check if the image was successfully fetched
                if ($imageContent !== false) {
                    // Get image info from the image content
                    $imageInfo = @getimagesizefromstring($imageContent);

                    if ($imageInfo !== false) {
                        header_remove('Content-Type');
                        header('Content-Type: ' . $imageInfo['mime']);
                        header('Content-Length: ' . strlen($imageContent));

                        // Clear output buffer
                        if (ob_get_length()) {
                            ob_end_clean();
                        }

                        // Output the image content
                        echo $imageContent;
                    } else {
                        header_remove('Content-Type');
                        header('Content-Type: image/svg+xml');
                        header('Content-Length: ' . strlen($imageContent));

                        // Clear output buffer
                        if (ob_get_length()) {
                            ob_end_clean();
                        }

                        // Output the image content
                        echo $imageContent;
                        // Image data is invalid
                        // header("HTTP/1.0 415 Unsupported Media Type");
                        // echo "Invalid image data.";
                    }
                } else {
                    // Image not found or could not be fetched
                    header("HTTP/1.0 404 Not Found");
                    echo "Image not found!";
                }
            }
            else if($analytics_type == 'banner'){

                $rBannerData = $GLOBALS['DB']->row("SELECT * FROM `registerusers_analytics` WHERE `id`= ? ",array($redirectUrlId));
                $imagePath = '';
                if($rBannerData){
                    $dateToday = date('Y-m-d');
                    $bannerName = $rBannerData['url'];
                    $userId = $rBannerData['user_id'];
                    $newImpressions = $rBannerData['impressions']+1;
                    $data = array('impressions' => $newImpressions, 'date'=>$dateToday, 'location'=>$userLocation);
                    
                    if($userIp == $rBannerData['ip']){
                        $canUpdate = $GLOBALS['DB']->row("SELECT (NOW() > updated_at + INTERVAL 10 SECOND) AS can_update FROM registerusers_analytics WHERE id = ?", [$redirectUrlId]);
                        if ($canUpdate && $canUpdate['can_update']) {
                            if (!isset($_SERVER['HTTP_REFERER']) || strpos($_SERVER['HTTP_REFERER'], 'customesignature.com') === false){
                                $existingEntry = $GLOBALS['DB']->row("SELECT * FROM registerusers_analytics WHERE id= ? AND date=?",array($redirectUrlId,$dateToday));
                                if ($existingEntry) {
                                    // Update today's entry
                                    $GLOBALS['DB']->update('registerusers_analytics',$data,array('id' => $redirectUrlId, 'date' => $dateToday));
                                } else {
                                    
                                    // Add new entry
                                    $existingData = $GLOBALS['DB']->row("SELECT * FROM registerusers_analytics WHERE id= ?",array($redirectUrlId));
                                    if($existingData){
                                        $existingEntrySameDate = $GLOBALS['DB']->row("SELECT * FROM registerusers_analytics WHERE user_id = ? AND date= ? AND url = ? AND analytic_type='banner'",array($existingData['user_id'],$dateToday,$existingData['url']));
                                        if($existingEntrySameDate){
                                            // Update today's entry
                                            $newData['user_id'] = $existingData['user_id'];
                                            $newData['signature_id'] = $existingData['signature_id'];
                                            $newData['url'] = $existingData['url'];
                                            $newData['analytic_type'] = $existingData['analytic_type'];
                                            $newData['impressions'] = $existingEntrySameDate['impressions']+1;
                                            $GLOBALS['DB']->update('registerusers_analytics',$newData,array('id' => $existingEntrySameDate['id']));
                                        }else{
                                            $data['user_id'] = $existingData['user_id'];
                                            $data['signature_id'] = $existingData['signature_id'];
                                            $data['url'] = $existingData['url'];
                                            $data['analytic_type'] = $existingData['analytic_type'];
                                            $data['impressions'] = 1;
                                            $data['date'] = $dateToday;
                                            $GLOBALS['DB']->insert('registerusers_analytics', $data);
                                        }
                                    }
                                }
                            }
                        } else {
                            // skip update; too soon
                        }
                    }else{
                        $canUpdate = $GLOBALS['DB']->row("SELECT (NOW() > updated_at + INTERVAL 10 SECOND) AS can_update FROM registerusers_analytics WHERE id = ?", [$redirectUrlId]);
                        if ($canUpdate && $canUpdate['can_update']) {
                            if (!isset($_SERVER['HTTP_REFERER']) || strpos($_SERVER['HTTP_REFERER'], 'customesignature.com') === false){
                                $existingDataSameIP = $GLOBALS['DB']->row("SELECT * FROM registerusers_analytics WHERE date= ? AND user_ip=?",array($dateToday,$userIp));
                                if($existingDataSameIP){
                                    $GLOBALS['DB']->update('registerusers_analytics',$data,array('id' => $redirectUrlId, 'date' => $dateToday));
                                } 
                                else{
                                    $existingData = $GLOBALS['DB']->row("SELECT * FROM registerusers_analytics WHERE id= ?",array($redirectUrlId));
                                    if($existingData){
                                        // Update today's entry
                                        $newData['user_id'] = $existingData['user_id'];
                                        $newData['signature_id'] = $existingData['signature_id'];
                                        $newData['url'] = $existingData['url'];
                                        $newData['analytic_type'] = $existingData['analytic_type'];
                                        $newData['impressions'] = 1;
                                        $newData['date'] = $dateToday;
                                        $newData['user_ip'] = $userIp;
                                        $newData['location'] = $userLocation;
                                        $GLOBALS['DB']->insert('registerusers_analytics', $newData);
                                    }
                                }
                            }
                        } else {
                            // skip update; too soon
                        }
                    }

                    $imagePath = $GLOBALS['BUCKETBASEURL'].'/upload-beta/signature/banner/'.$bannerName;
                    $filePath = 'upload-beta/bannercampaign/'.$bannerName;
					if (file_exists($filePath)) {
						$imagePath =  $GLOBALS['BUCKETBASEURL'].'/upload-beta/bannercampaign/'.$bannerName;
					}
                }else{
                    $imagePath = $GLOBALS['BUCKETBASEURL'].'/images/banner-img1.png';
                }

                // Try to get image content from remote URL
                $imageContent = @file_get_contents($imagePath);

                // Check if the image was successfully fetched
                if ($imageContent !== false) {
                    // Get image info from the image content
                    $imageInfo = @getimagesizefromstring($imageContent);

                    if ($imageInfo !== false) {
                        header_remove('Content-Type');
                        header('Content-Type: ' . $imageInfo['mime']);
                        header('Content-Length: ' . strlen($imageContent));

                        // Clear output buffer
                        if (ob_get_length()) {
                            ob_end_clean();
                        }

                        // Output the image content
                        echo $imageContent;
                    } else {
                        // Image data is invalid
                        header("HTTP/1.0 415 Unsupported Media Type");
                        echo "Invalid image data.";
                    }
                } else {
                    // Image not found or could not be fetched
                    header("HTTP/1.0 404 Not Found");
                    echo "Image not found!";
                }
            }
            else{
                if($redirectUrlId == ""){
                    $redirectUrlId = $_REQUEST['id'];  //Redirect Url ID

                }
                $rUrlData = $GLOBALS['DB']->row("SELECT * FROM `registerusers_analytics` WHERE `id`= ? ",array($redirectUrlId));
                if($rUrlData){
                    $url = $rUrlData['url'];
                    $newClicks = $rUrlData['clicks']+1;
                    // Platform check  

                    $userAgent = strtolower($_SERVER["HTTP_USER_AGENT"]);

                    // OS/device detection
                    $isIPad = strpos($userAgent, 'ipad') !== false;
                    $isIPhone = strpos($userAgent, 'iphone') !== false;
                    $isAndroid = strpos($userAgent, 'android') !== false;

                    // Tablet: iPad OR Android device without 'mobile' in UA
                    $isTablet = $isIPad || ($isAndroid && strpos($userAgent, 'mobile') === false) || strpos($userAgent, 'tablet') !== false;

                    // Mobile: iPhone or Android with 'mobile' in UA
                    $isMobile = !$isTablet && ($isIPhone || ($isAndroid && strpos($userAgent, 'mobile') !== false) || strpos($userAgent, 'mobile') !== false);

                    // Desktop: not mobile and not tablet
                    $isDesktop = !$isMobile && !$isTablet;

                    // OS detection
                    $isWindows = strpos($userAgent, 'windows') !== false;
                    $isMac = (strpos($userAgent, 'macintosh') !== false || strpos($userAgent, 'mac os') !== false) && !$isIPad && !$isIPhone;
                    $isLinux = strpos($userAgent, 'linux') !== false && !$isAndroid;
                    $isIOS = $isIPhone || $isIPad;

                    // Click counters from DB
                    $mobileClicks = $rUrlData['mobile_clicks'];
                    $desktopClicks = $rUrlData['desktop_clicks'];
                    $tabletClicks = $rUrlData['tablet_clicks'];
                    $windowsClicks = $rUrlData['windows_clicks'];
                    $macosClicks = $rUrlData['macos_clicks'];
                    $linuxClicks = $rUrlData['linux_clicks'];
                    $iosClicks = $rUrlData['ios_clicks'];
                    $androidClicks = $rUrlData['android_clicks'];

                    // Increment counters based on device/OS
                    if ($isMobile) $mobileClicks++;
                    if ($isTablet) $tabletClicks++;
                    if ($isDesktop) $desktopClicks++;
                    if ($isWindows) $windowsClicks++;
                    if ($isMac) $macosClicks++;
                    if ($isLinux) $linuxClicks++;
                    if ($isIOS) $iosClicks++;
                    if ($isAndroid) $androidClicks++;


                    $dateToday = date('Y-m-d');
                    $data = array('clicks' => $newClicks,'mobile_clicks' => $mobileClicks, 'desktop_clicks' => $desktopClicks, 'tablet_clicks' => $tabletClicks, 'windows_clicks' => $windowsClicks, 'macos_clicks'=> $macosClicks, 'linux_clicks' => $linuxClicks, 'ios_clicks' => $iosClicks, 'android_clicks' => $androidClicks, 'date'=>$dateToday, 'location'=>$userLocation);
                    if($userIp == $rUrlData['ip']){
                        $canUpdate = $GLOBALS['DB']->row("SELECT (NOW() > updated_at + INTERVAL 10 SECOND) AS can_update FROM registerusers_analytics WHERE id = ?", [$redirectUrlId]);
                        if ($canUpdate && $canUpdate['can_update']) {
                            if (!isset($_SERVER['HTTP_REFERER']) || strpos($_SERVER['HTTP_REFERER'], 'customesignature.com') === false){
                                $existingEntry = $GLOBALS['DB']->row("SELECT * FROM registerusers_analytics WHERE id= ? AND date=?",array($redirectUrlId,$dateToday));
                                if ($existingEntry) {
                                    // Update today's entry
                                    $GLOBALS['DB']->update('registerusers_analytics',$data,array('id' => $redirectUrlId, 'date' => $dateToday));
                                } else {
                                    $existingData = $GLOBALS['DB']->row("SELECT * FROM registerusers_analytics WHERE id= ?",array($redirectUrlId));

                                    if($existingData){
                                        $existingEntrySameDate = $GLOBALS['DB']->row("SELECT * FROM registerusers_analytics WHERE user_id = ? AND date= ? AND url = ? ",array($existingData['user_id'],$dateToday,$existingData['url']));
                                        if($existingEntrySameDate){
                                            // Update today's entry
                                            $mobileClicksSameDate = $existingEntrySameDate['mobile_clicks'];
                                            $desktopClicksSameDate = $existingEntrySameDate['desktop_clicks'];
                                            $tabletClicksSameDate = $existingEntrySameDate['tablet_clicks'];
                                            $windowsClicksSameDate = $existingEntrySameDate['windows_clicks'];
                                            $macosClicksSameDate = $existingEntrySameDate['macos_clicks'];
                                            $linuxClicksSameDate = $existingEntrySameDate['linux_clicks'];
                                            $iosClicksSameDate = $existingEntrySameDate['ios_clicks'];
                                            $androidClicksSameDate = $existingEntrySameDate['android_clicks'];
                                            $newClicksSameDate = $existingEntrySameDate['clicks']+1;
                                            if ($isMobile) $mobileClicksSameDate++;
                                            if ($isTablet) $tabletClicksSameDate++;
                                            if ($isDesktop) $desktopClicksSameDate++;
                                            if ($isWindows) $windowsClicksSameDate++;
                                            if ($isMac) $macosClicksSameDate++;
                                            if ($isLinux) $linuxClicksSameDate++;
                                            if ($isIOS) $iosClicksSameDate++;
                                            if ($isAndroid) $androidClicksSameDate++;
                                            $newData = array('clicks' => $newClicksSameDate,'mobile_clicks' => $mobileClicksSameDate, 'desktop_clicks' => $desktopClicksSameDate, 'tablet_clicks' => $tabletClicksSameDate, 'windows_clicks' => $windowsClicksSameDate, 'macos_clicks'=> $macosClicksSameDate, 'linux_clicks' => $linuxClicksSameDate, 'ios_clicks' => $iosClicksSameDate, 'android_clicks' => $androidClicksSameDate, 'date'=>$dateToday, 'location'=>$userLocation);
                                            $GLOBALS['DB']->update('registerusers_analytics',$newData,array('id' => $existingEntrySameDate['id']));
                                        }
                                        else{
                                            // Add new entry

                                            $mobileClicks = $desktopClicks = $tabletClicks = $windowsClicks = $macosClicks = $linuxClicks = $iosClicks = $androidClicks = 0;
                                            if ($isMobile) $mobileClicks++;
                                            if ($isTablet) $tabletClicks++;
                                            if ($isDesktop) $desktopClicks++;
                                            if ($isWindows) $windowsClicks++;
                                            if ($isMac) $macosClicks++;
                                            if ($isLinux) $linuxClicks++;
                                            if ($isIOS) $iosClicks++;
                                            if ($isAndroid) $androidClicks++;
                                            $data = array('clicks' => $newClicks,'mobile_clicks' => $mobileClicks, 'desktop_clicks' => $desktopClicks, 'tablet_clicks' => $tabletClicks, 'windows_clicks' => $windowsClicks, 'macos_clicks'=> $macosClicks, 'linux_clicks' => $linuxClicks, 'ios_clicks' => $iosClicks, 'android_clicks' => $androidClicks, 'date'=>$dateToday, 'location'=>$userLocation);
                                            $data['user_id'] = $existingData['user_id'];
                                            $data['signature_id'] = $existingData['signature_id'];
                                            $data['url'] = $existingData['url'];
                                            $data['analytic_type'] = $existingData['analytic_type'];
                                            $data['clicks'] = 1;
                                            $GLOBALS['DB']->insert('registerusers_analytics', $data);
                                        }
                                    
                                    }
                                }
                            }

                        } else {
                            // skip update; too soon
                        }
                    } else {
                        $canUpdate = $GLOBALS['DB']->row("SELECT (NOW() > updated_at + INTERVAL 10 SECOND) AS can_update FROM registerusers_analytics WHERE id = ?", [$redirectUrlId]);
                        if ($canUpdate && $canUpdate['can_update']) {
                            if (!isset($_SERVER['HTTP_REFERER']) || strpos($_SERVER['HTTP_REFERER'], 'customesignature.com') === false){
                                $existingDataSameIP = $GLOBALS['DB']->row("SELECT * FROM registerusers_analytics WHERE date= ? AND user_ip=?",array($dateToday,$userIp));
                                if($existingDataSameIP){
                                    $GLOBALS['DB']->update('registerusers_analytics',$data,array('id' => $redirectUrlId, 'date' => $dateToday));
                                } 
                                else {
                                    $existingData = $GLOBALS['DB']->row("SELECT * FROM registerusers_analytics WHERE id= ?",array($redirectUrlId));
                                    if($existingData){
                                        $mobileClicks = $desktopClicks = $tabletClicks = $windowsClicks = $macosClicks = $linuxClicks = $iosClicks = $androidClicks = 0;
                                        if ($isMobile) $mobileClicks++;
                                        if ($isTablet) $tabletClicks++;
                                        if ($isDesktop) $desktopClicks++;
                                        if ($isWindows) $windowsClicks++;
                                        if ($isMac) $macosClicks++;
                                        if ($isLinux) $linuxClicks++;
                                        if ($isIOS) $iosClicks++;
                                        if ($isAndroid) $androidClicks++;
                                        $data = array('clicks' => $newClicks,'mobile_clicks' => $mobileClicks, 'desktop_clicks' => $desktopClicks, 'tablet_clicks' => $tabletClicks, 'windows_clicks' => $windowsClicks, 'macos_clicks'=> $macosClicks, 'linux_clicks' => $linuxClicks, 'ios_clicks' => $iosClicks, 'android_clicks' => $androidClicks, 'date'=>$dateToday, 'location'=>$userLocation);
                                        $data['user_id'] = $existingData['user_id'];
                                        $data['signature_id'] = $existingData['signature_id'];
                                        $data['url'] = $existingData['url'];
                                        $data['analytic_type'] = $existingData['analytic_type'];
                                        $data['clicks'] = 1;
                                        $GLOBALS['DB']->insert('registerusers_analytics', $data);
                                    }
                                }
                            }
                        } else {
                            // skip update; too soon
                        }
                    }
                    if (str_contains($url, 'http')) { 
                        if($rUrlData['analytic_type'] == 'email'){
                            echo '<script type="text/javascript">
                                    window.location.href = "mailto:' . $url . '";
                                </script>';
                            exit;
                        }
                        if($rUrlData['analytic_type'] == 'phone'){
                            echo '<script type="text/javascript">
                                    window.location.href = "tel:' . $url . '";
                                </script>';
                            exit;
                        }
                        header('Location: '.$url);
                    }
                    else{
                        if($rUrlData['analytic_type'] == 'email'){
                            echo '<script type="text/javascript">
                                    window.location.href = "mailto:' . $url . '";
                                </script>';
                            exit;
                        }
                        if($rUrlData['analytic_type'] == 'phone'){
                            echo '<script type="text/javascript">
                                    window.location.href = "tel:' . $url . '";
                                </script>';
                            exit;
                        }
                        header('Location: https://'.$url);
                    }
                }else{
                    throw new Exception("Something went wrong, try again");
                }
            }
            
        }catch(Exception $e) {
            echo $response = $e->getMessage();
            $response = json_decode($response,true);
            // GetFrontRedirectUrl(GetUrl(array('module'=>'dashboard')));
        }
        
	}

    function getUserLocation($ip = null) {
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
                return [
                    'country' => $data['country'],
                    'region' => $data['regionName'],
                    'city' => $data['city']
                ];
            }
        }

        return [
            'country' => 'Unknown',
            'region' => 'Unknown',
            'city' => 'Unknown'
        ];
    }
	
    function getUserIp($ip = null) {
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

        return $ip;
    }
}

?>