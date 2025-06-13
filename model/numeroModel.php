<?php

namespace Model;

use Exception;
use mysqli;
use mysqli_sql_exception;
use View\indexView;
require dirname(__FILE__, 2) . "/exceptions/database/connectionTimeout.php";
use Exceptions\database\connectionTimeout as dbTimeout;


$hostDatabase = getenv('mysql_host');
$userDatabase = getenv('mysql_user');
$passDatabase = getenv('mysql_password');

class numeroModel
{

    private static $instance = null;
    private $instanceDatabase;
    private $linkList;
    private $numberList;

    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new numeroModel();
        }
        return self::$instance;
    }

    public static function deleteInstance()
    {
        if (is_null(self::$instance)) {
            return;
        }
        if (is_null(self::$instanceDatabase)) {
            return;
        }
        self::$instanceDatabase->close();
        self::$instance = null;
    }

    public function close_database()
    {
        $this->instanceDatabase->close();
    }

    public function init_database()
    {
        global $hostDatabase;
        global $userDatabase;
        global $passDatabase;

        try {
            $this->instanceDatabase = mysqli_init();
            $this->instanceDatabase->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);
            if (!$this->instanceDatabase->real_connect($hostDatabase, $userDatabase, $passDatabase, 'my_database')) {
                $this->instanceDatabase = null;
                throw new dbTimeout("Falha ao conectar no banco de dados. Por favor, contate o administrador e o peça para verificar a instância do banco de dados.");
            }
        } catch (Exception $e) {
            //throw new dbTimeout("Falha ao conectar no banco de dados. Por favor, contate o administrador e o peça para verificar a instância do banco de dados.");
            throw new dbTimeout($e->getMessage());
        }

    }

    public function create_databases()
    {
        $numberSQL = "CREATE TABLE IF NOT EXISTS numeros(ID INT(5) AUTO_INCREMENT, name TEXT(200),number BIGINT(20),operator TEXT(30),server TEXT(32),stats INT(1),_date date,PRIMARY KEY (ID))";
        $linkSQL = "CREATE TABLE IF NOT EXISTS links(ID INT(5) AUTO_INCREMENT, name TEXT(200), link TEXT(500), PRIMARY KEY (ID))";

        $this->getInstance_selectDB();
        if (!$this->instanceDatabase->query($numberSQL)) {
            throw new dbTimeout("Falha ao criar a tabela de números");
        }
        if (!$this->instanceDatabase->query($linkSQL)) {
            throw new dbTimeout("Falha ao criar a tabela de links");
        }
    }

    function removeNumber($ID)
    {
        if (empty($ID)) {
            throw new dbTimeout("Informe um ID.");
        }
        $query = "DELETE FROM numeros WHERE ID = $ID";
        if (!$this->instanceDatabase->query($query)) {
            throw new dbTimeout("Falha ao deletar o ID $ID");
        }

        return true;
    }

    function removeLink($nome)
    {
        if (empty($nome)) {
            throw new dbTimeout("Informe um nome.");
        }
        $query = sprintf("DELETE FROM numeros WHERE name = %s", $this->instanceDatabase->real_escape_string($nome));
        if (!$this->instanceDatabase->query($query)) {
            throw new dbTimeout("Falha ao deletar o link " . $nome);
        }

        return true;
    }

    function updateNumber($ID, $values)
    {
        $this->getInstance_selectDB();
        $query = "UPDATE numeros SET ";
        $i = 0;
        $listToSQL = [];
        foreach ($values as $k => $v) {
            $i++;
            if ($i - 1 != count($values) && $i != 1) {
                $query .= ", ";
            }
            $query .= "$k = \"%s\"";
            array_push($listToSQL, is_numeric($v) ? $v : $this->instanceDatabase->real_escape_string($v));
        }

        $query .= " WHERE ID=%s";
        array_push($listToSQL, $ID);
        $query = sprintf($query, ...$listToSQL);
        $this->instanceDatabase->query($query);
        if ($this->instanceDatabase->affected_rows < 1) {
            throw new dbTimeout("Não foi possível encontrar o item. O mesmo não foi atualizado. ");
        }

        return true;
    }

    function updateLink($name, $values)
    {
        $this->getInstance_selectDB();
        $query = "UPDATE links SET ";
        $i = 0;
        $listToSQL = [];
        foreach ($values as $k => $v) {
            $i++;
            if ($i - 1 != count($values) && $i != 1) {
                $query .= ", ";
            }
            $query .= "$k = \"%s\"";
            array_push($listToSQL, $this->instanceDatabase->real_escape_string($v));
        }

        $query .= " WHERE name=\"%s\"";
        array_push($listToSQL, $this->instanceDatabase->real_escape_string($name));
        $query = sprintf($query, ...$listToSQL);
        $this->instanceDatabase->query($query);
        if ($this->instanceDatabase->affected_rows < 1) {
            throw new dbTimeout("Não foi possível encontrar o link. O mesmo não foi atualizado. ");
        }

        return true;
    }

    public function insertFirstLink()
    {
        $this->getInstance_selectDB();
        if (!array_key_exists("AMERICANET", $this->linkList)) {
            if (
                !$this->instanceDatabase->query("INSERT INTO links (name,link) Values ('AMERICANET', '179.127.199.66')")
            ) {
                throw new dbTimeout("Falha ao inserir registro 'AMERICANET'.");
            }
        }
        if (!array_key_exists("IDT", $this->linkList)) {
            if (
                !$this->instanceDatabase->query("INSERT INTO links (name,link) Values ('IDT', '177.10.199.6')")
            ) {
                throw new dbTimeout("Falha ao inserir registro 'IDT.");
            }
        }
        if (!array_key_exists("GOLDCOM", $this->linkList)) {
            if (
                !$this->instanceDatabase->query("INSERT INTO links (name,link) Values ('GOLDCOM', '179.127.199.132')")
            ) {
                throw new dbTimeout("Falha ao inserir registro 'GOLDCOM'.");
            }
        }
        if (!array_key_exists("VONEX", $this->linkList)) {
            if (
                !$this->instanceDatabase->query("INSERT INTO links (name,link) Values ('VONEX', '')")
            ) {
                throw new dbTimeout("Falha ao inserir registro 'VONEX'.");
            }
        }
    }

    private function getInstance_selectDB()
    {
        if (empty($this->instanceDatabase)) {
            throw new dbTimeout("Falha ao obter a instância do banco de dados. Recarregue a página e tente novamente.");
        }
        if (!$this->instanceDatabase->select_db("my_database")) {
            throw new dbTimeout("Falha ao selecionar o banco de dados.");
        }
    }

    public function insertNumber($data)
    {
        if (is_null($data) || empty($data || count($data) < 6)) {
            throw new dbTimeout("Dados passados como argumento são inválidos.");
        }
        $dataToInsert = [
            $this->instanceDatabase->real_escape_string($data["nome"]),
            $data["numero"],
            $this->instanceDatabase->real_escape_string($data["operator"]),
            $this->instanceDatabase->real_escape_string($data["server"]),
            $data["status"],
            $data["date"]
        ];
        $result = $this->instanceDatabase->execute_query("INSERT INTO numeros (name, number, operator, server, stats, _date) VALUES(?, ?, ?, ?, ?, ?)", $dataToInsert);
        if (!$result) {
            throw new dbTimeout("Falha ao adicionar número");
        }
        return true;
    }

    public function insertLink($data)
    {
        if (is_null($data) || empty($data)) {
            throw new dbTimeout("Dados passados como argumento são inválidos.");
        }
        $result = $this->instanceDatabase->execute_query("INSERT INTO links (name, link) VALUES(?, ?)", [$this->instanceDatabase->real_escape_string($data["name"]), $this->instanceDatabase->real_escape_string(($data["link"]))]);
        if (!$result) {
            throw new dbTimeout("Falha ao adicionar número");
        }
    }

    function existsNumber($name, $numero)
    {
        if ($name != null || $numero != null) {
            // query para buscar números ou nome de empresas
            return true;
        }
        return true;
    }

    function exitsLink($name, $link)
    {
        if ($name != null || $link != null) {
            // query para buscar números ou nome de empresas
            return true;
        }
        return true;
    }

    public function getNumbers($where = "")
    {
        $query = "SELECT * FROM numeros";
        if ($where != "") {
            $query .= " " . $where;
        }
        $query = $this->instanceDatabase->query($query);
        if (!$query) {
            throw new dbTimeout("Falha ao obter os números cadastrados.");
        }
        return $this->iterateNumbers($query);
    }

    private function iterateNumbers($data)
    {
        $dataReturn = [];
        while ($row = mysqli_fetch_array($data)) {
            array_unshift($dataReturn, [
                "ID" => $row["ID"],
                "name" => $row["name"],
                "number" => $row["number"],
                "operator" => $row["operator"],
                "server" => $row["server"],
                "stats" => $row["stats"],
                "date" => date_format(date_create($row["_date"]), "d/m/Y"),

            ]);
        }
        return $dataReturn;
    }

    private function iterateLinks($data)
    {
        $dataReturn = [];
        while ($row = mysqli_fetch_array($data)) {
            $dataReturn[$row["name"]] = $row["link"];
        }
        return $dataReturn;
    }

    public function getLinks($where = "")
    {
        $query = "SELECT * FROM links";
        if ($where != "") {
            $query .= " " . $where;
        }
        //throw new dbTimeout($query);

        $query = $this->instanceDatabase->query($query);
        if (!$query) {
            throw new dbTimeout("Falha ao obter os números cadastrados.");
        }
        return $this->iterateLinks($query);
    }


    private function replace_list($name, $array)
    {
        if ($name == "números") {
            $this->numberList = is_array($array) ? $array : [];
        } else if ($name == "links") {
            $this->linkList = is_array($array) ? $array : [];
        }
    }


}