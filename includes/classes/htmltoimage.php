<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/s3bucket/s3bucketinit.php'); // s3bucket init

$data = $_POST['image'];
if(is_null($data)){
  $return_arr = array("error" =>1, "msg"=>"Image data is not posted properly.");
  echo json_encode($return_arr); exit;
}
$uploadLink = $_SERVER['DOCUMENT_ROOT'].'/upload-beta'; // image uploaded path 

$imageName = $_POST['image_name'];
session_start();

// below if condition added for manage htmltoimage with API call
$userId = 'api';
if(isset($_SESSION['user_idPHPSESSID'])){
  $userId = $_SESSION['user_idPHPSESSID'];
}
$currentUrl = $_SERVER['HTTP_REFERER'];
@$signatureIdbefore = end(explode("/", $currentUrl));
$signatureId  = strstr($signatureIdbefore, '?', true);

if(strpos($signatureId, 'install') !== false) { 
  $signatureId = substr($signatureIdbefore, 8);
  parse_str($signatureId, $requestParams);
  $signatureId  = base64_decode($requestParams['uuid']);
  $userId  = base64_decode($requestParams['u']);
}else{
  $signatureId  = strstr($signatureIdbefore, '?', true);
}
if(!is_numeric($signatureId)){
  if(isset($_POST['signature_id']) && !is_null($_POST['signature_id']) && !empty($_POST['signature_id'])){
    $signatureId = $_POST['signature_id'];
  }elseif(isset($_SESSION['receive_signature_id'])){
    $signatureId = $_SESSION['receive_signature_id'];
  }

}

list($type, $data) = explode(';', $data);
list(, $data)      = explode(',', $data);
$data = base64_decode($data);

$pathToUserIDFolder = $uploadLink."/htmltoimage/".$userId;
if (!file_exists($pathToUserIDFolder)) {
    mkdir($pathToUserIDFolder, 0777, true);
}

$pathToImage = $uploadLink."/htmltoimage/".$userId."/".$signatureId."/";
if (!file_exists($pathToImage)) {
    mkdir($pathToImage, 0777, true);
}
$myfile = fopen($pathToImage.$imageName, "w") or die("Unable to open file!");
fwrite($myfile, $data);
fclose($myfile);


$location = $uploadLink."/htmltoimage/".$userId."/".$signatureId."/".$imageName;
$result = $GLOBALS['S3Client']->putObject(array( // upload image s3bucket
  'Bucket'=>$GLOBALS['BUCKETNAME'],
  'Key' =>  'upload-beta/htmltoimage/'.$userId.'/'.$signatureId.'/'.$imageName,
  'SourceFile' => $location,
  'StorageClass' => 'REDUCED_REDUNDANCY',
  'ACL'   => 'public-read'
));
$imagePath = $GLOBALS['BUCKETBASEURL']."/upload-beta/htmltoimage/".$userId."/".$signatureId."/".$imageName; 
$return_arr = array("error" =>0, "image_path"=> $imagePath);
echo json_encode($return_arr); exit;
?>