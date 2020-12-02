<?php

namespace SqlParser\SqlType;

use \SqlParser\SqlParser;


class SqlTypeComma extends SqlType
{
	public $type = 'comma';


	public static function startEndComma(SqlParser $parser, $pos)
	{
		$parser->logDebug(__METHOD__ . " @ $pos");

		$current_comma = new self;
		$current_comma->start($parser, $pos);

		$current_comma->end($pos);

		$parser->addItem($current_comma);
		//$parser->addComma($current_comma);

		//$parser->setCurrentComma(null);
	}


}
