<?php

namespace SqlParser;


class SqlActionPartInsert extends SqlActionPart
{
	protected $part_name = 'insert';


	public function parsePart()
	{
		$tmp_params = $this->getParamsFromItems(false);

        $debug = 1;

        // TODO: detecter keyword "ignore"
        
	}
	
	

}
