<?php

namespace SqlParser;


class SqlActionPartSelect extends SqlActionPart
{
	protected $part_name = 'select';
	
	protected $fields = [];


	public function parsePart()
	{
		$fields = []; // note: devrait s'appeler $fields_and_expressions

		$tmp_params = $this->getParamsFromItems(false);

		foreach ($tmp_params as $param) {
			$is_expr = (get_class($param) === SqlExpr::class);
			$is_word = (get_class($param) === SqlWord::class);
			$is_numeric = (get_class($param) === SqlNumeric::class);
			$is_asterisk = (get_class($param) === SqlOperator::class && $param->operator == '*');
			$is_joker = (get_class($param) === SqlJoker::class && $param->outer_text == '*');

            if ($is_expr) {
                $alias = $param->getAlias();
				$fields[$alias] = $param;
				
				// parse l'expression
				$param->detectAlias();
				$param->detectFields();
								
            } else if ($is_asterisk || $is_joker) {
				// select *
				$action = $this->getAction();
				$tables = $action->getTables();
				
				$param->fields = [];

				// pour chaque table de la requete
				foreach ($tables as $table) {
					$table_fields_names = $table->getFieldsNames();

					if ($table_fields_names) {
						// pour chaque champ de la table...
						foreach ($table_fields_names as $field_name) {
							$field = new SqlField($field_name, $table);
							$field->parent = $param;

							$fields[$field_name] = $field;

							$param->fields[$field_name] = $field;
						}
						unset($field);

					}
					unset($table_fields);

				}
				unset($table);

			} else if ($is_word) {
				$param->detectFields();

				if ($param->word_type == 'variable_sql') {
					$fields[$field_name] = $param;
					
				} else if ($param->word_type == 'field' && ! empty($param->fields)) {
					// multiple fields * => on unpack les fields
					foreach ($param->fields as $field) {
						$field_name = $field->getName();
						$table = $field->getTable();

						if (! $table) {
							throw new \Exception("invalid table for field " . $field_name);
						}

						$param_expr = new SqlField($field_name, $table);
						$param_expr->parent = $param; // TODO: a voir si on autorise un item word comme parent ?!
						$fields[$field_name] = $param_expr;
					}

				} else {
					throw new \Exception("check this case");

					// one field
					$param_expr = new SqlExpr($param->outer_text);
					$param_expr->action = $this->action;
					$param_expr->parser = $this->parser;
					$param_expr->parent = $param; // TODO: a voir si on autorise un item word comme parent ?!
					
					$param_expr->addItem($param);

					$alias = $param_expr->getAlias();

					$fields[$alias] = $param_expr;
				}
				
			} else if ($is_numeric) {
				$number = $param->number;
				$fields[$number] = $param;

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
