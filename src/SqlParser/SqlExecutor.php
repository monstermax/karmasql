<?php

namespace SqlParser;


class SqlExecutor
{
	protected $results_groups;
	protected $current_result_row_key;
	protected $current_field;


	function execute(SqlAction $action)
	{
		$select_fields = $action->getFieldsSelect();
		$group_fields = $action->getFieldsGroupBy();
		$order_fields = $action->getFieldsOrderBy();

		$table_from = $action->getTableFrom();

		$conditions_where = $action->getConditionsWhere();
		$joins = $action->getJoins();
		$tables = $action->getTables();

		$limit_offset = $action->getLimit();
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

		$this->results_groups = [];
		$this->current_result_row_key = null;
		

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
							$condition_result = $condition->validateCondition($this, $tmp_row_data); // NOTE: $condition est un SqlField ou un SqlExpr
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
			$where_result = $this->validateConditions($row_data, $conditions_where); // TODO: utiliser $row_data a la place de $row
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
				$group_results = $this->calculateFields($row_data, $group_fields);
				$key = implode("|", array_values($group_results));
			}
			$this->current_result_row_key = $key;
			
			
			// 7) add result
			$result = $this->calculateFields($row_data, $select_fields);
			$results[$key] = $result;


			// 8a) order by
			if ($order_fields) {
				$results_orders[$key] = $this->calculateFields($row_data, $order_fields);
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
		if ($this->results_groups) {
			foreach ($results as $key => $result) {
				foreach ($this->results_groups as $field_alias => $field_result) {
					$results[$key][$field_alias] = $field_result[$key]['result'];
				}
				unset($field_alias, $field_result);
			}
			unset($key, $result);
		}


		$this->current_result_row_key = null;

		return $results;
	}


    
	public function validateConditions($row_data, $conditions_where)
	{
		// TODO => valider (ou non) les conditions du where pour la $row donnée

		if ($conditions_where) {
			foreach ($conditions_where as $condition) {
				$condition_result = $condition->validateCondition($this, $row_data); // NOTE: $condition est un SqlField ou un SqlExpr

				if (!$condition_result) {
					return false;
				}
			}
		}

		return true;
    }
    

	public function calculateFields($row_data, $select_fields)
	{
		$values = [];
		// TODO => calculer les fields pour la $row donnée

		foreach ($select_fields as $field) {
			$this->current_field = $field;

			//$field_alias = $field->getAlias();
			$field_values = $field->getCalculatedValues($this, $row_data); // NOTE: $field est un SqlField ou un SqlExpr -- il peut contenir 1 champ ou plusieurs si "*"

			foreach ($field_values as $field_alias => $value) {
				$values[$field_alias] = $value;
			}
	}

		return $values;
    }
    

	public function getCurrentRowKey()
	{
		return $this->current_result_row_key;
	}


    /* ####################################### */



    public function version()
    {
        $ver = '0.0.1';
        $suffix = '';

        if (defined('APP_DIR')) {
            $cmd = 'cd ' . APP_DIR . '; git rev-parse master';
            $commit_id = trim(shell_exec($cmd));
            if ($commit_id) {
                $suffix = '-' . substr($commit_id, 0, 7);
            }
        }

        return 'PhpDB-' . strtolower(PHP_OS) . '-' . $ver . $suffix;
    }

    public function if($cond, $if_true, $if_false)
    {
        return $cond ? $if_true : $if_false;
    }

    public function ifnull($val, $default_value)
    {
        return is_null($val) ? $default_value : $val;
    }

    public function isnull($val)
    {
        return is_null($val);
    }


    public function curl($url)
    {
        return file_get_contents($url);
    }


    public function current_timestamp()
    {
        return $this->now();
    }

    public function now()
    {
        return date('Y-m-d H:i:s');
    }

    public function date($sql_ts)
    {
        return substr($sql_ts, 0, 10);
    }

    public function year($sql_ts)
    {
        return substr($sql_ts, 0, 4);
    }



    public function rand()
    {
        $up = 1000000000;
        return rand(0, $up) / $up;
    }


    public function concat()
    {
        $args = func_get_args();

        $result = '';
        foreach ($args as $arg) {
            $result .= $arg;
        }
        return $result;
    }

    public function replace($source, $search, $replace)
    {
        return str_replace($search, $replace, $source);
    }

    public function least()
    {
        $args = func_get_args();
        return min($args);
    }

    public function greatest()
    {
        $args = func_get_args();
        return max($args);
    }



    public function sum($expr)
    {
		$alias = $this->current_field->getAlias();
		$row_key = $this->current_result_row_key;

		if (! isset($this->results_groups[$alias])) {
			$this->results_groups[$alias] = [];
		}
		if (! isset($this->results_groups[$alias][$row_key])) {
			$this->results_groups[$alias][$row_key] = [
				'result' => 0,
			];
		}

		$this->results_groups[$alias][$row_key]['result'] += $expr;

		return 'GROUP';
    }


    public function avg($expr)
    {
		$alias = $this->current_field->getAlias();
		$row_key = $this->current_result_row_key;

		if (! isset($this->results_groups[$alias])) {
			$this->results_groups[$alias] = [];
		}
		if (! isset($this->results_groups[$alias][$row_key])) {
			$this->results_groups[$alias][$row_key] = [
				'result' => 0,
				'total' => 0,
				'nb' => 0,
			];
		}

		$this->results_groups[$alias][$row_key]['total'] += $expr;
		$this->results_groups[$alias][$row_key]['nb'] += 1;
		$this->results_groups[$alias][$row_key]['result'] = $this->results_groups[$alias][$row_key]['total'] / $this->results_groups[$alias][$row_key]['nb'];

		return 'GROUP';
    }


    public function count($expr)
    {
		$alias = $this->current_field->getAlias();
		$row_key = $this->current_result_row_key;

		if (! isset($this->results_groups[$alias])) {
			$this->results_groups[$alias] = [];
		}
		if (! isset($this->results_groups[$alias][$row_key])) {
			$this->results_groups[$alias][$row_key] = [
				'result' => 0,
			];
		}

		if (! is_null($expr)) {
			$this->results_groups[$alias][$row_key]['result'] += 1;
		}

		return 'GROUP';
    }


    public function min($expr)
    {
		$alias = $this->current_field->getAlias();
		$row_key = $this->current_result_row_key;

		if (! isset($this->results_groups[$alias])) {
			$this->results_groups[$alias] = [];
		}
		if (! isset($this->results_groups[$alias][$row_key])) {
			$this->results_groups[$alias][$row_key] = [
				'result' => null,
			];
		}

        if (is_null($this->results_groups[$alias][$row_key]['result'])) {
			$this->results_groups[$alias][$row_key]['result'] = $expr;
        } else {
			$this->results_groups[$alias][$row_key]['result'] = min($expr, $this->results_groups[$alias][$row_key]['result']);
		}

		return 'GROUP';
    }


    public function max($expr)
    {
		$alias = $this->current_field->getAlias();
		$row_key = $this->current_result_row_key;

		if (! isset($this->results_groups[$alias])) {
			$this->results_groups[$alias] = [];
		}
		if (! isset($this->results_groups[$alias][$row_key])) {
			$this->results_groups[$alias][$row_key] = [
				'result' => null,
			];
		}

        if (is_null($this->results_groups[$alias][$row_key]['result'])) {
			$this->results_groups[$alias][$row_key]['result'] = $expr;
        } else {
			$this->results_groups[$alias][$row_key]['result'] = max($expr, $this->results_groups[$alias][$row_key]['result']);
		}

		return 'GROUP';
    }


}

/*

		'ifnull' => 1,
		'isnull' => 1,

		'now' => 1,
		'current_timestamp' => 1,
		'current_date' => 1,
		'current_time' => 1,
		'date' => 1,
		'time' => 1,
		'year' => 1,
		'month' => 1,
		'day' => 1,
		'hour' => 1,
		'minute' => 1,
		'second' => 1,
		'date_add' => 1,
		'date_sub' => 1,
		'datediff' => 1,

		'count' => 1,
		'abs' => 1,
		'rand' => 1,
		'round' => 1,
		'floor' => 1,
		'ceil' => 1,
		'sqrt' => 1,
		'pow' => 1,
		'pi' => 1,

		'sum' => 1,
		'avg' => 1,
		'min' => 1,
		'max' => 1,
		'least' => 1,
		'greatest' => 1,

		'length' => 1,
		'lower' => 1,
		'upper' => 1,
		'mid' => 1,
		'left' => 1,
		'right' => 1,
		'replace' => 1,
		'locate' => 1,
		'concat' => 1,
		'substr' => 1,
		'substring' => 1,
		'substring_index' => 1,
		'trim' => 1,
		'rtrim' => 1,
		'ltrim' => 1,
*/