<?php

namespace SqlParser;

use \SqlParser\SqlFragment\SqlFragmentMain;
use \SqlParser\SqlFragment\SqlFragmentQuery;


class SqlParser
{
	// CONFIG VARIABLES
	public $allow_php_functions = true;
	public $allow_php_variables = true;


	// SYSTEM VARIABLES

	protected $sql;
	protected $database = null;
	protected $fragment_main = null;
	protected $parse_duration = null;


	protected $sql_actions = [
		'select' => [
			'select' => 1,
			'from' => 1,
			'where' => 1,
			'join' => 1,
			'inner join' => 1,
			'left join' => 1,
			'right join' => 1,
			'left outer join' => 1,
			'right outer join' => 1,
			'group by' => 1,
			'order by' => 1,
			'limit' => 1,
			// TODO: gerer les join
		],
		'update' => [
			'update' => 1,
			'from' => 1,
			'where' => 1,
			'group by' => 1,
			'order by' => 1,
			'limit' => 1,
			'set' => 1,
			// TODO: gerer les join
		],
		'insert' => [
			'into' => 1,
			'values' => 1,
			'select' => 1,
			'from' => 1,
			'where' => 1,
			'join' => 1,
			'inner join' => 1,
			'left join' => 1,
			'right join' => 1,
			'left outer join' => 1,
			'right outer join' => 1,
			'group by' => 1,
			'order by' => 1,
			'limit' => 1,
		],
		'delete' => [
			'from' => 1,
			'where' => 1,
			'order by' => 1,
			'limit' => 1,
		],
		'set' => [
			'set' => 1,
		],
		'create table' => [
			'create table' => 1,
		],
		'drop table' => [
			'drop table' => 1,
		],
		'rename' => [
			'rename table' => 1,
		],
		'truncate table' => [
			'truncate table' => 1,
		],
		'replace' => 1,
		'show' => 1,
		'desc' => 1,
		'alter' => 1,
		'use' => 1,
	];

	protected $sql_keywords = [
		'left' => 1,
		'right' => 1,
		'inner' => 1,
		'outer' => 1,
		'join' => 1,
		'as' => 1,
		'like' => 1,
		'and' => 1,
		'or' => 1,
		'not' => 1,
		'in' => 1,
		'distinct' => 1,
		'desc' => 1,
		'offset' => 1,
		'using' => 1,
		'on' => 1,
	];

	protected $sql_functions = [
		'version' => 1,
		'curl' => 1, // test

		'if' => 1,
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

		'group_concat' => 1,
		'last_insert_id' => 1,
		'md5' => 1,
		'encrypt' => 1,
		'password' => 1,
		'old_password' => 1,
	];



	public function __construct($sql, & $database=[])
	{
		$this->sql = $sql;
		$this->database = & $database;
		
		$this->fragment_main = new SqlFragmentMain($this, $sql);

		$this->fragment_main->parseSQL();
		$this->parse_duration = $this->fragment_main->getParseDuration();
	}

	
	public function LoadTableToDatabase($table_name, array $data_table)
	{
		$this->database[$table_name] = $data_table;
	}


	public function getParser()
	{
		return $this;
	}


	public function getParsedSql()
	{
		$sqls = [];

		foreach ($this->fragment_main->getQueries() as $query) {
			$sqls[] = $query->rebuildSql(false, false);
		}

		$sql = implode("; ", $sqls);

		return $sql;
	}


	public function showParsedSql()
	{
		// called by main

		echo '<div>';

		foreach ($this->fragment_main->getQueries() as $query_idx => $query) {
			if (empty($query->getAction())) {
				continue;
			}

			if (empty($query->getParseDuration())) {
				$query->parseQuery();
			}

			echo '<div class="jumbotron p-2">';

			echo '<h5>QUERY #' . ($query_idx+1) . ' - ' . strtoupper($query->getAction()->getName()) . ' - REWRITTEN QUERY</h5>';
			$query->rebuildSql(false, true);
			
			echo '</div>';
		}

		echo '</div>';

	}


	public function execute()
	{
		// called by main

		if (is_null($this->parse_duration)) {
			throw new \Exception("invalid parsing. cannot execute", 1);
		}

		$results = $this->fragment_main->executeQueries();

		$this->results = $results;
		
		return $this->results;
	}


	public function showInputSql()
	{
		// called by main


		echo '<div class="jumbotron p-3">';
		echo '<h5>Input SQL</h5>';
		echo '<pre>' . ($this->sql) . '</pre>';
		echo '</div>';
		//echo '<hr />';
	}

	public function showResults()
	{
		// called by main


		echo '<div class="jumbotron p-3">';
		echo '<h5>SQL parsing</h5>';
		echo '<div><small>SQL parsing duration: ' . round($this->parse_duration, 5) . ' second</small></div>';
		echo '</div>';

        foreach ($this->fragment_main->getQueries() as $query_idx => $query) {
            $this->showQueryResults($query, $query_idx);
        }
	}


	public function showQueryResults(SqlFragmentQuery $query, $query_idx=0)
	{
		// called by main


		if (empty($query->getAction())) {
			return;
		}

		$results = $query->getResults();

		if (! $results) {
			$results = [];
		}

		
		$html = '';
		$html .= '<div class="jumbotron p-3">';

		$html .= '<h5>QUERY #' . ($query_idx+1) . ' - ' . strtoupper($query->getAction()->getName()) . ' - RESULTS</h5>';

		$html .= '<table class="table table-hover table-striped table-bordered bg-light" border="1" width="100%">';
		$html .= '	<thead>';
		$html .= '		<tr>';

        if ($results) {
            $rows = array_values($results);

            foreach ($rows[0] as $field_name => $value) {
                $html .= '			<td>' . $field_name  . '</td>';
            }

            $html .= '		</tr>';
            $html .= '	</thead>';
            $html .= '	<tbody>';

            foreach ($rows as $result_idx => $result) {
                $html .= '		<tr>';
                
                foreach ($result as $field_name => $value) {
					if (is_null($value)) {
						$value = '<i>NULL</i>';
					}
					if (is_array($value)) {
						$value = json_encode($value);

					} else if (is_object($value)) {
						$value = json_encode($value);
					}
                    $html .= '			<td>' . $value  . '</td>';
                }
                
                $html .= '		</tr>';
            }
        }

		$html .= '	</tbody>';
		$html .= '</table>';

		$result_name = in_array($query->getAction()->getName(), ['select']) ? 'results' : 'affected rows';

		$html .= '<div><small>Query parsing duration: ' . round($query->getParseDuration(), 5) . ' second</small></div>';
		$html .= '<div><small>Execute duration: ' . round($query->getExecuteDuration(), 5) . ' second</small><small> (' . count($results) . ' ' . $result_name . ')</small></div>';

		$html .= '</div>';

		echo $html;
	}
	
	
	public function showDatabase()
	{
		// called by main
		
		//echo '<hr /><pre>DATABASE: ' . print_r($this->database, true) . '</pre>';

		foreach ($this->database as $table_name => $table) {
			$this->showDatabaseTable($table, $table_name);
		}
	}
	
	
	public function showDatabaseTable(array $data_table, $table_name=null)
	{
		// called by main
		// called by SqlParser::showDatabase()

		//echo '<hr /><pre>DATABASE - TABLE `' . $table_name . '` : ' . PHP_EOL . print_r($table, true) . '</pre>';

		if ($table_name == '_variables') {
			return;
		}

		$html = '<h5>DATABASE - TABLE `' . $table_name . '`</h5>';

		$html .= '<div class="jumbotron p-3">';

		$html .= '<table class="table table-hover table-striped table-bordered bg-light" border="1" width="100%">';
		$html .= '	<thead>';
		$html .= '		<tr>';

        if (! empty($data_table)) {
            $rows = array_values($data_table);

            foreach ($rows[0] as $field_name => $value) {
                $html .= '			<td>' . $field_name  . '</td>';
            }

            $html .= '		</tr>';
            $html .= '	</thead>';
            $html .= '	<tbody>';

            foreach ($rows as $result_idx => $result) {
                $html .= '		<tr>';
                
                foreach ($result as $field_name => $value) {
					if (is_null($value)) {
						$value = '<i>NULL</i>';
					}
                    $html .= '			<td>' . $value  . '</td>';
                }
                
                $html .= '		</tr>';
            }
        }

		$html .= '	</tbody>';
		$html .= '</table>';

		$html .= '</div>';

		echo $html;
	}




	/* GETTERS */

	public function getDatabase()
	{
		return $this->database;
	}

	public function getDatabaseTable($table_name)
	{
		return isset($this->database[$table_name]) ? $this->database[$table_name] : null;
	}

	public function getSqlActions()
	{
		return $this->sql_actions;
	}

	public function getSqlFunctions()
	{
		return $this->sql_functions;
	}

	public function getSqlKeywords()
	{
		return $this->sql_keywords;
	}

	public function getSql()
	{
		return $this->sql;
	}



	/* SETTERS */

	public function setDatabase($database, $table_name=null)
	{
		//$this->database = $database;

		if (is_null($this->database)) {
			$this->database = [];
		}

		foreach ($database as $tmp_table_name => $table) {
			if (! is_null($table_name) && $tmp_table_name !== $table_name) {
				continue;
			}
			$this->database[$tmp_table_name] = $table;
		}

	}

	public function setSql($sql)
	{
		$this->sql = $sql;
	}


}
