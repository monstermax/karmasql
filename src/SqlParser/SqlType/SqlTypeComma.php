<?php

namespace SqlParser\SqlType;

use SqlParser\SqlFragment\SqlFragmentMain;
use \SqlParser\SqlParser;


class SqlTypeComma extends SqlType
{
	public $type = 'comma';


	public function __construct(SqlFragmentMain $fragment_main, $pos)
	{
        $fragment_main->logDebug(__CLASS__ . " @ $pos");

        parent::__construct($fragment_main, $pos);
    }

}
