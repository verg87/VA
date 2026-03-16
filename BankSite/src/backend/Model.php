<?php

declare(strict_types=1);

namespace App;

require_once __DIR__ . "\\..\\..\\vendor\\autoload.php";

abstract class Model
{
    protected DB $db;

    public function __construct()
    {
        $this->db = DBI;
    }
}