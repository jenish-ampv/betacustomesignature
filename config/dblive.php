<?php
define('DBHost', 'localhost');
define('DBPort', 3306);
define('DBName', 'custom_signature');
define('DBUser', 'custom_signature');
define('DBPassword', 'brDdE[V&gvcu');
require(dirname(realpath(dirname(__FILE__)))."/lib/pdo/PDO.class.php");
$GLOBALS['DB'] = new Db(DBHost, DBPort, DBName, DBUser, DBPassword);

?>


