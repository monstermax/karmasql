<?php

namespace SqlParser;


class SqlActionPart
{
	use SqlItems_trait;
	use SqlName_trait;
	use SqlDebugInfo_trait;

	protected $action;
	protected $parser;


	public function __construct(SqlAction $action, $name)
	{
		$this->action = $action;
		$this->name = $name;
		$this->parser = $action->getParser();
	}

	/*
	public function toPhp()
	{
		return $this->name;
	}
	*/


	public static function startPart(SqlAction $action, $name)
	{
		if ($name == 'select') {
			$part = new SqlActionPartSelect($action, $name);

		} else if ($name == 'from') {
			$part = new SqlActionPartFrom($action, $name);

		} else if ($name == 'where') {
			$part = new SqlActionPartWhere($action, $name);

		} else if ($name == 'group by') {
			$part = new SqlActionPartGroupBy($action, $name);

		} else if ($name == 'join') {
			$part = new SqlActionPartJoin($action, $name);

		} else if ($name == 'order by') {
			$part = new SqlActionPartOrderBy($action, $name);

		} else if ($name == 'limit') {
			$part = new SqlActionPartLimit($action, $name);

		} else if ($name == 'insert') {
			$part = new SqlActionPartInsert($action, $name);

		} else if ($name == 'into') {
			$part = new SqlActionPartInto($action, $name);

		} else if ($name == 'values') {
			$part = new SqlActionPartValues($action, $name);

		} else {
			throw new \Exception('non implemented case');
			$part = new SqlActionPart($action, $name);
		}

		return $part;
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

