<?php

namespace SqlParser\SqlType;

use \SqlParser\SqlDebugInfo_trait;
use \SqlParser\SqlExecutor;
use SqlParser\SqlFragment\SqlFragmentMain;
use \SqlParser\SqlFunction;
use \SqlParser\SqlItems_trait;
use \SqlParser\SqlParent_trait;
use \SqlParser\SqlParser;


class SqlTypeParenthese extends SqlType
{
	use SqlDebugInfo_trait;
	use SqlItems_trait;
	use SqlParent_trait;
	
	
	public $type = 'parenthese';

	public $level;
	//public $fragment; // @SqlFragmentParenthese
	
	public $is_function;
	public $function_name;
	public $function_params;

	public $is_subquery;

	public $is_in_values;
	public $in_values;
	

	public function __construct(SqlFragmentMain $fragment_main, $pos)
	{
		parent::__construct($fragment_main, $pos);

		//$this->debug_skips = ['fragment', 'parser', 'parent', 'action', 'items'];
		$this->debug_skips = ['fragment', 'parser', 'parent', 'action'];

		
		$this->level = get_class($this->parent) == self::class ? $this->parent->level+1 : 1;
		$fragment_main->setCurrentParenthese($this);


		// detection function
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

			} else if (is_callable($last_word)) {
				// the parenthese is the arguments of a php function
				$this->is_function = true;
				$this->function_name = $last_word;
				
				// TODO: indiquer au word precedent qu'il s'agit d'un word_type=function_php
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


	public static function startParenthese(SqlFragmentMain $fragment_main, $pos)
	{
		$fragment_main->logDebug(__METHOD__ . " @ $pos");

		throw new \Exception("deprecated. replace me by a __construct", 1);

		//$parent = $fragment_main->getCurrentParenthese() ? $fragment_main->getCurrentParenthese() : null;
		//$level = $parent ? ($parent->level+1) : 1;

		$current_parenthese = new self;

		$current_parenthese->action = $fragment_main->getCurrentAction();
		
		$current_parenthese->start($fragment_main, $pos); // bien mettre ceci avant le setCurrentParenthese (sinon mauvaise detection du parent)
		
		$current_parenthese->level = get_class($current_parenthese->parent) == self::class ? $current_parenthese->parent->level+1 : 1;
		$fragment_main->setCurrentParenthese($current_parenthese);


		// detection function
		$prev_sql = substr($fragment_main->getSql(), 0, $pos);
		$prev_sql = trim(preg_replace('/\s+/', ' ', $prev_sql)); // TODO: attention s'il y a un commentaire entre le nom de la function et la parenthese, ca ne fonctionne plus
		if ($prev_sql) {
			$parts = explode(" ", $prev_sql);
			$last_word = $parts[count($parts)-1];

			if ($last_word == 'in') {
				// in (...)
				$current_parenthese->is_in_values = true;

			} else if (array_key_exists($last_word, $fragment_main->getParser()->getSqlFunctions())) {
				// functionFoo(...)
				$current_parenthese->is_function = true;
				$current_parenthese->function_name = $last_word;

			} else if (is_callable($last_word)) {
				// the parenthese is the arguments of a php function
				$current_parenthese->is_function = true;
				$current_parenthese->function_name = $last_word;
				
				// TODO: indiquer au word precedent qu'il s'agit d'un word_type=function_php
				$parent_items = $current_parenthese->parent->getItems();
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


	public function isParentheseEnd($char)
	{
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

		$this->end($pos);


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
			// functionFoo(...)
			$this->function_params = $this->getParamsFromItems(true);
		}

        if ($this->is_subquery) {
			//throw new \Exception('debug me : missing subquery in output sql', 1);
        }


		// add item
		if (! $this->parent) {
			$this->fragment_main->addItem($this);

		} else {
			$this->parent->addItem($this);
		}

		// add parenthese
		$this->fragment_main->addParenthese($this);

		// set new parent
		if ($this->parent && get_class($this->parent) == self::class) {
			// parent is parenthese => level 2+
			$this->fragment_main->setCurrentParenthese($this->parent);

		} else {
			// no parent (or SqlActionPart) => level 1
			$this->fragment_main->setCurrentParenthese(null);
		}

		if ($this->is_subquery) {
			$this->fragment_main->setCurrentAction($this->action);
		}
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
		$sub_sql = $this->itemsToSql($to_php, $show_sql);
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
		$items = $this->getItems(false, false);

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
		$items = $this->getItems(false);
		
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

}
