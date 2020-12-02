<?php

namespace SqlParser\SqlType;

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


	public static function startComparator(SqlParser $parser, $pos)
	{
		$parser->logDebug(__METHOD__ . " @ $pos");

		$current_comparator = new self;
		$parser->setCurrentComparator($current_comparator);

		$current_comparator->start($parser, $pos);
	}


	public function endComparator($pos)
	{
		$this->parser->logDebug(__METHOD__ . " @ $pos");
		
		$current_comparator = $this->parser->getCurrentComparator();

		if (empty($current_comparator)) {
			throw new \Exception("not in an comparator", 1);
		}
		if ($current_comparator !== $this) {
			throw new \Exception("mismatch comparator", 1);
		}
		
		$this->end($pos);

		$this->comparator = $this->outer_text;

		$this->parser->addItem($this);
		$this->parser->addComparator($this);

		$this->parser->setCurrentComparator(null);
	}
	

}
