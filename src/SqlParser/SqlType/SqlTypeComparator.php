<?php

namespace SqlParser\SqlType;

use SqlParser\SqlFragment\SqlFragmentMain;
use \SqlParser\SqlParser;


class SqlTypeComparator extends SqlType
{
	public $type = 'comparator';   //  = != <> > < <= >=
	public $comparator = '';



	public function __construct(SqlFragmentMain $fragment_main, $pos)
	{
        $fragment_main->logDebug(__CLASS__ . " @ $pos");

		parent::__construct($fragment_main, $pos);

		//$fragment_main->addComparator($this);
    }


	public function endComparator($pos)
	{
		$this->fragment_main->logDebug(__CLASS__ . " @ $pos");
		
		$current_comparator = $this->fragment_main->getCurrentComparator();

		if (empty($current_comparator)) {
			throw new \Exception("not in an comparator", 1);
		}
		if ($current_comparator !== $this) {
			throw new \Exception("mismatch comparator", 1);
		}
		
		$this->end($pos);

		$this->comparator = $this->outer_text;

		$this->fragment_main->setCurrentComparator(null);
	}
	

	public function toPhp($print_debug=false)
	{
		$comparator = $this->comparator;
		if ($comparator == '=') {
			$comparator = '==';
		}
		return $comparator;
	}


}
