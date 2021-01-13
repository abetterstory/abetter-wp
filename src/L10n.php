<?php

namespace ABetter\WP;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Corcel\Model\Option;
use ABetter\WP\Post;

class L10n extends Model {

	public static $global;
	public static $plugin;

	public static $master;
	public static $current;
	public static $languages;

	// ---

	public static function parseGlobal() {
		if (isset(self::$global)) return self::$global;
		self::$global = ($plugin = self::plugin()) ? (object) [
			'plugin' => $plugin,
			'current' => self::current('slug'),
			'master' => self::master('slug'),
			'languages' => self::languages(),
		] : NULL;
		return self::$global;
	}

	public static function plugin() {
		if (isset(self::$plugin)) return self::$plugin;
		self::$plugin = FALSE;
		$plugins = implode(',',Option::get('active_plugins'));
		if (preg_match('/polylang/',$plugins)) {
			self::$plugin = 'polylang';
		} else if (preg_match('/sitepress/',$plugins)) {
			self::$plugin = 'wpml';
		}
		return self::$plugin;
	}

	// ---

	public static function master($key=NULL) {
		if (isset(self::$master)) return ($key) ? self::$master->{$key} ?? '' : self::$master;
		$option = Option::get('polylang')['default_lang'] ?? '';
		foreach (self::languages() AS $language) {
			if ($language->slug == $option) self::$master = $language;
		}
		return ($key) ? self::$master->{$key} ?? '' : self::$master;
	}

	public static function current($key=NULL) {
		if (isset(self::$current)) return ($key) ? self::$current->{$key} ?? '' : self::$current;
		self::$current = self::master();
		$slug = ($e = explode('/',trim(strtolower($_SERVER['REQUEST_URI']),'/'))) ? reset($e) : '';
		if (in_array($slug,array_keys(self::languages()))) {
			self::$current = self::languages($slug);
		}
		return ($key) ? self::$current->{$key} ?? '' : self::$current;
	}

	public static function languages($key=NULL) {
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

	public static function switchTranslation($post) {
		if (empty($post->l10n['language'])) return NULL;
		if ($post->l10n['current'] != self::current('slug')) {
			return $post->l10n['translations'][self::current('slug')] ?? NULL;
		}
		return NULL;
	}

	public static function getTranslations($post) {
		$post = (is_numeric($post)) ? Post::getPost('ID',$post) : $post;
		$translations = $post->l10n['translations'] ?? [];
		return $translations;
	}

}
