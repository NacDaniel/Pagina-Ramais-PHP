<?php

namespace Model;
require "exceptions/database/connectionTimeout.php";
use Exceptions\database\connectionTimeout as dbTimeout;


$hostDatabase = getenv('mysql_host');
$userDatabase = getenv('mysql_user');
$passDatabase = getenv('mysql_password');

// Classe criada para simular o construtor, atributos e todo o resto do mysqli.
// Não tenho o mysqli :/
class mysqli
{
    public $affected_rows = 0;
    public $error;
    public function __construct($host, $user, $password)
    {
    }
    public function select_db($databaseName)
    {
        return true;
    }
    public function query($query)
    {
        return true;
    }

    public function execute_query($query, $args = null)
    {
        return true;
    }

    public function real_escape_string($string)
    {
        return $string;
    }
}

/*Não esquecer de deletar a classe mysqli*/ 

class numeroModel
{
    private $instanceDatabase;
    private $linkList;
    private $numberList;
    public function init_database()
    {
        global $hostDatabase;
        global $userDatabase;
        global $passDatabase;
        $this->instanceDatabase = new mysqli($hostDatabase, $userDatabase, $passDatabase);
        if ($this->instanceDatabase->error) {
            $this->instanceDatabase = null;
            throw new dbTimeout("Falha ao conectar no banco de dados. Por favor, contate o administrador e o peça para verificar a instância do banco de dados.");
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
        $query = sprintf("DELETE FROM numeros WHERE ID = %s", $this->instanceDatabase->real_escape_string($ID));
        if (!$this->instanceDatabase->query($query)) {
            throw new dbTimeout("Falha ao deletar o ID $ID");
        }
    }

    function updateNumber($ID, $values)
    {
        $this->getInstance_selectDB();
        $query = "UPDATE numeros SET ";
        $i = 1;
        $listToSQL = [];
        foreach ($values as $k => $v) {
            $i++;
            if ($i != count($values)) {
                $query .= ", ";
            }
            $query .= "$k = %s";
            array_push($listToSQL, $v);
        }

        $query .= " WHERE ID=%s";
        array_push($listToSQL, $ID);
        $query = sprintf($query, ...$listToSQL);
        $this->instanceDatabase->query($query);
        if ($this->instanceDatabase->affected_rows < 1) {
            throw new dbTimeout("Não foi possível encontrar o item. O mesmo não foi atualizado.");
        }
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
        if (is_null($data) || empty($data)) {
            throw new dbTimeout("Dados passados como argumento são inválidos.");
        }
        $result = $this->instanceDatabase->execute_query("INSERT INTO numeros (name, number, operator, server, stats, _date) VALUES(?, ?, ?, ?, ?, ?)", [$data["nome"], $data["numero"], $data["operator"], $data["server"], $data["status"], $data["data"]]);
        if (!$result) {
            throw new dbTimeout("Falha ao adicionar número");
        }
    }

    public function insertLink($data)
    {
        if (is_null($data) || empty($data)) {
            throw new dbTimeout("Dados passados como argumento são inválidos.");
        }
        $result = $this->instanceDatabase->execute_query("INSERT INTO links (name, link) VALUES(?, ?)", [$data["nome"], (string) $data["link"]]);
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
        if (!$this->instanceDatabase->query($query)) {
            throw new dbTimeout("Falha ao obter os números cadastrados.");
        }
    }

    public function getLinks($where = "")
    {
        $query = "";
        if ($where != "") {
            $query .= " " . $where;
        }
        if (!$this->instanceDatabase->query($query)) {
            throw new dbTimeout("Falha ao obter os números cadastrados.");
        }
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