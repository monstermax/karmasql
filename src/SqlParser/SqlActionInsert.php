<?php

namespace SqlParser;


class SqlActionInsert extends SqlAction
{

	public function executeAction(SqlExecutor $executor)
	{
		$table_into = $this->getIntoTable();
		$insert_keys = $this->getInsertKeys();
		$insert_values = $this->getInsertValues();

		$table_name = $table_into->getName();
		$data = $table_into->getData();

		$new_data = [];

		//$insert_fields_names = array_map(function ($item) {return $item->word;}, $insert_keys);
		$insert_fields_names = $table_into->getFieldsNames();

		foreach ($insert_values as $idx => $insert_row) {
			//$new_row = $insert_row;
			//$row_data = $new_row[$idx];

			$row_insert_data = array_fill_keys($insert_fields_names, null);

			$row_insert_data = array_merge($row_insert_data, $executor->calculateFields(null, $insert_row, $insert_keys));
			
			$data[] = $row_insert_data;
			$new_data[] = $row_insert_data;
		}

		$table_into->setData($data, true);

		$database = $this->parser->getDatabase();
		$table_name = $table_into->getName();
		$table_into->saveDataToDatabase($this->parser, $database, $table_name);

		//pre($table_into->getData());
		$debug = 1;

		return $new_data;
	}


	public function getIntoTable()
	{
		$parts = $this->getPart('into');

		$table = null;
		foreach ($parts as $part) {
			$table = $part->getTable();
			break;
		}

		return $table;
	}


	public function getInsertKeys()
	{
		$parts = $this->getPart('into');

		$fields = [];
		foreach ($parts as $part) {
			$fields = $part->getFields();
			break;
		}

		return $fields;
	}


	public function getInsertValues()
	{
		$parts = $this->getPart('values');

		$rows = [];
		foreach ($parts as $part) {
			$rows = $part->getRows();
			break;
		}

		return $rows;
	}


	public function parseParts()
	{
		$intos = iterator_to_array($this->getPart('into'));
		if ($intos) {
			$intos[0]->parsePart();
		}

		$values = iterator_to_array($this->getPart('values'));
		if ($values) {
			$values[0]->parsePart();
		}

		$debug = 1;

		// Note: parser les subqueries
	}

}
