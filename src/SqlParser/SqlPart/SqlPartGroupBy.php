<?php

namespace SqlParser\SqlPart;

use \SqlParser\SqlExpr;


class SqlPartGroupBy extends SqlPart
{
	protected $part_name = 'group by';
	
	protected $fields = [];


	public function parsePart()
	{
		$fields = []; // note: devrait s'appeler $fields_and_expressions

		$tmp_params = $this->getParamsFromItems(true);

		foreach ($tmp_params as $param) {
			$is_expr = (get_class($param) === SqlExpr::class);

            if ($is_expr) {
				$fields[] = $param;
				
				// parse l'expression
				$param->detectFields();

			} else {
				throw new \Exception("non implemented case");
			}

		}
		unset($param);

		$this->fields = $fields;
		return $fields;
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
