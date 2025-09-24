<?php
define(constant_name: 'DBHost', value: 'localhost');
define(constant_name: 'DBPort', value: 3306);
define(constant_name: 'DBName', value: 'esignature-beta');
define(constant_name: 'DBUser', value: 'root');
define(constant_name: 'DBPassword', value: '');
require(dirname(path: realpath(path: dirname(path: __FILE__)))."/lib/pdo/PDO.class.php");
$GLOBALS['DB'] = new Db(Host: DBHost, DBPort: DBPort, DBName: DBName, DBUser: DBUser, DBPassword: DBPassword);

?>


