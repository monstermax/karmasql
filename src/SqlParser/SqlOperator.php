<?php

namespace SqlParser;


class SqlOperator extends SqlParseItem
{
	public $type = 'operator';   //  + - * /
	public $operator = '';
	public $fields = null;


	public static function startOperator(SqlParser $parser, $pos)
	{
		$parser->logDebug(__METHOD__ . " @ $pos");

		$current_operator = new SqlOperator;
		$parser->setCurrentOperator($current_operator);

		$current_operator->start($parser, $pos);
	}


	public function endOperator($pos)
	{
		$this->parser->logDebug(__METHOD__ . " @ $pos");
		
		$current_operator = $this->parser->getCurrentOperator();

		if (empty($current_operator)) {
			throw new \Exception("not in an operator", 1);
		}
		
		if ($current_operator !== $this) {
			throw new \Exception("mismatch operator", 1);
		}
		
		$this->end($pos);

		$this->operator = $this->outer_text;

		$this->parser->addItem($this);
		$this->parser->addOperator($this);

		$this->parser->setCurrentOperator(null);
	}

	
	public function toPhp() {
		$outer_text = $this->outer_text;
		return $outer_text;
	}
	
	public function toSql($to_php=false, $print_debug=false)
	{
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
