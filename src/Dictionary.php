<?php

namespace ABetter\WP;

use ABetter\WP\L10n;
use ABetter\WP\Post;

class Dictionary extends Post {

	public static function get($key,$lang='current',$fallback='master',$return='key') {
		$post = Post::getPost('post_name',$key);
		$translations = $post->l10n['translations'] ?? [];
		$current = ($lang == 'current') ? L10n::current('slug') : $lang;
		$fallback = ($fallback == 'master') ? L10n::master('slug') : $fallback;
		// Try current
		if ($try = Post::getPost('ID',$translations[$current]??NULL)->post_content ?? NULL) {
			return $try;
		}
		// Try fallback
		if ($try = Post::getPost('ID',$translations[$fallback]??NULL)->post_content ?? NULL) {
			return $try;
		}
		// Return hint
		return ($return == 'key') ? "{{$key}}" : $return;
	}

}
