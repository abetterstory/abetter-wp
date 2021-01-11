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
				$response->setStatusCode($data['error']);
			}

			// Cache
			if (method_exists($response,'header')) {
				$expire = (!empty($data['error'])) ? 300 : 2628000; // Default 1 month
				if (isset($data['expire']) && $data['expire'] !== '') {
					$expire = (is_numeric($data['expire'])) ? (int) $data['expire'] : strtotime($data['expire'],0);
				}
				if ($expire > 0) {
					$response->header('Pragma', 'public');
					$response->header('Cache-Control', 'public, max-age='.$expire);
					$response->header('Expires', gmdate('D, d M Y H:i:s \G\M\T', time() + $expire));
					$response->setEtag(md5($response->content()));
				} else {
					$response->header('Pragma', 'no-cache');
					$response->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
					$response->header('Expires', '0');
				}
			}

		}

		// ---

		return $response;

	}

}
