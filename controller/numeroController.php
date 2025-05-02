<?php

namespace Controller;
require "./model/numeroModel.php";
require "./view/indexView.php";
use Model\numeroModel;
use Exceptions\database\connectionTimeout;
use View\indexView;
function request_get($path)
{
    $database = new numeroModel();
    $index = new indexView();
    $index->index();
    try {
        $database->init_database();
        $database->create_databases();
        //$index->setListNumbers($database->getLinks(), $database->getNumbers());
        $index->setListNumbers(
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
        $index->insertErrorMessageInScreen($e->getMessage());
    } finally {
        // $database->close();
    }
}

function engine_start()
{
    if (isset($_GET)) {
        request_get("/");
    }
}