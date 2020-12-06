<?php

namespace SqlParser\SqlAction;

use \SqlParser\SqlExecutor;
use \SqlParser\SqlFragment\SqlFragment;
use \SqlParser\SqlFragment\SqlFragmentQuery;
use \SqlParser\SqlPart\SqlPart;

use \SqlParser\SqlName_trait;
use \SqlParser\SqlParent_trait;
use \SqlParser\SqlItems_trait;

// TODO: a renommer en SqlFragmentAction

class SqlAction extends SqlFragment
{
	//use SqlDebugInfo_trait;
	use SqlName_trait;
	use SqlParent_trait;
	use SqlItems_trait;

	protected $query; // @SqlQuery
	protected $current_part;
	protected $parts = null;
	protected $results;


	public function __construct(SqlFragment $query, $name)
	{
		$this->name = $name;
		$this->query = $query;
		$this->parent = $query;
	}


	function execute()
	{
		// called by sqlfragmentQuery::excecuteQuery

		$executor = new SqlExecutor;
		$this->query->setExecutor($executor);
		return $this->executeAction($executor);
    }
	

	protected function executeAction(SqlExecutor $executor)
	{
		// EXTEND ME
		throw new \Exception("extend me !", 1);
	}

	
	public static function startAction(SqlFragmentQuery $query, $name)
	{
		if ($name == 'select') {
			$action = new SqlActionSelect($query, $name);

		} else if ($name == 'insert') {
			$action = new SqlActionInsert($query, $name);

		} else if ($name == 'update') {
			$action = new SqlActionUpdate($query, $name);

		} else if ($name == 'delete') {
			$action = new SqlActionDelete($query, $name);

		} else if ($name == 'set') {
			$action = new SqlActionSet($query, $name);

		} else if ($name == 'create table') {
			$action = new SqlActionCreateTable($query, $name);

		} else if ($name == 'drop table') {
			$action = new SqlActionDropTable($query, $name);

		} else if ($name == 'truncate table') {
			$action = new SqlActionTruncateTable($query, $name);

		} else {
			$action = new SqlAction($query, $name);
		}

		return $action;
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

		// EXTEND ME
		throw new \Exception("extend me !", 1);
		
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
		throw new \Exception("extend me !", 1);
	}



	public function getParts()
	{
		// called by SqlFragmentQuery::rebuildSql

		return $this->parts;
	}


	public function getPart($part_name)
	{
		// called by SqlAction*::parseParts

		// GENERATOR
		foreach ($this->parts as $part) {
			if ($part->getName() == $part_name) {
				//$parts[] = $part;
				yield $part;
			}
		}
	}


    public function setCurrentPart(SqlPart $action_part=null)
	{
		if (is_null($this->parts)) {
			$this->parts = [];
		}

		if ($this->current_part) {
			$this->current_part->endPart();
		}

		$this->current_part = $action_part;

        if ($action_part) {
			// si c'est non null on l'ajoute a la liste des parts de l'action
            $this->parts[] = $this->current_part;
		}
		
		$this->getFragmentMain()->setCurrentPart($action_part);
	}


	public function getCurrentPart()
	{
		return $this->current_part;
	}


	public function getQuery()
	{
		return $this->query;
	}

	
	public function setQuery($query)
	{
		$this->query = $query;

		return $this;
	}

	
	public function getResults()
	{
		return $this->results;
	}
	
}

