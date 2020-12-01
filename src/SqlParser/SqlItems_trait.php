<?php

namespace SqlParser;


trait SqlItems_trait
{
	protected $items = [];

	/*
	Used by:
	- SqlActionPart
	- SqlParenthese
	- SqlExpr
	*/


	public function getItems($with_spaces=true, $with_commas=true, $with_comments=false)
	{
		if ($with_spaces && $with_comments && $with_commas) {
			return $this->items;
		}

		$items = [];

		foreach ($this->items as $item) {
			$ok = true;

			if (! $with_spaces && $item->type == 'space') {
				$ok = false;
			}

			if (! $with_comments && $item->type == 'comment') {
				$ok = false;
			}

			if (! $with_commas && $item->type == 'comma') {
				$ok = false;
			}


			if ($ok) {
				$items[] = $item;
			}
		}

		return $items;
	}


	public function getParamsFromItems($with_spaces=true, $with_comments=false)
	{
		//$items = $this->items;
		$items = $this->getItems($with_spaces, true, $with_comments);

		$params = [];
		$current_param = null;
		
		foreach ($items as $item) {
			$is_comma = ($item->type == 'comma');
			$is_action = ($item->type == 'word' && $item->word_type == 'action');
			$is_action_part = ($item->type == 'word' && $item->word_type == 'action_part');

			if ($is_action || $is_action_part) {
				continue;
			}

			if ($is_comma) {
				// COMMA
				if (! is_null($current_param)) {
					$params[] = $current_param;
				}
				$current_param = null;

			} else if ($item->type == 'comment') {
				// COMMENT => skip

			} else {
				// PART OF A PARAM

				if (is_null($current_param)) {
					// on met un item
					$current_param = $item;

				} else {
					if (get_class($current_param) !== SqlExpr::class) {
						// on transforme un item en expression
						//$expr_first_part = new SqlParseItem;
						$expr_first_part = $current_param;

						$current_param = new SqlExpr($expr_first_part->outer_text); // TODO: il manque les espaces dans le outer_text du SqlExpr que l'on créé
						$current_param->parent = $this;
						$current_param->parser = $this->parser;
						$current_param->action = $this->action;
						
						
						$current_param->addItem($expr_first_part);
						$expr_first_part->parent = $current_param;
					} 

					// on ajoute a l'expression
					$current_param->addItem($item);
					$item->parent = $current_param;

					$current_param->outer_text .= $item->outer_text;
				}
			}
		}

		if (! is_null($current_param)) {
			$params[] = $current_param;
		}
		$current_param = null;

		return $params;
	}



	/**
	 * Set the value of items
	 *
	 * @return  self
	 */ 
	public function setItems($items)
	{
		$this->items = $items;

		return $this;
	}

	/**
	 * Add a value to items
	 *
	 * @return  self
	 */ 

	public function addItem(SqlParseItem $item)
	{
		$item->action = $this->action;
		//$item->parent = $this; // TODO: a tester

		$this->items[] = $item;

		return $this;
	}
	
	public function addItems($items)
	{
		foreach ($items as $item) {
			$this->addItem($item);
			$item->parent = $this;
		}
		
		return $this;
	}


	public function itemsToSql($to_php=false, $print_debug=false)
	{
		$colors = [
			'string' => 'darkgray',
			'comment' => 'lightgray',
			'numeric' => '#f0a476',
			'comma' => '#2b7ccd',
			'parenthese' => 'purple',
			'word/action' => '#007fbf',
			'word/action_part' => '#007fbf',
			'word/function_sql' => '#b14ac4',
			'word/function_php' => 'red',
			'word/keyword' => '#00afdf',
			'word/field' => 'darkcyan',
			'word/table_name' => 'orange',
			'word/table_alias' => '#995528',
			'word/field_name' => 'darkblue',
			'word/field_alias' => '#795548',
			'word/variable_php' => 'green',
			'word/variable_sql' => 'green',
		];

		$items = $this->getItems();

		$sql = "";

		foreach ($items as $idx => $item) {
			if ($item->type == 'comment') {
				continue;
			}

			if ($print_debug) {
				// display sql
				$class = "";
				$style = "";
				$title = $item->type;

				if ($item->type != 'space') {
					if ($item->type == 'word') {
						$color = isset($colors[$item->type . '/' . $item->word_type]) ? $colors[$item->type . '/' . $item->word_type] : '';

					} else {
						$color = isset($colors[$item->type]) ? $colors[$item->type] : '';
					}

					if ($color) {
						$style .= "color:$color;";
					}
				}

				if ($item->type == 'word') {
					$title .= ' / ' . $item->word_type;

					if (in_array($item->word_type, ['action', 'action_part', "keyword"])) {
						if ($item->word_type === 'action_part') {
							echo "<br />";
						}
						$style .= "font-weight:bold;";
					}
				}

                if ($item->type == 'operator' && !empty($item->fields)) {
					// l'item * is a field and not an operator
					$title = 'field *';
                }

				if ($item->type == 'parenthese') {
					// todo: recursive ?
					if ($item->is_function) {
						$title .= ' / function ' . $item->function_name;
					}
					if ($item->is_in_values) {
						$title .= ' / in';
					}
					if ($item->is_subquery) {
						$title .= ' / subquery';
					}

					//continue; // skip
				}

				$id = 'item-' . $idx . '-' . $item->type;

				echo '<span id="' . $id . '" class="' . $class . '" style="' . $style . '" title="' . $title . '">';
				$value = $item->toSql($to_php, $print_debug);
				echo '</span>';

			} else {
				$value = $item->toSql($to_php, $print_debug);
			}


			$sql .= $value;
		}

		return $sql;
	}

}
