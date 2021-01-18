<?php

namespace ABetter\WP;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\View;
use Closure;
use Corcel\Model\Post AS CorcelPost;
use Corcel\Model\Option AS CorcelOption;
use ABetter\WP\L10n;
use ABetter\Core\Service;

use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController {

	protected $l10n;

	public function handle() {
		// Prepare
		$this->args = func_get_args();
		$this->uri = strtok($_SERVER['REQUEST_URI'],'?#');
		$this->path = preg_replace('/\/\/+/','/','/'.trim($this->args[0]??'','/').'/');
		$this->query = explode('?',$_SERVER['REQUEST_URI'])[1] ?? '';
		$this->theme = env('WP_THEME') ?: '';
		// Locations
		if ($this->theme) {
			//$this->prependLocation(base_path().'/resources/views/'.$this->theme);
			view()->addLocation(base_path().'/resources/views/'.$this->theme);
			view()->addLocation(base_path().'/vendor/abetter/wp/views/'.$this->theme);
		}
		view()->addLocation(base_path().'/vendor/abetter/wp/views/default');
		$this->location = Config::get('view.paths');
		// Service
		if (preg_match('/^\/robots/',$this->uri)) return $this->handleService('robots','text/plain');
		if (preg_match('/^\/sitemap/',$this->uri)) return $this->handleService('sitemap','text/xml');
		if (preg_match('/^\/wp\/service/',$this->uri)) return $this->handleService();
		// Post
		if (!$this->post = $this->getPreview($this->path,$this->uri)) {
			if (!$this->post = $this->getPost($this->path)) {
				if (!$this->post = $this->getError()) {
					return abort(404);
				}
			}
		}
		// Localization
		$this->l10n = L10n::parseGlobal();
		if ($switch = L10n::switchPostTranslationById($this->post->ID)) {
			$this->post = L10n::getPost('ID',$switch);
		}
		// Template
		$this->template = $this->getPostTemplate($this->post);
		$this->suggestions = $this->getPostTemplateSuggestions($this->post,$this->template);
		// View
		foreach ($this->suggestions AS $suggestion) {
			if (view()->exists($suggestion)) {
				$this->view = $suggestion;
				break;
			}
		}
		// Response
		if (!empty($this->view)) {
			// Pass to Core Middleware
			$GLOBALS['HEADERS']['error'] = $this->error ?? '';
			$GLOBALS['HEADERS']['expire'] = $this->post->meta->settings_expire ?? '';
			$GLOBALS['HEADERS']['redirect'] = $this->post->meta->settings_redirect ?? '';
			return view($this->view)->with([
				'post' => $this->post,
				'l10n' => $this->l10n,
				'template' => $suggestion,
			]);
		}
		// Fail
		if (in_array(strtolower(env('APP_ENV')),['production','stage'])) return abort(404);
		return "No template found in views.";
    }

	// ---

	public function handleService($view=NULL,$format=NULL,$data=[]) {
		$this->service = pathinfo($this->uri, PATHINFO_FILENAME);
		$this->extension = pathinfo($this->uri, PATHINFO_EXTENSION);
		$this->view = ($view) ? $view : 'services.'.$this->service;
		$this->format = ($format) ? $format : Service::format($this->extension);
		if (view()->exists($this->view)) {
			$GLOBALS['HEADERS']['format'] = $this->format;
			$GLOBALS['HEADERS']['expire'] = '5 minutes';
			return view($this->view)->with(array_merge([
				'wp' => TRUE,
				'service' => $this->service,
				'format' => $this->format,
			],$data));
		}
		if (in_array(strtolower(env('APP_ENV')),['production','stage'])) return abort(404);
		return "No service found in views.";
	}

	// ---

	public function getPreview($path,$uri) {
		if ($path !== '/' || !preg_match('/preview/',$uri)) return NULL;
		$this->preview = (preg_match('/(page_id|p)(\=|\/)([0-9]+)/',$uri,$match)) ? (int) $match[3] : '';
		return L10n::getPost('ID',$this->preview);
	}

	public function getPost($path) {
		$this->guid = 'route:'.$path;
		return L10n::getPost('guid',$this->guid);
	}

	public function getPostById($id) {
		return L10n::getPost('ID',$id);
	}

	public function getError() {
		$this->error = 404;
		$post = L10n::getPost('post_name',"{$this->error}%",'like');
		$post->error = $this->error;
		return $post;
	}

	// ---

	public function postFrontIds() {
		return (array) L10n::getPostTranslationsById(CorcelOption::get('page_on_front'));
	}

	public function postPostsIds() {
		return (array) L10n::getPostTranslationsById(CorcelOption::get('page_for_posts'));
	}

	public function postIsFront($post) {
		return (in_array($post->ID,$this->postFrontIds())) ? TRUE : FALSE;
	}

	public function postIsPosts($post) {
		return (in_array($post->ID,$this->postPostsIds())) ? TRUE : FALSE;
	}

	// ---

	public function getPostTemplate($post) {
		$template = strtok($post->meta->_wp_page_template ?? 'default', '.');
		if ($template == 'default') {
			$template = $post->post_type;
			if (in_array($post->ID,$this->postFrontIds())) $template = 'front';
			if (in_array($post->ID,$this->postPostsIds())) $template = 'posts';
		}
		return $template;
	}

	public function getPostTemplateSuggestions($post,$template) {
		$suggestions = [];
		if (empty($this->post->ID)) return $suggestions;
		if ($post->post_type == 'post') $suggestions[] = 'page';
		$suggestions[] = $post->post_type;
		$suggestions[] = $post->post_type.'--'.$post->post_name;
		if ($template != $post->post_type) $suggestions[] = $template;
		if (!empty($this->error)) {
			if ($template != 'error') $suggestions[] = 'error';
			$suggestions[] = (string) $this->error;
		}
		return array_reverse($suggestions);
	}

	// ---

	public function prependLocation($path) {
		// Breaks Telescope
        Config::set('view.paths', array_merge([$path], Config::get('view.paths')));
        View::setFinder(app()['view.finder']);
    }

}
