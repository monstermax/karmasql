<?php

namespace SqlParser\SqlType;

use SqlParser\SqlFragment\SqlFragment;
use \SqlParser\SqlParser;


class SqlTypeComma extends SqlType
{
	public $type = 'comma';


	public static function startEndComma(SqlFragment $fragment, $pos)
	{
		$fragment->logDebug(__METHOD__ . " @ $pos");

		$current_comma = new self;
		$current_comma->start($fragment, $pos);

		$current_comma->end($pos);

		$fragment->addItem($current_comma);
		//$fragment->addComma($current_comma);

		//$fragment->setCurrentComma(null);
	}


}
