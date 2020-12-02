<?php

namespace SqlParser\SqlType;

use \SqlParser\SqlParser;
use \SqlParser\SqlParent_trait;


class SqlType implements \JsonSerializable
{
	//use SqlDebugInfo_trait;
	use SqlParent_trait;
	
	//public $parent = null;  // @SqlActionPart | SqlTypeParenthese
	public $type = null;

	public $parser; // @SqlParser
	public $action; // @SqlAction
	
	public $pos_start;
	public $pos_end;

	public $outer_text;
	public $outer_len;

	public $inner_text;
	public $inner_len;

	public $enclosure_start;
	public $enclosure_end;



	public function jsonSerialize() {
		$values = get_object_vars($this);

		$skips = ['parser', 'parent', 'action', 'items'];

		foreach ($skips as $skip) {
			if (isset($values[$skip])) {
				if (is_object($values[$skip])) {
					$values[$skip] = get_class($values[$skip]);

				} else if (is_array($values[$skip])) {
					$values[$skip] = 'Array(' . count($values[$skip]) . ')';
				}
			}
		}

		return $values;
	}


	public function append($val)
	{
		$this->outer_text .= (string) $val;
	}


	public function toPhp()
	{
		return $this->outer_text;
	}


	public function toSql($to_php=false, $print_debug=false)
	{
		$sql = "";

        if ($to_php) {
			$outer_text = $this->toPhp();
        } else {
			$outer_text = $this->outer_text;
		}

		$sql .= $outer_text;

        if ($print_debug) {
            $idx = 'X';
            $id = 'item-' . $idx . '-' . $this->type;
            echo '<span id="' . $id . '">';
			echo $outer_text;
			echo '</span>';
		}

		return $sql;
	}


	public function start(SqlParser $parser, $pos)
	{

		// idenfication du parent
		$parent = $parser->getCurrentParenthese();
		if (empty($parent)) {
			// on est pas dans une parenthese (on est donc a la racine)
			$current_action = $parser->getCurrentAction();

			if (! $current_action) {
				if (count($parser->getWords()) > 1) {
					//throw new \Exception("missing current_action", 1); // désactivé car sinon empeche les multiples queries
					// TODO: A REVOIR
				}
				
			} else {
				// on a defini le type de query (select, update, insert, ...)
				$current_part = $current_action->getCurrentPart();

				if (! $current_part) {
					throw new \Exception("missing current_part", 1);
					//$parent = $parser;
					$parent = null;

				} else {
					// on a defini dans quel partie de la query on est (from, where, group by, ...)
					$parent = $current_part;
				}
			}
		}

		$this->parser = $parser;
		$this->parent = $parent;
		$this->pos_start = $pos;
	}
	

	public function end($pos)
	{
		$this->pos_end = $pos;

		$extra_enclosure_end = max(0, strlen($this->enclosure_end)-1);

		$start = $this->pos_start;
		$length = $this->pos_end + 1 + $extra_enclosure_end - $this->pos_start;
		$this->outer_text = substr($this->parser->getSql(), $start, $length);
		$this->outer_len = strlen($this->outer_text);

		$start = $this->pos_start + strlen($this->enclosure_start);
		$length = $this->pos_end + 1 - $this->pos_start - strlen($this->enclosure_start) - intval(!!strlen($this->enclosure_end));
		$this->inner_text = substr($this->parser->getSql(), $start, $length);
		$this->inner_len = strlen($this->inner_text);
	}
	

	/*
	// NE PAS UTILISER CAR CA CHANGE les $POS ET PLUS RIEN NE FONCTIONNE
	public function replaceOuterTextIn($str, $str_replace='')
	{
		return substr($str, 0, $this->pos_start) . $str_replace . substr($str, $this->pos_end + strlen($this->enclosure_end));
	}
	*/



	/**
	 * Get the value of action
	 */ 
	public function getAction()
	{
		return $this->action;
	}

	/**
	 * Set the value of action
	 *
	 * @return  self
	 */ 
	public function setAction($action)
	{
		$this->action = $action;

		return $this;
	}


}
