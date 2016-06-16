<?php
//定义目录分割符号
define('DS', DIRECTORY_SEPARATOR);
//define base Directory
define("ROOT", dirname(__DIR__));
//define include Directory
define("INC_PATH", ROOT. DS. 'include');
//define main application Directory
define("APP_PATH", ROOT. DS. 'application');
//define public Directory
define("PUB_PATH", ROOT. DS. 'public');
//define local library Directory
define("LOCAL_LIB", ROOT. DS. 'application'. DS. 'library');
//define global library Directory
define("GLOBAL_LIB", INC_PATH. DS. 'library');
//define View Directory
define('APP_VIEW', APP_PATH . DS . 'views');
//deifne Modules
define("MODULE_PATH", APP_PATH . DS . 'modules');
//get Time Zone
define("TIME_ZONE", (isset($_COOKIE['timezone']) && is_numeric($_COOKIE['timezone'])) ? $_COOKIE['timezone'] : -8);

$app  = new Yaf_Application(ROOT . "/conf/application.ini");
$app->bootstrap()->run();