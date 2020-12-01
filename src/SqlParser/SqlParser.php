<?php

namespace SqlParser;


class SqlParser
{
	// CONFIG VARIABLES
	public $allow_php_functions = false;
	public $allow_php_variables = false;


	// SYSTEM VARIABLES

	protected $sql;
	protected $database = null;
	//protected $pos = null;
	//protected $word = null;

	protected $items = [];
	protected $queries = [];

	public $parse_duration = null;

	protected $current_query = null;

	protected $current_action = null;

	protected $current_space = null;

	protected $current_operator = null;
	protected $operators = [];

	protected $current_comparator = null;
	protected $comparators = [];

	protected $strings = [];
	protected $current_string = null;

	protected $numerics = [];
	protected $current_numeric = null;

	protected $comments = [];
	protected $current_comment = null;

	protected $parentheses = [];
	protected $current_parenthese = null;

	protected $words = [];
	protected $current_word = null;

	protected $actions = [
		'select' => [
			'select' => 1,
			'from' => 1,
			'where' => 1,
			'join' => 1,
			'inner join' => 1,
			'left join' => 1,
			'right join' => 1,
			'left outer join' => 1,
			'right outer join' => 1,
			'group by' => 1,
			'order by' => 1,
			'limit' => 1,
			// TODO: gerer les join
		],
		'update' => [
			'update' => 1,
			'from' => 1,
			'where' => 1,
			'group by' => 1,
			'order by' => 1,
			'limit' => 1,
			'set' => 1,
			// TODO: gerer les join
		],
		'insert' => [
			'into' => 1,
			'values' => 1,
			'select' => 1,
			'from' => 1,
			'where' => 1,
			'join' => 1,
			'inner join' => 1,
			'left join' => 1,
			'right join' => 1,
			'left outer join' => 1,
			'right outer join' => 1,
			'group by' => 1,
			'order by' => 1,
			'limit' => 1,
		],
		'delete' => [
			'from' => 1,
			'where' => 1,
			'order by' => 1,
			'limit' => 1,
		],
		'set' => [
			'set' => 1,
		],
		'create table' => [
			'create table' => 1,
		],
		'drop table' => [
			'drop table' => 1,
		],
		'rename' => [
			'rename table' => 1,
		],
		'truncate table' => [
			'truncate table' => 1,
		],
		'replace' => 1,
		'show' => 1,
		'desc' => 1,
		'alter' => 1,
		'use' => 1,
	];

	protected $keywords = [
		'left' => 1,
		'right' => 1,
		'inner' => 1,
		'outer' => 1,
		'join' => 1,
		'as' => 1,
		'like' => 1,
		'and' => 1,
		'or' => 1,
		'not' => 1,
		'in' => 1,
		'distinct' => 1,
		'desc' => 1,
		'offset' => 1,
		'using' => 1,
		'on' => 1,
	];

	protected $functions = [
		'version' => 1,
		'curl' => 1, // test

		'if' => 1,
		'ifnull' => 1,
		'isnull' => 1,

		'now' => 1,
		'current_timestamp' => 1,
		'current_date' => 1,
		'current_time' => 1,
		'date' => 1,
		'time' => 1,
		'year' => 1,
		'month' => 1,
		'day' => 1,
		'hour' => 1,
		'minute' => 1,
		'second' => 1,
		'date_add' => 1,
		'date_sub' => 1,
		'datediff' => 1,

		'count' => 1,
		'abs' => 1,
		'rand' => 1,
		'round' => 1,
		'floor' => 1,
		'ceil' => 1,
		'sqrt' => 1,
		'pow' => 1,
		'pi' => 1,

		'sum' => 1,
		'avg' => 1,
		'min' => 1,
		'max' => 1,
		'least' => 1,
		'greatest' => 1,

		'length' => 1,
		'lower' => 1,
		'upper' => 1,
		'mid' => 1,
		'left' => 1,
		'right' => 1,
		'replace' => 1,
		'locate' => 1,
		'concat' => 1,
		'substr' => 1,
		'substring' => 1,
		'substring_index' => 1,
		'trim' => 1,
		'rtrim' => 1,
		'ltrim' => 1,

		'group_concat' => 1,
		'last_insert_id' => 1,
		'md5' => 1,
		'encrypt' => 1,
		'password' => 1,
		'old_password' => 1,
	];



	public function __construct($sql, & $database=[])
	{
		$this->sql = $sql;
		$this->database = & $database;
	}



	public function parse()
	{
		$this->parseSQL();
	}

	
	public function parseSQL()
	{
		$ts_start = microtime(true);

		$sql_len = strlen($this->sql);

		
		if (empty($this->current_query)) {
			$this->current_query = new SqlQuery;
			$this->queries[] = $this->current_query;
		}

		$prev_char_no_space = null;

		// STEP 1 : browse each character

		$pos = -1;
		while ($pos < $sql_len - 1) {
			$pos++;

			$char = substr($this->sql, $pos, 1);
			$char_id = ord($char);

			$prev_char = substr($this->sql, $pos-1, 1);
			$next_char = substr($this->sql, $pos+1, 1);

			$is_underscore = ($char === '_');
			$is_asterisk = ($char === '*'); // TODO: creer une class SqlJoker
			$is_dot = ($char === '.');
			$is_arobase = ($char === '@');
			$is_semicolon = ($char === ';');
			$is_dollar = ($char === '$');
			$is_comma = ($char === ',');
			$is_exclamation = ($char === '!'); // note: a gerer comme le keyword "not"
			$is_numeric = ($char_id >= 48 && $char_id <= 57);
			$is_alpha_lower = ($char_id >= 65 && $char_id <= 90);
			$is_alpha_upper = ($char_id >= 97 && $char_id <= 122);
			$is_space = in_array($char_id, [9, 10, 13, 32]);
			$is_comparator = in_array($char, ['=', '<', '>']) || ($is_exclamation && $next_char === '=');
			$is_operator = in_array($char, ['+', '-', '%', '^', '&', '|']) || ($char === '*' && $prev_char !== '/' && $next_char !== '/') || ($char === '/' && $prev_char !== '*' && $next_char !== '*');
			$is_joker  = $is_asterisk && in_array($prev_char_no_space, ['', ',']); // on capture ici seulement le joker global "*" (et pas les variantes comme "mytable.*" ou "count(*)")
			$is_joker2 = $is_asterisk && in_array($prev_char_no_space, ['.', '(']); // on capture ici seulement les variantes comme "mytable.*" ou "count(*)" (et pas le joker global "*")

			if ($is_asterisk) {
				$debug = 1;
			}
			//echo "debug character at position $pos : $char<hr >";

			if ($this->current_action && !$is_space) {
				$prev_char_no_space = $char;
			}

			// fin de space ?
			if ($this->current_space) {
				// on est dans un space
				if ($is_space) {
					// on continue le space
					//$this->current_space->append($char);
					continue;
				} else {
					// fin de space
					$this->current_space->endSpace($pos-1);
				}
			}


			//if ($is_asterisk && ! $this->current_word && ! $this->current_string && ! $this->current_comment) {
			if ($is_joker2 && ! $this->current_string && ! $this->current_comment) {
				// on identifie s'il s'agit de l'operator * ou du regroupeur de champs (comme dans count(*) ou mytable.* )

				// Ce code ne detecte que les mytable.* (et non les "*", qui sont gérés comme des operators)

				if (in_array($prev_char, ['.', '('])) {
					// TODO: gerer s'il y a des espaces entre "(" et "*"

					SqlWord::startWord($this, $pos);
					$this->current_word->endWord($pos);
					
					//SqlNumeric::startNumeric($this, $pos);
					//$this->current_numeric->endNumeric($pos);

					//$is_operator = false;
					continue;
				}
			}


			// fin de operator ?
			if ($this->current_operator) {
				// on est dans un operator
				if ($is_operator) {
					// on continue le operator
					$this->current_operator->append($char);
					continue;
				} else {
					// fin de operator
					$this->current_operator->endOperator($pos-1);
				}
			}

			// fin de comparator ?
			if ($this->current_comparator) {
				// on est dans un comparator
				if ($is_comparator) {
					// on continue le comparator
					$this->current_comparator->append($char);
					continue;
				} else {
					// fin de comparator
					$this->current_comparator->endComparator($pos-1);
				}
			}

			// fin de word ?
			if ($this->current_word) {
				// on est dans un word
				if ($is_alpha_lower || $is_alpha_upper || $is_underscore || $is_numeric || $is_dot || $is_dollar || ($is_asterisk && $prev_char === '.') || $char === '[' || $char === ']' || $char === '"' || $char === "'") {
					// on continue le word
					$this->current_word->append($char);
					continue;
				} else {
					// fin de word
					$this->current_word->endWord($pos-1);
				}
			}

			// fin de numeric ?
			if ($this->current_numeric) {
				// TODO: si on est dans un numeric (sans point), on peut basculer sur un word (exemple: "2pi" )

				// on est dans un numeric
				if ($is_numeric || $is_dot) {
					// on continue le number
					$this->current_numeric->append($char);
					continue;
				} else {
					// fin de number
					$this->current_numeric->endNumeric($pos-1);
				}
			}



			// debut/fin de comments ?
			if ($this->current_comment) {
				// on est dans un commentaire
				$comment_type = $this->current_comment->isCommentEnd($char, $next_char);
				if ($comment_type && $comment_type == $this->current_comment->comment_type) {
					// fin du commentaire
					$enclosure_end_len = strlen($this->current_comment->enclosure_end);
					$this->current_comment->endComment($pos, $comment_type);
					if ($enclosure_end_len > 1) {
						$pos += $enclosure_end_len-1;
					}
				} else {
					//throw new \Exception("debug me", 1);
				}
				unset($comment_type);
				
				continue;
			} else {
				// on n'est PAS dans un commentaire
				$comment_type = SqlComment::isCommentStart($this, $char, $next_char);
				if ($comment_type) {
					// debut d'un commentaire
					SqlComment::startComment($this, $pos, $comment_type);
					unset($comment_type);
					continue;
				}
				unset($comment_type);
			}


			// debut/fin de strings ?
			if ($this->current_string) {
				// on est dans un string
				$string_type = $this->current_string->isStringEnd($char, $next_char);
				if ($string_type && $this->current_string->string_type == $string_type) {
					// fin du string
					$this->current_string->endString($pos, $string_type);
				}
				unset($string_type);
				continue;

			// TODO: revoir la detection des strings imbriquées (ex: concat('<b>', '<span class="error">xx</span>', '</b>') )
			} else {
				// on n'est PAS dans un string

				if (! $this->current_word) {
					$string_type = SqlString::isStringStart($this, $char, $next_char);
					if ($string_type) {
						// debut d'un string
						SqlString::startString($this, $pos, $string_type);
						unset($string_type);
						continue;
					}
					unset($string_type);
				}
			}


			// debut/fin de parentheses ?
			if ($this->current_parenthese) {
				// on est dans une parenthese
				$is_parenthese_end = $this->current_parenthese->isParentheseEnd($char);
				if ($is_parenthese_end) {
					// fin du la parenthese
					$this->current_parenthese->endParenthese($pos);
					unset($is_parenthese_end);
					continue;
				}
				unset($is_parenthese_end);

				$parenthese_type = SqlParenthese::isParentheseStart($this, $char);
				if ($parenthese_type) {
					// debut d'une parenthese (de niveau 2+)
					SqlParenthese::startParenthese($this, $pos);
					unset($parenthese_type);
					continue;
				}
				unset($parenthese_type);
			} else {
				// on n'est PAS dans une parenthese
				$parenthese_type = SqlParenthese::isParentheseStart($this, $char);
				if ($parenthese_type) {
					// debut d'une parenthese (de niveau 1)
					SqlParenthese::startParenthese($this, $pos);
					unset($parenthese_type);
					continue;
				}
				unset($parenthese_type);
			}



			// debut de word ?
			if (! $this->current_word) {
				// on n'est PAS dans un mot
				if ($is_alpha_lower || $is_alpha_upper || $is_arobase || $is_underscore || $is_dollar) {
					SqlWord::startWord($this, $pos);
					continue;
				}
			}


			// debut de numeric ?
			if (! $this->current_numeric) {
				// on n'est PAS dans un numeric
				if ($is_numeric) {
					SqlNumeric::startNumeric($this, $pos);
					continue;
				}
			}


			// debut de space ?
			if (! $this->current_space) {
				// on n'est PAS dans un space
				if ($is_space) {
					if ($this->current_word) {
						// cas des mots composés (alter table, order by, group by, inner join, ...)
						$this->current_word->append($char);
					} else {
						SqlSpace::startSpace($this, $pos);
					}
					continue;
				}
			}


			// virgule ?
			if ($is_comma) {
				SqlComma::startEndComma($this, $pos);
				continue;
			}


			// joker ?
			if ($is_joker) {
				SqlJoker::startEndJoker($this, $pos);
				continue;
			}


			// operator ?
			if ($is_operator) {
				SqlOperator::startOperator($this, $pos);
				continue;
			}


			// comparator ?
			if ($is_comparator) {
				SqlComparator::startComparator($this, $pos);
				continue;
			}


			if ($is_semicolon) {
				// fin de requete. on prepare une nouvelle requete
				$this->current_query = new SqlQuery;
				$this->queries[] = $this->current_query;
				$prev_char_no_space = null;
				$this->current_action = null;
				continue;
			}


			echo "unknown character at position $pos : $char<hr >";
			throw new \Exception("debug me", 1);
			pre($this, 1);
		}



		if ($this->current_space) {
			$this->current_space->endSpace($pos);
		}

		if ($this->current_operator) {
			$this->current_operator->endOperator($pos);
		}

		if ($this->current_comparator) {
			$this->current_comparator->endComparator($pos);
		}

		if ($this->current_string) {
			$this->current_string->endString($pos);
		}

		if ($this->current_numeric) {
			$this->current_numeric->endNumeric($pos);
		}

		if ($this->current_comment) {
			$this->current_comment->endComment($pos, null);
		}

		if ($this->current_word) {
			$this->current_word->endWord($pos);
		}

		if ($this->current_parenthese) {
			$this->current_parenthese->endParenthese($pos);
		}

		if ($this->current_action) {
			$this->current_action->setCurrentPart(null);
		}
		$this->current_action = null;


		$ts_end = microtime(true);
		$this->parse_duration = $ts_end - $ts_start;


		if (! $this->current_query->principal_action) {
			// aucune requete trouvée
			return [];
		}
	}


	public function getParsedSql()
	{
		if (empty($this->parse_duration)) {
			$this->parseSQL();
		}

		$sqls = [];

		foreach ($this->queries as $query) {
			$sqls[] = $query->rebuildSql(false, false);
		}

		$sql = implode("; ", $sqls);

		return $sql;
	}


	public function showParsedSql()
	{
		if (empty($this->parse_duration)) {
			$this->parseSQL();
		}

		echo '<div>';

		foreach ($this->queries as $query_idx => $query) {
			if (empty($query->principal_action)) {
				continue;
			}

			if (true) {
				if (empty($query->parse_duration)) {
					$query->parseQuery();
				}
			}

			echo '<div class="jumbotron p-2">';

			echo '<h5>QUERY #' . ($query_idx+1) . ' - ' . strtoupper($query->principal_action->getName()) . ' - REWRITTEN QUERY</h5>';
			$query->rebuildSql(false, true);
			
			echo '</div>';
		}

		echo '</div>';

	}


	public function execute()
	{
		if (is_null($this->parse_duration)) {
			$this->parseSQL();
		}

		$results = null;

		foreach ($this->queries as $query) {
			$debug = $this->database;
			$results = $query->execute();
		}

		$this->results = $results;
		
		return $this->results;
	}


	public function showInputSql()
	{
		echo '<div class="jumbotron p-3">';
		echo '<h5>Input SQL</h5>';
		echo '<pre>' . ($this->sql) . '</pre>';
		echo '</div>';
		//echo '<hr />';
	}

	public function showResults()
	{
		echo '<div class="jumbotron p-3">';
		echo '<h5>SQL parsing</h5>';
		echo '<div><small>SQL parsing duration: ' . round($this->parse_duration, 5) . ' second</small></div>';
		echo '</div>';

        foreach ($this->queries as $query_idx => $query) {
            $this->showQueryResults($query, $query_idx);
        }
	}


	public function showQueryResults(SqlQuery $query, $query_idx=0)
	{
		if (empty($query->principal_action)) {
			return;
		}

		$results = $query->results;

		if (! $results) {
			$results = [];
		}

		
		$html = '';
		$html .= '<div class="jumbotron p-3">';

		$html .= '<h5>QUERY #' . ($query_idx+1) . ' - ' . strtoupper($query->principal_action->getName()) . ' - RESULTS</h5>';

		$html .= '<table class="table table-hover table-striped table-bordered bg-light" border="1" width="100%">';
		$html .= '	<thead>';
		$html .= '		<tr>';

        if ($results) {
            $rows = array_values($results);

            foreach ($rows[0] as $field_name => $value) {
                $html .= '			<td>' . $field_name  . '</td>';
            }

            $html .= '		</tr>';
            $html .= '	</thead>';
            $html .= '	<tbody>';

            foreach ($rows as $result_idx => $result) {
                $html .= '		<tr>';
                
                foreach ($result as $field_name => $value) {
					if (is_null($value)) {
						$value = '<i>NULL</i>';
					}
                    $html .= '			<td>' . $value  . '</td>';
                }
                
                $html .= '		</tr>';
            }
        }

		$html .= '	</tbody>';
		$html .= '</table>';

		$result_name = in_array($query->principal_action->getName(), ['select']) ? 'results' : 'affected rows';

		$html .= '<div><small>Query parsing duration: ' . round($query->parse_duration, 5) . ' second</small></div>';
		$html .= '<div><small>Execute duration: ' . round($query->execute_duration, 5) . ' second</small><small> (' . count($results) . ' ' . $result_name . ')</small></div>';

		$html .= '</div>';

		echo $html;
	}
	
	
	public function showDatabase()
	{
		//echo '<hr /><pre>DATABASE: ' . print_r($this->database, true) . '</pre>';

		foreach ($this->database as $table_name => $table) {
			$this->showDatabaseTable($table, $table_name);
		}
	}
	
	
	public function showDatabaseTable(array $data_table, $table_name=null)
	{
		//echo '<hr /><pre>DATABASE - TABLE `' . $table_name . '` : ' . PHP_EOL . print_r($table, true) . '</pre>';

		if ($table_name == '_variables') {
			return;
		}

		$html = '<h5>DATABASE - TABLE `' . $table_name . '`</h5>';

		$html .= '<div class="jumbotron p-3">';

		$html .= '<table class="table table-hover table-striped table-bordered bg-light" border="1" width="100%">';
		$html .= '	<thead>';
		$html .= '		<tr>';

        if (! empty($data_table)) {
            $rows = array_values($data_table);

            foreach ($rows[0] as $field_name => $value) {
                $html .= '			<td>' . $field_name  . '</td>';
            }

            $html .= '		</tr>';
            $html .= '	</thead>';
            $html .= '	<tbody>';

            foreach ($rows as $result_idx => $result) {
                $html .= '		<tr>';
                
                foreach ($result as $field_name => $value) {
					if (is_null($value)) {
						$value = '<i>NULL</i>';
					}
                    $html .= '			<td>' . $value  . '</td>';
                }
                
                $html .= '		</tr>';
            }
        }

		$html .= '	</tbody>';
		$html .= '</table>';

		$html .= '</div>';

		echo $html;
	}


	/* GETTERS */

	public function getDatabase()
	{
		return $this->database;
	}

	public function getDatabaseTable($table_name)
	{
		return isset($this->database[$table_name]) ? $this->database[$table_name] : null;
	}

	public function getPrincipalAction()
	{
		if (empty($this->current_query)) {
			throw new \Exception("missing current query", 1);
		}
		return $this->current_query->principal_action;
	}

	public function getCurrentAction()
	{
		return $this->current_action;
	}

	public function getFunctions()
	{
		return $this->functions;
	}

	public function getActions()
	{
		return $this->actions;
	}

	public function getActionItems($action)
	{
		return ($action && $this->actions[$action->getName()]) ? $this->actions[$action->getName()] : [];
	}

	public function getKeywords()
	{
		return $this->keywords;
	}

	public function getSql()
	{
		return $this->sql;
	}

	public function getCurrentComment()
	{
		return $this->current_comment;
	}

	public function getCurrentString()
	{
		return $this->current_string;
	}

	public function getCurrentParenthese()
	{
		return $this->current_parenthese;
	}

	public function getCurrentWord()
	{
		return $this->current_word;
	}

	public function getCurrentNumeric()
	{
		return $this->current_numeric;
	}

	public function getCurrentSpace()
	{
		return $this->current_space;
	}

	public function getCurrentOperator()
	{
		return $this->current_operator;
	}

	public function getCurrentComparator()
	{
		return $this->current_comparator;
	}

	public function getWords()
	{
		return $this->words;
	}



	/* SETTERS */

	public function setDatabase($database, $table_name=null)
	{
		//$this->database = $database;

		if (is_null($this->database)) {
			$this->database = [];
		}

		foreach ($database as $tmp_table_name => $table) {
			if (! is_null($table_name) && $tmp_table_name !== $table_name) {
				continue;
			}
			$this->database[$tmp_table_name] = $table;
		}

	}

	public function setSql($sql)
	{
		$this->sql = $sql;
	}

	public function setCurrentAction(SqlAction $action)
	{
		if (empty($this->current_query)) {
			throw new \Exception("missing current query", 1);
		}

		if (!$this->current_query->principal_action) {
			$this->current_query->principal_action = $action;
		}

		$this->current_action = $action;
	}

	public function setCurrentComment($comment)
	{
		$this->current_comment = $comment;
	}

	public function setCurrentString($string)
	{
		$this->current_string = $string;
	}

	public function setCurrentParenthese($parenthese)
	{
		$this->current_parenthese = $parenthese;
	}

	public function setCurrentWord($word)
	{
		$this->current_word = $word;
	}

	public function setCurrentNumeric($numeric)
	{
		$this->current_numeric = $numeric;
	}

	public function setCurrentSpace($space)
	{
		$this->current_space = $space;
	}

	public function setCurrentOperator($operator)
	{
		$this->current_operator = $operator;
	}

	public function setCurrentComparator($comparator)
	{
		$this->current_comparator = $comparator;
	}


	public function LoadTableToDatabase($table_name, array $data_table)
	{
		$this->database[$table_name] = $data_table;
	}



	/* ADDERS */

	public function addItem(SqlParseItem $item)
	{
		//$this->items[] = $item;

		$current_parenthese = $this->getCurrentParenthese();
		if ($current_parenthese) {
			$parent = $current_parenthese;

		} else {
			$current_action = $this->getCurrentAction();

			if (! $current_action) {
				
				if ($item->type === 'space') {
					// skip error for spaces
					return;
				}
				
				if ($item->type === 'comment') {
					// skip error for comments
					return;
				}
				//if (count($this->getWords()) > 1) {


					throw new \Exception("missing current_action", 1);
				//}
				
			} else {
				// on a defini le type de query (select, update, insert, ...)
				$current_part = $current_action->getCurrentPart();

				if (! $current_part) {
					throw new \Exception("missing current_part", 1);
					//$parent = $this;
					$parent = null;

				} else {
					// on a defini dans quel partie de la query on est (from, where, group by, ...)
					$parent = $current_part;
				}
			}
		}

		$parent->addItem($item);
		
	}

	public function addComment(SqlComment $comment)
	{
		$this->comments[] = $comment;
	}

	public function addString(SqlString $string)
	{
		$this->strings[] = $string;
	}

	public function addParenthese(SqlParenthese $parenthese)
	{
		$this->parentheses[] = $parenthese;
	}

	public function addWord(SqlWord $word)
	{
		$this->words[] = $word;
	}

	public function addNumeric(SqlNumeric $number)
	{
		$this->numerics[] = $number;
	}

	public function addSpace(SqlSpace $space)
	{
		$this->spaces[] = $space;
	}

	public function addOperator(SqlOperator $operator)
	{
		$this->operators[] = $operator;
	}

	public function addComparator(SqlComparator $comparator)
	{
		$this->comparators[] = $comparator;
	}



	public function logDebug($str)
	{
		//echo "$str<hr />";
	}



	/**
	 * Get the value of queries
	 */ 
	public function getQueries()
	{
		return $this->queries;
	}

	/**
	 * Set the value of queries
	 *
	 * @return  self
	 */ 
	public function setQueries($queries)
	{
		$this->queries = $queries;

		return $this;
	}

	/**
	 * Add a query to queries
	 *
	 * @return  self
	 */ 
	public function addQuery(SqlQuery $query)
	{
		$this->queries[] = $query;

		return $this;
	}

}
