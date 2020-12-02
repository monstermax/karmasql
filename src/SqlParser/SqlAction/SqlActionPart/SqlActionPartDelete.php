<?php

namespace SqlParser\SqlAction\SqlActionPart;


class SqlActionPartDelete extends SqlActionPart
{
	protected $part_name = 'delete';


	public function parsePart()
	{
		$tmp_params = $this->getParamsFromItems(false);

        $debug = 1;

        // TODO: detecter keyword "ignore"
        
	}
	
	

}
