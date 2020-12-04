<?php

namespace SqlParser\SqlPart;

use \SqlParser\SqlAction\SqlAction;
use \SqlParser\SqlFragment\SqlFragment;

use \SqlParser\SqlItems_trait;
use \SqlParser\SqlName_trait;
use \SqlParser\SqlParent_trait;
use \SqlParser\SqlDebugInfo_trait;


// TODO: a renommer en SqlFragmentPart

class SqlPart extends SqlFragment
{
    use SqlItems_trait;
    use SqlName_trait;
    use SqlDebugInfo_trait;
    use SqlParent_trait;


    protected $action;
    protected $query;


    public function __construct(SqlAction $action, $name)
    {
        $this->action = $action;
        $this->name = $name;
        $this->query = $action->getQuery();
        $this->parent = $action;
    }


    public static function startPart(SqlAction $action, $name)
    {
        if ($name == 'select') {
            $part = new SqlPartSelect($action, $name);
        } elseif ($name == 'from') {
            $part = new SqlPartFrom($action, $name);
        } elseif ($name == 'where') {
            $part = new SqlPartWhere($action, $name);
        } elseif ($name == 'group by') {
            $part = new SqlPartGroupBy($action, $name);
        } elseif ($name == 'join') {
            $part = new SqlPartJoin($action, $name);
        } elseif ($name == 'order by') {
            $part = new SqlPartOrderBy($action, $name);
        } elseif ($name == 'limit') {
            $part = new SqlPartLimit($action, $name);
        } elseif ($name == 'insert') {
            $part = new SqlPartInsert($action, $name);
        } elseif ($name == 'into') {
            $part = new SqlPartInto($action, $name);
        } elseif ($name == 'values') {
            $part = new SqlPartValues($action, $name);
        } elseif ($name == 'update') {
            $part = new SqlPartUpdate($action, $name);
        } elseif ($name == 'set') {
            $part = new SqlPartSet($action, $name);
        } elseif ($name == 'delete') {
            $part = new SqlPartDelete($action, $name);
        } elseif ($name == 'create table') {
            $part = new SqlPartCreateTable($action, $name);
        } elseif ($name == 'drop table') {
            $part = new SqlPartDropTable($action, $name);
        } elseif ($name == 'truncate table') {
            $part = new SqlPartTruncateTable($action, $name);
        } else {
            throw new \Exception('non implemented case');
            $part = new SqlPart($action, $name);
        }

        return $part;
    }


    public function endPart()
    {
        //$this->pos_end = $pos;

        $debug = 1;

        $this->action->getFragmentMain()->setCurrentPart(null);
    }


    public function getAction()
    {
        return $this->action;
    }


	public function setAction($action)
    {
        $this->action = $action;

        return $this;
	}
	

    public function getQuery()
    {
        return $this->query;
	}
	

    public function setQuery($query)
    {
        $this->query = $query;

        return $this;
    }
}

