<?php

namespace Exceptions\database;

use Exception;

class connectionTimeout extends Exception{
    public function __construct(String $texto) {
        parent::__construct($texto);
    }
}