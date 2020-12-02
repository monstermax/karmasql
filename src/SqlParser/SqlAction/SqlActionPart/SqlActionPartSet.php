<?php

namespace SqlParser\SqlAction\SqlActionPart;


class SqlActionPartSet extends SqlActionPart
{
	protected $part_name = 'set';
    
    protected $fields = [];


	public function parsePart()
	{
        $tmp_params = $this->getParamsFromItems(false);
                
        foreach ($tmp_params as $param) {
            $param->detectFields();
        }

        $this->fields = $tmp_params;

        return $this->fields;
	}
	
	


    /**
     * Get the value of fields
     */ 
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Set the value of fields
     *
     * @return  self
     */ 
    public function setFields($fields)
    {
        $this->fields = $fields;

        return $this;
    }
    
}
