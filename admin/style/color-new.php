<?php

header('Content-type: text/css');
$website_id = $_GET['site_id'];
if(isset($_GET['site_id'])){$siteid = trim($_GET['site_id']);} else {$siteid = ''; }

// switch($siteid){
// 	case 0: $color = ''; $rgb= ''; break;
// 	case 1: $color = '#dd9933'; $subcolor='#222222'; $rgb= '221,153,51,0.5'; break; //Woodlands Cottages
// 	case 3: $color = '#d8006c'; $subcolor='#666666'; $rgb= '216,0,108,0.5'; break; //EZ Finance
// 	case 4: $color = '#e00f0a'; $subcolor='#010e03'; $rgb= '87,5,3,0.5'; break; //Buz On Biz 
// 	case 9: $color = '#'; $subcolor='#'; $rgb= '0,0,0,0.5'; break; //The New Retail Revolution 
// 	case 20: $color = '#'; $subcolor='#'; $rgb= '0,0,0,0.5'; break; //Southern Football League 
// 	case 25: $color = '#002e62'; $subcolor='#01563d'; $rgb= '0,46,98,0.5'; break; //Eagles Rewards 
// 	case 28: $color = '#C51230'; $subcolor='#f08a45'; $rgb= '197,18,48,0.5'; break; //Ecomotel Rewards 
// 	case 29: $color = '#1272b7'; $subcolor='#73b721'; $rgb= '18,114,183,0.5'; break; //Transform Rewards 
// 	case 30: $color = '#328C80'; $subcolor='#222222'; $rgb= '50,140,128,0.5'; break; //Child Protection Advocacy 
// 	case 54: $color = '#47cfe0'; $subcolor='#042638'; $rgb= '71,207,224,0.5'; break; //We Make A Difference 
// 	case 55: $color = '#006ebd'; $subcolor='#d66d2a'; $rgb= '0,110,189,0.5'; break; //MND SA Rewards 
// 	case 56: $color = '#00B495'; $subcolor='#061D45'; $rgb= '0,180,149,0.5'; break; //Teachers Health Shopping Portal 
// 	case 57: $color = '#07074e'; $subcolor='#ff5000'; $rgb= '7,7,78,0.5'; break; //Phoenix Healthfund Rewards 
// 	case 60: $color = '#406fb5'; $subcolor='#222222'; $rgb= '64,111,181,0.5'; break; //Lake Analytics Rewards 
// 	case 61: $color = '#812990'; $subcolor='#9f1f63'; $rgb= '129,41,144,0.5'; break; //Real Innovation Rewards 
// 	case 63: $color = '#2eda00'; $subcolor='#ff0000'; $rgb= '46,218,0,0.5'; break; //We ToGether Rewards 
// 	case 65: $color = '#283679'; $subcolor='#2d2a26'; $rgb= '40,54,121,0.5'; break; //BBA REAWARDS
// 	case 66: $color = '#ff87b8'; $subcolor='#4f4f4f'; $rgb= '255,135,184,0.5'; break; //The FGF Charitable Fund
// 	case 67: $color = '#00539b'; $subcolor='#7bc143'; $rgb= '0,83,155,0.5'; break; //ACA Health
// 	case 68: $color = '#f15050'; $subcolor='#443f3f'; $rgb= '241,80,80,0.5'; break; //bfsbenefits
// 	case 69: $color = '#0079b8'; $subcolor='#353e47'; $rgb= '0,121,184,0.5'; break;  //goodlife
// 	case 70: $color = '#3a978a'; $subcolor='#f5518b'; $rgb= '58,151,138,0.5'; break;  //goodlife
// 	case 71: $color = '#009fda'; $subcolor='#232021'; $rgb= '0,159,218,0.5'; break;  //Bionics Institute 
// 	default:  $color = ''; $rgb= ''; break;
// }
?> 
