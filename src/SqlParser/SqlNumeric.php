<?php

namespace SqlParser;


class SqlNumeric extends SqlParseItem
{
	public $type = 'numeric';
	public $number = '';


	public static function startNumeric(SqlParser $parser, $pos)
	{
		$parser->logDebug(__METHOD__ . " @ $pos");

		$current_numeric = new SqlNumeric;
		$parser->setCurrentNumeric($current_numeric);

		$current_numeric->start($parser, $pos);
	}


	public function endNumeric($pos)
	{
		$this->parser->logDebug(__METHOD__ . " @ $pos");
		
		$current_numeric = $this->parser->getCurrentNumeric();
		if (empty($current_numeric)) {
			throw new \Exception("not in a numeric", 1);
		}
		if ($current_numeric !== $this) {
			throw new \Exception("mismatch numeric", 1);
		}
		
		$this->end($pos);

		$this->number = $this->outer_text;

		$this->parser->addItem($this);
		$this->parser->addNumeric($this);

		$this->parser->setCurrentNumeric(null);
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


}
