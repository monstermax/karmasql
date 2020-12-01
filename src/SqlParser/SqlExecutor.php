<?php

namespace SqlParser;


class SqlExecutor
{
	public $results_groups;
	public $current_group_key;
	public $current_result;

	protected $current_field;


	function execute(SqlAction $action)
	{
		return $action->executeAction($this);
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
	

	public function calculateFields($row_data, $select_fields, $fields_aliases=null)
	{
		$values = [];
		// TODO => calculer les fields pour la $row donnée

		$idx = 0;

		foreach ($select_fields as $field) {
			$this->current_field = $field;

			//$field_alias = $field->getAlias();
			//$row_data = $row_data[$idx];

			$field_values = $field->getCalculatedValues($this, $row_data); // NOTE: $field est un SqlField ou un SqlExpr -- il peut contenir 1 champ ou plusieurs si "*"

			if ($field_values) {
				foreach ($field_values as $field_alias => $value) {
					if ($fields_aliases) {
						// cas pour le insert into
						$field_alias = $fields_aliases[$idx]->word;
					}
					$values[$field_alias] = $value;
				}
			}

			$idx++;
		}

		return $values;
	}
	

	public function getCurrentRowKey()
	{
		return $this->current_group_key;
	}




	/* #################### SQL FUNCTIONS ################### */



	public function version()
	{
		$ver = '0.0.1';
		$suffix = '';

		$show_commit = false;
		if ($show_commit) {
			$cmd = 'cd ' . __DIR__ . '; git rev-parse master';
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


	public function length($var)
	{
		return strlen($var);
	}
	

	public function upper($var)
	{
		return strtoupper($var);
	}
	

	public function lower($var)
	{
		return strtolower($var);
	}


	public function substring_index($var, $delimiter, $number)
	{
		$parts = explode($delimiter, $var);

        if (empty($number)) {
            return '';
        }

		if ($number > 0) {
			$parts = array_slice($parts, 0, $number);
			return implode($delimiter, $parts);

		} else {
			$number = -$number;
			$parts = array_reverse($parts);
			$parts = array_slice($parts, 0, $number);
			$parts = array_reverse($parts);
			return implode($delimiter, $parts);
		}
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
		$row_key = $this->current_group_key;

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
		$row_key = $this->current_group_key;

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
		$row_key = $this->current_group_key;

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
		$row_key = $this->current_group_key;

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
		$row_key = $this->current_group_key;

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


	public function group_concat($expr)
	{
		$alias = $this->current_field->getAlias();
		$row_key = $this->current_group_key;

		if (! isset($this->results_groups[$alias])) {
			$this->results_groups[$alias] = [];
		}
		if (! isset($this->results_groups[$alias][$row_key])) {
			$this->results_groups[$alias][$row_key] = [
				'result' => null,
				'data' => [],
			];
		}

		$delimiter = ",";

		$this->results_groups[$alias][$row_key]['data'][] = $expr;
		$this->results_groups[$alias][$row_key]['result'] = implode($delimiter, $this->results_groups[$alias][$row_key]['data']);


		return 'GROUP';
	}


}
