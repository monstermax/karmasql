<?php

namespace SqlParser\SqlType;

use \SqlParser\SqlExecutor;
use \SqlParser\SqlParser;


class SqlTypeString extends SqlType
{
	public $type = 'string';

	public $string_type; // ' or "



	public static function isStringStart(SqlParser $parser, $char)
	{
		if ($parser->getCurrentString()) {
			// on est deja dans une string
			return false;
		}

		if ($parser->getCurrentComment()) {
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
		if (! $this->parser->getCurrentString()) {
			// on n'est PAS dans une string
			return false;
		}

		if ($this->parser->getCurrentComment()) {
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
	

	public static function startString(SqlParser $parser, $pos, $string_type='simple_quote')
	{
		$parser->logDebug(__METHOD__ . " @ $pos");

		$current_string = new self;
		$current_string->string_type = $string_type;
		$parser->setCurrentString($current_string);

		$current_string->start($parser, $pos);


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
		$this->parser->logDebug(__METHOD__ . " @ $pos");

		$current_string = $this->parser->getCurrentString();
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


		$this->parser->addItem($this);
		$this->parser->addString($this);

		$this->parser->setCurrentString(null);
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
