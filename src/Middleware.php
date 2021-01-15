<?php

namespace ABetter\WP;

use Closure;

class Middleware {

	public function handle($request, Closure $next) {

		$response = $next($request);

		// ---

		return $response;

	}

}
