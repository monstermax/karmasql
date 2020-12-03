<?php

namespace SqlParser\SqlType;

use \SqlParser\SqlExecutor;
use \SqlParser\SqlFragment\SqlFragmentMain;
use \SqlParser\SqlParser;


class SqlTypeString extends SqlType
{
	public $type = 'string';

	public $string_type; // ' or "



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
		if (! $this->fragment->getCurrentString()) {
			// on n'est PAS dans une string
			return false;
		}

		if ($this->fragment->getCurrentComment()) {
			// on est dans un commentaire
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
	

	public static function startString(SqlFragmentMain $fragment_main, $pos, $string_type='simple_quote')
	{
		$fragment_main->logDebug(__METHOD__ . " @ $pos");

		$current_string = new self;
		$current_string->string_type = $string_type;
		$fragment_main->setCurrentString($current_string);

		$current_string->start($fragment_main, $pos);


		if ($string_type == 'double_quote') {
			$current_string->enclosure_start = '"';
			$current_string->enclosure_end = '"';

		} else if ($string_type == "simple_quote") {
			$current_string->enclosure_start = "'";
			$current_string->enclosure_end = "'";
			
		} else  {
			throw new \Exception("invalid string_type " . $string_type, 1);
		}

	}


	public function endString($pos, $string_type='simple_quote')
	{
		$this->fragment->logDebug(__METHOD__ . " @ $pos");

		$current_string = $this->fragment->getCurrentString();
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


		$this->fragment->addItem($this);
		$this->fragment->addString($this);

		$this->fragment->setCurrentString(null);
	}



	public function getCalculatedValues(SqlExecutor $executor, $row_data)
	{
		//$alias = $this->pos_start . '-string';
		$alias = $this->inner_text;

		return [
			$alias => $this->inner_text,
		];
	}


}
