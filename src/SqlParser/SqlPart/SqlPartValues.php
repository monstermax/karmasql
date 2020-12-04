<?php

namespace SqlParser\SqlPart;


class SqlPartValues extends SqlPart
{
	protected $part_name = 'values';
    
    protected $rows = [];


	public function parsePart()
	{
        $tmp_params = $this->getParamsFromItems(false);

        $insert_keys = $this->getAction()->getInsertKeys();

        if (empty($insert_keys)) {
            $table_into = $this->getAction()->getParts()[1]->getTable();
            $insert_keys = $table_into->getFieldsNames();
        }

        
        $rows = [];

        foreach ($tmp_params as $tmp_param) {
            $parenthese_params = $tmp_param->getParamsFromItems(false);
            
            $row = [];
            foreach ($parenthese_params as $idx => $param) {
                if ($param->type === 'word') {
                    $param->detectFields();
                }

                $item_key = $insert_keys[$idx];
                $field_name = $item_key->word;
                $row[$field_name] = $param;
            }

            $rows[] = $row;
        }

        $this->rows = $rows;

        return $rows;
	}
	
	


    /**
     * 
     * Get the value of rows
     */ 
    public function getRows()
    {
        return $this->rows;
    }

    /**
     * Set the value of rows
     *
     * @return  self
     */ 
    public function setRows($rows)
    {
        $this->rows = $rows;

        return $this;
    }

}
