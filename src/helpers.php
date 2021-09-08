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

// ---

if (!function_exists('_wp_cdn')) {

	function _wp_cdn($url,$cache='/_cache/') {
		if (empty($url)) return $url;
		if (($domain = env('WP_CDN')) && preg_match('/\/wp-content\/uploads\//',$url)) {
			return 'https://'.preg_replace('/^https?\:\/+/','',$domain).(explode('/wp-content/uploads',$url)[1] ?? "");
		}
		return $cache.$url;
	}

}
