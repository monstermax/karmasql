<?php

namespace SqlParser;


class SqlJoker extends SqlParseItem
{
	public $type = 'joker';


	public static function startEndJoker(SqlParser $parser, $pos)
	{
		$parser->logDebug(__METHOD__ . " @ $pos");

		$current_joker = new self;
		$current_joker->start($parser, $pos);

		$current_joker->end($pos);

		$parser->addItem($current_joker);
		//$parser->addJoker($current_joker);

		//$parser->setCurrentJoker(null);
	}

	/*
	public function toPhp($print_debug=false)
	{
		$result_fields = [];

		$action = $this->parent->getAction();
		$tables = $action->getTables();

		$fields = [];
		foreach ($tables as $table) {
			$table_fields_names = $table->getFieldsNames();
			//$fields_names = array_merge($fields, $table_fields);
			$table_alias = $table->getAlias();

            foreach ($table_fields_names as $field_name) {
				$part = $table_alias . "." . $field_name;
				if ($print_debug) {
					echo $part;
				}
				
            }
			
		}

		return $result_fields;
	}
	*/


	public function toSql($to_php=false, $print_debug=false)
	{
		if ($to_php) {
			//return $this->toPhp($print_debug);
		}

		$sql = "";
		$all_parts = [];

		$action = $this->parent->getAction();
		$tables = $action->getTables();

		foreach ($tables as $table) {
			$table_fields_names = $table->getFieldsNames();
			$table_alias = $table->getAlias();

			$parts = [];

            foreach ($table_fields_names as $field_name) {
				if ($to_php) {
					$part = '$row["' . $field_name . '"]';

				} else {
					$part = $table_alias . "." . $field_name;
				}
				
				$parts[$field_name] = $part;
			}
			
			$sql .= implode(', ', $parts);

            if ($to_php) {
				$all_parts = array_merge($all_parts, $parts);
			}
			
		}

        if (empty($sql)) {
            $sql .= "*";
        }


		if ($to_php) {
			return $all_parts;
		}

		if ($print_debug) {
			echo '<span style="color:darkcyan;">';
			echo $sql;
			echo '</span>';
		}

		return $sql;
	}

}
