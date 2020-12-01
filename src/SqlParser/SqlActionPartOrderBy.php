<?php

namespace SqlParser;


class SqlActionPartOrderBy extends SqlActionPart
{
	protected $part_name = 'order by';
	
	protected $fields = [];


	public function parsePart()
	{
		$fields = []; // note: devrait s'appeler $fields_and_expressions

		$tmp_params = $this->getParamsFromItems(false);

		$select_fields = array_values($this->getAction()->getFieldsSelect());

		foreach ($tmp_params as $param) {
			$is_expr = (get_class($param) === SqlExpr::class);
			$is_word = (get_class($param) === SqlWord::class);
			$is_string = (get_class($param) === SqlString::class);
			$is_numeric = (get_class($param) === SqlNumeric::class);

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
				$fields[] = $param;
				$param->word_type = 'field_name';

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
				}

				$fields[] = $param;
				
				// parse l'expression
				$param->detectFields();

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
