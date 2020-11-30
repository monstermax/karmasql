<?php

namespace SqlParser;


class SqlExecutor
{
	public $results_groups;
	public $current_group_key;
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


    /* ####################################### */



    public function version()
    {
        $ver = '0.0.1';
        $suffix = '';

        if (defined('APP_DIR')) {
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