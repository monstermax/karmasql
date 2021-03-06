<?php

namespace SqlParser\SqlPart;

use \SqlParser\SqlExpr;
use \SqlParser\SqlType\SqlTypeWord;
use \SqlParser\SqlType\SqlTypeString;
use \SqlParser\SqlType\SqlTypeNumeric;


class SqlPartOrderBy extends SqlPart
{
	protected $part_name = 'order by';
	
	protected $fields = [];


	public function parsePart()
	{
		// called by SqlActionSelect::parseParts

		$fields = []; // note: devrait s'appeler $fields_and_expressions

		$tmp_params = $this->getParamsFromItems(false);

		$select_fields = array_values($this->getAction()->getFieldsSelect());

		foreach ($tmp_params as $param) {
			$is_expr = (get_class($param) === SqlExpr::class);
			$is_word = (get_class($param) === SqlTypeWord::class);
			$is_string = (get_class($param) === SqlTypeString::class);
			$is_numeric = (get_class($param) === SqlTypeNumeric::class);

            if ($is_numeric) {
				// order by 1
				$number = $param->number;
				if (empty($select_fields[$number-1])) {
					throw new \Exception("ERROR 1054 (42S22): Unknown column '" . $number . "' in 'order clause'", 1);

				} else {
					$fields[] = $select_fields[$number-1];
				}
				
            } else if ($is_string) {
				// order by string
				$fields[] = $param;
				
            } else if ($is_word) {
				// order by word

				$is_alias = false;

                if ($param->word_type === 'field') {
					// field
					
                } else if ($param->word_type == 'undefined') {

					// is select field alias ?
					foreach ($select_fields as $select_field) {
						if ($select_field->getAlias() === $param->word) {
							$is_alias = true;
							$param->word_type = 'order_field_alias';
							$param->detectSelectFieldsAlias();
							break;
						}
					}

				} else {
					throw new \Exception("unknown case", 1);
				}

				if (! $param->fields) {
					$param->detectFields();

					if (! $param->fields) {
						throw new \Exception("missing field(s)", 1);
					}
				}

				$fields[] = $param;

            } else if ($is_expr) {
				// order by expr
				$param_items = $param->getItems(false);

				if ($param_items && $param_items[0]->type === 'numeric') {
					// order by 1 ...
					$number = $param_items[0]->number;

					if (count($param_items) == 2) {
				
						if ($param_items && $param_items[0]->type === 'word' && $param_items[0]->word_type === 'keyword' && $param_items[0]->word === 'desc') {
							// keyword 'desc' detected
							// TODO: trouver comment enregistrer l'info que c'est en desc

							$fields[] = $select_fields[$number-1];
						}

					} else {
						throw new \Exception("unknown case");
					}

					$fields[] = $select_fields[$number-1];

				} else if ($param_items && $param_items[ count($param_items)-1 ]->type === 'word' && $param_items[ count($param_items)-1 ]->word_lower === 'desc') {
					// order by DESC
					$debug = 1;
					$param->order_desc = true;

					if ($param_items && $param_items[0]->type === 'word' && $param_items[0]->word_type === 'undefined') {
						$param_items[0]->detectFields();
					}

					
				}

				$fields[] = $param;
				
				// parse l'expression
				$param->detectFields();
				//$param->detectAlias();

			} else {
				throw new \Exception("non implemented case");
			}

		}
		unset($param);

		$this->fields = $fields;
		return $fields;
	}


	/**
	 * Get the value of fields
	 */ 
	public function getFields()
	{
		return $this->fields;
	}

	/**
	 * Set the value of fields
	 *
	 * @return  self
	 */ 
	public function setFields($fields)
	{
		$this->fields = $fields;

		return $this;
	}
	
}
