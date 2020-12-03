<?php

namespace SqlParser;

use \SqlParser\SqlFragment\SqlFragment;


trait SqlParent_trait
{
	protected $parent;


	/**
	 * Get the value of parent
	 */ 
	public function getParent()
	{
		return $this->parent; /* @SqlFragment */
	}

	/**
	 * Set the value of parent
	 *
	 * @return  self
	 */ 
	public function setParent(SqlFragment $parent)
	{
		$this->parent = $parent;

		return $this;
    }
    
}
