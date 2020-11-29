<?php

namespace SqlParser;


trait SqlDebugInfo_trait
{
    protected $debug_skips = ['parser', 'parent', 'action'];


	public function __debugInfo() {
		$values = get_object_vars($this);

        if (true) {
            foreach ($this->debug_skips as $skip) {
                if (isset($values[$skip])) {
                    if (is_object($values[$skip])) {
                        $suffix = '';

                        if (isset($values[$skip]->outer_text)) {
                            $suffix = ' [' . $values[$skip]->outer_text . ']';
                        }

                        $values[$skip] = get_class($values[$skip]) . $suffix;

                    } elseif (is_array($values[$skip])) {
                        $values[$skip] = 'Array(' . count($values[$skip]) . ')';
                    }
                }
            }
        }
        
        unset($values['debug_skips']);

		return $values;
	}

}
