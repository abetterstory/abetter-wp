<?php

namespace ABetter\WP;

use Closure;

class Middleware {

	public function handle($request, Closure $next) {

		$response = $next($request);

		// ---

		if ($data = (isset($response->original) && method_exists($response->original,'getData')) ? $response->original->getData() : NULL) {

			// Redirect
			if (!empty($data['redirect'])) {
				return \Redirect::to($data['redirect']);
			}

			// Error
			if (!empty($data['error']) && $data['error'] > 400) {
				@http_response_code($data['error']);
			}

			// Expire
			if (method_exists($response,'header')) {
				$expire = (!empty($data['expire'])) ? ((is_numeric($data['expire'])) ? $data['expire'] : strtotime($data['expire'],0)) : 2628000;
				$response->header('Pragma', 'public');
				$response->header('Cache-Control', 'public, max-age='.$expire);
				$response->header('Expires', gmdate('D, d M Y H:i:s \G\M\T', time() + $expire));
				$response->setEtag(md5($response->content()));
			}

		}

		// ---

		return $response;

	}

}
