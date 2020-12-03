<?php

namespace SqlParser;

throw new \Exception("deprecated", 1);

class SqlQuery
{
	public $execute_duration = null;
	public $parse_duration = null;

	public $principal_action = null;
	public $results = null;



	public function execute()
	{
		if (empty($this->principal_action)) {
			return null;
		}
		
		if (empty($this->parse_duration)) {
			$this->parseQuery();
		}
		

		$ts_start = microtime(true);

		$this->results = $this->principal_action->execute();

		$ts_end = microtime(true);
		$this->execute_duration = $ts_end - $ts_start;

		return $this->results;
	}


	public function parseQuery()
	{
		$ts_start = microtime(true);

		$this->principal_action->parseParts();

		$ts_end = microtime(true);
		
		$this->parse_duration = $ts_end - $ts_start;
	}


	public function rebuildSql($to_php=false, $print_debug=false)
	{
		if (empty($this->principal_action)) {
			return '';
			//throw new \Exception("missing principal_action. query must be parsed first", 1);
		}

		$sql = "";

		$principal_action = $this->principal_action;

		if ($principal_action) {
			$parts = $principal_action->getParts();
			if ($parts) {
				foreach ($parts as $part) {
					$sql .= $part->itemsToSql($to_php, $print_debug);
				}
			}
		}

		return $sql;
	}

}
