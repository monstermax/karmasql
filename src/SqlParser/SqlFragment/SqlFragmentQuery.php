<?php

namespace SqlParser\SqlFragment;

use \SqlParser\SqlPart\SqlPart;


class SqlFragmentQuery extends SqlFragment
{
	protected $execute_duration = null;
	protected $parse_duration = null;

	protected $action = null;
	protected $action2 = null;	// used only for "insert ... select" which has 2 actions to execute (1 select then 1 insert)
	protected $results = null;


    public function __construct(SqlFragment $fragment_parent, $sql='')
    {
        parent::__construct($fragment_parent, $sql);

        if (get_class($fragment_parent) !== SqlFragmentMain::class) {
            throw new \Exception("wrong parent. SqlFragmentMain expected", 1);
        }
    }


	public function parseQuery()
	{
		$ts_start = microtime(true);

		$this->action->parseParts();

		if ($this->action2) {
			$this->action2->parseParts();
		}

		$ts_end = microtime(true);
		
		$this->parse_duration = $ts_end - $ts_start;
	}


	
	public function executeQuery()
	{
		if (is_null($this->parse_duration)) {
            if (! $this->action) {
				return;
                //throw new \Exception("query not parsed or do not contain any action", 1);
			}
			
			$this->action->parseParts();
			
			if ($this->action2) {
				throw new \Exception("debug me", 1);
				$this->action2->parseParts();
			}
		}

		$ts_start = microtime(true);
		$results = $this->action->execute();
		$ts_end = microtime(true);
		$this->execute_duration = $ts_end - $ts_start;

		$this->results = $results;
	}



	public function rebuildSql($to_php=false, $print_debug=false)
	{
		if (empty($this->action)) {
			return '';
			//throw new \Exception("missing action. query must be parsed first", 1);
		}

		$sql = "";

		if ($this->action) {
			$parts = $this->action->getParts();
			if ($parts) {
				foreach ($parts as $part) {
					$sql .= $part->itemsToSql($to_php, $print_debug);
				}
			}
		}
		
		if ($this->action2) {
			if ($print_debug) {
				echo '<br />';
			}
			$sql .= ' ';
			
            $parts = $this->action2->getParts();
            if ($parts) {
                foreach ($parts as $part) {
                    $sql .= $part->itemsToSql($to_php, $print_debug);
                }
            }
		}

		return $sql;
	}



	public function getParseDuration()
	{
		return $this->parse_duration;
	}


	public function getExecuteDuration()
	{
		return $this->execute_duration;
	}


	/**
	 * Get the value of action
	 */ 
	public function getAction()
	{
		return $this->action;
	}

	/**
	 * Set the value of action
	 *
	 * @return  self
	 */ 
	public function setAction($action)
	{
		$this->action = $action;

		return $this;
	}


	/**
	 * Get the value of action2
	 */ 
	public function getAction2()
	{
		return $this->action2;
	}


	/**
	 * Set the value of action2
	 *
	 * @return  self
	 */ 
	public function setAction2($action2)
	{
		$this->action2 = $action2;

		return $this;
	}

	public function getCurrentAction()
	{
		return $this->action2 ?: $this->action;
	}



	/**
	 * Get the value of results
	 */ 
	public function getResults()
	{
		return $this->results;
		//return $this->getAction()->getResults();
	}
}
