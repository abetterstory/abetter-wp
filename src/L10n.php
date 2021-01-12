<?php

namespace ABetter\WP;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Corcel\Model\Option;
use Corcel\Model\Post as Corcel;

class L10n extends Corcel {

	public static $global;
	public static $post;
	public static $plugin;
	public static $localizations;
	public static $master;
	public static $current;
	public static $translations;

	// ---

	public static function parseGlobal() {
		if (isset(self::$global)) return self::$global;
		self::$global = ($plugin = self::plugin()) ? (object) [
			'plugin' => $plugin,
			'current' => self::current('slug'),
			'master' => self::master('slug'),
			'list' => self::localizations(),
		] : NULL;
		return self::$global;
	}

	public static function parsePost($post) {
		if (isset(self::$post)) return self::$post;
		self::$post = $post;
		$trans = ($t = $post->taxonomies()->where('taxonomy','post_translations')->first()) ? (array) unserialize($t->description) : [];
		$l10n = (object) [];
		$l10n->localization = self::current('slug');
		$l10n->language = self::current('language');
		$l10n->master = NULL;
		foreach ($trans AS $key => $id) {
			if ($id == $post->ID) $l10n->localization = $key;
			if ($id != $post->ID && $key == self::master('slug')) {
				$l10n->master = self::localizations($key);
				$l10n->master->ID = $id;
				$l10n->master->post = Corcel::where('ID', $id)->first();
			}
		}
		$l10n->language = self::localizations($l10n->localization)->language ?? '';
		$l10n->locale = self::localizations($l10n->localization)->locale ?? '';
		self::$post->l10n = $l10n;
		return self::$post;
	}

	// ---

	public static function plugin() {
		if (isset(self::$plugin)) return self::$plugin;
		self::$plugin = FALSE;
		$plugins = implode(',',Option::get('active_plugins'));
		if (preg_match('/polylang/',$plugins)) {
			self::$plugin = 'polylang';
		} else if (preg_match('sitepress',$plugins)) {
			self::$plugin = 'wpml';
		}
		return self::$plugin;
	}

	// ---

	public static function localizations($key=NULL) {
		if (isset(self::$localizations)) return ($key) ? self::$localizations[$key] ?? '' : self::$localizations;
		$result = DB::connection('wordpress')
			->table('term_taxonomy')
			->select('term_taxonomy_id AS tid','description AS data')
			->where('taxonomy', 'language')
			->get();
		self::$localizations = [];
		foreach ($result AS $row) {
			$term = ($t = DB::connection('wordpress')->table('terms')->where('term_id',$row->tid)->get()) ? reset($t)[0] ?? [] : [];
			$data = (object) unserialize($row->data);
			$data->term = $term->term_id;
			$data->slug = $term->slug;
			$data->language = strtolower(strtok($data->locale,'_'));
			$data->name = $term->name;
			$data->icon = $data->flag_code; unset($data->flag_code);
			self::$localizations[$data->slug] = $data;
		}
		return ($key) ? self::$localizations[$key] ?? '' : self::$localizations;
    }

	// ---

	public static function master($key=NULL) {
		if (isset(self::$master)) return ($key) ? self::$master->{$key} ?? '' : self::$master;
		$option = Option::get('polylang')['default_lang'] ?? '';
		foreach (self::localizations() AS $localization) {
			if ($localization->slug == $option) self::$master = $localization;
		}
		return ($key) ? self::$master->{$key} ?? '' : self::$master;
	}

	public static function current($key=NULL) {
		if (isset(self::$current)) return ($key) ? self::$current->{$key} ?? '' : self::$current;
		self::$current = self::master();
		$slug = ($e = explode('/',trim(strtolower($_SERVER['REQUEST_URI']),'/'))) ? reset($e) : '';
		if (in_array($slug,array_keys(self::localizations()))) {
			self::$current = self::localizations($slug);
		}
		return ($key) ? self::$current->{$key} ?? '' : self::$current;
	}

	// ---

    public static function translations() {
		if (isset(self::$translations)) return self::$translations;
		$result = DB::connection('wordpress')
			->table('term_taxonomy')
			->select("description")
			->where('taxonomy', 'post_translations')
			->get();
		self::$translations = [];
		$master = self::master('slug');
		foreach ($result AS $row) {
			$data = (object) unserialize($row->description);
			self::$translations[$data->{$master}] = $data;
		}
		return self::$translations;
    }

}
