<?php

namespace SqlParser\SqlAction;

use \SqlParser\SqlExecutor;


class SqlActionSelect extends SqlAction
{	

	protected function executeAction(SqlExecutor $executor)
	{
		$select_fields = $this->getFieldsSelect();
		$group_fields = $this->getFieldsGroupBy();
		$order_fields = $this->getFieldsOrderBy();

		if (empty($select_fields)) {
			throw new \Exception("empty select_fields", 1);
		}

		$table_from = $this->getTableFrom();

		$conditions_where = $this->getConditionsWhere();
		$joins = $this->getJoins();
		$tables = $this->getTables();

		$limit_offset = $this->getLimit();
		$limit = $limit_offset['limit'];
		$offset = $limit_offset['offset'];

		// load data
		$database = $this->query->getParser()->getDatabase();
		foreach ($tables as $table) {
			$table->loadDataFromDatabase($database);
		}

		if (empty($offset)) {
			$offset = 0;
		}
		$nb_skipped = 0;

		if ($table_from) {
			$rows = $table_from->getData();

		} else {
			// no from table found
			$rows = [];
			foreach ($select_fields as $select_alias => $select_field) {
				$row[$select_alias] = $select_alias;
				$rows[] = $row;
			}
		}

		$executor->results_groups = [];
		$executor->current_group_key = null;
		

		$results = []; // TODO: créer SqlResult ? et/ou SqlRecordset ?
		$results_orders = [];

		$current_row_idx = 0;
		foreach ($rows as $row) {

			// 1) check limit
			if ($limit && count($results) >= $limit) {
				break;
			}

			// 2) FROM
			$row_data = [];
			if ($table_from) {
				$table_from_name = $table_from->getName();
				$table_from_alias = $table_from->getAlias();
				$row_data[$table_from_alias] = $row;
			}

			
			// 3) resolve joins
			$debug = 1;
			if ($joins) {
				foreach ($joins as $join_alias => $join_conditions) {
					$join_table_data = $tables[$join_alias]->getData();
					
					// pour chaque ligne de la table joined...
					foreach ($join_table_data as $join_row_idx => $join_row) {
						$ok = 1;
						foreach ($join_conditions as $condition) {
							$tmp_row_data = array_merge($row_data, [$join_alias => $join_row]);
							$condition_result = $condition->validateCondition($executor, $tmp_row_data); // NOTE: $condition est un SqlField ou un SqlExpr
							if (! $condition_result) {
								$ok = false;
								break;
							}
						}
		
						if ($ok) {
							$row_data[$join_alias] = $join_row;
							break; // TODO: gerer le renvoi de plusieurs lignes de jointure
						}
					}

				}
			}


			// 4) check WHERE conditions
			$where_result = $executor->validateConditions($row_data, $conditions_where); // TODO: utiliser $row_data a la place de $row
			if (!$where_result) {
				unset($where_result);
				continue;
			}
			unset($where_result);



			// 5) limit offet
			if ($nb_skipped < $offset) {
				$nb_skipped++;
				continue;
			}

			
			// 6a) group by
			if (! $group_fields) {
				$key = '_' . count($results);
			} else {
				$group_results = $executor->calculateFields($row_data, $group_fields);
				$key = implode("|", array_values($group_results));
			}
			$executor->current_group_key = $key;
			
			
			// 7) add result
			$result = $executor->calculateFields($row_data, $select_fields);
			$results[$key] = $result;
			$executor->current_result = $result;


			// 8a) order by
			if ($order_fields) {
				$results_orders[$key] = $executor->calculateFields($row_data, $order_fields);
			}


			$current_row_idx++;
		}
		unset($row);
		unset($current_row_idx);

		$executor->current_group_key = null;
		$executor->current_result = null;


		// 8b) order by
		if ($order_fields) {
			uksort($results, function ($a, $b) use ($results_orders) {
				$result_order_a = $results_orders[$a];
				$result_order_b = $results_orders[$b];

				foreach ($result_order_a as $key => $field_a) {
					$key_parts = explode(" ", $key);
					$desc = count($key_parts) > 1 && $key_parts[count($key_parts)-1] == 'desc';
					$field_b = $result_order_b[$key];

					if ($field_b == $field_a) {

					} else if ($desc) {
						return ($field_a < $field_b) ? 1 : -1;

					} else {
						return ($field_a < $field_b) ? -1 : 1;
					}
				}
				unset($key, $field_a, $field_b);

				return 0;
			});

		}
		
		
		// 6b) group by (retrieve results of eval functions)
		if ($executor->results_groups) {
			foreach ($results as $key => $result) {
				foreach ($executor->results_groups as $field_alias => $field_result) {
					$results[$key][$field_alias] = $field_result[$key]['result'];
				}
				unset($field_alias, $field_result);
			}
			unset($key, $result);
		}



		// reset rows values
		$results = array_values($results);

		$this->results = $results;

		return $results;
	}


	public function parseParts()
	{
		$froms = iterator_to_array($this->getPart('from'));
		if ($froms) {
			$froms[0]->parsePart();
		}

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


		// Note: parser les subqueries
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

}
