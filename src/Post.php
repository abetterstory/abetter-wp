<?php

namespace ABetter\WP;

use Illuminate\Support\Facades\DB;
use ABetter\WP\L10n;
use Corcel\Model\Meta\PostMeta;
use Corcel\Model\Post as BaseCorcel;

class Post extends BaseCorcel {

	protected $appends = [
		'language',
		'translations',
		'l10n',
    ];

	// ---

	public function getLanguageAttribute() {
		if (!L10n::plugin()) return NULL;
		$translations = array_flip($this->getTranslationsAttribute());
		$language = $translations[$this->ID] ?? NULL;
		$this->language = $language;
		return $language;
    }

	public function getTranslationsAttribute() {
		if (!L10n::plugin()) return NULL;
		$translations = ($result = DB::connection('wordpress')
			->table('term_taxonomy')
			->select('description')
			->where('taxonomy', 'post_translations')
			->whereRaw("description LIKE '%:{$this->ID};%'")
			->first()) ? (array) unserialize(reset($result)) : [];
		unset($translations['sync']);
		$this->translations = $translations;
		return $translations;
    }

	// ---

	public function getL10nAttribute() {
		if (!L10n::plugin()) return NULL;
		$l10n = [];
		$l10n['languages'] = L10n::languages();
		$l10n['language'] = $this->getLanguageAttribute();
		$l10n['requested'] = L10n::current('slug');
		$l10n['master'] = L10n::master('slug');
		$l10n['current'] = $this->getLanguageAttribute();
		$l10n['translations'] = $this->getTranslationsAttribute();
		$l10n['current_id'] = NULL;
		$l10n['master_id'] = NULL;
		foreach ($l10n['translations'] AS $key => $id) {
			if ($key == $l10n['master']) $l10n['master_id'] = $id;
			if ($key == $l10n['current']) $l10n['current_id'] = $id;
		}
		$l10n['is_master'] = ($l10n['master_id'] == $l10n['current_id']);
		$this->l10n = $l10n;
		return $l10n;
	}

	// ---

	public static function getPost($key,$val,$operator='=') {
		return self::where($key,$operator,$val)->first();
	}

}
