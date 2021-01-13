<?php

// ---

if (!function_exists('_dictionary')) {

	function _dictionary($key,$lang='current',$fallback='master',$return='key') {
		return \ABetter\WP\Dictionary::get($key,$lang,$fallback,$return);
	}

}
