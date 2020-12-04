<?php

namespace SqlParser\SqlFragment;

use \SqlParser\SqlAction\SqlAction;
use \SqlParser\SqlItems_trait;
use \SqlParser\SqlParser;
use \SqlParser\SqlPart\SqlPart;
use \SqlParser\SqlQuery;
use \SqlParser\SqlType\SqlType;
use \SqlParser\SqlType\SqlTypeComma;
use \SqlParser\SqlType\SqlTypeComment;
use \SqlParser\SqlType\SqlTypeComparator;
use \SqlParser\SqlType\SqlTypeJoker;
use \SqlParser\SqlType\SqlTypeNumeric;
use \SqlParser\SqlType\SqlTypeOperator;
use \SqlParser\SqlType\SqlTypeParenthese;
use \SqlParser\SqlType\SqlTypeSpace;
use \SqlParser\SqlType\SqlTypeString;
use \SqlParser\SqlType\SqlTypeWord;


class SqlFragmentMain extends SqlFragment
{
	use SqlItems_trait;

    protected $parse_duration = null;

	protected $comments = []; // list of @SqlFragmentComment
	protected $comparators = []; // list of @SqlFragmentComparator
	protected $numerics = []; // list of @SqlFragmentNumeric
	protected $operators = []; // list of @SqlFragmentOperator
	protected $parentheses = []; // list of @SqlFragmentParenthese
	protected $strings = []; // list of @SqlFragmentString
	protected $words = []; // list of @SqlFragmentWord
    protected $queries = null; // list of @SqlFragmentQuery
	
	protected $current_query = null; // @SqlFragmentQuery
	protected $current_part = null; // @SqlPart (a renommer en SqlFragmentPart)
    protected $current_action = null; // en theorie plus ou moins un alias de $current_query->getPrincipalAction()
        
	protected $current_comment = null; // @SqlTypeComment
	protected $current_comparator = null; // @SqlTypeComparator
	protected $current_numeric = null; // @SqlTypeNumeric
	protected $current_operator = null; // @SqlTypeOperator
	protected $current_parenthese = null; // @SqlTypeParenthese
	protected $current_string = null; // @SqlTypeString
	protected $current_word = null; // @SqlTypeWord
	protected $current_space = null; // @SqlTypeSpace
    


    public function __construct($parent, $sql='')
    {
        parent::__construct($parent, $sql);

    }



	public function parseSQL()
	{
		$this->queries = [];
        
		$ts_start = microtime(true);

		$sql_len = strlen($this->sql);

		
		if (empty($this->current_query)) {
			$this->current_query = new SqlFragmentQuery($this);
            $this->queries[] = $this->current_query;
            
		} else {
            throw new \Exception("unknwon case", 1);
        }

		$prev_char_no_space = null;

		// STEP 1 : browse each character

		$parsed_sql = "";

		$pos = -1;
		while ($pos < $sql_len - 1) {
			$pos++;

			$char = substr($this->sql, $pos, 1);
			$char_id = ord($char);
			$parsed_sql .= $char;

			$prev_char = substr($this->sql, $pos-1, 1);
			$next_char = substr($this->sql, $pos+1, 1);

			$is_underscore = ($char === '_');
			$is_asterisk = ($char === '*'); // TODO: creer une class SqlTypeJoker
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
			$is_joker2 = $is_asterisk && in_array($prev_char_no_space, ['.']); // on capture ici seulement les variantes comme "mytable.*"
			$is_joker3 = $is_asterisk && in_array($prev_char_no_space, ['(']); // on capture ici seulement les "count(*)" (ou autre fonction)

			if ($is_dot) {
				$debug = 1;
			}
			if ($is_asterisk) {
				$debug = 1;
			}
			if ($is_joker) {
				$debug = 1;
			}
			if ($is_joker2) {
				$debug = 1;
			}
			if ($is_joker3) {
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

			/*
			if ($is_joker2 && ! $this->current_string && ! $this->current_comment) {
				// mytable.*

				$this->addWord( new SqlTypeWord($this, $pos, 'joker_table') );
				$this->current_word->endWord($pos);
				
				//SqlTypeNumeric::startNumeric($this, $pos);
				//$this->current_numeric->endNumeric($pos);

				//$is_operator = false;
				continue;
			}
			*/


			if ($is_joker3 && ! $this->current_string && ! $this->current_comment) {
				// count(*)  (ou autre fonction)

				$this->addWord( new SqlTypeWord($this, $pos, 'joker_function') );
				$this->current_word->endWord($pos);
				
				//SqlTypeNumeric::startNumeric($this, $pos);
				//$this->current_numeric->endNumeric($pos);

				//$is_operator = false;
				continue;
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
				$comment_type = SqlTypeComment::isCommentStart($this, $char, $next_char);
				if ($comment_type) {
					// debut d'un commentaire
					//SqlTypeComment::startComment($this, $pos, $comment_type);
					$this->addComment( new SqlTypeComment($this, $pos, $comment_type) );
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
					$string_type = SqlTypeString::isStringStart($this, $char, $next_char);
					if ($string_type) {
						// debut d'un string
						//SqlTypeString::startString($this, $pos, $string_type);
						$this->addString( new SqlTypeString($this, $pos, $string_type) );
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

				$parenthese_type = SqlTypeParenthese::isParentheseStart($this, $char);
				if ($parenthese_type) {
					// debut d'une parenthese (de niveau 2+)
					//SqlTypeParenthese::startParenthese($this, $pos);
					$this->addParenthese( new SqlTypeParenthese($this, $pos) );
					unset($parenthese_type);
					continue;
				}
				unset($parenthese_type);
			} else {
				// on n'est PAS dans une parenthese
				$parenthese_type = SqlTypeParenthese::isParentheseStart($this, $char);
				if ($parenthese_type) {
					// debut d'une parenthese (de niveau 1)
					//SqlTypeParenthese::startParenthese($this, $pos);
					$this->addParenthese( new SqlTypeParenthese($this, $pos) );
					unset($parenthese_type);
					continue;
				}
				unset($parenthese_type);
			}



			// debut de word ?
			if (! $this->current_word) {
				// on n'est PAS dans un mot
				if ($is_alpha_lower || $is_alpha_upper || $is_arobase || $is_underscore || $is_dollar) {
                    //SqlTypeWord::startWord($this, $pos);
                    $this->addWord( new SqlTypeWord($this, $pos) );
					continue;
				}
			}


			// debut de numeric ?
			if (! $this->current_numeric) {
				// on n'est PAS dans un numeric
				if ($is_numeric) {
					//SqlTypeNumeric::startNumeric($this, $pos);
					$this->addNumeric( new SqlTypeNumeric($this, $pos) );
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
						//SqlTypeSpace::startSpace($this, $pos);
						$this->addSpace( new SqlTypeSpace($this, $pos) );
					}
					continue;
				}
			}


			// virgule ?
			if ($is_comma) {
				//SqlTypeComma::startEndComma($this, $pos);
				$comma = new SqlTypeComma($this, $pos);
				$comma->end($pos);
				//$this->addComma($comma);
				continue;
			}


			// joker ?
			if ($is_joker) {
				//SqlTypeJoker::startEndJoker($this, $pos);
				$joker = new SqlTypeJoker($this, $pos);
				$joker->end($pos);
				//$this->addJoker($joker);
				continue;
			}


			// operator ?
			if ($is_operator) {
				//SqlTypeOperator::startOperator($this, $pos);
				$this->addOperator( new SqlTypeOperator($this, $pos) );
				continue;
			}


			// comparator ?
			if ($is_comparator) {
				//SqlTypeComparator::startComparator($this, $pos);
				$this->addComparator( new SqlTypeComparator($this, $pos) );
				continue;
			}


			if ($is_semicolon) {
				// fin de requete. on prepare une nouvelle requete

				if (empty($this->queries)) {
					throw new \Exception("excepted queries in object SqlFragment (" . get_class($this) . ")", 1);
				}


				$this->current_query = new SqlFragmentQuery($this);
				$this->queries[] = $this->current_query;
	
				$prev_char_no_space = null;
				$this->current_action = null;
				$this->current_action = null;
				$this->current_part = null;
				$this->current_parenthese = null;
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


		if (! $this->current_query->getAction()) {
			// aucune requete trouvée
			return [];
		}
	}




	public function executeQueries()
	{
		if (is_null($this->queries)) {
			throw new \Exception("main fragment not parsed", 1);
		}

		$results = null;

		foreach ($this->queries as $query) {
			$results = $query->executeQuery();
		}

		return $results;
	}



    /* ADDERS */

	public function addComment(SqlTypeComment $comment)
	{
		$this->comments[] = $comment;
		$this->setCurrentComment($comment);
	}

	public function addString(SqlTypeString $string)
	{
		$this->strings[] = $string;
		$this->setCurrentString($string);
	}

	public function addParenthese(SqlTypeParenthese $parenthese)
	{
		$this->parentheses[] = $parenthese;
		$this->setCurrentParenthese($parenthese);
	}

	public function addWord(SqlTypeWord $word)
	{
		$this->words[] = $word;
		$this->setCurrentWord($word);
	}

	public function addNumeric(SqlTypeNumeric $number)
	{
		$this->numerics[] = $number;
		$this->setCurrentNumeric($number);
	}

	public function addSpace(SqlTypeSpace $space)
	{
		$this->spaces[] = $space;
		$this->setCurrentSpace($space);
	}

	public function addOperator(SqlTypeOperator $operator)
	{
		$this->operators[] = $operator;
		$this->setCurrentOperator($operator);
	}

	public function addComparator(SqlTypeComparator $comparator)
	{
		$this->comparators[] = $comparator;
		$this->setCurrentComparator($comparator);
	}

	public function addQuery(SqlQuery $query)
	{
		$this->queries[] = $query;

		return $this;
	}


    
    /* GETTERS */

	public function getCurrentParent()
	{
		if ($this->current_parenthese) {
			$parenthese_fragments = $this->current_parenthese->getFragments();
			if (empty($parenthese_fragments)) {
				throw new \Exception("missing parenthese fragment", 1);
			}
			return $parenthese_fragments[0];
		}
		if ($this->current_part) {
			return $this->current_part;
		}
		if ($this->current_query) {
			//throw new \Exception("parent cannot be the query", 1);
			return $this->current_query;
		}
		throw new \Exception("parent not found", 1);
	}


	public function getCurrentPart()
	{	
		return $this->current_part;
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

	public function getQueries()
	{
		return $this->queries;
    }
    
	public function getCurrentQuery()
	{
		return $this->current_query;
    }
    
	public function getParseDuration()
	{
		return $this->parse_duration;
    }
    


    /* SETTERS */

	public function setCurrentAction(SqlAction $action=null)
	{
		if (empty($this->current_query)) {
			throw new \Exception("missing current query", 1);
		}

        if ($action) {
            if (!$this->current_query->getAction()) {
                // set the principal action of the query (select, update, insert, delete, ...)
				$this->current_query->setAction($action);
				
            } else {
                // it is a second action (ex: insert into ... select ... from ...)

                $action = $this->current_query->getAction2();
                if ($action) {
                    throw new \Exception("2 actions already defined", 1);
                }

                //throw new \Exception("debug me", 1);
			}

		} else {
			// set $action = null

			/*
			if ($this->current_action) {
				$this->current_action->endAction();
			}
			*/

			if ($this->current_query) {
				$action2 = $this->current_query->getAction2();
				if ($action2) {
					$action2->endAction();
				}

				$action = $this->current_query->getAction();
				if ($action) {
					$action->endAction();
				}

			} else {
				throw new \Exception("unknown case", 1);
			}

		}


		$this->current_action = $action;
	}


	public function setCurrentPart(SqlPart $part=null)
	{
		$this->current_part = $part;
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


	public function setQueries($queries)
	{
		$this->queries = $queries;

		return $this;
    }
    

}
