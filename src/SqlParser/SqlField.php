<?php

namespace SqlParser;


class SqlField /* extends SqlExpr */
{
	use SqlName_trait;
	use SqlAlias_trait;
	use SqlDebugInfo_trait;

	public $parent; // @SqlTypeWord
	protected $table; // @SqlTable
	//public $tmp_table_alias = null;


	public function __construct($name, SqlTable $table=null, $alias=null)
	{
		$this->name = $name;
		$this->table = $table;
		$this->alias = $alias ? $alias : $name;
		
		//unset($this->items);
	}


	public function getCalculatedValues(SqlExecutor $executor, $row_data)
	{
		// called by SqlExecutor::calculateFields()

		//throw new \Exception("called by ?", 1);

		$table_alias = $this->getTable()->getAlias();

		return [
			$this->alias => $row_data[$table_alias][$this->alias],
		];
	}


	public function toPhp()
	{
		// called by SqlField::toSql()

		//throw new \Exception("called by ?", 1);

		$field_name = $this->getName();
		//$field_alias = $this->getAlias();

		$table = $this->getTable();
		if (! $table) {
			throw new \Exception('missing table', 1);
		}

		//$table_name = $table->getName();
		$table_alias = $table->getAlias();

		//$sql = '$tables["' . $table_alias . '"][$current_row_idx]["' . $field_name . '"]';
		$sql = '$row_data["' . $table_alias . '"]["' . $field_name . '"]';

		return $sql;
	}
	

	public function toSql($to_php=false, $print_debug=false)
	{
		// called by SqlTypeWord::toSql

		if ($to_php) {
			$sql = $this->toPhp();

			if ($print_debug) {
				echo $sql;
			}

			return $sql;
		}

		$field_name = $this->getName();
		$field_alias = $this->getAlias();

		$table = $this->getTable();
		if (! $table) {
			throw new \Exception('missing table on field ' . $field_name, 1);
			$sql = "UNKNOWN_TABLE_C." . $field_name;

		} else {

            //$table_name = $table->getName();
            $table_alias = $table->getAlias();

            //$sql = $field_name;
            $sql = $table_alias . "." . $field_name;

            if ($field_alias && $field_alias != $field_name) {
                // append alias
                $sql .= " as " . $field_alias;
            }
        }

		if ($print_debug) {
			echo $sql;
		}

		return $sql;
	}



	public function getTable()
	{
		return $this->table;
	}


	public function setTable(SqlTable $table)
	{
		$this->table = $table;

		return $this;
	}

}
