<?php

// ---

if (!function_exists('_dictionary')) {

	function _dictionary() {
		return \ABetter\WP\Dictionary::get(...func_get_args());
	}

}
