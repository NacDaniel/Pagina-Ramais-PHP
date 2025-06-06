<?php
namespace Service;
require dirname(__FILE__, 2) . "/model/numeroModel.php";
use Model\numeroModel;
use Exceptions\database\connectionTimeout;
class numeroService
{
    private static $instance = null;

    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new numeroService();
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

    private function getURL($text)
    {
        if (strlen($text) <= 0) {
            return;
        }
        if (str_contains($text, "http://") || str_contains($text, "https://")) {
            return;
        }
        return "http://" . $text;
    }

    private function isNullData($data)
    {
        foreach ($data as $k => $v) {
            if (is_null($k) || is_null($v)) {
                throw new connectionTimeout("Dados passados como argumento são inválidos. Argumento: " . ($k ?? "N/A") . ": " . ($v ?? "N/A"));
            }
        }
    }

    private function removeSpaceInText($data)
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

    private function checkSizeTexts($data)
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

    private function findSequencyNumInText($text)
    {
        if (!strpos($text, "-")) {
            return [$text];
        }
        if (strpos($text, "-") + 1 == strlen($text)) {
            throw new connectionTimeout('Caráctere inválido no final do número.');
        }
        $listReturn = [];
        list($start, $end) = explode("-", $text);
        $range = range($start, $end);
        return $range;
    }

    function upgradeDataBefereUpdateOrInsert($data)
    {
        $data = json_decode($data, true);
        if (empty($data)) {
            throw new connectionTimeout("Dados passados como argumento são inválidos.");
        }

        $this->isNullData($data);

        $data["date"] = $data["data"];
        unset($data["data"]);
        if ($data["date"] == "") {
            $data["date"] = date("Y/m/d");
        }
        $data = $this->removeSpaceInText($data) ?? $data;

        $data["contrato"] = $this->getURL(array_map('urldecode', [$data["contrato"]])[0]) ?? $data["contrato"];
        $data["server"] = $this->getURL($data["server"]) ?? $data["server"];

        $data["status"] = ["Ativo" => 1, "Suspenso" => 2, "Cancelado" => 3][$data["status"]];
        $data["date"] = date("Y-m-d", date_timestamp_get(date_create($data["date"])));

        $this->checkSizeTexts($data);

        return $data;
    }
    public function checkInsertNumber($data)
    {
        $numberList = explode(",", $data["numeros"]) ?? [];
        unset($data["numeros"]);
        if (count($numberList) <= 0) {
            throw new connectionTimeout("Argumentos inválidos passados no campo \"Numero\".");
        }
        $numberListReturn = [
            "success" => [],
            "fail" => []
        ];
        foreach ($numberList as $index => $number) {
            $numAtt = $number;
            try {
                if (strlen($number) < 3) {
                    throw new connectionTimeout("Número deve possuir ao menos 3 digitos.");
                }
                $numbersSequence = $this->findSequencyNumInText($number);
                foreach ($numbersSequence as $num) {
                    $data["numero"] = $num;
                    $numAtt = $num;
                    if (count(numeroModel::getInstance()->getNumbers("WHERE number = \"$num\"")) != 0) {
                        throw new connectionTimeout("Número já existente.");
                    }
                    if (!is_numeric($num)) {
                        throw new connectionTimeout("Não é um número de telefone.");
                    }
                    if (strlen($num) > 20) {
                        throw new connectionTimeout("Número maior que 20 carácteres.");
                    }
                    if (
                        numeroModel::getInstance()->insertNumber([
                            "nome" => $data["nome"],
                            "operator" => $data["operator"],
                            "server" => $data["server"],
                            "status" => $data["status"],
                            "date" => $data["date"],
                            "numero" => $num,
                        ])
                    ) {
                        array_push($numberListReturn["success"], $num);
                    }
                }
            } catch (connectionTimeout $e) {
                array_push($numberListReturn["fail"], $numAtt . " ('" . $e->getMessage() . "')");
            }
        }
        return $numberListReturn;
    }

    public function checkUpdateNumber($data)
    {
        $numberOldData = numeroModel::getInstance()->getNumbers("WHERE ID = " . $data["id"]);
        if (!$numberOldData || count($numberOldData) <= 0) {
            throw new connectionTimeout("Número não encontrado.");
        }
        $numberOldData = $numberOldData[0];

        if (empty($numberOldData)) {
            throw new connectionTimeout("Ocorreu um erro ao verificar se o número existe.");
        }

        if (
            numeroModel::getInstance()->updateNumber($data["id"], [
                "name" => (String) $data["nome"],
                "operator" => (String) $data["operator"],
                "server" => (String) $data["server"],
                "stats" => (int) $data["status"] ?? 5,
                "_date" => $data["date"]
            ])
        ) {
            return $this->checkAndUpdateOrInsertLink($numberOldData["name"], $data["nome"], $data["contrato"]);
        }
    }

    private function checkAndUpdateOrInsertLink($oldName, $newName, $contrato = "")
    {
        $exitsNameOld = numeroModel::getInstance()->getNumbers("WHERE name = \"$oldName\"") > 0;
        $exitsNameNew = numeroModel::getInstance()->getNumbers("WHERE name = \"$newName\"") > 0;
        $exitsLinkByNameOld = numeroModel::getInstance()->getLinks("WHERE name = \"$oldName\"") > 0;

        if ($exitsNameOld) {
            if ($exitsLinkByNameOld) {
                numeroModel::getInstance()->insertLink(["name" => $newName, "link" => $contrato]);
                return true;
            }
        } else {
            if (numeroModel::getInstance()->updateLink($oldName, ["name" => $newName, "link" => $contrato])) {
                numeroModel::getInstance()->insertLink(["name" => $newName, "link" => $contrato]);
            }
        }

    }

    public function checkDeleteNumber($ID)
    {
        if (empty(($ID))) {
            throw new connectionTimeout("Informe um ID");
        }

        $numero = numeroModel::getInstance()->getNumbers("WHERE ID = $ID");

        if (count($numero) == 0) {
            throw new connectionTimeout("Número não encontrado.");
        }

        if (!numeroModel::getInstance()->removeNumber($ID)) {
            throw new connectionTimeout("Falha ao remover o número.");
        }

        $this->checkOthersNumbersAndDeleteLink($numero[0]["name"]);

        return true;
    }

    private function checkOthersNumbersAndDeleteLink($nome)
    {
        $numeros = numeroModel::getInstance()->getNumbers("WHERE name = \"$nome\"");
        if (!(count($numeros) != 0)) {
            numeroModel::getInstance()->removeLink($nome);
        }
    }
}

?>