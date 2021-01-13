<?php

namespace ABetter\WP;

use ABetter\WP\L10n;

use Illuminate\Database\Eloquent\Model AS BaseModel;

class Dictionary extends BaseModel {

	public static function get($key,$lang='current',$fallback='master',$return='key') {

		$post = L10n::getPost('post_name',$key);
		$current = ($lang == 'current') ? L10n::current('slug') : $lang;
		$fallback = ($fallback == 'master') ? L10n::master('slug') : $fallback;

		$translations = $post->l10n['translations'] ?? [];

		// Try current
		if ($try = L10n::getPost('ID',$translations[$current]??NULL)->post_content ?? NULL) {
			return $try;
		}

		// Try fallback
		if ($try = L10n::getPost('ID',$translations[$fallback]??NULL)->post_content ?? NULL) {
			return $try;
		}

		// Return hint
		return ($return == 'key') ? "{{$key}}" : $return;

	}

}
