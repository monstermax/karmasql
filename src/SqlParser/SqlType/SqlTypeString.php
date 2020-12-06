<?php

namespace SqlParser\SqlType;

use \SqlParser\SqlExecutor;
use \SqlParser\SqlFragment\SqlFragmentMain;
use \SqlParser\SqlParser;


class SqlTypeString extends SqlType
{
	public $type = 'string';

	public $string_type; // ' or "


	public function __construct(SqlFragmentMain $fragment_main, $pos, $string_type)
	{
		$fragment_main->logDebug(__CLASS__ . " @ $pos");

		parent::__construct($fragment_main, $pos);
		
		$this->string_type = $string_type;

		if ($string_type == 'double_quote') {
			$this->enclosure_start = '"';
			$this->enclosure_end = '"';

		} else if ($string_type == "simple_quote") {
			$this->enclosure_start = "'";
			$this->enclosure_end = "'";
			
		} else  {
			throw new \Exception("invalid string_type " . $string_type, 1);
		}

		//$fragment_main->addString($this);
    }


	public static function isStringStart(SqlFragmentMain $fragment_main, $char)
	{
		if ($fragment_main->getCurrentString()) {
			// on est deja dans une string
			return false;
		}

		if ($fragment_main->getCurrentComment()) {
			// on est dans un commentaire
			return false;
		}

        if ($fragment_main->getCurrentBracket()) {
            // on est dans un bracket
            return false;
        }


		if ($char == '"') {
			// "
			return 'double_quote';
		}

		if ($char == "'") {
			// '
			return "simple_quote";
		}

		return false;
	}


	public function isStringEnd($char)
	{
		if (! $this->fragment_main->getCurrentString()) {
			// on n'est PAS dans une string
			return false;
		}

		if ($this->fragment_main->getCurrentComment()) {
			// on est dans un commentaire
			return false;
		}

		if ($this->fragment_main->getCurrentBracket()) {
			// on est dans un bracket
			return false;
		}

		if ($char == '"') {
			// "
			return 'double_quote';
		}

		if ($char == "'") {
			// '
			return "simple_quote";
		}

		return false;
	}
	


	public function endString($pos, $string_type='simple_quote')
	{
		$this->fragment_main->logDebug(__CLASS__ . " @ $pos");

		$current_string = $this->fragment_main->getCurrentString();
		if (!$current_string) {
			throw new \Exception("not in a string", 1);
		}
		if ($current_string !== $this) {
			throw new \Exception("mismatch string", 1);
		}
		
		if ($this->string_type != $string_type) {
			throw new \Exception("mismatch string type", 1);
		}

		$this->end($pos);

		$this->fragment_main->setCurrentString(null);
	}



	public function getCalculatedValues(SqlExecutor $executor, $row_data)
	{
		// called by SqlExecutor::calculateFields()

		//throw new \Exception("used by ?", 1);

		$alias = $this->inner_text;

		return [
			$alias => $this->inner_text,
		];
	}


}
