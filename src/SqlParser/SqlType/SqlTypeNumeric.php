<?php

namespace SqlParser\SqlType;

use \SqlParser\SqlExecutor;
use SqlParser\SqlFragment\SqlFragmentMain;
use \SqlParser\SqlParser;


class SqlTypeNumeric extends SqlType
{
	public $type = 'numeric';
	public $number = '';


	public function __construct(SqlFragmentMain $fragment_main, $pos)
	{
        $fragment_main->logDebug(__CLASS__ . " @ $pos");

		parent::__construct($fragment_main, $pos);

		//$fragment_main->addNumeric($this);
	}
	

	public function endNumeric($pos)
	{
		$this->fragment_main->logDebug(__CLASS__ . " @ $pos");
		
		$current_numeric = $this->fragment_main->getCurrentNumeric();
		if (empty($current_numeric)) {
			throw new \Exception("not in a numeric", 1);
		}
		if ($current_numeric !== $this) {
			throw new \Exception("mismatch numeric", 1);
		}
		
		$this->end($pos);

		$this->number = $this->outer_text;

		$this->fragment_main->setCurrentNumeric(null);
	}

	
	public function toSql($to_php=false, $print_debug=false)
	{
		$sql = "";

        if ($to_php) {
			$outer_text = $this->toPhp();
        } else {
			$outer_text = $this->outer_text;
		}

		if ($outer_text == '*') {
			// count(*) => count(1)
			$outer_text = 1;
		}

		$sql .= $outer_text;

        if ($print_debug) {
            $idx = 'X';
            $id = 'item-' . $idx . '-' . $this->type;
            echo '<span id="' . $id . '">';
			echo $outer_text;
			echo '</span>';
		}

		return $sql;
	}


	public function getCalculatedValues(SqlExecutor $executor, $row_data)
	{
		$alias = $this->number;

		return [
			$alias => $this->number,
		];
	}


}
