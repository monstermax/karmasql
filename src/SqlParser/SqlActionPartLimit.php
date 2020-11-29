<?php

namespace SqlParser;


class SqlActionPartLimit extends SqlActionPart
{
	protected $part_name = 'limit';
	
	protected $fields = [];

	protected $limit = null;
	protected $offset = 0;


	public function parsePart()
	{
		$tmp_params = $this->getParamsFromItems(false);

		$limit = null;
		$offset = 0;
		
		if ($tmp_params) {
			if (count($tmp_params) == 1) {
				if (get_class($tmp_params[0]) !== SqlNumeric::class) {
					throw new \Exception("wrong value type", 1);
				}
				$limit = $tmp_params[0]->number;
				
			} else if (count($tmp_params) == 2) {
				if (get_class($tmp_params[0]) !== SqlNumeric::class) {
					throw new \Exception("wrong value type", 1);
				}
				if (get_class($tmp_params[1]) !== SqlNumeric::class) {
					throw new \Exception("wrong value type", 1);
				}

				$offset = $tmp_params[0]->number;
				$limit = $tmp_params[1]->number;

			} else {
				throw new \Exception("unknown case");
			}

			// todo: detecter le keyword "offset"
		}

		$this->limit = $limit;
		$this->offset = $offset;

		
		return [
			'limit' => $limit,
			'offset' => $offset,
		];
	}
	


	/**
	 * Get the value of limit
	 */ 
	public function getLimit()
	{
		return $this->limit;
	}

	/**
	 * Set the value of limit
	 *
	 * @return  self
	 */ 
	public function setLimit($limit)
	{
		$this->limit = $limit;

		return $this;
	}
	
	


	/**
	 * Get the value of offset
	 */ 
	public function getOffset()
	{
		return $this->offset;
	}

	/**
	 * Set the value of offset
	 *
	 * @return  self
	 */ 
	public function setOffset($offset)
	{
		$this->offset = $offset;

		return $this;
	}
	

}
