<?php

namespace SqlParser;


class SqlComma extends SqlParseItem
{
	public $type = 'comma';


	public static function startEndComma(SqlParser $parser, $pos)
	{
		$parser->logDebug(__METHOD__ . " @ $pos");

		$current_comma = new SqlComma;
		$current_comma->start($parser, $pos);

		$current_comma->end($pos);

		$parser->addItem($current_comma);
		//$parser->addComma($current_comma);

		//$parser->setCurrentComma(null);
	}


}
