<?php

namespace SqlParser\SqlAction\SqlActionPart;

use \SqlParser\SqlAction\SqlAction;
use \SqlParser\SqlItems_trait;
use \SqlParser\SqlName_trait;
use \SqlParser\SqlDebugInfo_trait;


class SqlActionPart
{
    use SqlItems_trait;
    use SqlName_trait;
    use SqlDebugInfo_trait;

    protected $action;
    protected $query;


    public function __construct(SqlAction $action, $name)
    {
        $this->action = $action;
        $this->name = $name;
        $this->query = $action->getQuery();
    }


    public static function startPart(SqlAction $action, $name)
    {
        if ($name == 'select') {
            $part = new SqlActionPartSelect($action, $name);
        } elseif ($name == 'from') {
            $part = new SqlActionPartFrom($action, $name);
        } elseif ($name == 'where') {
            $part = new SqlActionPartWhere($action, $name);
        } elseif ($name == 'group by') {
            $part = new SqlActionPartGroupBy($action, $name);
        } elseif ($name == 'join') {
            $part = new SqlActionPartJoin($action, $name);
        } elseif ($name == 'order by') {
            $part = new SqlActionPartOrderBy($action, $name);
        } elseif ($name == 'limit') {
            $part = new SqlActionPartLimit($action, $name);
        } elseif ($name == 'insert') {
            $part = new SqlActionPartInsert($action, $name);
        } elseif ($name == 'into') {
            $part = new SqlActionPartInto($action, $name);
        } elseif ($name == 'values') {
            $part = new SqlActionPartValues($action, $name);
        } elseif ($name == 'update') {
            $part = new SqlActionPartUpdate($action, $name);
        } elseif ($name == 'set') {
            $part = new SqlActionPartSet($action, $name);
        } elseif ($name == 'delete') {
            $part = new SqlActionPartDelete($action, $name);
        } elseif ($name == 'create table') {
            $part = new SqlActionPartCreateTable($action, $name);
        } elseif ($name == 'drop table') {
            $part = new SqlActionPartDropTable($action, $name);
        } elseif ($name == 'truncate table') {
            $part = new SqlActionPartTruncateTable($action, $name);
        } else {
            throw new \Exception('non implemented case');
            $part = new SqlActionPart($action, $name);
        }

        return $part;
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

