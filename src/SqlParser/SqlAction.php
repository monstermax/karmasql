<?php

namespace SqlParser;


class SqlAction
{
	use SqlDebugInfo_trait;
	use SqlName_trait;
	use SqlParent_trait;

	public $parser;
	protected $current_part;
	protected $parts = null;


	public function __construct(SqlParser $parser, $name)
	{
		$this->name = $name;
		$this->parser = $parser;
	}



	public function getFieldsSelect()
	{		
		$parts = $this->getPart('select');

		$fields = null;
		foreach ($parts as $part) {
			$fields = $part->getFields(); // a list of SqlField and SqlExpr
			break;
		}

		return $fields;
	}


	public function getFieldsGroupBy()
	{		
		$parts = $this->getPart('group by');

		$fields = null;
		foreach ($parts as $part) {
			$fields = $part->getFields(); // a list of SqlField and SqlExpr
			break;
		}

		return $fields;
	}


	public function getFieldsOrderBy()
	{		
		$parts = $this->getPart('order by');

		$fields = null;
		foreach ($parts as $part) {
			$fields = $part->getFields(); // a list of SqlField and SqlExpr
			break;
		}

		return $fields;
	}


	public function getTables($include_from=true, $include_joins=true)
	{
		$tables = [];

        if ($include_from) {
            $from_table = $this->getTableFrom();
            if ($from_table) {
				$table_alias = $from_table->getAlias();
				$tables[$table_alias] = $from_table;
				
            } else {
                //throw new \Exception("missing from table");
            }
        }

        if ($include_joins) {
            $join_tables = $this->getTablesJoin();
            $tables = array_merge($tables, $join_tables);

            $subqueries_tables = $this->getTablesSubqueries();
            $tables = array_merge($tables, $subqueries_tables);
        }

		return $tables;
	}


	public function getTableFrom()
	{		
		$parts = $this->getPart('from');

		$table = null;
		foreach ($parts as $part) {
			$tables = $part->getTables();

			if ($tables) {
				$table = array_shift($tables);
				break;
			}
		}

		return $table;
	}


	public function getTablesJoin()
	{
		$parts = $this->getPart('join');

		$tables = [];
		foreach ($parts as $part) {
			$table = $part->getTable();

			if ($table) {
				$table_alias = $table->getAlias();
				$tables[$table_alias] = $table;
				//$tables = array_merge($tables, $part_tables);
			}
		}

		return $tables;
	}

	
	public function getTablesSubqueries()
	{		
		$tables = [];
		
		// TODO: lister les sous-requetes (liste des parentheses qui ont is_subquery=1)

		return $tables;
	}



	public function getConditionsWhere()
	{
		$parts = $this->getPart('where');

		$conditions = null;
		foreach ($parts as $part) {
			$conditions = $part->getConditions();
			break;
		}

		return $conditions;
	}


	public function getJoins()
	{
		$parts = $this->getPart('join');

		$join_rows = [];
		$joins = null;
		foreach ($parts as $part) {
			$table = $part->getTable();
			$table_alias = $table->getAlias();
			$conditions = $part->getConditions();

			/*
			$rows = $table->getData();

			foreach ($rows as $row) {
				$ok = 1;

				foreach ($conditions as $condition) {
					$condition_result = $condition->validateCondition($this, $row); // NOTE: $condition est un SqlField ou un SqlExpr
					if (! $condition_result) {
						$ok = false;
						break;
					}
				}

				if ($ok) {
					$join_rows[] = $row;
				}
			}
			*/

			$joins[$table_alias] = $conditions;
			
		}

		return $joins;
	}


	public function getLimit()
	{
		$parts = $this->getPart('limit');

		$offset = 0;
		$limit = null;

		$conditions = null;
		foreach ($parts as $part) {
			$offset = $part->getOffset();
			$limit = $part->getLimit();
			break;
		}

		return [
			'offset' => $offset,
			'limit' => $limit,
		];
	}



	public function parseParts()
	{
		$froms = iterator_to_array($this->getPart('from'));
		$from_params = $froms ? $froms[0]->parsePart() : null;
		//pre($from_params, 0, '$from_params = ');

		$wheres = iterator_to_array($this->getPart('where'));
		$where_params = $wheres ? $wheres[0]->parsePart() : null;
		//pre($where_params, 0, '$where_params = ');

		$joins = iterator_to_array($this->getPart('join'));
		if ($joins) {
			foreach ($joins as $join) {
				$join_params = $join->parsePart();
				//pre($join_params, 0, '$join_params = ');
			}
		}

		$selects = iterator_to_array($this->getPart('select'));
		$select_params = $selects ? $selects[0]->parsePart() : null;
		//pre($select_params, 0, '$select_params = ');

		$groups = iterator_to_array($this->getPart('group by'));
		$group_params = $groups ? $groups[0]->parsePart() : null;
		//pre($group_params, 0, '$group_params = ');

		$orders = iterator_to_array($this->getPart('order by'));
		$order_params = $orders ? $orders[0]->parsePart() : null;
		//pre($order_params, 0, '$order_params = ');

		$limits = iterator_to_array($this->getPart('limit'));
		$limit_params = $limits ? $limits[0]->parsePart() : null;
		//pre($limit_params, 0, '$limit_params = ');


		// Note: parser les subqueries
	}


	function execute()
	{
		$executor = new SqlExecutor;

		return $executor->execute($this);
    }

	/*
	function execute()
	{
		$select_fields = $this->getFieldsSelect();

		$table_from = $this->getTableFrom();
		$conditions_where = $this->getConditionsWhere();

		$limit = null; // TODO: parser le limit de la query
		$offset = 0;
		$nb_skipped = 0;

		if ($table_from) {
			$rows = $table_from->getData();

		} else {
			// no from table found
			$rows = [];
			foreach ($select_fields as $select_alias => $select_field) {
				$row[$select_alias] = $select_alias;
			}
			$rows[] = $row;
		}

		$results = []; // TODO: créer SqlResult ? et/ou SqlRecordset ?

		$current_row_idx = 0;
		foreach ($rows as $row) {

			// 1) check limit
			if ($limit && count($results) >= $limit) {
				break;
			}

			// 2) check WHERE conditions
			$where_result = $this->validateConditions($row, $conditions_where);
			if (!$where_result) {
				unset($where_result);
				continue;
			}
			unset($where_result);

			// 3) resolve joins => TODO


			// 4) limit offet
			if ($nb_skipped < $offset) {
				$nb_skipped++;
				continue;
			}

			// 5) add result
			$result = $this->calculateFields($row, $select_fields);
			$results[] = $result;

			$current_row_idx++;
		}
		unset($row);
		unset($current_row_idx);

		return $results;
	}

	public function validateConditions($row, $conditions_where)
	{
		// TODO => valider (ou non) les conditions du where pour la $row donnée

		if ($conditions_where) {
			foreach ($conditions_where as $condition) {
				$condition_result = $condition->validateCondition($row); // NOTE: $condition est un SqlField ou un SqlExpr

				if (!$condition_result) {
					return false;
				}
			}
		}

		return true;
	}

	public function calculateFields($row, $select_fields)
	{
		$values = [];
		// TODO => calculer les fields pour la $row donnée

		foreach ($select_fields as $field) {
			//$field_alias = $field->getAlias();
			$field_values = $field->getCalculatedValues($row); // NOTE: $field est un SqlField ou un SqlExpr -- il peut contenir 1 champ ou plusieurs si "*"

			foreach ($field_values as $field_alias => $value) {
				$values[$field_alias] = $value;
			}
		}

		return $values;
	}

	*/


	public function toPhp()
	{
		return $this->name;
	}


	public function getParts()
	{
		return $this->parts;
	}


	public function getPart($part_name)
	{
		//return isset($this->parts[$part_name]) ? $this->parts[$part_name] : null;

		//$parts = [];
		foreach ($this->parts as $part) {
			if ($part->getName() == $part_name) {
				//$parts[] = $part;
				yield $part;
			}
		}

		//return null;
	}

	public function getCurrentPart()
	{
		return $this->current_part;
	}


	public function setCurrentPart(SqlActionPart $action_part=null)
	{
		if (is_null($this->parts)) {
			$this->parts = [];
		}

		$this->current_part = $action_part;

		if ($action_part) {
			$this->parts[] = $this->current_part;
		}
	}


	/**
	 * Get the value of parser
	 */ 
	public function getParser()
	{
		return $this->parser;
	}

	/**
	 * Set the value of parser
	 *
	 * @return  self
	 */ 
	public function setParser($parser)
	{
		$this->parser = $parser;

		return $this;
	}
	
}

