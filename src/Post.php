<?php

namespace ABetter\WP;

use ABetter\WP\L10n;

use Corcel\Model\Post as BaseModel;

class Post extends BaseModel {

	protected $appends = [
		'language',
		'translations',
		'l10n',
    ];

	// ---

	public function getLanguageAttribute() {
		$language = L10n::getPostLanguageById($this->ID);
		$this->language = $language;
		return $language;
    }

	public function getTranslationsAttribute() {
		$translations = L10n::getPostTranslationsById($this->ID);
		$this->translations = $translations;
		return $translations;
    }

	public function getL10nAttribute() {
		$l10n = L10n::getPostL10nById($this->ID);
		$this->l10n = $l10n;
		return $l10n;
	}

	// ---

	public static function getPost($key,$val,$operator='=') {
		return self::where($key,$operator,$val)
			->with('meta')
			->first();
	}

}
