<?php

namespace SqlParser;


trait SqlName_trait
{
	protected $name;

	/*
	Used by:
	- SqlTable
	- SqlField
	*/


    /**
     * Get the value of name
     */ 
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the value of name
     *
     * @return  self
     */ 
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

}
