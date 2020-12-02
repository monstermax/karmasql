<?php

namespace SqlParser;


trait SqlParent_trait
{
	//protected $parent;
	public $parent;

	/*
    Used by:
    - SqlAction
    - SqlTypeParenthese
    - SqlExpr
    - SqlType
    */


	/**
	 * Get the value of parent
	 */ 
	public function getParent()
	{
		return $this->parent;
	}

	/**
	 * Set the value of parent
	 *
	 * @return  self
	 */ 
	public function setParent($parent)
	{
		$this->parent = $parent;

		return $this;
    }
    
}
