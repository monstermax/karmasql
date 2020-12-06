<?php

namespace SqlParser\SqlType;

use SqlParser\SqlFragment\SqlFragmentMain;
use \SqlParser\SqlParser;


class SqlTypeOperator extends SqlType
{
	public $type = 'operator';   //  + - * /
	public $operator = '';
	public $fields = null;


	public function __construct(SqlFragmentMain $fragment_main, $pos)
	{
        $fragment_main->logDebug(__CLASS__ . " @ $pos");
        
		parent::__construct($fragment_main, $pos);
		
		//$fragment_main->addOperator($this);
    }


	public function endOperator($pos)
	{
		$this->fragment_main->logDebug(__METHOD__ . " @ $pos");
		
		$current_operator = $this->fragment_main->getCurrentOperator();

		if (empty($current_operator)) {
			throw new \Exception("not in an operator", 1);
		}
		
		if ($current_operator !== $this) {
			throw new \Exception("mismatch operator", 1);
		}
		
		$this->end($pos);

		$this->operator = $this->outer_text;

		$this->fragment_main->setCurrentOperator(null);
	}

	
	public function toPhp()
	{
		// called by SqlTypeOperator::toSql()

		//throw new \Exception("used by ?", 1);

		$outer_text = $this->outer_text;
		return $outer_text;
	}

	
	public function toSql($to_php=false, $print_debug=false)
	{
		// called by SqlPart::itemsToSql()

		//throw new \Exception("used by ?", 1);

		$sql = '';

		if (empty($this->fields)) {
			// oerator *
			if ($to_php) {
				$sql .= $this->toPhp();

			} else {
				$sql .= $this->outer_text;
			}

			if ($print_debug) {
				echo $sql;
			}
	
		} else {
			// field *
			$parts = [];
			$idx = 0;

			throw new \Exception("it would be better to have a joker and not a operator_field");

			foreach ($this->fields as $field) {
				if ($print_debug) {
					$class = "";
					$style = "";
					$title = $this->type . ' / field';

					$color = '#795548';
					if ($color) {
						$style .= "color:$color;";
					}
	
					$parent_idx = 'X-subitem-' . $idx;
					$id = 'item-' . $parent_idx . '-' . $this->type;

					echo '<span id="' . $id . '" class="' . $class . '" style="' . $style . '" title="' . $title . '">';
				}

				$parts[] = $field->toSql($to_php, $print_debug);

				if ($print_debug) {
					echo '</span>';

					if ($idx++ < count($this->fields) - 1) {
						echo ", ";
					}
				}
			}

			$sql .= implode(', ', $parts);
		}
		
		return $sql;
	}
	

}
