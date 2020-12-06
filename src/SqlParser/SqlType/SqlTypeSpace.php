<?php

namespace SqlParser\SqlType;

use \SqlParser\SqlFragment\SqlFragmentMain;
use \SqlParser\SqlParser;


class SqlTypeSpace extends SqlType
{
	public $type = 'space';


	public function __construct(SqlFragmentMain $fragment_main, $pos)
	{
		$fragment_main->logDebug(__CLASS__ . " @ $pos");
		
		parent::__construct($fragment_main, $pos);
		
		//$fragment_main->addSpace($this);
    }


	public function endSpace($pos)
	{
		$this->fragment_main->logDebug(__METHOD__ . " @ $pos");
		
		$current_space = $this->fragment_main->getCurrentSpace();
		if (! $current_space || $current_space !== $this) {
			throw new \Exception("not in a space", 1);
		}

		$this->end($pos);

		$this->fragment_main->setCurrentSpace(null);
	}


	public function toPhp($print_debug=false)
	{
		throw new \Exception("used by ?", 1);

		return ' ';
	}
	

	public function toSql($to_php=false, $print_debug=false)
	{
		// called by SqlPart::itemsToSql

		if ($print_debug) {
			echo " ";
		}
		return ' ';
	}

}
