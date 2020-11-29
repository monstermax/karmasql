<?php

namespace SqlParser;


class SqlExpr
{
	use SqlDebugInfo_trait;
	use SqlItems_trait;
	use SqlAlias_trait;
	use SqlParent_trait;

	//public $parent = null; // @SqlActionPart | SqlParenthese
	public $outer_text;
	public $action; // @SqlAction
	

	public function __construct($outer_text=null)
	{
		$this->debug_skips = ['parser', 'parent', 'action', 'items'];
		//$this->debug_skips = ['parser', 'parent', 'action'];
		$this->outer_text = $outer_text;
	}

	public function getExpr($with_spaces=false, $with_comments=false)
	{
		$sql = '';

		$items = $this->getItems();

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

		$results = [];
		$result = '';
		$code = '';

		$tables = $this->action->getTables();

		if (count($items) == 1 && $items[0]->type === 'joker') {
			// expr will return several fields (joker *)

			$item = $items[0];
			$item_codes = $item->toSql(true);   // TODO: toSql à remplacer/renommer par toPhp

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
		
		foreach ($items as $item) {
			$code .= $item->toSql(true);
		}
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
