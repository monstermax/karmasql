<?php

namespace SqlParser;


class SqlActionInsert extends SqlAction
{

    public function executeAction(SqlExecutor $executor)
    {
        $into_table = $this->getIntoTable();
        $insert_keys = $this->getInsertKeys();
        $insert_values = $this->getInsertValues();

        $table_name = $into_table->getName();
        $data = $into_table->getData();

        $new_data = [];

        //$insert_fields_names = array_map(function ($item) {return $item->word;}, $insert_keys);
        $insert_fields_names = $into_table->getFieldsNames();

        foreach ($insert_values as $idx => $insert_row) {
            //$new_row = $insert_row;
            //$row_data = $new_row[$idx];

            $row_insert_data = array_fill_keys($insert_fields_names, null);

            $row_insert_data = array_merge($row_insert_data, $executor->calculateFields(null, $insert_row, $insert_keys));
            
            $data[] = $row_insert_data;
            $new_data[] = $row_insert_data;
        }

        $into_table->setData($data, true);

        $database = $this->parser->getDatabase();
        $into_table->saveDataToDatabase($this->parser, $database);

        //pre($into_table->getData());
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
		$into_params = $intos ? $intos[0]->parsePart() : null;
		//pre($into_params, 0, '$into_params = ');

		$values = iterator_to_array($this->getPart('values'));
		$value_params = $values ? $values[0]->parsePart() : null;
		//pre($value_params, 0, '$value_params = ');

        $debug = 1;

		// Note: parser les subqueries
	}

}
