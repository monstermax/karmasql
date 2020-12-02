<?php

namespace SqlParser\SqlType;

use SqlParser\SqlFragment\SqlFragment;
use \SqlParser\SqlParser;


class SqlTypeComparator extends SqlType
{
	public $type = 'comparator';   //  = != <> > < <= >=
	public $comparator = '';


	public function toPhp($print_debug=false)
	{
		$comparator = $this->comparator;
		if ($comparator == '=') {
			$comparator = '==';
		}
		return $comparator;
	}


	public static function startComparator(SqlFragment $fragment, $pos)
	{
		$fragment->logDebug(__METHOD__ . " @ $pos");

		$current_comparator = new self;
		$fragment->setCurrentComparator($current_comparator);

		$current_comparator->start($fragment, $pos);
	}


	public function endComparator($pos)
	{
		$this->fragment->logDebug(__METHOD__ . " @ $pos");
		
		$current_comparator = $this->fragment->getCurrentComparator();

		if (empty($current_comparator)) {
			throw new \Exception("not in an comparator", 1);
		}
		if ($current_comparator !== $this) {
			throw new \Exception("mismatch comparator", 1);
		}
		
		$this->end($pos);

		$this->comparator = $this->outer_text;

		$this->fragment->addItem($this);
		$this->fragment->addComparator($this);

		$this->fragment->setCurrentComparator(null);
	}
	

}
