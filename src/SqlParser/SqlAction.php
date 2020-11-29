<?php

namespace SqlParser;


class SqlAction
{
	use SqlDebugInfo_trait;
	use SqlName_trait;
	use SqlParent_trait;

	public $parser;
	protected $current_part;
	protected $parts = null;


	public function __construct(SqlQueryParser $parser, $name)
	{
		$this->name = $name;
		$this->parser = $parser;
	}


	public function executeAction(SqlExecutor $executor)
	{
		// EXTEND ME
	}


	public static function startAction(SqlQueryParser $parser, $name)
	{
		if ($name == 'select') {
			$part = new SqlActionSelect($parser, $name);

		} else if ($name == 'insert') {
			$part = new SqlActionInsert($parser, $name);

		} else if ($name == 'update') {
			$part = new SqlActionUpdate($parser, $name);

		} else if ($name == 'insert') {
			$part = new SqlActionDelete($parser, $name);

		} else if ($name == 'set') {
			$part = new SqlActionSet($parser, $name);

		} else {
			$part = new SqlAction($parser, $name);
		}

		return $part;
	}


	public function getFieldsSelect()
	{
		$parts = $this->getPart('select');

		$fields = null;
		foreach ($parts as $part) {
			$fields = $part->getFields(); // a list of SqlField and SqlExpr
			break;
		}

		return $fields;
	}


	public function getFieldsGroupBy()
	{		
		$parts = $this->getPart('group by');

		$fields = null;
		foreach ($parts as $part) {
			$fields = $part->getFields(); // a list of SqlField and SqlExpr
			break;
		}

		return $fields;
	}


	public function getFieldsOrderBy()
	{		
		$parts = $this->getPart('order by');

		$fields = null;
		foreach ($parts as $part) {
			$fields = $part->getFields(); // a list of SqlField and SqlExpr
			break;
		}

		return $fields;
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


	public function getTableFrom()
	{		
		$parts = $this->getPart('from');

		$table = null;
		foreach ($parts as $part) {
			$tables = $part->getTables();

			if ($tables) {
				$table = array_shift($tables);
				break;
			}
		}

		return $table;
	}


	public function getTablesJoin()
	{
		$parts = $this->getPart('join');

		$tables = [];
		foreach ($parts as $part) {
			$table = $part->getTable();

			if ($table) {
				$table_alias = $table->getAlias();
				$tables[$table_alias] = $table;
				//$tables = array_merge($tables, $part_tables);
			}
		}

		return $tables;
	}

	
	public function getTablesSubqueries()
	{		
		$tables = [];
		
		// TODO: lister les sous-requetes (liste des parentheses qui ont is_subquery=1)

		return $tables;
	}



	public function getConditionsWhere()
	{
		$parts = $this->getPart('where');

		$conditions = null;
		foreach ($parts as $part) {
			$conditions = $part->getConditions();
			break;
		}

		return $conditions;
	}


	public function getJoins()
	{
		$parts = $this->getPart('join');

		$join_rows = [];
		$joins = null;
		foreach ($parts as $part) {
			$table = $part->getTable();
			$table_alias = $table->getAlias();
			$conditions = $part->getConditions();

			$joins[$table_alias] = $conditions;
			
		}

		return $joins;
	}


	public function getLimit()
	{
		$parts = $this->getPart('limit');

		$offset = 0;
		$limit = null;

		$conditions = null;
		foreach ($parts as $part) {
			$offset = $part->getOffset();
			$limit = $part->getLimit();
			break;
		}

		return [
			'offset' => $offset,
			'limit' => $limit,
		];
	}



	public function parseParts()
	{
		// EXTEND ME
	}


	function execute()
	{
		$executor = new SqlExecutor;

		return $executor->execute($this);
    }

	

	/*
	public function toPhp()
	{
		return $this->name;
	}
	*/


	public function getParts()
	{
		return $this->parts;
	}


	public function getPart($part_name)
	{
		//return isset($this->parts[$part_name]) ? $this->parts[$part_name] : null;

		//$parts = [];
		foreach ($this->parts as $part) {
			if ($part->getName() == $part_name) {
				//$parts[] = $part;
				yield $part;
			}
		}

		//return null;
	}

	public function getCurrentPart()
	{
		return $this->current_part;
	}


	public function setCurrentPart(SqlActionPart $action_part=null)
	{
		if (is_null($this->parts)) {
			$this->parts = [];
		}

		$this->current_part = $action_part;

		if ($action_part) {
			$this->parts[] = $this->current_part;
		}
	}


	/**
	 * Get the value of parser
	 */ 
	public function getParser()
	{
		return $this->parser;
	}

	/**
	 * Set the value of parser
	 *
	 * @return  self
	 */ 
	public function setParser($parser)
	{
		$this->parser = $parser;

		return $this;
	}
	
}

