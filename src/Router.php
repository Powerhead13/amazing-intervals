<?php

class Router
{
    const DEFAULT_CONTROLLER = "Interval";
    const DEFAULT_ACTION = "index";

    /**
     * @param DbManager $dbManager
     * @return mixed
     * @throws Exception
     */
    public static function run(DbManager $dbManager) {
        $ctrl = $_REQUEST["controller"] ?? self::DEFAULT_CONTROLLER;
        $controllerName = "{$ctrl}Controller";
        $action = $_REQUEST["action"] ?? self::DEFAULT_ACTION;
        $actionName = "{$action}Action";

        $controllerPath = realpath(SRC_ROOT . "/{$controllerName}.php");
        if(file_exists($controllerPath)) {
            require_once $controllerPath;
            $controller = new $controllerName($dbManager);
            if(method_exists($controller, $actionName)) {
                return $controller->$actionName();
            } else {
                throw new Exception("Unknown action $action");
            }
        } else {
            throw new Exception("Unknown controller $ctrl");
        }
    }

}