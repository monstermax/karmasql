<?php

namespace SqlParser;


class SqlActionPartJoin extends SqlActionPart
{
	protected $part_name = 'join';
    
    protected $table = [];
    protected $conditions = [];
	
    

	public function parsePart()
	{
        $tmp_params = $this->getParamsFromItems(false);
        
        // TODO: detecter "on" ou "using"
        $table_name = null;
        $table_alias = null;
        $item_on = null;
        $item_using = null;

        $tmp_param = $tmp_params[0];

        $items_on = [];
        $items_using = [];

		$conditions = [];
		$current_condition = null;

        $items = $tmp_param->getItems();
        foreach ($items as $item) {
            $is_on = $item->type === 'word' && $item->word == 'on';
            $is_using = $item->type === 'word' && $item->word == 'using';
            $is_and = ($item->type == 'word' && $item->word_type == 'keyword' && $item->word == 'and');

            if (!$is_on && !$is_using && $item->type === 'word') {
                if (! $table_name) {
                    $table_name = $item->word;
                    $item->word_type = 'table_name';
                    continue;
                    
                } else if (! $table_alias) {
                    $table_alias = $item->word;
                    $item->word_type = 'table_alias';
                    continue;
                }
            }

            if ($is_on) {
                $item_on = $item;
                continue;
            }

            if ($is_using) {
                $item_using = $item;
                continue;
            }

            if ($item_on) {
                $items_on[] = $item;

                if (true) {
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
                            //$item->detectFields();

                        } elseif ($item->type === 'parenthese') {
                            // TODO
                            throw new \Exception("non implemented case");
                        }
                    }
                }

                continue;
            }

            if ($item_using) {
                $items_using[] = $item;
                continue;
            }

            throw new \Exception("debug me", 1);

        }

		if (! is_null($current_condition)) {
			$conditions[] = $current_condition;
		}
		$current_condition = null;


        if ($table_name) {
            if (! $table_alias) {
                $table_alias = $table_name;
            }
            
            $this->table = new SqlTable($table_name, $table_alias);


			if (true) {
				// find table into database to set fields names
				
				$parser = $this->getAction()->getParser();
				
				$database = $parser->getDatabse();
				$data_table = isset($database[$table_name]) ? $database[$table_name] : null;

				if (is_null($data_table)) {
					throw new \Exception("table '" . $table_name . "' is not in the database", 1);
				}

				if (empty($data_table)) {
					throw new \Exception("empty table '" . $table_name . "'", 1);
				}

				$fields_names = array_keys($data_table[0]);

				foreach ($fields_names as $field_name) {
					$this->table->addFieldName($field_name);
				}

				$this->table->setData($data_table);
			}
        }


        if ($items_using) {
            // TODO: creer des $conditions à partir de la liste d'items des using
        }

        $this->conditions = $conditions;

        $tmp_param->detectFields();

    }

    
    public function getTable()
    {
        return $this->table;
    }
    
    
    public function getConditions()
    {
        return $this->conditions;
    }
    
}
