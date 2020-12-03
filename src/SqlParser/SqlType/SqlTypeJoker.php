<?php

namespace SqlParser\SqlType;

use \SqlParser\SqlFragment\SqlFragment;


class SqlTypeJoker extends SqlType
{
	public $type = 'joker';



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
