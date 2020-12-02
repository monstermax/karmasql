<?php

namespace SqlParser\SqlType;

use SqlParser\SqlFragment\SqlFragment;
use \SqlParser\SqlParser;


class SqlTypeSpace extends SqlType
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


	public static function startSpace(SqlFragment $fragment, $pos)
	{
		$fragment->logDebug(__METHOD__ . " @ $pos");

		$current_space = new self;
		$fragment->setCurrentSpace($current_space);

		$current_space->start($fragment, $pos);
	}


	public function endSpace($pos)
	{
		$this->fragment->logDebug(__METHOD__ . " @ $pos");
		
		$current_space = $this->fragment->getCurrentSpace();
		if (! $current_space || $current_space !== $this) {
			throw new \Exception("not in a space", 1);
		}

		$this->end($pos);

		$this->fragment->addItem($this);
		$this->fragment->addSpace($this);

		$this->fragment->setCurrentSpace(null);
	}


}
