<?php

namespace SqlParser\SqlAction\SqlActionPart;

use \SqlParser\SqlExpr;
use \SqlParser\SqlTable;
use \SqlParser\SqlType\SqlTypeParenthese;
use \SqlParser\SqlType\SqlTypeSpace;
use \SqlParser\SqlType\SqlTypeWord;


class SqlActionPartFrom extends SqlActionPart
{
	protected $part_name = 'from';

	protected $tables = [];


	public function parsePart()
	{
		$params = $this->getParamsFromItems(false);

		$tables = [];

		foreach ($params as $param) {
			if (get_class($param) == SqlTypeWord::class) {
				// word
				$table_name = $param->word;
				$table_alias = $table_name;
				$param->word_type = 'table_name';

			} else if (get_class($param) == SqlExpr::class) {
				// expr
				$param_items = $param->getItems();

				// table_name
				$param_item = array_shift($param_items);
				if (!$param_item || $param_item->type !== 'word') {
					throw new \Exception("excpected table name", 1);
				}
				$param_item->word_type = 'table_name';
				$table_name = $param_item->word;

				// keyword "as" and/or alias
				$param_item = array_shift($param_items);
				if (!$param_item || $param_item->type !== 'word') {
					throw new \Exception("excpected keyword 'as' or table alias", 1);
				}
                if ($param_item->word_type === 'keyword' && $param_item->word == 'as') {
					// keyword 'as'
					$param_item = array_shift($param_items);
                }

				// alias
				if (!$param_item || $param_item->type !== 'word') {
					throw new \Exception("excpected table alias", 1);
				}
				$table_alias = $param_item->word;
				$param_item->word_type = 'table_alias';

			} else if (get_class($param) == SqlTypeSpace::class) {
				// space => skip

			} else if (get_class($param) == SqlTypeParenthese::class) {
				// parenthese => TODO
				// exemple: select 21, from (select 42) as t2

			} else {
				throw new \Exception("unknown case");
			}

			if (isset($tables[$table_alias])) {
				throw new \Exception("table alias '" . $table_alias . "' already defined");
			}

			$tables[$table_alias] = new SqlTable($table_name, $table_alias);


			if (true) {
				// find table into database to set fields names

				$fields_names = null;

				// TODO: stocker les fields_names en metadonnée. Comme ca si on fait un "truncate" ou "delete" on pourra continuer a utiliser la table

				if (empty($fields_names)) {
					$parser = $this->getAction()->getParser();
					$database = $parser->getDatabase();
					
					$data_table = isset($database[$table_name]) ? $database[$table_name] : null;
	
					if (is_null($data_table)) {
						throw new \Exception("table '" . $table_name . "' is not in the database", 1);
					}
	
					if (empty($data_table)) {
						throw new \Exception("empty table '" . $table_name . "' => cannot match fields", 1);
					}
	
					$fields_names = array_keys($data_table[0]);
				}

				foreach ($fields_names as $field_name) {
					$tables[$table_alias]->addFieldName($field_name);
				}


				// set data from database
				//$tables[$table_alias]->setData($data_table);
				//$tables[$table_alias]->loadDataFromDatabase($database);
			}

		}

		$this->tables = $tables;
		return $tables;
	}


	/**
	 * Get the value of tables
	 */ 
	public function getTables()
	{
		return $this->tables;
	}

	/**
	 * Set the value of tables
	 *
	 * @return  self
	 */ 
	public function setTables($tables)
	{
		$this->tables = $tables;

		return $this;
	}

}
