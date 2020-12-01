<?php

namespace SqlParser;


class SqlResult
{
    public $result;
    

    public function __construct($result)
    {
        $this->result = $result;
    }

}
