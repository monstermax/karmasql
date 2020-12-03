<?php

namespace SqlParser;

use \SqlParser\SqlAction\SqlActionPart\SqlActionPartSelect;


class SqlExpr
{

	use SqlDebugInfo_trait;
	use SqlItems_trait;
	use SqlAlias_trait;
	use SqlParent_trait;

	public $type = 'expr';
	//public $parent = null; // @SqlActionPart | SqlTypeParenthese
	public $outer_text;
	public $action; // @SqlAction
	

	public function __construct($outer_text=null)
	{
		$this->debug_skips = ['parser', 'parent', 'action', 'items'];
		//$this->debug_skips = ['parser', 'parent', 'action'];
		$this->outer_text = $outer_text;

		//throw new \Exception("replace SqlExr by SqlFragment*", 1);
	}

	public function getExpr($with_spaces=false, $with_comments=false)
	{
		$sql = '';

		$items = $this->getItems(true);

		foreach ($items as $item) {
			if (! $with_comments && $item->type == 'comment') {
				continue;
			}
			if (! $with_spaces && $item->type == 'space') {
				continue;
			}

			$sql .= $item->outer_text;
		}

		return $sql;
	}


	public function getAlias()
	{
		$alias = $this->alias;

		if (empty($alias)) {
			$alias = trim($this->getExpr(true));
			$alias = substr($alias, 0, 64);
		}

		return $alias;
	}


	public function detectFields()
	{
		$items = $this->getItems(false);

		foreach ($items as $item) {
			if ($item->type == 'word') {
				$item->detectFields();

			} else if ($item->type == 'parenthese') {
				$item->detectFields();

			} else {
				//throw new \Exception("non implemented case");
			}
		}
    }

	public function detectAlias()
	{
		if (get_class($this->parent) !== SqlActionPartSelect::class) {
			// les alias ne peuvent etre qu'au premier niveau (pas dans une parenthese) => TODO: gerer l'exception des sous-requetes
			return;
		}

		$items = $this->getItems(false);
		$items_reverse = array_reverse($items);

		if (count($items_reverse) > 1 && $items_reverse[0]->type === 'word') {
			$last_item = $items_reverse[0];

			if ($last_item->type === 'word') {
				// le dernier item est un word
				$before_last_item = isset($items_reverse[1]) ? $items_reverse[1] : null;

				if ($before_last_item && $before_last_item->type === 'word' && $before_last_item->word_type == 'keyword' && $before_last_item->word == 'as') {
					// "as" detected
					$last_item->word_type = 'field_alias';
					$this->alias = $last_item->word;
					return;
				}

				if ($before_last_item && in_array($before_last_item->type, ['word', 'numeric', 'parenthese', 'string'])) {
					$last_item->word_type = 'field_alias';
					$this->alias = $last_item->word;
					return;
				}

				throw new \Exception("non implemented case");
			
			} else if (in_array($last_item->type, ['operator', 'comparator'])) {
				// operator or comparator detected => expr has no alias
				throw new \Exception("non implemented case");

			} else {
				throw new \Exception("non implemented case");
			}
			
		}
    }


	public function getCalculatedValues(SqlExecutor $executor, $row_data)
	{
		$items = $this->getItems(false);
		$functions_repository = new SqlFunction($executor); // used by eval

		$results = [];
		$result = '';
		$code = '';

		$tables = $this->action->getTables();

		if (count($items) == 1 && $items[0]->type === 'joker') {
			// expr will return several fields (joker *)

			$item = $items[0];
			$item_codes = $item->toSql(true);   // TODO: toSql à remplacer/renommer par toPhp

			$functions_repository = new SqlFunction($executor);

			foreach ($item_codes as $field_alias => $item_code) {
				if (! is_string($item_code)) {
					throw new \Exception("invalid return " . print_r($item_code), 1);
				}
				
				$result = null;
				eval('$result = (' . $item_code . ');');
				
				$results[$field_alias] = $result;
			}
			
			
		} else {
			// expr will return only one field
			
			$item_codes = [];
			foreach ($items as $item) {
				if ($item->type === 'word' && $item->word_type === 'keyword' && $item->word === 'as') {
					continue;
				}
				if ($item->type === 'word' && $item->word_type === 'keyword' && $item->word === 'desc') {
					continue;
				}
				if ($item->type === 'word' && $item->word_type === 'field_alias') {
					continue;
				}

                if ($item->type === 'parenthese') {
					// parenthese
					$item->detectFields(); // TODO: a deplacer en amont

				} else if ($item->type === 'joker') {
					// joker

				} else if ($item->type === 'numeric') {
					// numeric

                } else if ($item->type === 'word') {
                    if ($item->word_type == 'field') {
						// field sql
					
					} else if ($item->word_type == 'function_sql') {
						// function sql

					} else if ($item->word_type == 'function_php') {
						// function php

					} else if ($item->word_type == 'variable_php') {
						// variable php
						$debug = 1;

					} else {
						$debug = $item->type;
						throw new \Exception("unknown case", 1);
					}

				} else {
					throw new \Exception("unknown case", 1);
				}

				$item_code = $item->toSql(true);   // TODO: toSql à remplacer/renommer par toPhp
				$item_codes[] = $item_code;
				
				if (! is_string($item_code)) {
					throw new \Exception("invalid return " . print_r($item_code), 1);
				}
			}
			$item_code = implode(' ', $item_codes);

			$field_alias = $this->getAlias();
			
			$result = null;
			eval('$result = (' . $item_code . ');');

			$results = [
				$field_alias => $result,
			];
		}
		
		return $results;
	}



	public function validateCondition(SqlExecutor $executor, $row_data)
	{
		$items = $this->getItems(false);

		$result = '';
		$code = '';

		$tables = $this->action->getTables();
		
		$item_next = null;
		$item_prev = null;
		$item_prev_prev = null;
		$buffer_item = null;

		foreach ($items as $idx => $item) {
			$item_next = ($idx >= count($items)-1) ? null : $items[$idx+1];

			// in_values
			if ($item->type === 'word' && $item->word_type === 'keyword' && $item->word === 'in') {
				// in (keyword in)

			} else if ($item_next && $item_next->type === 'word' && $item_next->word_type === 'keyword' && $item_next->word === 'in') {
				// in (expr before in)
				$buffer_item = $item;

			} else if ($item_prev && $item_prev->type === 'word' && $item_prev->word_type === 'keyword' && $item_prev->word === 'in') {
				// in (expr after in)
				$value = $buffer_item->toSql(true);
				$in_params = $item->toSql(true);
				$in_params = '[' . trim($in_params, '()') . ']';
				$debug = 1;
				$code .= 'in_array(' . $value . ', ' . $in_params . ')';

			} else {
				// all other cases

				$code .= $item->toSql(true);
			}


			$item_prev_prev = $item_prev;
			$item_prev = $item;
		}
		
		$functions_repository = new SqlFunction($executor); // used by eval
		eval('$result = (' . $code . ');');

		return !! $result ;
	}



	public function toSql($to_php=false, $show_sql=false)
	{
		$sql = '';

		if ($show_sql) {
			echo '<span style="color:darkyellow;">';
		}

		// ajout expr inner text
		$sub_sql = $this->itemsToSql($to_php, $show_sql);
        if ($show_sql) {
			//echo $sub_sql;
        }
		$sql .= $sub_sql;


		if ($show_sql) {
			echo '</span>';
		}

		return $sql;
	}


	/**
	 * Get the value of action
	 */ 
	public function getAction()
	{
		return $this->action;
	}

	/**
	 * Set the value of action
	 *
	 * @return  self
	 */ 
	public function setAction($action)
	{
		$this->action = $action;

		return $this;
	}

}
