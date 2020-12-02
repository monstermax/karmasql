<?php

namespace SqlParser\SqlAction;

use \SqlParser\SqlExecutor;


class SqlActionDropTable extends SqlAction
{
	protected $table = null;


	public function executeAction(SqlExecutor $executor)
	{
		$table_drop = $this->table;
		$table_name = $table_drop->getName();
		$database = $this->parser->getDatabase();

		if (! isset($database[$table_name])) {
			throw new \Exception("table not exist", 1);
		}

		unset($database[$table_name]);
		$table_drop->saveDataToDatabase($this->parser, $database);
	}



	public function parseParts()
	{
		$drops = iterator_to_array($this->getPart('drop table'));

		if ($drops) {
			$drops[0]->parsePart();
			$table = $drops[0]->getTable();
			
			$this->table = $table;
		}


		$debug = 1;
	}

}
