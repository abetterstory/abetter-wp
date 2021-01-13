<?php

namespace ABetter\WP;

use ABetter\WP\L10n;

use Corcel\Model\Post as BaseModel;

class Post extends BaseModel {

	protected $casts = [
        'l10n' => 'array',
    ];

	protected $appends = [
		'l10n',
    ];

	// ---

	public function getL10nAttribute() {
		return L10n::getPostL10nById($this->ID);
	}

	// ---

	public static function getPost($key,$val,$operator='=') {
		return self::where($key,$operator,$val)
			->with('meta')
			->first();
	}

}
