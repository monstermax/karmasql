<?php

namespace SqlParser\SqlAction;

use \SqlParser\SqlExecutor;
use \SqlParser\SqlExpr;


class SqlActionSet extends SqlAction
{
    protected $set = null;


	public function executeAction(SqlExecutor $executor)
	{
        $debug = 1;

        $update_set = $this->set;

        $items = $update_set->getItems(false);

        if (empty($items)) {
            throw new \Exception("missing items", 1);
        }
        
        $set = array_shift($items);
        $var = array_shift($items);
        $var_name = ltrim($var->word, '@');

        $equal = array_shift($items);

        if (count($items) == 1) {
            $expr_set = $items[0];

        } else {
            // more than 1 item
            $items_sql = ""; // TODO: 
            $expr_set = new SqlExpr($items_sql); // ou SqlCondition ?
            //$expr_set->parent = $this;
            $expr_set->action = $this;
            $expr_set->parser = $this->parser;

            $expr_set->addItems($items);
        }


        $row_data = [];

        $expr_set_values = $expr_set->getCalculatedValues($executor, $row_data);
        $expr_set_value = array_shift($expr_set_values);

        //$row[$var_name] = array_pop($expr_set_value);

        //$data[$this->curren]
        $debug = 1;

        $database = $this->query->getParser()->getDatabase();

        $database['_variables'][$var_name] = $expr_set_value;
        $this->query->getParser()->setDatabase($database);

        // TODO: parser les subqueries

        $this->results = [
            ['@' . $var_name => $expr_set_value],
        ];
        
        return $this->results;
	}



	public function parseParts()
	{
        $sets = iterator_to_array($this->getPart('set'));

        if ($sets) {
            $sets[0]->parsePart();
        }

        $this->set = empty($sets[0]) ? null : $sets[0];

        $debug = 1;
    }


	public function getTables($include_from=true, $include_joins=true)
	{
		$tables = [];
		
		return $tables;
	}



}