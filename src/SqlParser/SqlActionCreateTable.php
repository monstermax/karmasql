<?php

namespace SqlParser;


class SqlActionCreateTable extends SqlAction
{
    protected $table = null;


	public function executeAction(SqlExecutor $executor)
	{
		$table_create = $this->table;
		$table_name = $table_create->getName();

		$debug = 1;

		$database = $this->parser->getDatabase();

		if (isset($database[$table_name])) {
			throw new \Exception("table already exists", 1);
		}

		$database[$table_name] = [];
		$table_create->saveDataToDatabase($this->parser, $database, $table_name);
	}



	public function parseParts()
	{
        $creates = iterator_to_array($this->getPart('create table'));

        if ($creates) {
			$creates[0]->parsePart();
			$table = $creates[0]->getTable();
			
			$this->table = $table;
        }


        $debug = 1;

    }

}
