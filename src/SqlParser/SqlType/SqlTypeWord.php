<?php

namespace SqlParser\SqlType;

use \SqlParser\SqlAction\SqlAction;
use \SqlParser\SqlExecutor;
use \SqlParser\SqlField;
use \SqlParser\SqlFragment\SqlFragment;
use \SqlParser\SqlFragment\SqlFragmentAction;
use \SqlParser\SqlFragment\SqlFragmentPart;
use \SqlParser\SqlFragment\SqlFragmentMain;
use \SqlParser\SqlFunction;
use \SqlParser\SqlParser;
use \SqlParser\SqlPart\SqlPart;


class SqlTypeWord extends SqlType
{
	public $type = 'word';
	public $word_type = 'undefined'; // keyword or operator or field or ...?
	public $word = '';

	// for table fields names OR for joker
	public $fields = null;

	// for functions only
	public $var_name;
	public $var_value;


	public function __construct(SqlFragmentMain $fragment_main, $pos, $word_type='undefined')
	{
		$fragment_main->logDebug(__CLASS__ . " @ $pos");

		parent::__construct($fragment_main, $pos);
		
		//$fragment_main->addWord($this);
		$this->word_type = $word_type;
    }


	public function endWord($pos) {
		$this->fragment_main->logDebug(__METHOD__ . " @ $pos");

		$current_word = $this->fragment_main->getCurrentWord();
		if (! $current_word || $current_word !== $this) {
			throw new \Exception("not in a word", 1);
		}

		$this->end($pos, false);

		$this->word = strtolower($this->outer_text);

		$next_char = substr($this->fragment_main->getSql(), $pos+1, 1);
		if (in_array($this->word, ['group', 'order', 'inner', 'outer', 'left', 'left', 'create', 'drop', 'rename', 'truncate']) && $next_char == ' ') {
			// on ne veut pas creer un nouveau mot. on va concatener ce mot avec le prochain
			return;
		}

		$query_action = $this->query->getAction(); // principal action of the current query
		$current_action = $this->query->getAction2() ?: $query_action;  // principal action of the current query => can be different of $query_action only if "insert ... select"
		$action_items = $current_action ? $this->fragment_main->getActionItems($current_action) : [];

		// SEARCH FOR ACTION
		if ($this->word_type === 'undefined') {
			if (array_key_exists($this->word, $this->fragment_main->getParser()->getSqlActions())) {
				// determination de l'action de la requete (select, update, insert, ...)

				if (! $query_action) {
					// principal action detected
					$this->word_type = 'action';
					$action_name = $this->word;

					$query_action = SqlAction::startAction($this->fragment_main->getCurrentQuery(), $action_name);
					$this->fragment_main->setCurrentAction($query_action);
					$this->action = $query_action;
					
					$action_part = SqlPart::startPart($query_action, $action_name);
					$query_action->setCurrentPart($action_part);
					$this->part = $action_part;

					$this->parent = $action_part;
					//$this->setParents();
					
				} else{
					// principal action already defined

					if ($query_action->getName() === 'select' && $this->word === 'desc') {
						// "desc" after a "select" action
						$this->word_type = 'keyword';
						
					} else if ($query_action->getName() === 'update' && $this->word === 'set') {
						// "set" after an "update" action
						//$this->word_type = 'action_part';
						
						//$part_name = $this->word;
						//$action_part = SqlPart::startPart($current_action, $part_name);
						//$current_action->setCurrentPart($action_part);

					} else if ($query_action->getName() === 'insert' && $this->word === 'select') {
						// "select" after a "insert" action
						// an action_part will be defined in the next code block

						//throw new \Exception("debug me. insert select", 1);
						
						$this->word_type = 'action';
						$action_name = $this->word;
		
						$query_action = SqlAction::startAction($this->fragment_main->getCurrentQuery(), $action_name);
						$this->fragment_main->setCurrentAction($query_action);
						$this->action = $query_action;
		
						$action_part = SqlPart::startPart($query_action, $action_name);
						$query_action->setCurrentPart($action_part);
						$this->part = $action_part;

						$this->parent = $action_part;

						$this->fragment_main->setPrevCharNoSpace(null);

			
					} else {
						throw new \Exception("principal action already defined");
					}

				}
			}
		}

		// SEARCH FOR PART
		if ($this->word_type === 'undefined') {
			if ($this->query->getCurrentAction() && array_key_exists($this->word, $action_items)) {
				// determination de la partie de la requete dans laquelle on est (select, from, where, group by, ...)
				$this->word_type = 'action_part';
				$part_name = $this->word;
				$current_action = $this->query->getCurrentAction();
		
				if (in_array($part_name, ['inner join', 'left join', 'right join', 'left outer join', 'right outer join'])) {
					$part_name = 'join';
				}			
	
				$action_part = SqlPart::startPart($current_action, $part_name);
				$current_action->setCurrentPart($action_part);


				if (true) {
					// on réaffecte le parent et la part de l'item
					//throw new \Exception("debug me", 1);
					$this->part = $action_part;
					$this->parent = $action_part;

					//$this->setParents();
				}

			}
		}
		
		// SEARCH FOR KEYWORD
        if ($this->word_type === 'undefined') {
			if (array_key_exists($this->word, $this->fragment_main->getParser()->getSqlKeywords())) {
				// le mot correspond à un keyword sql
				$this->word_type = 'keyword';
				
			}
		}
		
		// SEARCH FOR FUNCTION
        if ($this->word_type === 'undefined') {
			if (array_key_exists($this->word, $this->fragment_main->getParser()->getSqlFunctions())) {
				// le mot correspond à un nom de fonction
				$this->word_type = 'function_sql';
				
			}
		}
		
		// SEARCH FOR TABLE PREFIXED FIELD
        if ($this->word_type === 'undefined') {
            if (strpos($this->word, '.') !== false) {
				$parts = explode('.', $this->word);

				if (count($parts) > 1) {
					$this->word_type = 'field_undefined';

					//$this->detectFields(); // a faire ici ? ou plus tard ? => ici on ne connait pas encore la liste des tables car le from n'a pas encore été parsé
				}


            }
        }

		// SEARCH FOR JOKER
        if ($this->word_type === 'undefined') {
			if ($this->word === '*') {
				//$this->word_type = 'joker'; // on prefere laisser undefined. Ensuite, la fonction "detectFields" determinera le bon type de joker
				$debug = 1;
				$this->word_type = 'joker_undefined';

				
			} else {
				// here, $word must be a field. Else it is an error
				$debug = 1;

			}
		}
		
		// SEARCH FOR PHP VARIABLE
        if ($this->word_type === 'undefined') {
			if (substr($this->word, 0, 1) === '$') {
				$this->word_type = 'variable_php';
            }
        }
		
		// ALL OTHER CASES
        if ($this->word_type === 'undefined') {
			// undefined word type => it can be a table name or a field name
			$debug = 1;
			//throw new \Exception("non implemented case", 1);
        }


		$this->addItemToParents();

		
		if (! $this->parent) {
			throw new \Exception("missing parent");
		}

		if (! $this->action) {
			throw new \Exception("missing action");
		}

		if (! $this->query) {
			throw new \Exception("missing query");
		}

		$this->fragment_main->setCurrentWord(null);
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

		} else if ($this->word_type == 'joker_function') {
			$outer_text = '1';

		} else if ($this->word_type == 'function_sql') {
			$func_name = $outer_text;

            if (is_callable([SqlFunction::class, $func_name])) {
				$outer_text = '$functions_repository->' . $func_name;
			
			/*
            } else if ($this->fragment_main->allow_php_functions && is_callable($func_name)) {
				$outer_text = $func_name;
			*/

			} else {
				throw new \Exception('unknown function ' . $func_name);
			}
			
		} else if ($this->word_type == 'function_php') {
			if (! $this->fragment_main->allow_php_functions) {
				throw new \Exception('PHP functions are not allowed');
			}
			
		} else if ($this->word_type == 'variable_php') {
			if (! $this->fragment_main->allow_php_variables) {
				throw new \Exception('PHP variables are not allowed');
			}

        } else {
			throw new \Exception("unknown case", 1);
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

		// $table = $fragment->getTableFrom();

		if (in_array($this->word_type, ['undefined', 'joker_undefined', 'field_undefined'])) {
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
				$from_table = $this->parent->getQuery()->getCurrentAction()->getTableFrom();

				if (!$from_table) {
					throw new \Exception("missing from table", 1);
				}
				
				$tables = $this->action->getTables(); // TODO: utiliser getTables au lieu du code ci-dessous
				$debug_here = 1;
				
				if (empty($tables[$table_alias])) {
					throw new \Exception("unknown table " . $table_alias, 1);
				}

				$table = $tables[$table_alias];


				if ($field_name == '*') {
					// field = mytable.*
					$this->word_type = 'joker_table';

					if ($this->parent && get_class($this->parent) !== SqlTypeParenthese::class) {
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

					if ($this->parent && get_class($this->parent) !== SqlTypeParenthese::class) {
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

					$tables = $this->action->getTables(); // TODO: a revoir pour le "insert ... select" car ici $this->action pointe alors vers le SqlActionInsert et non vers le SqlActionSelect

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
        if ($this->word_type === 'field') {
			// when field_name is calculated by the "order by"
			$field_alias = $this->word;
			return [
				$field_alias => $executor->current_result[$field_alias],
			];

        } else if ($this->word_type === 'joker_undefined') {
            return [
                '*' => 1,
            ];

			
        } else if ($this->word_type === 'variable_sql') {
			$var_name = $this->var_name;

			$database = $this->fragment_main->getParent()->getDatabase();
			$var = isset($database['_variables'][$var_name]) ? $database['_variables'][$var_name] : null;

			return [
				'@' . $var_name => $var,
			];

        } else {
			throw new \Exception("unknown word '" . $this->word . "'. don't known how to evaluate", 1);
		}

	}

}
