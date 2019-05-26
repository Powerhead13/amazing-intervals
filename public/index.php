<?php
require_once("../src/defines.php");

try {

    $dbConf = parse_ini_file(CONF_DB_PATH);
    $dbManager = new DbManagerMySql($dbConf);
    Router::run($dbManager);

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}