<?php
class CIT_ANALYTICS
{

	public function __construct()
	{
		if(!isset($_SESSION[GetSession('user_id')]) && !isset($_REQUEST['uuid'])){
			GetFrontRedirectUrl(GetUrl(array('module'=>'signin')));
		}

		if($GLOBALS['plan_cancel'] == 1){
			GetFrontRedirectUrl($GLOBALS['renewaccount']);
		}

		// Handle AJAX requests
		if(isset($_REQUEST['category_id'])) {
			switch($_REQUEST['category_id']) {
				case 'getAnalyticsByDateRange':
					$this->getAnalyticsByDateRange();
					break;
				case 'getChartData':
					$this->getChartData();
					break;
				case 'getMapData':
					$this->getMapData();
					break;
				case 'getTypePopularChartData':
					$this->getTypePopularChartData();
					break;
				case 'getDashboardChartData':
					$this->getDashboardChartData();
					break;
				case 'getDashboadSignatureChart':
					$this->getDashboadSignatureChart();
					break;
			}
		}
	}

	public function displayPage(){

		$GLOBALS['CLA_HTML']->addMain($GLOBALS['WWW_TPL'].'/analytics.html');
		$GLOBALS['HEADER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.header.html');
		$GLOBALS['FOOTER'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.footer.html');
		$GLOBALS['SIDEBAR'] = $GLOBALS['CLA_HTML']->addSub($GLOBALS['WWW_TPL'].'/page.sidebar.html');
		$GLOBALS['CLA_HTML']->display();
		RemoveMessageInfo();
		exit();

	}


	public function getAnalyticsByDateRange() {
		// Validate and sanitize input
		$start_date = filter_input(INPUT_POST, 'start_date', FILTER_SANITIZE_STRING);
		$end_date = filter_input(INPUT_POST, 'end_date', FILTER_SANITIZE_STRING);

		if (!$start_date || !$end_date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
			header('Content-Type: application/json');
			http_response_code(400);
			echo json_encode(['error' => 'Invalid date format']);
			exit();
		}

		try {
			// Query analytics data within date range
			$analyticsData = $GLOBALS['DB']->query(
				"SELECT analytic_type, SUM(clicks) as total_clicks, SUM(impressions) as total_impressions, 
				COUNT(DISTINCT CASE WHEN clicks > 0 THEN user_ip END) AS unique_clicks,
				SUM(mobile_clicks) as mobile_clicks, SUM(desktop_clicks) as desktop_clicks, 
				SUM(tablet_clicks) as tablet_clicks, SUM(windows_clicks) as windows_clicks, 
				SUM(macos_clicks) as macos_clicks, SUM(linux_clicks) as linux_clicks, 
				SUM(ios_clicks) as ios_clicks, SUM(android_clicks) as android_clicks
				FROM registerusers_analytics 
				WHERE user_id = ? AND date BETWEEN ? AND ? 
				GROUP BY analytic_type",
				array($GLOBALS['USERID'], $start_date, $end_date)
			);
			// Initialize response data
			$response = array(
				'total_clicks' => 0,
				'total_impressions' => 0,
				'total_unique_clicks' => 0,
				'total_ctr' => 0,
				'logo_clicks' => 0,
				'logo_impressions' => 0,
				'banner_clicks' => 0,
				'banner_impressions' => 0,
				'profile_clicks' => 0,
				'profile_impressions' => 0,
				'social_clicks' => 0,
				'social_impressions' => 0,
				'desktop_clicks_percentage' => 0,
				'mobile_clicks_percentage' => 0,
				'tablet_clicks_percentage' => 0,
				'windows_clicks_percentage' => 0,
				'macos_clicks_percentage' => 0,
				'linux_clicks_percentage' => 0,
				'ios_clicks_percentage' => 0,
				'android_clicks_percentage' => 0
			);
			
			foreach ($analyticsData as $data) {
				$response['total_clicks'] += $data['total_clicks'];
				$response['total_impressions'] += $data['total_impressions'];
				$response['total_unique_clicks'] += $data['unique_clicks'];
			}
			$response['total_ctr'] = $response['total_clicks'] > 0 ? round((($response['total_unique_clicks'] * 100)/$response['total_clicks']),2)."%" : "0%";

			$otherClicks = ['email','phone','custombtnlink','ctabtnlink1','ctabtnlink2','ctabtnlink3','playstorebtn','appstorebtn'];
			$socialPlatforms = ['web','insta','linkedin','facebook','tiktok','youtube','twitter','vimeo','pintrest','google','yelp','zillow','airbnb','whatsapp','discord','imbd','ebay','spotify','amazon','clendly','wechat','apple','snapchat','reddit','shopify','threads','venmo','zelle'];
			// Process query results
			foreach ($analyticsData as $data) {
				if($data['analytic_type'] == 'logo') {
					$response['logo_impressions'] += $data['total_impressions'];
				}
				else if($data['analytic_type'] == 'banner') {
					$response['banner_impressions'] = $data['total_impressions'];
				}
				else if($data['analytic_type'] == 'profile') {
					$response['profile_impressions'] = $data['total_impressions'];
				}
				else if($data['analytic_type'] == 'logoclick') {
					$response['logo_clicks'] += $data['total_clicks'];
					$totalClicks = $response['total_clicks'] > 0 ? $response['total_clicks'] : 1;

					$response['total_logo_clicks'] = $response['logo_clicks'];
					$response['logo_unique_clicks'] += $data['unique_clicks'];
					$response['total_logo_unique_clicks'] = $response['logo_unique_clicks'];
					if($response['total_logo_clicks'] > 0){
						$response['total_logo_ctr'] = round(($response['total_logo_unique_clicks']*100)/$response['total_logo_clicks'],2)."%";
					}else{
						$response['total_logo_ctr'] = '0%';
					}
					// Device Type Percentages
					$response['desktop_clicks_percentage'] = ($totalClicks > 0) ? round(($response['desktop_clicks_percentage'] + (($data['desktop_clicks'] / $totalClicks) * 100)), 2) : 0;
					$response['mobile_clicks_percentage'] = ($totalClicks > 0) ? round(($response['mobile_clicks_percentage'] + (($data['mobile_clicks'] / $totalClicks) * 100)), 2) : 0;
					$response['tablet_clicks_percentage'] = ($totalClicks > 0) ? round(($response['tablet_clicks_percentage'] + (($data['tablet_clicks'] / $totalClicks) * 100)), 2) : 0;
					// Platform Percentages
					$response['windows_clicks_percentage'] = ($totalClicks > 0) ? round(($response['windows_clicks_percentage'] + (($data['windows_clicks'] / $totalClicks) * 100)), 2) : 0;
					$response['macos_clicks_percentage'] = ($totalClicks > 0) ? round(($response['macos_clicks_percentage'] + (($data['macos_clicks'] / $totalClicks) * 100)), 2) : 0;
					$response['linux_clicks_percentage'] = ($totalClicks > 0) ? round(($response['linux_clicks_percentage'] + (($data['linux_clicks'] / $totalClicks) * 100)), 2) : 0;
					$response['ios_clicks_percentage'] = ($totalClicks > 0) ? round(($response['ios_clicks_percentage'] + (($data['ios_clicks'] / $totalClicks) * 100)), 2) : 0;
					$response['android_clicks_percentage'] = ($totalClicks > 0) ? round(($response['android_clicks_percentage'] + (($data['android_clicks'] / $totalClicks) * 100)), 2) : 0;
				}
				else if($data['analytic_type'] == 'bannerclick') {
					$response['banner_clicks'] += $data['total_clicks'];
					$response['total_banner_clicks'] = $response['banner_clicks'];
					$response['banner_unique_clicks'] += $data['unique_clicks'];
					$response['total_banner_unique_clicks'] = $response['banner_unique_clicks'];
					if($response['total_banner_clicks'] > 0){
						$response['total_banner_ctr'] = round(($response['total_banner_unique_clicks']*100)/$response['total_banner_clicks'],2)."%";
					}else{
						$response['total_banner_ctr'] = '0%';
					}

					$totalClicks = $response['total_clicks'] > 0 ? $response['total_clicks'] : 1;
					// Device Type Percentages
					$response['desktop_clicks_percentage'] = ($totalClicks > 0) ? round(($response['desktop_clicks_percentage'] + (($data['desktop_clicks'] / $totalClicks) * 100)), 2) : 0;
					$response['mobile_clicks_percentage'] = ($totalClicks > 0) ? round(($response['mobile_clicks_percentage'] + (($data['mobile_clicks'] / $totalClicks) * 100)), 2) : 0;
					$response['tablet_clicks_percentage'] = ($totalClicks > 0) ? round(($response['tablet_clicks_percentage'] + (($data['tablet_clicks'] / $totalClicks) * 100)), 2) : 0;
					// Platform Percentages
					$response['windows_clicks_percentage'] = ($totalClicks > 0) ? round(($response['windows_clicks_percentage'] + (($data['windows_clicks'] / $totalClicks) * 100)), 2) : 0;
					$response['macos_clicks_percentage'] = ($totalClicks > 0) ? round(($response['macos_clicks_percentage'] + (($data['macos_clicks'] / $totalClicks) * 100)), 2) : 0;
					$response['linux_clicks_percentage'] = ($totalClicks > 0) ? round(($response['linux_clicks_percentage'] + (($data['linux_clicks'] / $totalClicks) * 100)), 2) : 0;
					$response['ios_clicks_percentage'] = ($totalClicks > 0) ? round(($response['ios_clicks_percentage'] + (($data['ios_clicks'] / $totalClicks) * 100)), 2) : 0;
					$response['android_clicks_percentage'] = ($totalClicks > 0) ? round(($response['android_clicks_percentage'] + (($data['android_clicks'] / $totalClicks) * 100)), 2) : 0;
				}
				else if(in_array($data['analytic_type'], $otherClicks)){
					$response['total_'.$data['analytic_type'].'_clicks'] = $data['total_clicks'];
					$response['total_'.$data['analytic_type'].'_unique_clicks'] = $data['unique_clicks'];
					if($data['total_clicks'] > 0){
						$response['total_'.$data['analytic_type'].'_ctr'] = round(($data['unique_clicks']*100)/$data['total_clicks'],2)."%";
					}else{
						$response['total_'.$data['analytic_type'].'_ctr'] = '0%';
					}
					$totalClicks = $response['total_clicks'] > 0 ? $response['total_clicks'] : 1;
					// Device Type Percentages
					$response['desktop_clicks_percentage'] = ($totalClicks > 0) ? round(($response['desktop_clicks_percentage'] + (($data['desktop_clicks'] / $totalClicks) * 100)), 2) : 0;
					$response['mobile_clicks_percentage'] = ($totalClicks > 0) ? round(($response['mobile_clicks_percentage'] + (($data['mobile_clicks'] / $totalClicks) * 100)), 2) : 0;
					$response['tablet_clicks_percentage'] = ($totalClicks > 0) ? round(($response['tablet_clicks_percentage'] + (($data['tablet_clicks'] / $totalClicks) * 100)), 2) : 0;
					// Platform Percentages
					$response['windows_clicks_percentage'] = ($totalClicks > 0) ? round(($response['windows_clicks_percentage'] + (($data['windows_clicks'] / $totalClicks) * 100)), 2) : 0;
					$response['macos_clicks_percentage'] = ($totalClicks > 0) ? round(($response['macos_clicks_percentage'] + (($data['macos_clicks'] / $totalClicks) * 100)), 2) : 0;
					$response['linux_clicks_percentage'] = ($totalClicks > 0) ? round(($response['linux_clicks_percentage'] + (($data['linux_clicks'] / $totalClicks) * 100)), 2) : 0;
					$response['ios_clicks_percentage'] = ($totalClicks > 0) ? round(($response['ios_clicks_percentage'] + (($data['ios_clicks'] / $totalClicks) * 100)), 2) : 0;
					$response['android_clicks_percentage'] = ($totalClicks > 0) ? round(($response['android_clicks_percentage'] + (($data['android_clicks'] / $totalClicks) * 100)), 2) : 0;
				}
				else if(in_array($data['analytic_type'], $socialPlatforms)){
					$response['total_social_clicks']  += $data['total_clicks'];
					$response['total_social_unique_clicks']  += $data['unique_clicks'];
					if($response['total_social_clicks'] > 0){
						$response['total_social_ctr'] = round(($response['total_social_unique_clicks']*100)/$response['total_social_clicks'],2)."%";
					}else{
						$response['total_social_ctr'] = '0%';
					}
					$response['total_'.$data['analytic_type'].'_clicks'] = $data['total_clicks'];
					$response['total_'.$data['analytic_type'].'_unique_clicks'] = $data['unique_clicks'];
					if($data['total_clicks'] > 0){
						$response['total_'.$data['analytic_type'].'_ctr'] = round(($data['unique_clicks']*100)/$data['total_clicks'],2)."%";
					}else{
						$response['total_'.$data['analytic_type'].'_ctr'] = '0%';
					}
					$totalClicks = $response['total_clicks'] > 0 ? $response['total_clicks'] : 1;
					// Device Type Percentages
					$response['desktop_clicks_percentage'] = ($totalClicks > 0) ? round(($response['desktop_clicks_percentage'] + (($data['desktop_clicks'] / $totalClicks) * 100)), 2) : 0;
					$response['mobile_clicks_percentage'] = ($totalClicks > 0) ? round(($response['mobile_clicks_percentage'] + (($data['mobile_clicks'] / $totalClicks) * 100)), 2) : 0;
					$response['tablet_clicks_percentage'] = ($totalClicks > 0) ? round(($response['tablet_clicks_percentage'] + (($data['tablet_clicks'] / $totalClicks) * 100)), 2) : 0;
					// Platform Percentages
					$response['windows_clicks_percentage'] = ($totalClicks > 0) ? round(($response['windows_clicks_percentage'] + (($data['windows_clicks'] / $totalClicks) * 100)), 2) : 0;
					$response['macos_clicks_percentage'] = ($totalClicks > 0) ? round(($response['macos_clicks_percentage'] + (($data['macos_clicks'] / $totalClicks) * 100)), 2) : 0;
					$response['linux_clicks_percentage'] = ($totalClicks > 0) ? round(($response['linux_clicks_percentage'] + (($data['linux_clicks'] / $totalClicks) * 100)), 2) : 0;
					$response['ios_clicks_percentage'] = ($totalClicks > 0) ? round(($response['ios_clicks_percentage'] + (($data['ios_clicks'] / $totalClicks) * 100)), 2) : 0;
					$response['android_clicks_percentage'] = ($totalClicks > 0) ? round(($response['android_clicks_percentage'] + (($data['android_clicks'] / $totalClicks) * 100)), 2) : 0;
				}
				else{
					$totalClicks = $response['total_clicks'] > 0 ? $response['total_clicks'] : 1;
					// Device Type Percentages
					$response['desktop_clicks_percentage'] = ($totalClicks > 0) ? round(($response['desktop_clicks_percentage'] + (($data['desktop_clicks'] / $totalClicks) * 100)), 2) : 0;
					$response['mobile_clicks_percentage'] = ($totalClicks > 0) ? round(($response['mobile_clicks_percentage'] + (($data['mobile_clicks'] / $totalClicks) * 100)), 2) : 0;
					$response['tablet_clicks_percentage'] = ($totalClicks > 0) ? round(($response['tablet_clicks_percentage'] + (($data['tablet_clicks'] / $totalClicks) * 100)), 2) : 0;
					// Platform Percentages
					$response['windows_clicks_percentage'] = ($totalClicks > 0) ? round(($response['windows_clicks_percentage'] + (($data['windows_clicks'] / $totalClicks) * 100)), 2) : 0;
					$response['macos_clicks_percentage'] = ($totalClicks > 0) ? round(($response['macos_clicks_percentage'] + (($data['macos_clicks'] / $totalClicks) * 100)), 2) : 0;
					$response['linux_clicks_percentage'] = ($totalClicks > 0) ? round(($response['linux_clicks_percentage'] + (($data['linux_clicks'] / $totalClicks) * 100)), 2) : 0;
					$response['ios_clicks_percentage'] = ($totalClicks > 0) ? round(($response['ios_clicks_percentage'] + (($data['ios_clicks'] / $totalClicks) * 100)), 2) : 0;
					$response['android_clicks_percentage'] = ($totalClicks > 0) ? round(($response['android_clicks_percentage'] + (($data['android_clicks'] / $totalClicks) * 100)), 2) : 0;
				}
			}


			// For Type and popular day section
			$weeklyAnalyticsData = $GLOBALS['DB']->query(
				"SELECT 
					DAYOFWEEK(date) as day_of_week, 
					SUM(clicks) as day_clicks,
					SUM(impressions) as day_impressions
				FROM registerusers_analytics 
				WHERE user_id = ? AND date BETWEEN ? AND ?
				GROUP BY DAYOFWEEK(date)",
				array($GLOBALS['USERID'], $start_date, $end_date)
			);

			$dayMap = [
				1 => 'sun',
				2 => 'mon',
				3 => 'tue',
				4 => 'wed',
				5 => 'thu',
				6 => 'fri',
				7 => 'sat',
			];
			$response = array_merge($response, array( 'type_and_popular_data' => array(
    			'sun_clicks' => 0,
				'sun_impressions' => 0,
				'mon_clicks' => 0,
				'mon_impressions' => 0,
				'tue_clicks' => 0,
				'tue_impressions' => 0,
				'wed_clicks' => 0,
				'wed_impressions' => 0,
				'thu_clicks' => 0,
				'thu_impressions' => 0,
				'fri_clicks' => 0,
				'fri_impressions' => 0,
				'sat_clicks' => 0,
				'sat_impressions' => 0
			)));

			$totalWeekClicks = 0;
			$totalWeekImpressions = 0;

			// Calculate total clicks and impressions
			foreach ($weeklyAnalyticsData as $row) {
				$totalWeekClicks += $row['day_clicks'];
				$totalWeekImpressions += $row['day_impressions'];
			}

			// Initialize total values
			$response['total_week_clicks'] = $totalWeekClicks;
			$response['total_week_clicks_percentage'] = $totalWeekClicks > 0 ? 100 : 0;

			$response['total_week_impressions'] = $totalWeekImpressions;
			$response['total_week_impressions_percentage'] = $totalWeekImpressions > 0 ? 100 : 0;

			// Assign daily percentages for clicks and impressions
			foreach ($weeklyAnalyticsData as $row) {
				$day = $dayMap[$row['day_of_week']] ?? null;
				if ($day) {
					$clickKey = "{$day}_clicks";
					$impressionKey = "{$day}_impressions";

					$response['type_and_popular_data'][$clickKey] = $totalWeekClicks > 0 
						? $row['day_clicks']
						: 0;

					$response['type_and_popular_data'][$impressionKey] = $totalWeekImpressions > 0 
						? $row['day_impressions']
						: 0;
				}
			}
			// 	For Type and popular day section

			// Send JSON response
			header('Content-Type: application/json');
			echo json_encode($response);
			exit();

		} catch (Exception $e) {
			header('Content-Type: application/json');
			http_response_code(500);
			echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
			exit();
		}
	}

	public function getChartData() {
		header('Content-Type: application/json');

		$range = $_GET['range'] ?? 'days';
		$start_date = $_GET['start'] ?? date('Y-m-d');
		$end_date = $_GET['end'] ?? date('Y-m-d');

		if ($range === 'months') {
			$analyticsData = $GLOBALS['DB']->query(
				"SELECT 
					MONTH(date) AS month, 
					SUM(clicks) AS total_clicks, 
					SUM(impressions) AS total_impressions
				FROM registerusers_analytics 
				WHERE user_id = ? AND date BETWEEN ? AND ? 
				GROUP BY MONTH(date) 
				ORDER BY MONTH(date) ASC",
				array($GLOBALS['USERID'], $start_date, $end_date)
			);

			$monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

			// Initialize data with zeros for all months
			$data = [
				'labels' => $monthNames,
				'clicks' => array_fill(0, 12, 0),
				'impressions' => array_fill(0, 12, 0)
			];

			// Fill clicks/impressions for months in the range
			foreach ($analyticsData as $entry) {
				$monthIndex = $entry['month'] - 1;
				$data['clicks'][$monthIndex] = $entry['total_clicks'];
				$data['impressions'][$monthIndex] = $entry['total_impressions'];
			}

			// Now slice the data arrays to only include months between start and end dates
			$startMonth = (int)date('n', strtotime($start_date)) - 1;  // 0-based index
			$endMonth = (int)date('n', strtotime($end_date)) - 1;

			foreach (['labels', 'clicks', 'impressions'] as $key) {
				$data[$key] = array_slice($data[$key], $startMonth, $endMonth - $startMonth + 1);
				$data[$key] = array_values($data[$key]); // reindex
			}

			echo json_encode($data);

		} else {
			// Get data grouped by day
			$analyticsData = $GLOBALS['DB']->query(
				"SELECT 
					DATE(date) AS day, 
					SUM(clicks) AS total_clicks, 
					SUM(impressions) AS total_impressions
				FROM registerusers_analytics 
				WHERE user_id = ? AND date BETWEEN ? AND ? 
				GROUP BY DATE(date) 
				ORDER BY DATE(date) ASC",
				array($GLOBALS['USERID'], $start_date, $end_date)
			);

			// Generate all days between start_date and end_date inclusive
			$period = new DatePeriod(
				new DateTime($start_date),
				new DateInterval('P1D'),
				(new DateTime($end_date))->modify('+1 day')
			);

			$allDays = [];
			$allDaysWhole = [];
			foreach ($period as $key => $date) {
				$start = strtotime($start_date);
				$end = strtotime($end_date);

				$diff_in_days = round(($end - $start) / (60 * 60 * 24));
				$diff_in_months = (date('Y', $end) - date('Y', $start)) * 12 + (date('m', $end) - date('m', $start));
				$diff_in_years = date('Y', $end) - date('Y', $start);

				if ($diff_in_days == 0 || $diff_in_months > 1) {
					if ($diff_in_years >= 1) {
						// More than a year, show day, short month, and 2-digit year
						$allDays[] = $date->format('d M y'); // e.g. "21 Jun 25"
					} else {
						// Less than a year, show day and short month
						$allDays[] = $date->format('d M');   // e.g. "21 Jun"
					}
				} else {
					// Just show the day
					// $allDays[] = $date->format('d');        // e.g. "21"
					$allDays[] = $date->format('d M');
				}

				$allDaysWhole[$date->format('Y-m-d')] = $key;
			}
			// Initialize data arrays with zeros
			$finalData = [
				'labels' => $allDays,
				'clicks' => array_fill(0, count($allDays), 0),
				'impressions' => array_fill(0, count($allDays), 0)
			];

			// Fill clicks/impressions into finalData arrays
			foreach ($analyticsData as $entry) {
				$day = $entry['day'];
				if (isset($allDaysWhole[$day])) {
					$index = $allDaysWhole[$day];
					$finalData['clicks'][$index] = $entry['total_clicks'];
					$finalData['impressions'][$index] = $entry['total_impressions'];
				}
			}

			echo json_encode($finalData);
		}

		exit;
	}


	public function getTypePopularChartData() {
		header('Content-Type: application/json');

		$start_date = $_GET['start'] ?? date('Y-m-d');
		$end_date = $_GET['end'] ?? date('Y-m-d');

		$thisDurationAnalyticsData = $GLOBALS['DB']->query(
			"SELECT 
				DAYOFWEEK(date) as day_of_week, 
				SUM(clicks) as day_clicks,
				COUNT(DISTINCT CASE WHEN clicks > 0 THEN user_ip END) AS unique_clicks
			FROM registerusers_analytics 
			WHERE user_id = ? AND date BETWEEN ? AND ?
			GROUP BY DAYOFWEEK(date)",
			array($GLOBALS['USERID'], $start_date, $end_date)
		);

		$weeklyAnalyticsDataUniqueClicks = $GLOBALS['DB']->query(
			"SELECT 
				DAYOFWEEK(date) as day_of_week, 
				SUM(clicks) as day_clicks,
				COUNT(DISTINCT CASE WHEN clicks > 0 THEN user_ip END) AS unique_clicks
			FROM registerusers_analytics 
			WHERE user_id = ? AND date BETWEEN ? AND ?
			GROUP BY DAYOFWEEK(date),analytic_type",
			array($GLOBALS['USERID'], $start_date, $end_date)
		);
		

		$response = [
			'labels' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
			'allClicks' => ['0'=>'0','1'=>'0','2'=>'0','3'=>'0','4'=>'0','5'=>'0','6'=>'0'],
			'uniqueClicks' => ['0'=>'0','1'=>'0','2'=>'0','3'=>'0','4'=>'0','5'=>'0','6'=>'0'],
			'clickThroughRate' => '0',
			'rateChange' => '0',
			'totalClicks' => '0',
			'totalUniqueClicks' => '0',
		];
		$totalClicks = 0;
		$totalUniqueClicks = 0;

		// Assign daily percentages for clicks and impressions
		foreach ($thisDurationAnalyticsData as $row) {
			$totalClicks += $row['day_clicks'];
			// $totalUniqueClicks += $row['unique_clicks'];
			$response['allClicks'][$row['day_of_week']-1] = $row['day_clicks'] > 0 ? $row['day_clicks'] : 0;
			// $response['uniqueClicks'][$row['day_of_week']-1] = $row['unique_clicks'] > 0 ? $row['unique_clicks'] : 0;
		}
		foreach ($weeklyAnalyticsDataUniqueClicks as $row) {
			$totalUniqueClicks += $row['unique_clicks'];
			$curruntUniqueClicks = $row['unique_clicks'] > 0 ? $row['unique_clicks'] : 0;
			if(isset($response['uniqueClicks'][$row['day_of_week']-1])){
				$curruntUniqueClicks = $curruntUniqueClicks + $response['uniqueClicks'][$row['day_of_week']-1];
			}
			$response['uniqueClicks'][$row['day_of_week']-1] = $curruntUniqueClicks;
		}
		$response['totalClicks'] = $totalClicks;
		$response['totalUniqueClicks'] = $totalUniqueClicks;
		if (!empty($totalClicks) && $totalClicks > 0) {
		    $clickThroughRate = round(($totalUniqueClicks * 100) / $totalClicks, 2);
		} else {
		    $clickThroughRate = 0;  // or null or any default value you want
		}
		$response['clickThroughRate'] = $clickThroughRate;

		$start = new DateTime($start_date);
		$end = new DateTime($end_date);

		// Calculate difference in days (exclusive)
		$interval = $start->diff($end);
		$days_gap = $interval->days; // e.g. 30 days

		// Previous period ends 1 day before current start date
		$previous_end = (clone $start)->modify('-1 day');

		// Previous period starts $days_gap days before previous_end to get same inclusive length
		$previous_start = (clone $previous_end)->modify('-' . $days_gap . ' days');

		// Format the dates to Y-m-d or any format you like
		$start_date_last_duration = $previous_start->format('Y-m-d');
		$end_date_last_duration = $previous_end->format('Y-m-d');
		
		$lastDurationAnalyticsData = $GLOBALS['DB']->query(
			"SELECT 
				DAYOFWEEK(date) as day_of_week, 
				SUM(clicks) as day_clicks,
				COUNT(DISTINCT CASE WHEN clicks > 0 THEN user_ip END) AS unique_clicks
			FROM registerusers_analytics 
			WHERE user_id = ? AND date BETWEEN ? AND ?
			GROUP BY DAYOFWEEK(date)",
			array($GLOBALS['USERID'], $start_date_last_duration, $end_date_last_duration)
		);
		$lastDurationTotalClicks = 0;
		$lastDurationTotalUniqueClicks = 0;
		foreach ($lastDurationAnalyticsData as $row) {
			$lastDurationTotalClicks += $row['day_clicks'];
			$lastDurationTotalUniqueClicks += $row['unique_clicks'];
		}
		// if (!empty($lastDurationTotalClicks) && $lastDurationTotalClicks > 0) {
		//     $lastDurationClickThroughRate = round(($lastDurationTotalUniqueClicks * 100) / $lastDurationTotalClicks, 2);
		// } else {
		//     $lastDurationClickThroughRate = 0;  // or null or any default value you want
		// }
		// $response['rateChange'] = round($clickThroughRate - $lastDurationClickThroughRate, 2);

		if (!empty($lastDurationTotalClicks) && $lastDurationTotalClicks > 0) {
			$lastDurationClickThroughRate = $lastDurationTotalClicks > 0 ? round((($totalClicks - $lastDurationTotalClicks) / $lastDurationTotalClicks)*100, 2) : 0;
		}
		else {
		    $lastDurationClickThroughRate = 0;  // or null or any default value you want
		}
		$response['rateChange'] = round($lastDurationClickThroughRate, 2);
		$response['clickThroughRate'] = $clickThroughRate;
		echo json_encode($response);exit;
	}

	public function getMapData(){
		header('Content-Type: application/json');

		$start_date = $_GET['start'] ?? date('Y-m-d');
		$end_date = $_GET['end'] ?? date('Y-m-d');
		$analyticsData = $GLOBALS['DB']->query(
			"SELECT 
				clicks AS total_clicks, 
				location,
				id
			FROM registerusers_analytics 
			WHERE user_id = ? AND date BETWEEN ? AND ? ",
			array($GLOBALS['USERID'],$start_date,$end_date)
		);
		$finalData = [];
		foreach ($analyticsData as $key => $data) {
			$location = json_decode($data['location'], true);
			if(is_array($location)){
				$country = $location['country'] ?? "";
				$countryCode = $this->getCountryNumericCode($country);
				if (empty($countryCode)) {
					$countryCode = 840;
				}
				$finalData[$countryCode] = $finalData[$countryCode] + $data['total_clicks'];
			}
		}
		echo json_encode($finalData);
		// echo json_encode([
		// 	"840" => 12,   // USA
		// 	"250" => 850,  // France
		// 	"356" => 156,    // India (zero clicks = grey)
		// 	"36"  => 45,   // Australia
		// 	"156" => 640,  // China
		// 	"566" => 752,    // Nigeria (zero clicks)
		// 	"392" => 390   // Japan
		// ]);
		exit;
	}

	public function getCountryNumericCode(string $countryName){
		$countryMap = [
			"Afghanistan" => "004",
			"Albania" => "008",
			"Algeria" => "012",
			"Andorra" => "020",
			"Angola" => "024",
			"Antigua and Barbuda" => "028",
			"Argentina" => "032",
			"Armenia" => "051",
			"Australia" => "036",
			"Austria" => "040",
			"Azerbaijan" => "031",
			"Bahamas" => "044",
			"Bahrain" => "048",
			"Bangladesh" => "050",
			"Barbados" => "052",
			"Belarus" => "112",
			"Belgium" => "056",
			"Belize" => "084",
			"Benin" => "204",
			"Bhutan" => "064",
			"Bolivia" => "068",
			"Bosnia and Herzegovina" => "070",
			"Botswana" => "072",
			"Brazil" => "076",
			"Brunei" => "096",
			"Bulgaria" => "100",
			"Burkina Faso" => "854",
			"Burundi" => "108",
			"Cabo Verde" => "132",
			"Cambodia" => "116",
			"Cameroon" => "120",
			"Canada" => "124",
			"Central African Republic" => "140",
			"Chad" => "148",
			"Chile" => "152",
			"China" => "156",
			"Colombia" => "170",
			"Comoros" => "174",
			"Congo (Brazzaville)" => "178",
			"Congo (Kinshasa)" => "180",
			"Costa Rica" => "188",
			"Croatia" => "191",
			"Cuba" => "192",
			"Cyprus" => "196",
			"Czechia" => "203",
			"Denmark" => "208",
			"Djibouti" => "262",
			"Dominica" => "212",
			"Dominican Republic" => "214",
			"Ecuador" => "218",
			"Egypt" => "818",
			"El Salvador" => "222",
			"Equatorial Guinea" => "226",
			"Eritrea" => "232",
			"Estonia" => "233",
			"Eswatini" => "748",
			"Ethiopia" => "231",
			"Fiji" => "242",
			"Finland" => "246",
			"France" => "250",
			"Gabon" => "266",
			"Gambia" => "270",
			"Georgia" => "268",
			"Germany" => "276",
			"Ghana" => "288",
			"Greece" => "300",
			"Grenada" => "308",
			"Guatemala" => "320",
			"Guinea" => "324",
			"Guinea-Bissau" => "624",
			"Guyana" => "328",
			"Haiti" => "332",
			"Honduras" => "340",
			"Hungary" => "348",
			"Iceland" => "352",
			"India" => "356",
			"Indonesia" => "360",
			"Iran" => "364",
			"Iraq" => "368",
			"Ireland" => "372",
			"Israel" => "376",
			"Italy" => "380",
			"Jamaica" => "388",
			"Japan" => "392",
			"Jordan" => "400",
			"Kazakhstan" => "398",
			"Kenya" => "404",
			"Kiribati" => "296",
			"Kuwait" => "414",
			"Kyrgyzstan" => "417",
			"Laos" => "418",
			"Latvia" => "428",
			"Lebanon" => "422",
			"Lesotho" => "426",
			"Liberia" => "430",
			"Libya" => "434",
			"Liechtenstein" => "438",
			"Lithuania" => "440",
			"Luxembourg" => "442",
			"Madagascar" => "450",
			"Malawi" => "454",
			"Malaysia" => "458",
			"Maldives" => "462",
			"Mali" => "466",
			"Malta" => "470",
			"Marshall Islands" => "584",
			"Mauritania" => "478",
			"Mauritius" => "480",
			"Mexico" => "484",
			"Micronesia" => "583",
			"Moldova" => "498",
			"Monaco" => "492",
			"Mongolia" => "496",
			"Montenegro" => "499",
			"Morocco" => "504",
			"Mozambique" => "508",
			"Myanmar" => "104",
			"Namibia" => "516",
			"Nauru" => "520",
			"Nepal" => "524",
			"Netherlands" => "528",
			"New Zealand" => "554",
			"Nicaragua" => "558",
			"Niger" => "562",
			"Nigeria" => "566",
			"North Korea" => "408",
			"North Macedonia" => "807",
			"Norway" => "578",
			"Oman" => "512",
			"Pakistan" => "586",
			"Palau" => "585",
			"Panama" => "591",
			"Papua New Guinea" => "598",
			"Paraguay" => "600",
			"Peru" => "604",
			"Philippines" => "608",
			"Poland" => "616",
			"Portugal" => "620",
			"Qatar" => "634",
			"Romania" => "642",
			"Russia" => "643",
			"Rwanda" => "646",
			"Saint Kitts and Nevis" => "659",
			"Saint Lucia" => "662",
			"Saint Vincent and the Grenadines" => "670",
			"Samoa" => "882",
			"San Marino" => "674",
			"Sao Tome and Principe" => "678",
			"Saudi Arabia" => "682",
			"Senegal" => "686",
			"Serbia" => "688",
			"Seychelles" => "690",
			"Sierra Leone" => "694",
			"Singapore" => "702",
			"Slovakia" => "703",
			"Slovenia" => "705",
			"Solomon Islands" => "090",
			"Somalia" => "706",
			"South Africa" => "710",
			"South Korea" => "410",
			"South Sudan" => "728",
			"Spain" => "724",
			"Sri Lanka" => "144",
			"Sudan" => "729",
			"Suriname" => "740",
			"Sweden" => "752",
			"Switzerland" => "756",
			"Syria" => "760",
			"Tajikistan" => "762",
			"Tanzania" => "834",
			"Thailand" => "764",
			"Timor-Leste" => "626",
			"Togo" => "768",
			"Tonga" => "776",
			"Trinidad and Tobago" => "780",
			"Tunisia" => "788",
			"Turkey" => "792",
			"Turkmenistan" => "795",
			"Tuvalu" => "798",
			"Uganda" => "800",
			"Ukraine" => "804",
			"United Arab Emirates" => "784",
			"United Kingdom" => "826",
			"United States" => "840",
			"Uruguay" => "858",
			"Uzbekistan" => "860",
			"Vanuatu" => "548",
			"Vatican City" => "336",
			"Venezuela" => "862",
			"Vietnam" => "704",
			"Yemen" => "887",
			"Zambia" => "894",
			"Zimbabwe" => "716"
		];

		return $countryMap[$countryName] ?? null;
	}

	public function getDashboardChartData(){
		header('Content-Type: application/json');

		$range = $_GET['range'] ?? 'days';
		$start_date = $_GET['start'] ?? date('Y-m-d');
		$end_date = $_GET['end'] ?? date('Y-m-d');

		if ($range === 'months') {
			// Query aggregated by month
			$analyticsData = $GLOBALS['DB']->query(
				"SELECT 
					MONTH(date) AS month, 
					SUM(clicks) AS total_clicks, 
					SUM(impressions) AS total_impressions,
					COUNT(DISTINCT CASE WHEN clicks > 0 THEN user_ip END) AS unique_clicks
				FROM registerusers_analytics 
				WHERE user_id = ? AND date BETWEEN ? AND ? 
				GROUP BY MONTH(date)
				ORDER BY MONTH(date) ASC",
				array($GLOBALS['USERID'], $start_date, $end_date)
			);

			$monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

			// Initialize with zeros for all months
			$data = [
				'labels' => $monthNames,
				'clicks' => array_fill(0, 12, 0),
				'impressions' => array_fill(0, 12, 0),
				'unique_clicks' => array_fill(0, 12, 0),
			];

			$totalClicks = 0;
			$totalImpressions = 0;
			$totalUniqueClicks = 0;

			foreach ($analyticsData as $entry) {
				$idx = $entry['month'] - 1;
				$data['clicks'][$idx] = $entry['total_clicks'];
				$data['impressions'][$idx] = $entry['total_impressions'];
				$data['unique_clicks'][$idx] = $entry['unique_clicks'];

				$totalClicks += $entry['total_clicks'];
				$totalImpressions += $entry['total_impressions'];
				$totalUniqueClicks += $entry['unique_clicks'];
			}

			$data['total_clicks'] = $totalClicks;
			$data['total_impressions'] = $totalImpressions;
			$data['total_unique_clicks'] = $totalUniqueClicks;
			$data['clickThroughRate'] = $totalClicks > 0 ? round(($totalUniqueClicks * 100) / $totalClicks, 2) : 0;

			$start = new DateTime($start_date);
			$end = new DateTime($end_date);

			// Calculate difference in days (exclusive)
			$interval = $start->diff($end);
			$days_gap = $interval->days; // e.g. 30 days

			// Previous period ends 1 day before current start date
			$previous_end = (clone $start)->modify('-1 day');

			// Previous period starts $days_gap days before previous_end to get same inclusive length
			$previous_start = (clone $previous_end)->modify('-' . $days_gap . ' days');

			// Format the dates to Y-m-d or any format you like
			$start_date_previous_duration = $previous_start->format('Y-m-d');
			$end_date_previous_duration = $previous_end->format('Y-m-d');

			$analyticsDataPreviousDuration = $GLOBALS['DB']->query(
				"SELECT 
					MONTH(date) AS month, 
					SUM(clicks) AS total_clicks, 
					SUM(impressions) AS total_impressions,
					COUNT(DISTINCT CASE WHEN clicks > 0 THEN user_ip END) AS unique_clicks
				FROM registerusers_analytics 
				WHERE user_id = ? AND date BETWEEN ? AND ? 
				GROUP BY MONTH(date)
				ORDER BY MONTH(date) ASC",
				array($GLOBALS['USERID'], $start_date_previous_duration, $end_date_previous_duration)
			);

			$totalClicksPreviousDuration = 0;
			$totalImpressionsPreviousDuration = 0;
			$totalUniqueClicksPreviousDuration = 0;
			foreach ($analyticsDataPreviousDuration as $entry) {
				$totalClicksPreviousDuration += $entry['total_clicks'];
				$totalImpressionsPreviousDuration += $entry['total_impressions'];
				$totalUniqueClicksPreviousDuration += $entry['unique_clicks'];
			}

			$data['rateChange'] = $totalClicksPreviousDuration > 0 ? round((($totalClicks - $totalClicksPreviousDuration) / $totalClicksPreviousDuration)*100, 2) : 0;

			echo json_encode($data);

		} else {
			// Build an array of dates between start and end
			$period = new DatePeriod(
				new DateTime($start_date),
				new DateInterval('P1D'),
				(new DateTime($end_date))->modify('+1 day')
			);

			$dates = [];
			foreach ($period as $date) {
				$dates[$date->format('Y-m-d')] = [
					'clicks' => 0,
					'impressions' => 0,
					'unique_clicks' => 0
				];
			}

			// Query aggregated by day
			$analyticsData = $GLOBALS['DB']->query(
				"SELECT 
					date, 
					SUM(clicks) AS total_clicks, 
					SUM(impressions) AS total_impressions,
					COUNT(DISTINCT CASE WHEN clicks > 0 THEN user_ip END) AS unique_clicks
				FROM registerusers_analytics 
				WHERE user_id = ? AND date BETWEEN ? AND ? 
				GROUP BY date
				ORDER BY date ASC",
				array($GLOBALS['USERID'], $start_date, $end_date)
			);

			// Fill the dates array with real data
			foreach ($analyticsData as $entry) {
				$dates[$entry['date']] = [
					'clicks' => (int)$entry['total_clicks'],
					'impressions' => (int)$entry['total_impressions'],
					'unique_clicks' => (int)$entry['unique_clicks'],
				];
			}

			// Prepare output arrays
			$labels = [];
			$clicks = [];
			$impressions = [];
			$unique_clicks = [];

			$totalClicks = 0;
			$totalImpressions = 0;
			$totalUniqueClicks = 0;

			$startDateObj = new DateTime($start_date);
			$endDateObj = new DateTime($end_date);
			$interval = $startDateObj->diff($endDateObj);
			$daysGap = $interval->days;

			foreach ($dates as $dateStr => $vals) {
				$dateObj = new DateTime($dateStr);

				// // Show "d M" if single day or 1 month or more, else "d"
				// if ($daysGap === 0 || $interval->m >= 1 || $interval->y > 0) {
				// 	$labels[] = $dateObj->format('d M');  // e.g. "25 Jul"
				// } else {
				// 	$labels[] = $dateObj->format('d');    // e.g. "25"
				// }
				$labels[] = $dateObj->format('d M');

				$clicks[] = $vals['clicks'];
				$impressions[] = $vals['impressions'];
				$unique_clicks[] = $vals['unique_clicks'];

				$totalClicks += $vals['clicks'];
				$totalImpressions += $vals['impressions'];
				$totalUniqueClicks += $vals['unique_clicks'];
			}

			$clickThroughRate = $totalClicks > 0 ? round(($totalUniqueClicks * 100) / $totalClicks, 2) : 0;

			$start = new DateTime($start_date);
			$end = new DateTime($end_date);

			// Calculate difference in days (exclusive)
			$interval = $start->diff($end);
			$days_gap = $interval->days; // e.g. 30 days

			// Previous period ends 1 day before current start date
			$previous_end = (clone $start)->modify('-1 day');

			// Previous period starts $days_gap days before previous_end to get same inclusive length
			$previous_start = (clone $previous_end)->modify('-' . $days_gap . ' days');

			// Format the dates to Y-m-d or any format you like
			$start_date_previous_duration = $previous_start->format('Y-m-d');
			$end_date_previous_duration = $previous_end->format('Y-m-d');

			$analyticsDataPreviousDuration = $GLOBALS['DB']->query(
				"SELECT 
					MONTH(date) AS month, 
					SUM(clicks) AS total_clicks, 
					SUM(impressions) AS total_impressions,
					COUNT(DISTINCT CASE WHEN clicks > 0 THEN user_ip END) AS unique_clicks
				FROM registerusers_analytics 
				WHERE user_id = ? AND date BETWEEN ? AND ? 
				GROUP BY MONTH(date)
				ORDER BY MONTH(date) ASC",
				array($GLOBALS['USERID'], $start_date_previous_duration, $end_date_previous_duration)
			);

			$totalClicksPreviousDuration = 0;
			$totalImpressionsPreviousDuration = 0;
			$totalUniqueClicksPreviousDuration = 0;
			foreach ($analyticsDataPreviousDuration as $entry) {
				$totalClicksPreviousDuration += $entry['total_clicks'];
				$totalImpressionsPreviousDuration += $entry['total_impressions'];
				$totalUniqueClicksPreviousDuration += $entry['unique_clicks'];
			}

			$rateChange = $totalClicksPreviousDuration > 0 ? round((($totalClicks - $totalClicksPreviousDuration) / $totalClicksPreviousDuration)*100, 2) : 0;

			echo json_encode([
				'labels' => $labels,
				'clicks' => $clicks,
				'impressions' => $impressions,
				'unique_clicks' => $unique_clicks,
				'total_clicks' => $totalClicks,
				'total_impressions' => $totalImpressions,
				'total_unique_clicks' => $totalUniqueClicks,
				'clickThroughRate' => $clickThroughRate,
				'rateChange' => $rateChange,
			]);
		}

		exit;
	}

	public function getDashboadSignatureChart(){
		header('Content-Type: application/json');

		$start_date = $_GET['start'] ?? date('Y-m-d');
		$end_date = $_GET['end'] ?? date('Y-m-d');
		
		
		$analyticsData = $GLOBALS['DB']->query(
			"SELECT 
				signature_id,
				SUM(clicks) AS total_clicks,
				SUM(impressions) AS total_impressions,
				COUNT(DISTINCT CASE WHEN clicks > 0 THEN user_ip END) AS unique_clicks
			FROM registerusers_analytics 
			WHERE user_id = ? AND date BETWEEN ? AND ? 
			GROUP BY signature_id ORDER BY total_clicks DESC LIMIT 5;",
			array($GLOBALS['USERID'],$start_date,$end_date)
		);
		$finalData = [
			"labels"=>[],
    		"allClicks"=> [],
    		"avatars"=> []
		];
		// $totalClicks = 0;
		// $totalImpressions = 0;
		// $totalUniqueClicks = 0;
		// $totalCTR = 0;
		foreach ($analyticsData as $key => $row) {
			$signatureData = $GLOBALS['DB']->row("SELECT signature_firstname,signature_profile FROM signature WHERE user_id=? AND signature_id=?",
			array($GLOBALS['USERID'],$row['signature_id']));
			if(is_array($signatureData)){
				$finalData['labels'][] = explode(' ', $signatureData['signature_firstname'])[0];
				$finalData['allClicks'][] = $row['total_clicks'];
				if($signatureData['signature_profile']){
					$filePath = 'upload-beta/signature/profile/'.$GLOBALS['USERID'].'/'.$signatureData['signature_profile'];
					if (file_exists($filePath)) {
						$finalData['avatars'][] = $GLOBALS['UPLOAD_LINK'].'/signature/profile/'.$GLOBALS['USERID'].'/'.$signatureData['signature_profile'];
					}else{
						$finalData['avatars'][] = $GLOBALS['UPLOAD_LINK'].'/signature/profile/'.$signatureData['signature_profile'];
					}
				}else{
					$finalData['avatars'][] = $GLOBALS['IMAGE_LINK'].'/images/profile-img1.png';
				}
			}
			// $totalClicks += $row['total_clicks'];
			// $totalImpressions += $row['total_impressions'];
			// $totalUniqueClicks += $row['unique_clicks'];
		}

		// if (!empty($totalClicks) && $totalClicks > 0) {
		//     $clickThroughRate = round(($totalUniqueClicks * 100) / $totalClicks, 2);
		// } else {
		//     $clickThroughRate = 0;  // or null or any default value you want
		// }

		// $finalData['total_clicks'] = $totalClicks;
		// $finalData['total_impressions'] = $totalImpressions;
		// $finalData['total_unique_clicks'] = $totalUniqueClicks;
		// $finalData['total_ctr'] = $clickThroughRate;
		echo json_encode($finalData);exit;
	}

}

?>
