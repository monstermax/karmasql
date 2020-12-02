<?php

namespace SqlParser\SqlAction;

use \SqlParser\SqlExecutor;
use \SqlParser\SqlResult;


class SqlActionInsert extends SqlAction
{

	public function executeAction(SqlExecutor $executor)
	{
		$table_into = $this->getIntoTable();
		$table_name = $table_into->getName();
		$data = $table_into->getData();

		//$insert_fields_names = array_map(function ($item) {return $item->word;}, $insert_keys);
		$insert_fields_names = $table_into->getFieldsNames();


		$insert_keys = $this->getInsertKeys();

		$table_from = $this->getTableFrom(); // insert ... select
		if ($table_from) {
			$action_select = new SqlActionSelect($this->query, 'select');
			$parts = array_slice($this->parts, 2);

			// on change l'action de chaque part de la requete select (la nouvelle action est un SqlActionSelect et non plus un SqlActionInsert)
			foreach ($parts as &$part) {
				$part->setAction($action_select);
			}
			$action_select->parts = $parts;
			$action_select->parseParts();
			$tmp_results = $action_select->executeAction($executor);

			foreach ($tmp_results as &$tmp_result) {
				$tmp_result = array_map(function ($field) {return new SqlResult($field);}, $tmp_result);
			}
			
			$insert_values = $tmp_results;

			if (empty($insert_keys)) {
				if (! empty($insert_fields_names)) {
					$insert_keys = $insert_fields_names;

				} else {
					$insert_keys = array_keys($tmp_results[0]);
				}
				// TODO: $insert_keys doivent contenir des SqlType
			}
			
		} else {
			$insert_values = $this->getInsertValues();
		}

		$new_data = [];


		foreach ($insert_values as $idx => $insert_row) {
			//$new_row = $insert_row;
			//$row_data = $new_row[$idx];

			// preload les data avec la liste des champs de la table
			$row_insert_data = $insert_fields_names ? array_fill_keys($insert_fields_names, null) : [];

			$insert_data = $executor->calculateFields(null, $insert_row, $insert_keys);
			$row_insert_data = array_merge($row_insert_data, $insert_data);
			
			$data[] = $row_insert_data;
			$new_data[] = $row_insert_data;
		}

		$table_into->setData($data, true);

		$database = $this->query->getParser()->getDatabase();
		$table_name = $table_into->getName();
		$table_into->saveDataToDatabase($this->query->getParser(), $database, $table_name);

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
		$inserts = iterator_to_array($this->getPart('insert'));
		if ($inserts) {
			$inserts[0]->parsePart();
		}

		$intos = iterator_to_array($this->getPart('into'));
		if ($intos) {
			$intos[0]->parsePart();
		}


		$values = iterator_to_array($this->getPart('values'));
		if ($values) {
			$values[0]->parsePart();
		}


		// insert ... select
		
		$froms = iterator_to_array($this->getPart('from'));
		if ($froms) {
			$froms[0]->parsePart();
		}
		/*
		$wheres = iterator_to_array($this->getPart('where'));
		if ($wheres) {
			$wheres[0]->parsePart();
		}

		$joins = iterator_to_array($this->getPart('join'));
		if ($joins) {
			foreach ($joins as $join) {
				$join->parsePart();
			}
		}

		$selects = iterator_to_array($this->getPart('select'));
		if ($selects) {
			$selects[0]->parsePart();
		}

		$groups = iterator_to_array($this->getPart('group by'));
		if ($groups) {
			$groups[0]->parsePart();
		}

		$orders = iterator_to_array($this->getPart('order by'));
		if ($orders) {
			$orders[0]->parsePart();
		}

		$limits = iterator_to_array($this->getPart('limit'));
		if ($limits) {
			$limits[0]->parsePart();
		}
		*/


		$debug = 1;

		// Note: parser les subqueries
	}

}
