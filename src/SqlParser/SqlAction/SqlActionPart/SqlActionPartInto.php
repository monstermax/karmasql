<?php

namespace SqlParser\SqlAction\SqlActionPart;

use \SqlParser\SqlTable;


class SqlActionPartInto extends SqlActionPart
{
	protected $part_name = 'into';

	protected $table = null;
	protected $fields = [];


	public function parsePart()
	{
		$tmp_params = $this->getParamsFromItems(false);
		$fields  = null;

		if (count($tmp_params) == 1 && $tmp_params[0]->type === 'word') {
			// into matable
			$item_table_name = array_shift($tmp_params);

			$table_name = $item_table_name->word;
			$item_table_name->word_type = 'table_name';

		} else {
			// into matable ...
			$items = $tmp_params[0]->getItems();

			$table_name = null;
			$item_table_name = array_shift($items);
			
			$item_2 = array_shift($items);
			if ($item_2->type === 'parenthese') {
				$item_fields_names = $item_2;
				
			} else if ($item_2->type === 'action') {
				$debug = 1;
	
			} else {
				throw new \Exception("unknown case", 1);
			}
	
			if (! empty($items)) {
				throw new \Exception("unknown case", 1);
			}
	
			if ($item_table_name->type === 'word' && $item_fields_names && $item_fields_names->type === 'parenthese') {
				$table_name = $item_table_name->word;
				$item_table_name->word_type = 'table_name';
				$fields = $item_fields_names->getParamsFromItems(false);
	
				foreach ($fields as $field) {
					if ($field->type !== 'word') {
						throw new \Exception("unknown case", 1);
					}
					$field->word_type = 'field_name';
				}
	
			} else {
				throw new \Exception("unknown case", 1);
			}
	
		}
		
	
		$this->table = new SqlTable($table_name);
		$this->fields = $fields;


		if (true) {
			// find table into database to set fields names
			
			$parser = $this->getAction()->getParser();
			
			$database = $parser->getDatabase();
			$data_table = isset($database[$table_name]) ? $database[$table_name] : null;

			$fields_names = null;

			if (empty($fields_names)) {
				if (is_null($data_table)) {
					throw new \Exception("table '" . $table_name . "' is not in the database", 1);
				}
				
				if (empty($data_table)) {
					return;
					//throw new \Exception("empty table '" . $table_name . "'. Cannot find fields names", 1);
				}
				
				$fields_names = array_keys($data_table[0]);
			}
				
			foreach ($fields_names as $field_name) {
				$this->table->addFieldName($field_name);
			}

			$this->table->setData($data_table);
		}	

	}
	

	public function getTable()
	{
		return $this->table;
	}


	/**
	 * Get the value of fields
	 */ 
	public function getFields()
	{
		return $this->fields;
	}

	/**
	 * Set the value of fields
	 *
	 * @return  self
	 */ 
	public function setFields($fields)
	{
		$this->fields = $fields;

		return $this;
	}

}
