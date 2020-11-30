<?php

namespace SqlParser;


class SqlActionUpdate extends SqlAction
{

	public function executeAction(SqlExecutor $executor)
	{
		$table_update = $this->getTableUpdate();
		$group_fields = $this->getFieldsGroupBy();
		$order_fields = $this->getFieldsOrderBy();

		if (empty($table_update)) {
			throw new \Exception("missing update table", 1);
		}

		$conditions_where = $this->getConditionsWhere();
		$update_sets = $this->getUpdateSets();

		$joins = $this->getJoins();
		$tables = $this->getTables();

		$limit_offset = $this->getLimit();
		$limit = $limit_offset['limit'];
		$offset = $limit_offset['offset'];

		if (empty($offset)) {
			$offset = 0;
		}
		$nb_skipped = 0;

		$rows = $table_update->getData();
		$rows_to_update = [];
		
		$current_row_idx = 0;
        foreach ($rows as $row_idx => &$row) {

			// 1) check limit
			if ($limit && count($rows_to_update) >= $limit) {
				break;
			}


			// 2) FROM
			$row_data = [];
			if ($table_update) {
				$table_from_name = $table_update->getName();
				$table_from_alias = $table_update->getAlias();
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


			// 7a) update (build list of items to update)
			$rows_to_update[$row_idx] = &$row;


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

		
		// 6b) group by (retrieve results of eval functions)
		// TODO


		// 7b) update
		// TODO: deplacer le offset/limit ici
		foreach ($rows_to_update as &$row) {
			$this->updateRow($executor, $row, $update_sets);

		}


		$table_update->setData($rows, true);

		$database = $this->parser->getDatabase();
		$table_update->saveDataToDatabase($this->parser, $database);

		$debug = 1;


		return $rows_to_update;
	}


	protected function updateRow($executor, &$row, $update_sets)
	{
		$table_update = $this->getTableUpdate();
		$table_name = $table_update->getName();

		//$data = $table_update->getData();

		foreach ($update_sets as $update_set) {
			$items = $update_set->getItems(false);

			if (empty($items)) {
				throw new \Exception("missing items", 1);
				break;
			}
			
			$field = array_shift($items);
			$field_name = $field->word;

			$equal = array_shift($items);

			if (count($items) == 1) {
				$expr_set = $items[0];

			} else {
				// more than 1 item
				$expr_set = new SqlExpr; // ou SqlCondition ?
				//$expr_set->parent = $this;
				$expr_set->action = $this;
				$expr_set->parser = $this->parser;

				$expr_set->addItems($items);
			}


			$row_data = [
				$table_name => $row,
			];

			$expr_set_value = $expr_set->getCalculatedValues($executor, $row_data);

			$row[$field_name] = array_pop($expr_set_value);

			//$data[$this->curren]
			$debug = 1;

		}

	}


	public function getTableUpdate()
	{
		$parts = $this->getPart('update');

		$table = null;
		foreach ($parts as $part) {
			$table = $part->getTable();
			break;
		}

		return $table;
	}


	public function getUpdateSets()
	{
		$parts = $this->getPart('set');

		$fields = [];
		foreach ($parts as $part) {
			$fields = $part->getFields();
			break;
		}

		return $fields;
	}




	public function parseParts()
	{
		$updates = iterator_to_array($this->getPart('update'));
        if ($updates) {
            $updates[0]->parsePart();
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

		$sets = iterator_to_array($this->getPart('set'));
		if ($sets) {
			$sets[0]->parsePart();
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
            $from_table = $this->getTableUpdate();
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
