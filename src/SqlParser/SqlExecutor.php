<?php

namespace SqlParser;

use \SqlParser\SqlAction\SqlAction;
use \SqlParser\SqlType\SqlTypeWord;


class SqlExecutor
{
	public $results_groups;
	public $current_group_key;
	public $current_result;
	public $current_field;


	public function validateConditions($row_data, $conditions_where)
	{
		// called by SqlActionSelect::executeAction
		
		// TODO => valider (ou non) les conditions du where pour la $row donnée

		if ($conditions_where) {
			foreach ($conditions_where as $condition) {
				$condition_result = $condition->validateCondition($this, $row_data); // NOTE: $condition est un SqlField ou un SqlExpr

				if (!$condition_result) {
					return false;
				}
			}
		}

		return true;
	}
	

	public function calculateFields($row_data, $select_fields, $fields_aliases=null)
	{
		// called by SqlActionSelect::executeAction
		// called by SqlActionInsert::executeAction
		
		$values = [];
		// TODO => calculer les fields pour la $row donnée

		$idx = 0;

		foreach ($select_fields as $field_alias => $field) {
			$this->current_field = $field;

			//$field_alias = $field->getAlias();
			//$row_data = $row_data[$idx];

			if (get_class($field) === SqlResult::class) {
				$tmp_field_alias = $field_alias;

				if ($fields_aliases) {
					// cas pour le insert into ... select
					$tmp_field_alias = array_shift($fields_aliases);
					
					if (is_object($tmp_field_alias) && get_class($tmp_field_alias) === SqlTypeWord::class) {
						$tmp_field_alias = $tmp_field_alias->word;
					}
				}
				$values[$tmp_field_alias] = $field->result;

				
			} else {
				$field_values = $field->getCalculatedValues($this, $row_data); // NOTE: $field est un SqlField ou un SqlExpr -- il peut contenir 1 champ ou plusieurs si "*"

				if ($field_values) {
					foreach ($field_values as $tmp_field_alias => $value) {
						if ($fields_aliases) {
							// cas pour le insert into
							$tmp_field_alias = $fields_aliases[$idx]->word;
						}
						$values[$tmp_field_alias] = $value;
					}
				}
			}

			$idx++;
		}

		return $values;
	}
	

	public function getCurrentRowKey()
	{
		return $this->current_group_key;
	}



}
