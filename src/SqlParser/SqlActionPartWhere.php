<?php

namespace SqlParser;


class SqlActionPartWhere extends SqlActionPart
{
	protected $part_name = 'where';


	protected $conditions = [];


	public function parsePart()
	{
		$conditions = [];
		$current_condition = null;

		$items = $this->getItems();

        foreach ($items as $item) {
            $is_comma = ($item->type == 'comma');
            $is_action = ($item->type == 'word' && $item->word_type == 'action');
			$is_action_part = ($item->type == 'word' && $item->word_type == 'action_part');
			$is_and = ($item->type == 'word' && $item->word_type == 'keyword' && $item->word == 'and');

            if ($is_action || $is_action_part) {
                continue;
			}

			if ($is_and) {
				if (! is_null($current_condition)) {
					$conditions[] = $current_condition;
				}

				$current_condition = null;
				
			} else {
				if (is_null($current_condition)) {
					$current_condition = new SqlExpr; // ou SqlCondition ?
					$current_condition->parent = $this;
					$current_condition->action = $this->action;
					$current_condition->parser = $this->parser;
				}
				
				$current_condition->addItem($item);
				$item->parent = $current_condition;

				if ($item->type === 'word') {
					$item->detectFields();

				} else if ($item->type === 'parenthese') {
				
				} else if ($item->type === 'space') {

				} else if ($item->type === 'numeric') {

				} else {
					throw new \Exception("non implemented case");
				}
			}
        }

		if (! is_null($current_condition)) {
			$conditions[] = $current_condition;
		}
		$current_condition = null;


		$this->conditions = $conditions;
		return $conditions;
	}


	/**
	 * Get the value of conditions
	 */ 
	public function getConditions()
	{
		return $this->conditions;
	}

	/**
	 * Set the value of conditions
	 *
	 * @return  self
	 */ 
	public function setConditions($conditions)
	{
		$this->conditions = $conditions;

		return $this;
	}
}
