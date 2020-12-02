<?php

namespace SqlParser\SqlType;

use \SqlParser\SqlExecutor;
use SqlParser\SqlFragment\SqlFragment;
use \SqlParser\SqlParser;


class SqlTypeNumeric extends SqlType
{
	public $type = 'numeric';
	public $number = '';


	public static function startNumeric(SqlFragment $fragment, $pos)
	{
		$fragment->logDebug(__METHOD__ . " @ $pos");

		$current_numeric = new self;
		$fragment->setCurrentNumeric($current_numeric);

		$current_numeric->start($fragment, $pos);
	}


	public function endNumeric($pos)
	{
		$this->fragment->logDebug(__METHOD__ . " @ $pos");
		
		$current_numeric = $this->fragment->getCurrentNumeric();
		if (empty($current_numeric)) {
			throw new \Exception("not in a numeric", 1);
		}
		if ($current_numeric !== $this) {
			throw new \Exception("mismatch numeric", 1);
		}
		
		$this->end($pos);

		$this->number = $this->outer_text;

		$this->fragment->addItem($this);
		$this->fragment->addNumeric($this);

		$this->fragment->setCurrentNumeric(null);
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
