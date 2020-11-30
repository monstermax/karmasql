<?php

namespace SqlParser;


class SqlActionTruncateTable extends SqlAction
{
    protected $table = null;


	public function executeAction(SqlExecutor $executor)
	{
		$table_truncate = $this->table;
		$table_name = $table_truncate->getName();

		$debug = 1;

		$database = $this->parser->getDatabase();

		if (! isset($database[$table_name])) {
			throw new \Exception("table not exist", 1);
		}

        //$database[$table_name] = [];
        $table_truncate->setData([]);
		$table_truncate->saveDataToDatabase($this->parser, $database, $table_name);
	}



	public function parseParts()
	{
        $truncates = iterator_to_array($this->getPart('truncate table'));

        if ($truncates) {
			$truncates[0]->parsePart();
			$table = $truncates[0]->getTable();
			
			$this->table = $table;
        }


        $debug = 1;

    }

}
