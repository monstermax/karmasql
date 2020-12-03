<?php

namespace SqlParser\SqlType;

use \SqlParser\SqlFragment\SqlFragmentMain;
use \SqlParser\SqlParent_trait;


class SqlType implements \JsonSerializable
{
	//use SqlDebugInfo_trait;
	
	use SqlParent_trait;
	//protected $parent = null;  // @SqlActionPart | SqlTypeParenthese
	
	public $type = null;
	
	public $fragment_main = null;
	public $query = null;
	public $action = null;
	public $part = null;

	//public $fragment; // le fragment (parent) auquel le type appartient => @SqlFragment*
	//public $action; // @SqlAction
	
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

		$skips = ['fragment', 'parent', 'action'];

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



	public function __construct(SqlFragmentMain $fragment_main, $pos)
	{
		$this->pos_start = $pos;

		$this->fragment_main = $fragment_main;
		$this->fragment_main->addItem($this);

		$this->setParents();

		$debug = 1;
	}


	public function setParents()
	{
		// TODO: ajouter les items a tous les niveaux de parents (et parentheses incluses)

		$old_query = $this->query;
		$old_action = $this->action;
		$old_part = $this->part;

		$this->parent = $this->fragment_main->getCurrentParent();
		$this->query = $this->parent->getQuery();

		if ($this->query) {
			$this->query->addItem($this);
		}

		$this->action = $this->query->getCurrentAction();
		if ($this->action) {
			$this->action->addItem($this);
			
			$current_part = $this->action->getCurrentPart();
			if ($current_part) {
				$current_part->addItem($this);
			}

		} else {
			$current_part = null;
		}
		$this->part = $current_part;


		// delete old parent's item
		if ($old_query && $old_query !== $this->query) {
			$old_query->removeLastItem();
		}
		if ($old_action && $old_action !== $this->action) {
			$old_action->removeLastItem();
		}
		if ($old_part && $old_part !== $this->part) {
			$old_part->removeLastItem();
		}


		if (in_array($this->parent, [$this->query, $this->action, $this->part])) {
			// item already added to the parent
			$debug = 1;

		} else {
			throw new \Exception("debug me. who is my parent ? who am i ?", 1);
			$this->parent->addItem($this);
		}

	}


	public function end($pos)
	{
		$this->pos_end = $pos;

		$extra_enclosure_end = max(0, strlen($this->enclosure_end)-1);

		$start = $this->pos_start;
		$length = $this->pos_end + 1 + $extra_enclosure_end - $this->pos_start;
		$this->outer_text = substr($this->fragment_main->getSql(), $start, $length);
		$this->outer_len = strlen($this->outer_text);

		$start = $this->pos_start + strlen($this->enclosure_start);
		$length = $this->pos_end + 1 - $this->pos_start - strlen($this->enclosure_start) - intval(!!strlen($this->enclosure_end));
		$this->inner_text = substr($this->fragment_main->getSql(), $start, $length);
		$this->inner_len = strlen($this->inner_text);


        if ($this->type === 'word') {
			// pour le SqlTypeWord, le averification est faitre par lui-meme (car pour le 1er mot de la requete, on n'a pas encore d'action definie)

        }else{
            if (! $this->parent) {
                throw new \Exception("missing parent");
            }

            if (! $this->action) {
                throw new \Exception("missing action");
            }

            if (! $this->query) {
                throw new \Exception("missing query");
            }
        }

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
