<?php

namespace ABetter\WP;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use ABetter\WP\Middleware;

class ServiceProvider extends BaseServiceProvider {

    public function boot() {

		$this->app->make(Kernel::class)->pushMiddleware(Middleware::class);

	}

    public function register() {
		//
    }

}
