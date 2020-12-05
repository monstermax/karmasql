<?php

namespace SqlParser\SqlType;

use \SqlParser\SqlExecutor;
use \SqlParser\SqlFragment\SqlFragmentMain;
use \SqlParser\SqlParser;


class SqlTypeBracket extends SqlType
{
	public $type = 'bracket';

	public $bracket_type; // bracket


	public function __construct(SqlFragmentMain $fragment_main, $pos, $bracket_type='bracket')
	{
		$fragment_main->logDebug(__CLASS__ . " @ $pos");

		parent::__construct($fragment_main, $pos);
		
		$this->bracket_type = $bracket_type;

		//$fragment_main->addBracket($this);
    }


	public static function isBracketStart(SqlFragmentMain $fragment_main, $char)
	{
		if ($fragment_main->getCurrentBracket()) {
			// on est deja dans un bracket
			return false;
		}

		if ($fragment_main->getCurrentComment()) {
			// on est dans un commentaire
			return false;
		}

		if ($fragment_main->getCurrentString()) {
			// on est dans une string
			return false;
		}

		if ($char == '[') {
            if (! $fragment_main->getParser()->allow_php_variables) {
                throw new \Exception("PHP variables are disabled", 1);
            }
			return 'bracket';
		}

		return false;
	}


	public function isBracketEnd($char)
	{
		if (! $this->fragment_main->getCurrentBracket()) {
			// on n'est PAS dans une bracket
			return false;
		}

		if ($this->fragment_main->getCurrentComment()) {
			// on est dans un commentaire
			return false;
		}

		if ($this->fragment_main->getCurrentString()) {
			// on est dans une string
			return false;
		}

		if ($char == ']') {
			return 'bracket';
		}

		return false;
	}
	


	public function endBracket($pos, $bracket_type='bracket')
	{
		$this->fragment_main->logDebug(__CLASS__ . " @ $pos");

		$current_bracket = $this->fragment_main->getCurrentBracket();
		if (!$current_bracket) {
			throw new \Exception("not in a bracket", 1);
		}
		if ($current_bracket !== $this) {
			throw new \Exception("mismatch bracket", 1);
		}
		
		if ($this->bracket_type != $bracket_type) {
			throw new \Exception("mismatch bracket type", 1);
		}

		$this->end($pos);

		$this->fragment_main->setCurrentBracket(null);
	}



	public function getCalculatedValues(SqlExecutor $executor, $row_data)
	{
		//$alias = $this->pos_start . '-bracket';
		$alias = $this->inner_text;

		return [
			$alias => $this->outer_text,
		];
	}


}
