<?php

namespace SqlParser;


class SqlSpace extends SqlParseItem
{
	public $type = 'space';


	public function toPhp($print_debug=false)
	{
		return ' ';
	}

	public function toSql($to_php=false, $print_debug=false)
	{
		if ($print_debug) {
			echo " ";
		}
		return ' ';
	}


	public static function startSpace(SqlQueryParser $parser, $pos)
	{
		$parser->logDebug(__METHOD__ . " @ $pos");

		$current_space = new SqlSpace;
		$parser->setCurrentSpace($current_space);

		$current_space->start($parser, $pos);
	}


	public function endSpace($pos)
	{
		$this->parser->logDebug(__METHOD__ . " @ $pos");
		
		$current_space = $this->parser->getCurrentSpace();
		if (! $current_space || $current_space !== $this) {
			throw new \Exception("not in a space", 1);
		}

		$this->end($pos);

		$this->parser->addItem($this);
		$this->parser->addSpace($this);

		$this->parser->setCurrentSpace(null);
	}


}
