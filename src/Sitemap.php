<?php

namespace ABetter\WP;

use Illuminate\Support\Facades\DB;
use Corcel\Model\Post AS CorcelPost;
use Corcel\Model\Option AS CorcelOption;
use ABetter\WP\L10n;
use ABetter\Core\Service;

class Sitemap extends Service {

	public $baseurl;
	public $domain;
	public $index;
	public $posts;
	public $items;
	public $types;

	public $whitelist = [
		'page',
		'post',
		'product',
		'news',
	];

	public $blacklist = [
		'acf-field',
		'acf-field-group',
		'attachment',
		'nav_menu_item',
		'polylang_mo',
		'revision',
		'dictionary',
		'slide',
	];

	public $hidden = [
		'403',
		'403-forbidden',
		'404',
		'404-not-found',
		'search',
	];

	// ---

	public function build() {
		$this->baseurl = env('APP_CANONICAL',env('APP_URL')).'/sitemap';
		$this->current = (preg_match('/sitemap\_?([^\.]+)\.xml$/',$this->uri,$match)) ? $match[1] : '';
		$this->index = ($this->current) ? FALSE : TRUE;
		$this->whitelist = $this->opt['types'] ?? $this->whitelist;
		$this->blacklist = $this->opt['blacklist'] ?? $this->blacklist;
		$this->hidden = $this->opt['hidden'] ?? $this->hidden;
		$this->types = $this->getTypes();
		$this->posts = $this->getPosts($this->current);
		$this->items = $this->parsePosts($this->posts);
	}

	// ---

	public function getTypes() {
		$types = [];
		$result = DB::connection('wordpress')
			->table('posts')
			->select('post_type')
			->whereNotIn('post_type',$this->blacklist)
			->groupBy('post_type')
			->get()
			->pluck('post_type');
		$list = array_intersect(
			($result) ? reset($result) : $types,
			$this->whitelist
		);
		foreach ($list AS $type) $types[] = (object) [
			'type' => $type,
			'url' => $this->baseurl.'_'.$type.'.xml',
		];
		return $types;
	}

	public function getPosts($type) {
		if (empty($type)) return [];
		$this->limit = 999;
		$this->sort = ($type == 'page') ? 'menu_order' : 'date';
		$this->order = ($type == 'page') ? 'ASC' : 'DESC';
		$this->masters = L10n::masterIndex();
		$ids = ($result = DB::connection('wordpress')
			->table('posts')
			->select('ID','post_name')
			->where('post_type',$type)
			->where('post_status','publish')
			->whereIn('ID',$this->masters)
			->whereNotIn('post_name',$this->hidden)
			->orderBy('menu_order', 'asc')
			->get()
			->pluck('ID')) ? reset($result) : [];
		return CorcelPost::whereIn('ID',$ids)->get();
	}

	public function parsePosts($posts) {
		if (empty($posts)) return [];
		$this->front = CorcelOption::get('page_on_front');
		$items = [];
		foreach ($posts AS $post) {
			$item = (object) [];
			$item->front = ($post->ID == $this->front) ? 'front' : '';
			$item->url = (preg_match('/route\:(.*)/',$post->guid,$match)) ? $match[1] : '/';
			$item->loc = $this->domain.$item->url;
			$item->timestamp = strtotime($post->post_date_gmt);
			$item->lastmod = date('Y-m-d\TH:i:sP',$item->timestamp);
			$item->changefreq = ($acf = $post->acf->settings_changefreq) ? $acf : (($item->front) ? "daily" : "weekly");
			$item->priority = ($acf = $post->acf->settings_priority) ? $acf : (($item->front) ? "0.8" : "0.5");
			$items[] = $item;
		}
		return $items;
	}

}
