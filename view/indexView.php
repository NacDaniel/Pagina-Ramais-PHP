<?php
namespace View;

header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.

class indexView
{


    private static $instance;

    public function __construct()
    {
        // verificar se já existe uma instãncia, senão, cria-la
        // cogitar apenas fazer o php dar um echo no bloco <script>
    }


    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new indexView();
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

    public function index()
    {
        echo file_get_contents("./view/index.html");
        echo "<script>updateListClient()</script>";
        return $this;
    }

    public function setListNumbers($numbers = null)
    {
        if ($numbers != null && is_array($numbers)) {
            echo '
            <script>listaNumeros=' . json_encode($numbers) . '</script>';
        }
        echo '<script>updateListClient()</script>';
        return $this;
    }

    public function setListLinks($link = null)
    {
        if ($link != null && is_array($link)) {
            echo '<script>listaLinks=' . json_encode($link) . '</script>';
        }
        echo '<script>updateListClient()</script>';
        return $this;
    }

    function insertErrorMessageInScreen($text, $time = null)
    {
        echo "<script>myAlertBottom(" . "\"" . (preg_replace('/\r|\n/', ' ', $text)) . "\"" . ", $time)</script>";
    }

    function insertAlertMessageInScreen($text, $time = null)
    {
        echo "<script>myAlertBottom(" . "\"" . (preg_replace('/\r|\n/', ' ', $text)) . "\"" . ", $time)</script>";
    }
}

?>