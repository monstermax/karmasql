<?php

namespace SqlParser\SqlType;

use \SqlParser\SqlDebugInfo_trait;
use \SqlParser\SqlExecutor;
use SqlParser\SqlFragment\SqlFragment;
use SqlParser\SqlFragment\SqlFragmentMain;
use SqlParser\SqlFragment\SqlFragmentParenthese;
use \SqlParser\SqlFunction;
use \SqlParser\SqlItems_trait;
use \SqlParser\SqlParent_trait;
use \SqlParser\SqlPart\SqlPart;
use \SqlParser\SqlParser;


class SqlTypeParenthese extends SqlType
{
	use SqlDebugInfo_trait;
	use SqlItems_trait;
	use SqlParent_trait;
	
	
	public $type = 'parenthese';

	public $level;
	protected $fragments; // liste des @SqlFragment que contient la parenthese (à son 1er niveau)
	
	public $is_function;
	public $function_name;
	public $function_params;
	public $function_type;

	public $is_subquery;

	public $is_in_values;
	public $in_values;
	

	public function __construct(SqlFragmentMain $fragment_main, $pos)
	{
		$fragment_main->logDebug(__CLASS__ . " @ $pos");

		parent::__construct($fragment_main, $pos);

		$this->debug_skips = ['fragment', 'parser', 'parent', 'action'];

		$this->fragments = [
			new SqlFragmentParenthese($this),
		];

		
		$this->level = get_class($this->parent) == self::class ? $this->parent->level+1 : 1;
		//$fragment_main->setCurrentParenthese($this);


		// detection function (regarding the word just before the parenthese)
		$prev_sql = substr($fragment_main->getSql(), 0, $pos);
		$prev_sql = trim(preg_replace('/\s+/', ' ', $prev_sql)); // TODO: attention s'il y a un commentaire entre le nom de la function et la parenthese, ca ne fonctionne plus
		if ($prev_sql) {
			$parts = explode(" ", $prev_sql);
			$last_word = $parts[count($parts)-1];

			if ($last_word == 'in') {
				// in (...)
				$this->is_in_values = true;

			} else if (array_key_exists($last_word, $fragment_main->getParser()->getSqlFunctions())) {
				// functionFoo(...)
				$this->is_function = true;
				$this->function_name = $last_word;
				$this->function_type = 'sql';
				
			} else if (is_callable($last_word)) {
				// the parenthese is the arguments of a php function
				$this->is_function = true;
				$this->function_name = $last_word;
				$this->function_type = 'php';
				
				// on indique au word precedent qu'il s'agit d'un word_type=function_php
				$parent_items = $this->parent->getItems();
				if ($parent_items) {
					$last_word_item = $parent_items[count($parent_items)-1];
					if ($last_word_item->type === 'word') {
						$last_word_item->word_type = 'function_php';
					}
				}

			} else {
				// undefined parenthese type
				$debug = 1;
			}
		}

	}



	public static function isParentheseStart(SqlFragmentMain $fragment_main, $char)
	{
        if ($char !== '(') {
			return false;
		}
		
		if ($fragment_main->getCurrentComment()) {
			// on est dans un commentaire
			return false;
		}

		if ($fragment_main->getCurrentString()) {
			// on est dans une string
			return false;
		}

		if ($char == '(') {
			return 'parenthese';
		}

		return false;
	}


	public function isParentheseEnd($char)
	{
        if ($char !== ')') {
			return false;
		}

		if (! $this->fragment_main->getCurrentParenthese()) {
			// on n'est pas dans une parenthese
			return false;
		}

		if ($this->fragment_main->getCurrentComment()) {
			// on est dans un commentaire
			return false;
		}

		if ($this->fragment_main->getCurrentString()) {
			// on est dans une string
			return false;
		}

		if ($char == ')') {
			return 'parenthese';
		}

		return false;
	}


	public function endParenthese($pos)
	{
		$this->fragment_main->logDebug(__METHOD__ . " @ $pos");

		$current_parenthese = $this->fragment_main->getCurrentParenthese();

		if (empty($current_parenthese)) {
			throw new \Exception("not in a parenthese", 1);
		}
		if ($current_parenthese !== $this) {
			throw new \Exception("mismatch parenthese", 1);
		}
		
		$this->enclosure_start = '(';
		$this->enclosure_end = ')';

		$this->end($pos, false);

		$this->fragments[0]->setSql($this->inner_text);



		// detection is_subquery
		$sub_items = $this->getItems(false, true, false);
		$is_subquery = $sub_items && $sub_items[0]->type == 'word' && $sub_items[0]->word == 'select';
		$current_parenthese->is_subquery = $is_subquery;


		if ($this->is_in_values) {
			// in (...)
			if (! $this->is_subquery) {
				$this->in_values = $this->getParamsFromItems(true);
			}

		} else if ($this->is_function) {
			// functionSqlOrPhp(...)
			$this->function_params = $this->getParamsFromItems(true);
		}

        if ($this->is_subquery) {
			//throw new \Exception('debug me : missing subquery in output sql', 1);
        }


		// set new parent
		if (!$this->parent) {
			throw new \Exception("unknown case", 1);

		} else if (get_class($this->parent) === SqlFragmentParenthese::class) {
			// parent is SqlFragmentParenthese => level 2+
			//$parent = $this->parent->getParent()->getParent();
			$parent = $this->parent->getParent();
			$this->fragment_main->setCurrentParenthese($parent);

		} else if (is_a($this->parent, SqlPart::class)) {
			// parent is SqlPart => level 1
			//$this->fragment_main->setCurrentParenthese($this->parent);
			$this->fragment_main->setCurrentParenthese(null);

		} else {
			throw new \Exception("unknown case", 1);
		}
		

        $this->addItemToParents(); // TODO: a revoir


		if ($this->is_subquery) {
			throw new \Exception("debug me", 1);
			$this->fragment_main->setCurrentAction($this->action);
		}
	}


	public function append($val)
	{
		parent::append($val);

		//$current_fragment = $this->getCurrentFragment();
		//$current_fragment->append($val);
	}



	public function toPhp($show_sql=false)
	{
		return $this->toSql(true, $show_sql);
	}


	public function toSql($to_php=false, $show_sql=false)
	{
		$sql = '';

		// ajout '('
		if ($show_sql) {
			echo '<span style="color:purple;">(</span>';
		}
		$sql .= '(';


		// ajout parenthese inner text
		$sub_sql = $this->fragments[0]->itemsToSql($to_php, $show_sql);
        if ($show_sql) {
			//echo $sub_sql;
        }
		$sql .= $sub_sql;


		// ajout ')'
		if ($show_sql) {
			echo '<span style="color:purple;">)</span>';
		}
		$sql .= ')';

		return $sql;
	}



	public function detectFields()
	{
		$items = $this->fragments[0]->getItems(false, false);

		foreach ($items as $item) {
			if ($item->type == 'word') {
				$item->detectFields();

			} else if ($item->type == 'parenthese') {
				$item->detectFields();
			}
		}
    }


	function getCalculatedValues(SqlExecutor $executor, $row_data)
	{
		$items = $this->fragments[0]->getItems(false);
		
		foreach ($items as $item) {
			if ($item->type === 'word' && $item->word_type === 'undefined') {
				$item->detectFields();
			}

			$item_code = $item->toSql(true);   // TODO: toSql à remplacer/renommer par toPhp
			$item_codes[] = $item_code;
			
			if (! is_string($item_code)) {
				throw new \Exception("invalid return " . print_r($item_code), 1);
			}
		}
		$item_code = implode(' ', $item_codes);

		$field_alias = $this->outer_text;
		
		$result = null;

		$functions_repository = new SqlFunction($executor); // used by eval
		eval('$result = (' . $item_code . ');');

		$results = [
			$field_alias => $result,
		];

		return $results;
	}

	
	public function getCurrentFragment()
	{
		return isset($this->fragments[0]) ? $this->fragments[0] : null;
	}
	
	public function getFragments()
	{
		return $this->fragments;
	}


	public function setFragments($fragments)
	{
		$this->fragments = $fragments;

		return $this;
	}

	public function addFragment(SqlFragment $fragment)
	{
		$this->fragments[] = $fragment;

		return $this;
	}

}
