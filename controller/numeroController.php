<?php

namespace Controller;
require dirname(__FILE__, 2) . "/model/numeroModel.php";
require dirname(__FILE__, 2) . "/view/indexView.php";
use Model\numeroModel;
use Exceptions\database\connectionTimeout;
use View\indexView;

class numeroController
{

    private static $instance = null;
    private $instanceModel;
    private $instanceView;

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
            //$index->setListNumbers(indexView::getInstance()->getLinks(), indexView::getInstance()->getNumbers());
            indexView::getInstance()->setListNumbers(
                [
                    "AMERICANET" => "192.168.1.1",
                    "Daniel Teste" => "192.168.1.1",
                ],
                [
                    [
                        "ID" => 1,
                        "name" => "Daniel Teste",
                        "number" => "40028922",
                        "operator" => "AMERICANET",
                        "server" => "192.168.1.1",
                        "stats" => 1,
                        "date" => date_format(date_create("04/29/2025"), "d/m/Y"),
                    ],
                    [
                        "ID" => 2,
                        "name" => "Daniel Teste2",
                        "number" => "40028922",
                        "operator" => "AMERICANET",
                        "server" => "192.168.1.1",
                        "stats" => 1,
                        "date" => date_format(date_create("04/29/2025"), "d/m/Y"),
                    ]
                ]
            );
        } catch (connectionTimeout $e) {
            $this->returnJson(404, $e->getMessage());
        } finally {
            // indexView::getInstance()->close();
        }
    }

    public function request_post($path, $data = [])
    {
        try {
            numeroModel::getInstance()->init_database();
            numeroModel::getInstance()->create_databases();
            if ($path == "delete") {
                $this->deleteNumber($data);
            } else if ($path == "update") {
                $this->whatActionNumber($data);
            }
        } catch (connectionTimeout $e) {
            $this->returnJson(500, $e->getMessage());
        } finally {
            // indexView::getInstance()->close();
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

    private function deleteNumber($data)
    {

        if (empty(($data["id"]))) {
            throw new connectionTimeout("Informe um ID");
        }

        $numero = numeroModel::getInstance()->getNumbers($data["id"]);

        if (count($numero) == 0) {
            throw new connectionTimeout("Número não encontrado.");
        }

        if (!numeroModel::getInstance()->removeNumber($data["id"])) {
            throw new connectionTimeout("Falha ao remover o número.");
        }

        $this->returnJson(201, "Número deletado com sucesso.");
    }

    private function whatActionNumber($data)
    {
        $data = json_decode($data, true);
        if (empty($data)) {
            throw new connectionTimeout("Dados passados como argumento são inválidos.");
        }

        $this->valuesNull($data);

        $data["date"] = $data["data"];
        unset($data["data"]);
        if ($data["date"] == "") {
            $data["date"] = date("Y/m/d");
        }
        $data = $this->checkSpaces($data) ?? $data;

        $data["contrato"] = $this->checkURL(array_map('urldecode', [$data["contrato"]])[0]) ?? $data["contrato"];
        $data["server"] = $this->checkURL($data["server"]) ?? $data["server"];

        $data["status"] = ["Ativo" => 1, "Suspenso" => 2, "Cancelado" => 3][$data["status"]];
        $data["date"] = date("Y-m-d", date_timestamp_get(date_create($data["date"])));

        $this->validarInputs($data);

        if (isset($data["id"])) {
            if (!$this->updateNumberAndLink($data)) {
                return;
            }
            $this->returnJson(201, "Número atualizado com sucesso.");
            return;
        }

        if ($this->insertNumberAndLink($data)) {
            $this->returnJson(201, "Número inserido com sucesso.");
        }

    }


    private function insertNumberAndLink($data)
    {

    }
    private function updateNumberAndLink($data)
    {
        $numberOldData = numeroModel::getInstance()->getNumbers("WHERE ID = " . $data["id"]);
        if (!$numberOldData || count($numberOldData) <= 0) {
            throw new connectionTimeout("Número não encontrado.");
        }
        $numberOldData = $numberOldData[0];

        if (
            numeroModel::getInstance()->updateNumber($data["id"], [
                "nome" => (String) $data["nome"],
                "operator" => (String) $data["operator"],
                "server" => (String) $data["server"],
                "stats" => (int) $data["status"],
                "date" => $data["date"]
            ])
        ) {
            if (numeroModel::getInstance()->getLinks("WHERE nome = " . $numberOldData["name"]) <= 0) { // verifica se ainda existe algum número com o nome antigo
                if (numeroModel::getInstance()->getLinks("WHERE nome = " . $numberOldData["name"]) <= 0) {
                    // não existe. criar
                    return true;
                }
                // existe link com o nome antigo = Atualiza
                return true;
            }

            $linkNew = numeroModel::getInstance()->getLinks("WHERE nome = " . $data["nome"]); // busca por links com o mesmo identificador do nome novo
            if (!(count($linkNew) != 0 && $linkNew[0]["link"] != $data["contrato"])) { // verifica se o link encontrado é diferente do link de contrato
                // Atualizar link
                return true;
            }

            numeroModel::getInstance()->insertLink([
                "nome" => $data["nome"],
                "link" => $data["contrato"]
            ]);
            //   numeroModel::getInstance()->updateLink($);

            return true;
        }
    }

    private function validarInputs($data)
    {
        if (strlen($data["date"]) < 10) {
            throw new connectionTimeout("A data deve seguir o formato (dd-mm-aaaa). Exemplo: 22-05-2025.");
        } else if (strlen($data["nome"]) < 4) {
            throw new connectionTimeout("Informe um nome com no mínimo 4 caracteres.");
        } else if (strlen($data["contrato"]) > 500) {
            throw new connectionTimeout("O contrato deve possuir, no máximo, 500 caracteres.");
        } else if (strlen($data["numeros"]) < 3) {
            throw new connectionTimeout("Informe ao menos um número válido, com no mínimo 3 digitos.");
        } else if ($data["operator"] != "AMERICANET" && $data["operator"] != "IDT" && $data["operator"] != "GOLDCOM" && $data["operator"] != "OI" && $data["operator"] != "VONEX" && $data["operator"] != "OPERADORANOVA2") {
            throw new connectionTimeout("Selecione uma das operadores válidas.");
        } else if (strlen($data["server"]) < 8) {
            throw new connectionTimeout("Informe o IP do servidor.");
        } else if ($data["status"] != 1 && $data["status"] != 2 && $data["status"] != 3) {
            throw new connectionTimeout("Status inválido. Informe corretamente. " . $data["status"]);
        } else if (strlen($data["nome"]) > 500) {
            throw new connectionTimeout("O nome pode ter no máximo 500 caracteres.");
        } else if (strlen($data["operator"]) > 30) {
            throw new connectionTimeout("A operadora pode ter no máximo 30 caractere");
        } else if (strlen($data["server"]) > 32) {
            throw new connectionTimeout("O servidor pode ter no máximo 32 caracteres.");
        } else if (strlen($data["status"]) > 1) {
            throw new connectionTimeout("No banco de dados, o status pode ter somente 1 caractere.");
        } else if (strlen($data["date"]) > 10) {
            throw new connectionTimeout("A data pode ter no máximo 10 caracteres.");
        }
    }

    private function checkSpaces($data)
    {
        if (count($data) <= 0) {
            return;
        }
        $dataReturn = [];
        foreach ($data as $k => $v) {
            $dataReturn[$k] = trim($v);
        }

        return $dataReturn;
    }

    private function valuesNull($data)
    {
        foreach ($data as $k => $v) {
            if (is_null($k) || is_null($v)) {
                throw new connectionTimeout("Dados passados como argumento são inválidos. Argumento: " . ($k ?? "N/A") . ": " . ($v ?? "N/A"));
            }
        }
    }

    private function checkURL($text)
    {
        if (strlen($text) <= 0) {
            return;
        }
        if (str_contains($text, "http://") && str_contains($text, "https://")) {
            return;
        }
        return "http://" . $text;
    }

    private function deleteLink($data)
    {
        http_response_code(404);

        if (empty(($data["ID"]))) {
            throw new connectionTimeout("Informe um ID");
        }


        $numero = numeroModel::getInstance()->getNumbers($data["ID"]);

        if (count($numero) == 0) {
            throw new connectionTimeout("Número não encontrado.");
        }

        if (!numeroModel::getInstance()->removeNumber($data["ID"])) {
            throw new connectionTimeout("Número não encontrado.");
        }

        http_response_code(201);
        indexView::getInstance()->insertErrorMessageInScreen("Número deletado com sucesso.");
    }
}


if ($_SERVER["REQUEST_METHOD"] === "POST") {
    header("Content-Type: application/JSON");
    if (isset($_POST["id"])) {
        numeroController::getInstance()->request_post("delete", ["id" => $_POST["id"]]);
        return;
    }
    if (isset($_POST["data"])) {
        numeroController::getInstance()->request_post("update", $_POST["data"]);
        return;
    }
} else if ($_SERVER["REQUEST_METHOD"] === "GET") {
    numeroController::getInstance()->request_get("/");
    return;
}

