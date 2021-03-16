<?php

// ---

if (!function_exists('_dictionary')) {

	function _dictionary() {
		return \ABetter\WP\Dictionary::get(...func_get_args());
	}

}

if (!function_exists('__d')) {

	function __d() {
		return \ABetter\WP\Dictionary::get(...func_get_args());
	}

}
