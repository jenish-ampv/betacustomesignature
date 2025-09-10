<?php
require_once('config/db.php'); 
require_once('config/config.php');



$signatures = $GLOBALS['DB']->query("SELECT signature_id,signature_style FROM `signature`");


try{

    foreach ($signatures as $signature) {
        if(isset($signature['signature_style'])){
            $styleAsArray = unserialize($signature['signature_style']);
            
            foreach ($styleAsArray as $key => $value) {
                if (strpos($key, 'signature_profileanimation') === 0) {
                    $styleAsArray[$key]['signature_profileanimation'] = null;
                }
            }
            
            $styleAsSerialized = serialize($styleAsArray);
            $updatedData = [ 'signature_style' => $styleAsSerialized];
            $where = [ 'signature_id' => $signature['signature_id']];
            $GLOBALS['DB']->update('signature',$updatedData,$where);
        }
    }


    echo("All animation data updated to animation off");

} catch(\Exception $e) {
    echo $e->getMessage();
}


?>