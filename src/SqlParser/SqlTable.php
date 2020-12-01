<?php

namespace SqlParser;


class SqlTable
{
    use SqlName_trait;
	use SqlAlias_trait;
	use SqlDebugInfo_trait;

	protected $fields_names;
	protected $data;
	

	public function __construct($name, $alias=null)
	{
		$this->name = $name;
		$this->alias = $alias ? $alias : $name;
		
		$this->debug_skips = ['data'];
	}


	public function getCurrentCol(SqlExecutor $executor, $field_name)
	{
		$current_row = $executor->getCurrentRowKey();

	}



	/**
	 * Get the value of fields_names
	 */ 
	public function getFieldsNames()
	{
		return $this->fields_names;
	}

	/**
	 * Set the value of fields_names
	 *
	 * @return  self
	 */ 
	public function setFieldsNames(array $fields_names)
	{
		$this->fields_names = $fields_names;

		return $this;
	}

	/**
	 * Add a value to fields_names
	 *
	 * @return  self
	 */ 
	public function addFieldName($field_name)
	{
		$this->fields_names[] = $field_name;

		return $this;
	}
	


	/**
	 * Get the value of data
	 *
	 * @return  array
	 */ 
	public function & getData()
	{
		return $this->data;
	}

	/**
	 * Set the value of data
	 *
	 * @return  self
	 */ 
	public function setData(array &$data)
	{
		$this->data = &$data;
		
		return $this;
	}


	public function loadDataFromDatabase($database)
	{
		$table_name = $this->getName();
		$data_table = isset($database[$table_name]) ? $database[$table_name] : null;

		$this->data = $data_table;
	}


	public function saveDataToDatabase(SqlParser $parser, $database, $table_name=null)
	{
		$table_name = $this->getName();
		
		$database[$table_name] = $this->data;

		$parser->setDatabase($database, $table_name);

	}

}

