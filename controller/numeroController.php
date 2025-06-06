<?php

namespace Controller;

use Exception;
require dirname(__FILE__, 2) . "/view/indexView.php";
require dirname(__FILE__, 2) . "/service/numeroService.php";
use Model\numeroModel;
use Exceptions\database\connectionTimeout;
use View\indexView;
use Service\numeroService;

class numeroController
{

    private static $instance = null;
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new numeroController();
        }
        return self::$instance;
    }

    public static function deleteInstance()
    {
        if (is_null(self::$instance)) {
            return;
        }
        self::$instance = null;
    }

    public function request_get($path)
    {
        indexView::getInstance()->index();
        try {
            numeroModel::getInstance()->init_database();
            numeroModel::getInstance()->create_databases();
            indexView::getInstance()->setListNumbers(numeroModel::getInstance()->getNumbers());
            indexView::getInstance()->setListLinks(numeroModel::getInstance()->getLinks());
        } catch (connectionTimeout $e) {
            indexView::getInstance()->insertErrorMessageInScreen($e->getMessage(), 30);
        } finally {
            numeroModel::getInstance()->close_database();
        }
    }

    public function request_post($path, $data = [])
    {
        try {
            numeroModel::getInstance()->init_database();
            numeroModel::getInstance()->create_databases();
            if ($path == "delete") {
                if (!numeroService::getInstance()->checkDeleteNumber($data["id"])) {
                    return;
                }
                $this->returnJson(201, "Número deletado com sucesso.");
            } else if ($path == "update_insert") {
                $data = numeroService::getInstance()->upgradeDataBefereUpdateOrInsert($data);
                if (array_key_exists("id", $data)) {
                    if (!numeroService::getInstance()->checkUpdateNumber($data)) {
                        $this->returnJson(201, "Falha ao atualizar esse número.");
                        return;
                    }
                    $this->returnJson(201, "Número atualizado com sucesso.");
                } else {
                    $this->returnJson(200, "Inserção realizada", numeroService::getInstance()->checkInsertNumber($data));
                }
            }
        } catch (connectionTimeout $e) {
            $this->returnJson(500, $e->getMessage());
        } finally {
            numeroModel::getInstance()->close_database();
        }
    }

    private function returnJson($code, $message, $args = [])
    {
        header("Content-Type: application/json");
        $args["code"] = $code ?? 500;
        $args["message"] = $message ?? "Argumentos inválidos no method returnJSON";
        http_response_code($args["code"]);
        echo json_encode($args);
    }
}


if ($_SERVER["REQUEST_METHOD"] === "POST") {
    header("Content-Type: application/JSON");
    if (isset($_POST["id"])) {
        numeroController::getInstance()->request_post("delete", ["id" => $_POST["id"]]);
        return;
    }
    if (isset($_POST["data"])) {
        numeroController::getInstance()->request_post("update_insert", $_POST["data"]);
    }
} else if ($_SERVER["REQUEST_METHOD"] === "GET") {
    numeroController::getInstance()->request_get("/");
    return;
}
