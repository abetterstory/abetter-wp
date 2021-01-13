<?php

namespace ABetter\WP;

use Corcel\Model\Post AS CorcelPost;
use Corcel\Model\Option AS CorcelOption;
use Illuminate\Support\Facades\DB;

use Illuminate\Database\Eloquent\Model AS BaseModel;

class L10n extends BaseModel {

	public static $global;
	public static $plugin;

	public static $default;
	public static $master;
	public static $current;
	public static $languages;

	public static $cached = [];

	// ---

	public static function parseGlobal() {
		// Cached
		if (isset(self::$global)) return self::$global;
		self::$global = ($plugin = self::plugin()) ? (object) [
			'plugin' => $plugin,
			'languages' => self::languages(),
			'requested' => self::current('slug'),
			'current' => self::current('slug'),
			'master' => self::master('slug'),
			'is_master' => (self::current('slug') == self::master('slug')),
		] : NULL;
		return self::$global;
	}

	public static function plugin() {
		// Cached
		if (isset(self::$plugin)) return self::$plugin;
		self::$plugin = FALSE;
		$plugins = implode(',',CorcelOption::get('active_plugins'));
		if (preg_match('/polylang/',$plugins)) {
			self::$plugin = 'polylang';
		} else if (preg_match('/sitepress/',$plugins)) {
			self::$plugin = 'wpml';
		}
		return self::$plugin;
	}

	// ---

	public static function default() {
		// Cached
		if (isset(self::$default)) return self::$default;
		self::$default = CorcelOption::get('polylang')['default_lang'];
		if (!self::$default) self::$default = CorcelOption::get('WPLANG');
		if (!self::$default) self::$default = 'en';
		return self::$default;
	}

	public static function master($key=NULL) {
		// Cached
		if (isset(self::$master)) return ($key) ? self::$master->{$key} ?? '' : self::$master;
		$default = self::default();
		foreach (self::languages() AS $language) {
			if ($language->slug == $default) self::$master = $language;
		}
		return ($key) ? self::$master->{$key} ?? '' : self::$master;
	}

	public static function current($key=NULL) {
		// Cached
		if (isset(self::$current)) return ($key) ? self::$current->{$key} ?? '' : self::$current;
		self::$current = self::master();
		$slug = ($e = explode('/',trim(strtolower($_SERVER['REQUEST_URI']),'/'))) ? reset($e) : '';
		if (in_array($slug,array_keys(self::languages()))) {
			self::$current = self::languages($slug);
		}
		return ($key) ? self::$current->{$key} ?? '' : self::$current;
	}

	public static function languages($key=NULL) {
		// Cached
		if (isset(self::$languages)) return ($key) ? self::$languages[$key] ?? '' : self::$languages;
		$result = DB::connection('wordpress')
			->table('term_taxonomy')
			->select('term_taxonomy_id AS tid','description AS data')
			->where('taxonomy', 'language')
			->get();
		self::$languages = [];
		foreach ($result AS $row) {
			$term = ($t = DB::connection('wordpress')->table('terms')->where('term_id',$row->tid)->get()) ? reset($t)[0] ?? [] : [];
			$data = (object) unserialize($row->data);
			$data->term = $term->term_id;
			$data->slug = $term->slug;
			$data->language = strtolower(strtok($data->locale,'_'));
			$data->name = $term->name;
			$data->icon = $data->flag_code; unset($data->flag_code);
			self::$languages[$data->slug] = $data;
		}
		return ($key) ? self::$languages[$key] ?? '' : self::$languages;
    }

	// ---

	public static function switchPostTranslation($post) {
		if (!self::plugin() || empty($post->l10n['language'])) return NULL;
		if ($post->l10n['current'] != self::current('slug')) {
			return $post->l10n['translations'][self::current('slug')] ?? NULL;
		}
		return NULL;
	}

	public static function getPostTranslations($post) {
		if (!self::plugin() || empty($post->l10n['translations'])) return NULL;
		return $post->l10n['translations'] ?? [];
	}

	// ---

	public static function switchPostTranslationById($id) {
		if (!self::plugin()) return NULL;
		$switch = NULL;
		$translations = self::getPostTranslationsById($id);
		$requested = self::current('slug');
		$language = self::getPostLanguageById($id);
		if ($language != $requested) {
			$switch = $translations[$requested] ?? '';
		}
		return $switch;
	}

	// ---

	public static function getPostTranslationsById($id) {
		// Cached
		if (isset(self::$cached[$id]['translations'])) return self::$cached[$id]['translations'];
		if (!self::plugin()) return NULL;
		$translations = ($result = DB::connection('wordpress')
			->table('term_taxonomy')
			->select('description')
			->where('taxonomy', 'post_translations')
			->whereRaw("description LIKE '%:{$id};%'")
			->first()) ? (array) unserialize(reset($result)) : [];
		unset($translations['sync']);
		self::$cached[$id]['translations'] = $translations;
		return $translations;
    }

	public static function getPostLanguageById($id) {
		// Cached
		if (isset(self::$cached[$id]['language'])) return self::$cached[$id]['language'];
		if (!self::plugin()) return NULL;
		$translations = array_flip(self::getPostTranslationsById($id));
		$language = $translations[$id] ?? NULL;
		self::$cached[$id]['language'] = $language;
		return $language;
	}

	// ---

	public static function getPostL10nById($id) {
		if (!self::plugin()) return NULL;
		$l10n = [];
		$l10n['translations'] = self::getPostTranslationsById($id);
		$l10n['requested'] = self::current('slug');
		$l10n['current'] = self::getPostLanguageById($id);
		$l10n['language'] = $l10n['current'];
		$l10n['master'] = self::master('slug');
		$l10n['master_id'] = NULL;
		$l10n['current_id'] = NULL;
		foreach ($l10n['translations'] AS $key => $id) {
			if ($key == $l10n['master']) $l10n['master_id'] = $id;
			if ($key == $l10n['current']) $l10n['current_id'] = $id;
		}
		$l10n['is_master'] = ($l10n['master_id'] == $l10n['current_id']);
		return $l10n;
	}

	// ---

	public static function getPost($key,$val,$operator='=') {
		if ($post = CorcelPost::where($key,$operator,$val)->with('meta')->first()) {
			$post->l10n = self::getPostL10nById($post->ID);
		}
		return $post;
	}

}
