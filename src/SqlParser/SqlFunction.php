<?php

namespace SqlParser;


class SqlFunction
{
    protected $executor;

    public function __construct(SqlExecutor $executor)
    {
        $this->executor = $executor;
    }


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
		return $this->executor->now();
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
		$alias = $this->executor->current_field->getAlias();
		$row_key = $this->executor->current_group_key;

		if (! isset($this->executor->results_groups[$alias])) {
			$this->executor->results_groups[$alias] = [];
		}
		if (! isset($this->executor->results_groups[$alias][$row_key])) {
			$this->executor->results_groups[$alias][$row_key] = [
				'result' => 0,
			];
		}

		$this->executor->results_groups[$alias][$row_key]['result'] += $expr;

		return $expr;
	}


	public function avg($expr)
	{
		$alias = $this->executor->current_field->getAlias();
		$row_key = $this->executor->current_group_key;

		if (! isset($this->executor->results_groups[$alias])) {
			$this->executor->results_groups[$alias] = [];
		}
		if (! isset($this->executor->results_groups[$alias][$row_key])) {
			$this->executor->results_groups[$alias][$row_key] = [
				'result' => 0,
				'total' => 0,
				'nb' => 0,
			];
		}

		$this->executor->results_groups[$alias][$row_key]['total'] += $expr;
		$this->executor->results_groups[$alias][$row_key]['nb'] += 1;
		$this->executor->results_groups[$alias][$row_key]['result'] = $this->executor->results_groups[$alias][$row_key]['total'] / $this->executor->results_groups[$alias][$row_key]['nb'];

		return $expr;
	}


	public function count($expr)
	{
		$alias = $this->executor->current_field->getAlias();
		$row_key = $this->executor->current_group_key;

		if (! isset($this->executor->results_groups[$alias])) {
			$this->executor->results_groups[$alias] = [];
		}
		if (! isset($this->executor->results_groups[$alias][$row_key])) {
			$this->executor->results_groups[$alias][$row_key] = [
				'result' => 0,
			];
		}

		if (! is_null($expr)) {
			$this->executor->results_groups[$alias][$row_key]['result'] += 1;
		}

		return $expr;
	}


	public function min($expr)
	{
		$alias = $this->executor->current_field->getAlias();
		$row_key = $this->executor->current_group_key;

		if (! isset($this->executor->results_groups[$alias])) {
			$this->executor->results_groups[$alias] = [];
		}
		if (! isset($this->executor->results_groups[$alias][$row_key])) {
			$this->executor->results_groups[$alias][$row_key] = [
				'result' => null,
			];
		}

		if (is_null($this->executor->results_groups[$alias][$row_key]['result'])) {
			$this->executor->results_groups[$alias][$row_key]['result'] = $expr;
		} else {
			$this->executor->results_groups[$alias][$row_key]['result'] = min($expr, $this->executor->results_groups[$alias][$row_key]['result']);
		}

		return $expr;
	}


	public function max($expr)
	{
		$alias = $this->executor->current_field->getAlias();
		$row_key = $this->executor->current_group_key;

		if (! isset($this->executor->results_groups[$alias])) {
			$this->executor->results_groups[$alias] = [];
		}
		if (! isset($this->executor->results_groups[$alias][$row_key])) {
			$this->executor->results_groups[$alias][$row_key] = [
				'result' => null,
			];
		}

		if (is_null($this->executor->results_groups[$alias][$row_key]['result'])) {
			$this->executor->results_groups[$alias][$row_key]['result'] = $expr;
		} else {
			$this->executor->results_groups[$alias][$row_key]['result'] = max($expr, $this->executor->results_groups[$alias][$row_key]['result']);
		}

		return $expr;
	}


	public function group_concat($expr)
	{
		$alias = $this->executor->current_field->getAlias();
		$row_key = $this->executor->current_group_key;

		if (! isset($this->executor->results_groups[$alias])) {
			$this->executor->results_groups[$alias] = [];
		}
		if (! isset($this->executor->results_groups[$alias][$row_key])) {
			$this->executor->results_groups[$alias][$row_key] = [
				'result' => null,
				'data' => [],
			];
		}

		$delimiter = ",";

		$this->executor->results_groups[$alias][$row_key]['data'][] = $expr;

		$data = $this->executor->results_groups[$alias][$row_key]['data'];
		
		// TODO: sort $data by $order_by
		//$order_by = $this->executor->getOrderFields();

		$this->executor->results_groups[$alias][$row_key]['result'] = implode($delimiter, $data);


		return $expr;
	}



}