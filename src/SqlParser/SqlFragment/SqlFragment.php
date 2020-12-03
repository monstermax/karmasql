<?php

namespace SqlParser\SqlFragment;

use \SqlParser\SqlAction\SqlAction;
use SqlParser\SqlItems_trait;
use SqlParser\SqlParent_trait;
use \SqlParser\SqlParser;
use \SqlParser\SqlQuery;
use \SqlParser\SqlType\SqlType;
use \SqlParser\SqlType\SqlTypeComma;
use \SqlParser\SqlType\SqlTypeComment;
use \SqlParser\SqlType\SqlTypeComparator;
use \SqlParser\SqlType\SqlTypeJoker;
use \SqlParser\SqlType\SqlTypeNumeric;
use \SqlParser\SqlType\SqlTypeOperator;
use \SqlParser\SqlType\SqlTypeParenthese;
use \SqlParser\SqlType\SqlTypeSpace;
use \SqlParser\SqlType\SqlTypeString;
use \SqlParser\SqlType\SqlTypeWord;


class SqlFragment
{
	use SqlItems_trait;
	use SqlParent_trait;


    protected $parser;
    protected $sql = '';
    

    public function __construct($parent, $sql='')
    {
		$this->sql = $sql;
		$this->parent = $parent;
    	$this->parser = $parent->getParser();
    }


	public function logDebug($str)
	{
		//echo "$str<hr />";
	}




    /* GETTERS */

	
	public function getParser()
	{
		return $this->parser;
	}
	

	public function getActionItems(SqlAction $action)
	{
        $sql_actions = $this->getParser()->getSqlActions();
		return ($action && $sql_actions[$action->getName()]) ? $sql_actions[$action->getName()] : [];
	}


	public function getSql()
	{
		return $this->sql;
	}


	public function getQuery()
	{
		$limit = 100;

		$parent = $this;

		while ($limit--) {
			if (get_class($parent) === SqlFragmentQuery::class) {
				return $parent;
			}

            if (get_class($parent) === SqlParser::class || get_class($parent) === SqlFragmentMain::class) {
				throw new \Exception("query not found", 1);
            }

			$parent = $parent->getParent();
		}

		throw new \Exception("fragment query not found", 1);
		return null;
	}


	public function getFragmentMain()
	{
		$limit = 100;

		$parent = $this;

		while ($limit--) {
			if (get_class($parent) === SqlFragmentMain::class) {
				return $parent;
			}
			if (get_class($parent) === SqlParser::class) {
				throw new \Exception("fragment main not found", 1);
			}

			$parent = $parent->getParent();
		}

		throw new \Exception("fragment main not found", 1);
		return null;
	}



    /* SETTERS */

	public function setSql($sql)
	{
		$this->sql = $sql;
	}

}
