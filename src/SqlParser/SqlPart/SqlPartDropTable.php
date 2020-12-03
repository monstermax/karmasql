<?php

namespace SqlParser\SqlPart;


class SqlPartDropTable extends SqlPart
{
	protected $part_name = 'drop table';
	protected $table;


	public function parsePart()
	{
		$tmp_params = $this->getParamsFromItems(false);
		
		$table_name = $tmp_params[0]->word;

		$this->table = new SqlTable($table_name);

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
