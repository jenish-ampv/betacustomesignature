<?php
define('DBHost', 'localhost');
define('DBPort', 3306);
define('DBName', 'betacustomesignature');
define('DBUser', 'root');
define('DBPassword', '');
require(dirname(realpath(dirname(__FILE__)))."/lib/pdo/PDO.class.php");
$GLOBALS['DB'] = new Db(DBHost, DBPort, DBName, DBUser, DBPassword);

?>


