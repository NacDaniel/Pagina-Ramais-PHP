<?php
namespace Model;
require dirname(__FILE__, 2) . "/model/numeroModel.php";
require dirname(__FILE__, 2) . "/view/indexView.php";
use Model\numeroModel;
use Exceptions\database\connectionTimeout;
use View\indexView;
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
        if (str_contains($text, "http://") && str_contains($text, "https://")) {
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
}
?>