<?php
define("APP_ROOT", realpath(dirname(__FILE__) . "/../") );
define("SRC_ROOT", realpath(APP_ROOT . "/src/") );
define("CONF_DB_PATH", realpath(APP_ROOT . "/conf/db.ini") );

require_once SRC_ROOT . "/DbManagerMySql.php";
require_once SRC_ROOT . "/Router.php";