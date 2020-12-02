<?php

namespace SqlParser\SqlAction;

use \SqlParser\SqlExecutor;


class SqlActionDelete extends SqlAction
{

	public function executeAction(SqlExecutor $executor)
	{
		//$group_fields = $this->getFieldsGroupBy();
		$order_fields = $this->getFieldsOrderBy();

		$table_from = $this->getTableFrom();

		$conditions_where = $this->getConditionsWhere();
		$joins = $this->getJoins();
		$tables = $this->getTables();

		$limit_offset = $this->getLimit();
		$limit = $limit_offset['limit'];
		$offset = $limit_offset['offset'];

		if (empty($offset)) {
			$offset = 0;
		}
		$nb_skipped = 0;


		if (empty($table_from)) {
			throw new \Exception("missing update table", 1);
        }
        
		
		// load data
		$database = $this->query->getParser()->getDatabase();
		$rows = $table_from->loadDataFromDatabase($database);
		

		$executor->results_groups = [];
		$executor->current_group_key = null;
		

		$rows_to_update = []; // TODO: créer SqlResult ? et/ou SqlRecordset ?
		$results_orders = [];

		$current_row_idx = 0;
		foreach ($rows as $row_idx => $row) {

			// 1) check limit
			if ($limit && count($rows_to_update) >= $limit) {
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
			//if (! $group_fields) {
			//	$key = '_' . count($rows_to_update);
			//} else {
			//	$group_results = $executor->calculateFields($row_data, $group_fields);
			//	$key = implode("|", array_values($group_results));
			//}
			//$executor->current_group_key = $key;
			
			
			// 7a) update (build list of items to delete)
			$rows_to_update[$row_idx] = $row;


			// 8a) order by
			if ($order_fields) {
				$results_orders[$row_idx] = $executor->calculateFields($row_data, $order_fields);
			}


			$current_row_idx++;
		}
		unset($row);
		unset($current_row_idx);


		// 8b) order by
		if ($order_fields) {
			uksort($rows_to_update, function ($a, $b) use ($results_orders) {
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
        $executor->current_group_key = null;


		
		// 6b) group by (retrieve results of eval functions)
        // TODO
        


		// 7b) delete
        // TODO: deplacer le offset/limit ici
		foreach ($rows_to_update as $row_idx => $row) {
			$this->deleteRow($rows, $row_idx);
		}

        $table_from->setData( array_values($rows) );

        $database = $this->query->getParser()->getDatabase();
        $table_name = $table_from->getName();
		$table_from->saveDataToDatabase($this->query->getParser(), $database, $table_name);


		$results = array_values($rows_to_update);
		return $results;
	}


    protected function deleteRow(&$data_table, $row_idx)
    {
        unset($data_table[$row_idx]);
    }


	public function parseParts()
	{
		$deletes = iterator_to_array($this->getPart('delete'));
        if ($deletes) {
            $deletes[0]->parsePart();
        }

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

		$limits = iterator_to_array($this->getPart('limit'));
		if ($limits) {
			$limits[0]->parsePart();
		}

		$debug = 1;

		// TODO: parser les subqueries
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


