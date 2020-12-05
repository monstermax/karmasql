<?php

namespace SqlParser\SqlFragment;


// TODO a fusionner avec SqlPart

class SqlFragmentPart extends SqlFragment
{

    public function __construct($parent, $sql='')
    {
        parent::__construct($parent, $sql);
        
    }


    public static function startPart($action, $part_name)
    {
        $debug = 1;
    }

}
