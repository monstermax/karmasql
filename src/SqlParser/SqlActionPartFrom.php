<?php

namespace SqlParser;


class SqlActionPartFrom extends SqlActionPart
{
	protected $part_name = 'from';

	protected $tables = [];


	public function parsePart()
	{
		$params = $this->getParamsFromItems(false);

		$tables = [];

		foreach ($params as $param) {
			if (get_class($param) == SqlWord::class) {
				// word
				$table_name = $param->word;
				$table_alias = $table_name;
				$param->word_type = 'table_name';

			} else if (get_class($param) == SqlExpr::class) {
				// expr
				$param_items = $param->getItems();

				//$param->detectAlias();
				
				$table_name = $param_items[0]->word;
				$param_items[0]->word_type = 'table_name';

				$table_alias = $param_items[count($param_items)-1]->word;
				$param_items[count($param_items)-1]->word_type = 'table_alias';

			} else if (get_class($param) == SqlSpace::class) {
				// space => skip

			} else if (get_class($param) == SqlParenthese::class) {
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

				$parser = $this->getAction()->getParser();
				$database = $parser->getDatabase();
				
				$data_table = isset($database[$table_name]) ? $database[$table_name] : null;

				if (is_null($data_table)) {
					throw new \Exception("table '" . $table_name . "' is not in the database", 1);
				}

				if (empty($data_table)) {
					throw new \Exception("empty table '" . $table_name . "'", 1);
				}

				$fields_names = array_keys($data_table[0]);

				foreach ($fields_names as $field_name) {
					$tables[$table_alias]->addFieldName($field_name);
				}


				// set data from database
				//$tables[$table_alias]->setData($data_table);
				$tables[$table_alias]->loadDataFromDatabase($database);
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
