<?php

namespace SqlParser;


class SqlActionPartInto extends SqlActionPart
{
	protected $part_name = 'into';

    protected $table = null;
    protected $fields = [];

	
	public function parsePart()
	{
        $tmp_params = $this->getParamsFromItems(false);
        $items = $tmp_params[0]->getItems();

        $table_name = null;
        $item_table_name = $items[0];
        $item_fields_names = isset($items[1]) ? $items[1] : null;

        if ($item_table_name->type === 'word' && $item_fields_names && $item_fields_names->type === 'parenthese') {
            $table_name = $item_table_name->word;
            $fields = $item_fields_names->getParamsFromItems(false);
        }

        if ($table_name) {
            $this->table = new SqlTable($table_name);
            $this->fields = $fields;


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
					$this->table->addFieldName($field_name);
				}

				$this->table->setData($data_table);
			}

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
