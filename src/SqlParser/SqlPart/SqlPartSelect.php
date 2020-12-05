<?php

namespace SqlParser\SqlPart;

use \SqlParser\SqlField;
use \SqlParser\SqlExpr;
use \SqlParser\SqlType\SqlTypeJoker;
use \SqlParser\SqlType\SqlTypeNumeric;
use \SqlParser\SqlType\SqlTypeOperator;
use \SqlParser\SqlType\SqlTypeParenthese;
use \SqlParser\SqlType\SqlTypeString;
use \SqlParser\SqlType\SqlTypeWord;


class SqlPartSelect extends SqlPart
{
	protected $part_name = 'select';
	
	protected $fields = [];


	public function parsePart()
	{
		$fields = []; // note: devrait s'appeler $fields_and_expressions

		$tmp_params = $this->getParamsFromItems(false);

		foreach ($tmp_params as $param) {
			$is_parenthese = (get_class($param) === SqlTypeParenthese::class);
			$is_expr = (get_class($param) === SqlExpr::class);
			$is_word = (get_class($param) === SqlTypeWord::class);
			$is_numeric = (get_class($param) === SqlTypeNumeric::class);
			$is_string = (get_class($param) === SqlTypeString::class);
			$is_asterisk = (get_class($param) === SqlTypeOperator::class && $param->operator == '*');
			$is_joker = (get_class($param) === SqlTypeJoker::class && $param->outer_text == '*');

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
						unset($field, $field_name);

					}
					unset($table_fields_names);

				}
				unset($table);

			} else if ($is_word) {
				$param->detectFields();

				if ($param->word_type == 'variable_sql') {
					$fields[ $param->var_name ] = $param;
				
				/*
				} else if ($param->word_type == 'joker_table') {
					// TODO: mytable.*

					$table_name = ''; // TODO
					$tables = $action->getTables();
					$table = $tables[$table_name];


                    $table_fields_names = $table->getFieldsNames();

                    if ($table_fields_names) {
                        // pour chaque champ de la table...
                        foreach ($table_fields_names as $field_name) {
                            $field = new SqlField($field_name, $table);
                            $field->parent = $param;

                            $fields[$field_name] = $field;

                            $param->fields[$field_name] = $field;
                        }
                        unset($field, $field_name);
                    }
                    unset($table_fields_names);
				*/

				} else if ($param->word_type === 'field' && ! empty($param->fields)) {
					// field
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

				} else if ($param->word_type === 'joker_table' && ! empty($param->fields)) {
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
					throw new \Exception("check this case. unknown field ?");

					// one field
					$param_expr = new SqlExpr($param->outer_text);
					$param_expr->action = $this->action;
					$param_expr->parser = $this->parser;
					$param_expr->parent = $param; // TODO: a voir si on autorise un item word comme parent ?!
					
					$param_expr->addItem($param);

					$alias = $param_expr->getAlias();

					$fields[$alias] = $param_expr;
				}
				
			} else if ($is_parenthese) {
				$field_alias = $param->outer_text;
				$fields[$field_alias] = $param;

			} else if ($is_numeric) {
				$number = $param->number;
				$fields[$number] = $param;
				
			} else if ($is_string) {
				$field_alias = $param->inner_text;
				$fields[$field_alias] = $param;

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
