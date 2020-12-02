<?php

namespace SqlParser\SqlType;

use \SqlParser\SqlFragment\SqlFragment;


class SqlTypeComment extends SqlType
{
	public $type = 'comment';

	public $comment_type;   //   /* or -- or #



	public function toPhp($print_debug=false)
	{
		return '';
	}


	public static function isCommentStart(SqlFragment $fragment, $char, $next_char='')
	{
		if ($fragment->getCurrentComment()) {
			// on est deja dans un commentaire
			return false;
		}

		if ($fragment->getCurrentString()) {
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


	public static function startComment(SqlFragment $fragment, $pos, $comment_type='slash')
	{
		$fragment->logDebug(__METHOD__ . " @ $pos");

		$current_comment = new self;
		$current_comment->comment_type = $comment_type;
		$fragment->setCurrentComment($current_comment);
		
		$current_comment->start($fragment, $pos);

		if ($comment_type == 'slash') {
			$current_comment->enclosure_start = '/*';
			$current_comment->enclosure_end = '*/';

		} else if ($comment_type == 'dash') {
			$current_comment->enclosure_start = '--';
			$current_comment->enclosure_end = null;

		} else if ($comment_type == 'hash') {
			$current_comment->enclosure_start = '#';
			$current_comment->enclosure_end = null;

		} else {
			throw new \Exception("invalid comment_type " . $comment_type, 1);
		}

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

		$this->fragment->addItem($this);
		$this->fragment->addComment($this);

		$this->fragment->setCurrentComment(null);
	}

}
