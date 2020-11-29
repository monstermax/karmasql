<?php

namespace SqlParser;


trait SqlAlias_trait
{
	protected $alias;

	/*
	Used by:
	- SqlTable
	- SqlField
	- SqlExpr
	*/

	/**
	 * Get the value of alias
	 */ 
	public function getAlias()
	{
		return $this->alias;
	}

	/**
	 * Set the value of alias
	 *
	 * @return  self
	 */ 
	public function setAlias($alias)
	{
		$this->alias = $alias;

		return $this;
	}


}
