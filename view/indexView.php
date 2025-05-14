<?php
namespace View;

header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.

class indexView
{


    private static $instance;

    public function __construct(){
        // verificar se já existe uma instãncia, senão, cria-la
        // cogitar apenas fazer o php dar um echo no bloco <script>
    }

    
    public static function getInstance()
    {
        if(is_null(self::$instance)) {
            self::$instance = new indexView();
        }
        return self::$instance;
    }

    public static function deleteInstance()
    {
        if(is_null(self::$instance)) {
            return;
        }
        self::$instance = null;
    }

    public function index()
    {
        echo file_get_contents("./view/index.html");
        return $this;
    }

    public function setListNumbers($link = null, $numbers = null)
    {
        if ($link != null && is_array($link)) {
            echo '<script>listaLinks=' . json_encode($link) . '</script>';
        }
        if ($numbers != null && is_array($numbers)) {
            echo '
            <script>listaNumeros=' . json_encode($numbers) . '</script>';
        }

        if (is_array($numbers) || is_array($link)) {
            echo '<script>updateListClient()</script>';
        }
        return $this;
    }

    function insertErrorMessageInScreen($text)
    {
        echo '<script>myAlertBottom("' . $text . '")</script>';
    }

    function insertAlertMessageInScreen($text)
    {
        echo '<script>myAlertTop("' . $text . '")</script>';
    }
}
?>