<?php

namespace SqlParser\SqlAction\SqlActionPart;

use \SqlParser\SqlTable;


class SqlActionPartUpdate extends SqlActionPart
{
	protected $part_name = 'update';
    
    protected $table = null;


	public function parsePart()
	{
        $tmp_params = $this->getParamsFromItems(false);
        $table_name = $tmp_params[0]->word;
        $tmp_params[0]->word_type = 'table_name';

        $this->table = new SqlTable($table_name);


        if (true) {
            // find table into database to set fields names // TODO : code a remplacer par une fonction getDataTableFieldsNames
            $parser = $this->query->getParser();
            
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
        }


        // load data from database
        //$parser = $this->getAction()->getParser();
        //$database = $parser->getDatabase();
        //$this->table->loadDataFromDatabase($database);

        $debug = 1;

        // TODO: detecter keyword "ignore"
        
	}
	
	


    /**
     * Get the value of table
     */ 
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Set the value of table
     *
     * @return  self
     */ 
    public function setTable($table)
    {
        $this->table = $table;


        return $this;
    }

}
