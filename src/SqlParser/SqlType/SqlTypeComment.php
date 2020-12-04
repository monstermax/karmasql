<?php

namespace SqlParser\SqlType;

use \SqlParser\SqlFragment\SqlFragment;
use \SqlParser\SqlFragment\SqlFragmentMain;


class SqlTypeComment extends SqlType
{
	public $type = 'comment';

	public $comment_type;   //   /* or -- or #


	public function __construct(SqlFragmentMain $fragment_main, $pos, $comment_type)
	{
		$fragment_main->logDebug(__CLASS__ . " @ $pos");

		parent::__construct($fragment_main, $pos);
		
		$this->comment_type = $comment_type;

		if ($comment_type == 'slash') {
			$this->enclosure_start = '/' . '*';
			$this->enclosure_end = '*' . '/';

		} else if ($comment_type == 'dash') {
			$this->enclosure_start = '--';
			$this->enclosure_end = null;

		} else if ($comment_type == 'hash') {
			$this->enclosure_start = '#';
			$this->enclosure_end = null;

		} else {
			throw new \Exception("invalid comment_type " . $comment_type, 1);
		}

		//$fragment_main->addComment($this);
    }


	public static function isCommentStart(SqlFragment $fragment_main, $char, $next_char='')
	{
		if ($fragment_main->getCurrentComment()) {
			// on est deja dans un commentaire
			return false;
		}

		if ($fragment_main->getCurrentString()) {
			// on est dans une string
			return false;
		}

		if ($char . $next_char == '/*') {
			// slash
			return 'slash';
		}

		if ($char . $next_char == '--') {
			// dash
			return 'dash';
		}

		if ($char . $next_char == '#') {
			// hash
			return 'hash';
		}

		return false;
	}


	public function isCommentEnd($char, $next_char='')
	{
		$current_comment = $this->fragment->getCurrentComment();

		if (! $current_comment) {
			// on n'est PAS dans un commentaire
			return false;
		}

		if ($this->fragment->getCurrentString()) {
			// on est dans une string
			return false;
		}

		if ($char . $next_char == '*/') {
			return 'slash';
		}

		$char_id = ord($char);

		if (in_array($char_id, [10, 13])) {
			// end of line

			if (in_array($current_comment->comment_type, ['hash', 'dash'])) {
				return $current_comment->comment_type;
			}
		}

		return false;
	}


	public function endComment($pos, $comment_type='slash')
	{
		$this->fragment->logDebug(__METHOD__ . " @ $pos");

		$current_comment = $this->fragment->getCurrentComment();

		if (empty($current_comment)) {
			throw new \Exception("not in a comment", 1);
		}
		
		if ($current_comment !== $this) {
			throw new \Exception("mismatch comment", 1);
		}

		if (!is_null($comment_type) && $this->comment_type != $comment_type) {
			throw new \Exception("mismatch comment type", 1);
		}

		$this->end($pos);

		$this->fragment_main->setCurrentComment(null);
	}

	public function toPhp($print_debug=false)
	{
		return '';
	}

}
