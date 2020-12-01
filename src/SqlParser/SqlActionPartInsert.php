<?php

namespace SqlParser;


class SqlActionPartInsert extends SqlActionPart
{
	protected $part_name = 'insert';


	public function parsePart()
	{
		$tmp_params = $this->getParamsFromItems(false);

		if ($tmp_params) {
			// TODO: detection le mot "ignore" entre "insert" et "into"
		}
		
		$debug = 1;
	}

}
