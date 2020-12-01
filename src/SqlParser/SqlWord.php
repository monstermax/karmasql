<?php

namespace SqlParser;

class SqlWord extends SqlParseItem
{
	public $type = 'word';
	public $word_type = 'undefined'; // keyword or operator or field or ...?
	public $word = '';
	public $fields = null;
	public $var_name;
	public $var_value;


	public static function startWord(SqlParser $parser, $pos) {
		$parser->logDebug(__METHOD__ . " @ $pos");

		$current_word = new self;
		$parser->setCurrentWord($current_word);

		$current_word->start($parser, $pos);
	}


	public function endWord($pos) {
		$this->parser->logDebug(__METHOD__ . " @ $pos");

		$current_word = $this->parser->getCurrentWord();
		if (! $current_word || $current_word !== $this) {
			throw new \Exception("not in a word", 1);
		}

		$this->end($pos);

		$this->word = strtolower($this->outer_text);

		$next_char = substr($this->parser->getSql(), $pos+1, 1);
		if (in_array($this->word, ['group', 'order', 'inner', 'outer', 'left', 'left', 'create', 'drop', 'rename', 'truncate']) && $next_char == ' ') {
			// on ne veut pas creer un nouveau mot. on va concatener ce mot avec le prochain
			return;
		}

		$principal_action = $this->parser->getPrincipalAction();
		$current_action = $this->parser->getCurrentAction();
		$action_items = $this->parser->getActionItems($current_action);

		if (!$principal_action && array_key_exists($this->word, $this->parser->getActions())) {
			// determination de l'action de la requete (select, update, insert, ...)
			$this->word_type = 'action';

			$action = SqlAction::startAction($this->parser, $this->word);
			$this->parser->setCurrentAction($action);

			$action_part = SqlActionPart::startPart($action, $this->word);
			$action->setCurrentPart($action_part);

		} else if (!empty($this->parser->getCurrentAction()) && array_key_exists($this->word, $action_items)) {
			// determination de la partie de la requete dans laquelle on est (select, from, where, group by, ...)
			$this->word_type = 'action_part';

			$current_action = $this->parser->getCurrentAction();

			$part_name = $this->word;

			if (in_array($part_name, ['inner join', 'left join', 'right join', 'left outer join', 'right outer join'])) {
				$part_name = 'join';
			}			

			$action_part = SqlActionPart::startPart($current_action, $part_name);

			$current_action->setCurrentPart($action_part);

		} else if (array_key_exists($this->word, $this->parser->getKeywords())) {
			// le mot correspond à un keyword sql
			$this->word_type = 'keyword';

		} else if (array_key_exists($this->word, $this->parser->getFunctions())) {
			// le mot correspond à un nom de fonction
			$this->word_type = 'function';
		}

		//$this->detectFields();

		$this->parser->addItem($this);
		$this->parser->addWord($this);

		$this->parser->setCurrentWord(null);
	}



	public function toPhp() {
		$outer_text = $this->outer_text;

		if ($this->word_type == 'field') {
			$fields = [];
			
			if ($this->fields) {
				foreach ($this->fields as $field) {
					if (! $field->getTable()) {
						continue;
					}
					$table_name = $field->getTable()->getName();
					$field_name = $field->getName();
					$fields[] = '$row["' . $field_name . '"]';
				}
			}

			if ($fields) {
				$outer_text = implode(', ', $fields);
			}
		}

		if ($this->word_type == 'function') {
			$func_name = $outer_text;

            if (is_callable([SqlExecutor::class, $func_name])) {
				$outer_text = '$executor->' . $func_name;
				
            } else if ($this->parser->allow_php_functions && is_callable($func_name)) {
				$outer_text = $func_name;

			} else {
				throw new \Exception('unknown function ' . $func_name);
			}
		}

		return $outer_text;
	}


	public function toSql($to_php = false, $print_debug = false)
	{
		$sql = '';

        if ($print_debug) {
            $idx = 'X';
            $id = 'item-' . $idx . '-' . $this->type;
            echo '<span id="' . $id . '">';
        }

		if ($this->fields) {
			// word est un field (ou une liste de fields : '*' ou 'mytable.*')
			$parts = [];

			$idx = 0;
			foreach ($this->fields as $field) {
				$parts[] = $field->toSql($to_php, $print_debug);

				if ($print_debug && $idx++ < count($this->fields) - 1) {
					echo ", ";
				}
			}

			$sql .= implode(", ", $parts);

		} else {
			// word est un simple mot

			if ($this->word_type === 'field' && $this->outer_text === '*') {
				// word = *
				$outer_text = 1;

			} else {
				// word normal
                if ($to_php) {
					$outer_text = $this->toPhp();
					
                } else {
					$outer_text = $this->outer_text;
				}
			}
			
            if ($print_debug) {
				echo $outer_text;
			}

			$sql .= $outer_text;
		}

        if ($print_debug) {
            echo '</span>';
        }
	
		
		return $sql;
	}


	public function detectFields()
	{
		// called by SqlActionPartSelect::parsePart

		// $table = $parser->getTableFrom();

		if ($this->word_type == 'undefined') {
			// detection field
			$word = $this->word;

			$is_var = (substr($word, 0, 1) === '@');

			if ($is_var) {
				$this->word_type = 'variable_sql';
				$this->var_name = ltrim($word, '@');
				$this->var_value = null;
				return;
			}

			$table_alias = null;
			$field_name = $word;

			$parts = explode('.', $word);
			if (count($parts) > 1) {
				$table_alias = $parts[0];
				$field_name = $parts[1];
			}

			if ($table_alias) {
				// alias de table spécifié

				$table = null;
				$from_table = $this->parent->getAction()->getTableFrom();

				if (!$from_table) {
					throw new \Exception("missing from table", 1);
				}

				$tables = $this->action->getTables(); // TODO: utiliser getTables au lieu du code ci-dessous
				$debug_here = 1;

				if ($from_table->getAlias() === $table_alias) {
					$table = $from_table;
				}

				if (! $table) {
					// TODO: search field in join tables & subqueries tables

					$join_tables = $this->parent->getAction()->getTablesJoin();
					foreach ($join_tables as $join_table) {
						if ($join_table->getAlias() === $table_alias) {
							$table = $join_table;
							break;
						}
					}

					$subqueries_tables = $this->parent->getAction()->getTablesSubqueries();
					foreach ($subqueries_tables as $subquery_table) {
						if ($subquery_table->getAlias() === $table_alias) {
							$table = $subquery_table;
							break;
						}
					}

				}

				if ($field_name == '*') {
					// field = mytable.*
					$this->word_type = 'field';

					if ($this->parent && get_class($this->parent) !== SqlParenthese::class) {
						// si on n'est pas dans une parenthese, pour s'assurer que ce n'est pas un "count(*)" (à revoir, car risque de ne pas fonctionne avec les suos-requetes)

						$this->fields = [];

						if ($table) {
							$fields_names = $table->getFieldsNames();

							if (!empty($fields_names)) {
								foreach ($fields_names as $tmp_field_name) {
									$field = new SqlField($tmp_field_name, $table);
									$field->parent = $this;
									$field_alias = $field->getAlias();

									$this->fields[$field_alias] = $field;
								}
							}

						} else {
							// table inconnue
							$this->fields['UNKNOWN_TABLE_A.*'] = new SqlField($table_alias . '.*', null);
						}
					}

				} else {
					// field = mytable.myfield

					if ($table) {
						//$table_name = $table->getName();

						$fields_names = $table->getFieldsNames();

						if ($fields_names && in_array($field_name, $fields_names)) {
							$this->word_type = 'field';

							$field = new SqlField($field_name, $table);
							$field->parent = $this;
							$field_alias = $field->getAlias();

							$this->fields = [
								$field_alias => $field,
							];
						}
						
					} else {
						// table inconnue
						$this->fields['UNKNOWN_TABLE_B.' . $field_name] = new SqlField($table_alias . '.' . $field_name, null);

						throw new \Exception("table inconnue", 1);

					}
				}


			} else {
				// alias de table non précisé

				if ($field_name == '*') {
					// field = *

					$this->word_type = 'field';

					if ($this->parent && get_class($this->parent) !== SqlParenthese::class) {
						$this->fields = [];

						//$tables = [];
						//$tables[] = $this->parent->action->getTableFrom();
						// TODO: inclure les tables de join (mais pas de subqueries)
						
						$tables = $this->action->getTables(); // TODO: utiliser getTables au lieu du code ci-dessous

						foreach ($tables as $tmp_table_name => $table) {
							$fields_names = $table->getFieldsNames();

							if (!empty($fields_names)) {
								foreach ($fields_names as $tmp_field_name) {
									$table = null; //new SqlTable($tmp_table_name); // TODO: ne pas creer la table maintenant, car on peut ne connaitre que son alias

									$field = new SqlField($tmp_field_name, $table);
									$field->parent = $this;
									$field_alias = $field->getAlias();

									$this->fields[$field_alias] = $field;
								}
								unset($tmp_field_name);
							}

						}
						unset($tmp_table_name);
						unset($table);

					}

				} else {
					// field = myfield
					// field_name précisé (sans alias de table)

					$tables = $this->action->getTables();

					foreach ($tables as $table) {
						$fields_names = $table->getFieldsNames();

						if ($fields_names && in_array($field_name, $fields_names)) {
							$this->word_type = 'field';

							$field = new SqlField($field_name, $table);
							$field->parent = $this;
							$field_alias = $field->getAlias();

							$this->fields = [
								$field_alias => $field,
							];
							break;
						}
					}
				}
			}
		}
	}


	public function getCalculatedValues(SqlExecutor $executor, $row_data)
	{
        if ($this->word_type === 'field_name') {
			// when field_name is calculated by the "order by"
			$field_alias = $this->word;
			return [
				$field_alias => $executor->current_result[$field_alias],
			];

        } else if ($this->word_type === 'variable_sql') {
			$var_name = $this->var_name;

			$database = $this->parser->getDatabase();
			$var = isset($database['_variables'][$var_name]) ? $database['_variables'][$var_name] : null;

			return [
				'@' . $var_name => $var,
			];

        } else {
			throw new \Exception("unknown word '" . $this->word . "'. don't known how to evaluate", 1);
		}

	}

}
