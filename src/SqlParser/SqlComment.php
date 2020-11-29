<?php

namespace SqlParser;


class SqlComment extends SqlParseItem
{
	public $type = 'comment';

	public $comment_type;   //   /* or -- or #



	public function toPhp($print_debug=false)
	{
		return '';
	}


	public static function isCommentStart(SqlParser $parser, $char, $next_char='')
	{
		if ($parser->getCurrentComment()) {
			// on est deja dans un commentaire
			return false;
		}

		if ($parser->getCurrentString()) {
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


	public static function startComment(SqlParser $parser, $pos, $comment_type='slash')
	{
		$parser->logDebug(__METHOD__ . " @ $pos");

		$current_comment = new SqlComment;
		$current_comment->comment_type = $comment_type;
		$parser->setCurrentComment($current_comment);
		
		$current_comment->start($parser, $pos);

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
		$current_comment = $this->parser->getCurrentComment();

		if (! $current_comment) {
			// on n'est PAS dans un commentaire
			return false;
		}

		if ($this->parser->getCurrentString()) {
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
		$this->parser->logDebug(__METHOD__ . " @ $pos");

		$current_comment = $this->parser->getCurrentComment();

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

		$this->parser->addItem($this);
		$this->parser->addComment($this);

		$this->parser->setCurrentComment(null);
	}

}
