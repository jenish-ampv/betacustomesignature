<?php
ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
session_start();


$facebook_oauth_app_id = '920312183247686';
$facebook_oauth_app_secret = '44b1e4dd368ddbf86564c0b0a3a841af';
$facebook_oauth_redirect_uri = 'https://betaapp.customesignature.com/facebook-login/profile.php';
$facebook_oauth_version = 'v18.0';
if (isset($_GET['code']) && !empty($_GET['code'])) {
    // Execute cURL request to retrieve the access token
    $params = [
        'client_id' => $facebook_oauth_app_id,
        'client_secret' => $facebook_oauth_app_secret,
        'redirect_uri' => $facebook_oauth_redirect_uri,
        'code' => $_GET['code']
    ];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://graph.facebook.com/oauth/access_token');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    $response = json_decode($response, true);

    if (isset($response['access_token']) && !empty($response['access_token'])) {
        // Execute cURL request to retrieve the user info associated with the Facebook account
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://graph.facebook.com/' . $facebook_oauth_version . '/me?fields=name,email,picture');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $response['access_token']]);
        $response = curl_exec($ch);
        $profile = json_decode($response, true);
        if (isset($profile['email'])) {
            // Authenticate the user
            session_regenerate_id();
            $_SESSION['facebook_loggedin'] = $profile['id'];
            $_SESSION['facebook_email'] = $profile['email'];
            $_SESSION['facebook_name'] = $profile['name'];
            $_SESSION['facebook_picture'] = $profile['picture']['data']['url'];
        
        } else {
            exit('Could not retrieve profile information! Please try again later!');
        }
    } else {
        exit('Invalid access token! Please try again later!');
    }
}

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION['facebook_loggedin'])) {
    if(isset($_SESSION['FACEBOOK_SIGNUP']) && $_SESSION['FACEBOOK_SIGNUP'] == TRUE){
        $redirectURL = $_SESSION['FACEBOOK_SIGNUP_FAIL_URL'];
        header("Location: $redirectURL");
    }
    else if(isset($_SESSION['FACEBOOK_SIGNIN']) && $_SESSION['FACEBOOK_SIGNIN'] == TRUE){
        $redirectURL = $_SESSION['FACEBOOK_SIGNIN_SUCCESS_URL'];
        header("Location: $redirectURL");
    }
    exit;
}
// Retrieve session variables
// $facebook_loggedin = $_SESSION['facebook_loggedin'];
// $facebook_email = $_SESSION['facebook_email'];
// $facebook_name = $_SESSION['facebook_name'];
// $facebook_picture = $_SESSION['facebook_picture'];

if(isset($_SESSION['FACEBOOK_SIGNUP']) && $_SESSION['FACEBOOK_SIGNUP'] == TRUE){
    $redirectURL = $_SESSION['FACEBOOK_SIGNUP_SUCCESS_URL'];
    header("Location: $redirectURL");
}
else if(isset($_SESSION['FACEBOOK_SIGNIN']) && $_SESSION['FACEBOOK_SIGNIN'] == TRUE){
    $redirectURL = $_SESSION['FACEBOOK_SIGNIN_SUCCESS_URL'];
    header("Location: $redirectURL");
}
exit;
?>