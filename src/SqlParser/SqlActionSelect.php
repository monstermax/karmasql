<?php

namespace SqlParser;


class SqlActionSelect extends SqlAction
{

	public function executeAction(SqlExecutor $executor)
	{
		$select_fields = $this->getFieldsSelect();
		$group_fields = $this->getFieldsGroupBy();
		$order_fields = $this->getFieldsOrderBy();

		$table_from = $this->getTableFrom();

		$conditions_where = $this->getConditionsWhere();
		$joins = $this->getJoins();
		$tables = $this->getTables();

		$limit_offset = $this->getLimit();
		$limit = $limit_offset['limit'];
		$offset = $limit_offset['offset'];

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

		$executor->results_groups = [];
		$executor->current_result_row_key = null;
		

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

			
			// 3) resolve joins => TODO
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
				$key = count($results);
			} else {
				$group_results = $executor->calculateFields($row_data, $group_fields);
				$key = implode("|", array_values($group_results));
			}
			$executor->current_result_row_key = $key;
			
			
			// 7) add result
			$result = $executor->calculateFields($row_data, $select_fields);
			$results[$key] = $result;


			// 8a) order by
			if ($order_fields) {
				$results_orders[$key] = $executor->calculateFields($row_data, $order_fields);
			}


			$current_row_idx++;
		}
		unset($row);
		unset($current_row_idx);


		// 8b) order by
		if ($order_fields) {
			$debug_sort = $results_orders; // debug

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

		
		// 6b) group by
		if ($executor->results_groups) {
			foreach ($results as $key => $result) {
				foreach ($executor->results_groups as $field_alias => $field_result) {
					$results[$key][$field_alias] = $field_result[$key]['result'];
				}
				unset($field_alias, $field_result);
			}
			unset($key, $result);
		}


		$executor->current_result_row_key = null;

		// reset rows keys
		$results = array_values($results);

		return $results;
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

}
