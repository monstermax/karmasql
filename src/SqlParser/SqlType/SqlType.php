<?php

namespace SqlParser\SqlType;

use \SqlParser\SqlFragment\SqlFragmentMain;
use \SqlParser\SqlFragment\SqlFragmentParenthese;
use \SqlParser\SqlFragment\SqlFragmentQuery;
use \SqlParser\SqlPart\SqlPart;
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
	public $parenthese = null; // la parenthese parente directe

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
		//$this->fragment_main->addItem($this);

		$this->setParents();

		$debug = 1;
	}


	public function setParents()
	{
		// TODO: ajouter les items a tous les niveaux de parents (et parentheses incluses)

		//$old_query = $this->query;
		//$old_action = $this->action;
		//$old_part = $this->part;

		$this->parent = $this->fragment_main->getCurrentParent();
		$this->query = $this->parent->getQuery();
		$this->action = $this->query->getCurrentAction();
		$this->part = $this->action ? $this->action->getCurrentPart() : null;

        if (! is_a($this->parent, SqlPart::class) && get_class($this->parent) !== SqlFragmentParenthese::class) {
            if (get_class($this->parent) === SqlFragmentQuery::class) {
                if ($this->type === 'space') {
					// skip space error

                } else if ($this->type === 'word') {
                    // pour le SqlTypeWord, la verification (de presence d'une action) est faite par lui-meme (car pour le 1er mot de la requete, on n'a pas encore d'action definie)
                } else {
                    throw new \Exception("parent cannot be the query", 1);
                }
            } else{
				throw new \Exception("unknown parent", 1);
			}
        }

	}


	public function end($pos, $add_to_parents=true)
	{
		$this->pos_end = $pos;

		
		// SET OUTER_TEXT
		$extra_enclosure_end = max(0, strlen($this->enclosure_end)-1);
		$start = $this->pos_start;
		$length = $this->pos_end + 1 + $extra_enclosure_end - $this->pos_start;
		$this->outer_text = substr($this->fragment_main->getSql(), $start, $length);
		$this->outer_len = strlen($this->outer_text);

		// SET INNER_TEXT
		$start = $this->pos_start + strlen($this->enclosure_start);
		$length = $this->pos_end + 1 - $this->pos_start - strlen($this->enclosure_start) - intval(!!strlen($this->enclosure_end));
		$this->inner_text = substr($this->fragment_main->getSql(), $start, $length);
		$this->inner_len = strlen($this->inner_text);


		if ($add_to_parents) {
			// SET PARENTS
			$this->addItemToParents();
		}


		if (! $this->parent) {
			throw new \Exception("missing parent");
		}

		if (! $this->action) {
            if ($this->type === 'space') {
				// skip space error
				
            } else if ($this->type === 'word') {
				// pour le SqlTypeWord, la verification (de presence d'une action) est faite par lui-meme (car pour le 1er mot de la requete, on n'a pas encore d'action definie)
	
			}else{
				throw new \Exception("missing action");
			}
		}

		if (! $this->query) {
			throw new \Exception("missing query");
		}


	}
	
	public function addItemToParents()
	{
		$current_parenthese = $this->fragment_main->getCurrentParenthese();

		$parent_parenthese = ($current_parenthese === $this) ? $this->parenthese : $current_parenthese;

		
        if ($parent_parenthese) {
			$parent_parenthese->getFragments()[0]->addItem($this);

        } else {
			// ADD ITEM TO PARENTS
			$this->fragment_main->addItem($this);

			if ($this->query) {
				$this->query->addItem($this);
			}
			if ($this->action) {
				$this->action->addItem($this);
			}
			if ($this->part) {
				$this->part->addItem($this);
			}

    
            // SET PARENTS SQL (experimental. a but de debug uniquement)
            if ($this->query) {
				$this->query->sql .= $this->outer_text;
				
            } else {
                throw new \Exception("missing query");
            }
    
            if ($this->action) {
				$this->action->sql .= $this->outer_text;
				
            } else {
                if ($this->type === 'space') {
					// skip space error

                } else if ($this->type === 'word') {
					// pour le SqlTypeWord, la verification (de presence d'une action) est faite par lui-meme (car pour le 1er mot de la requete, on n'a pas encore d'action definie)
					
                } else {
                    throw new \Exception("missing action");
                }
            }
    
            if ($this->part) {
				$this->part->sql .= $this->outer_text;
				
            } else {
                if ($this->type === 'space') {
					// skip space error

                }else if ($this->type === 'word') {
					// pour le SqlTypeWord, la verification (de presence d'une action) est faite par lui-meme
					
                } else {
                    throw new \Exception("missing part");
                }
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


	
	public function getParser()
	{
		return $this->fragment_main->getParser();
	}
	


}
