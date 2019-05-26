<?php


abstract class Controller
{
    /**
     * @param $name
     * @return mixed
     */
    protected function param($name) {
        if(isset($_REQUEST[$name])) {
            return $_REQUEST[$name];
        }
        return null;
    }

    /**
     * @param null $message
     */
    protected function jsonResponseOk($message = null) {
        $this->jsonResponse([
            "status" => "ok",
            "message" => $message,
        ]);
    }

    /**
     * @param null $message
     */
    protected function jsonResponseError($message = null) {
        $this->jsonResponse([
            "status" => "error",
            "message" => $message,
        ]);
    }

    /**
     * @param $response
     */
    protected function jsonResponse($response) {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}