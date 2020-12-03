<?php

namespace SqlParser\SqlPart;


class SqlPartDelete extends SqlPart
{
	protected $part_name = 'delete';


	public function parsePart()
	{
		$tmp_params = $this->getParamsFromItems(false);

        $debug = 1;

        // TODO: detecter keyword "ignore"
        
	}
	
	

}
