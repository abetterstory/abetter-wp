<?php

namespace ABetter\WP;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;

class BladeServiceProvider extends ServiceProvider {

    public function boot() {

		// Console
        Blade::directive('dictionary', function($expression){
			return "<?php echo _dictionary($expression); ?>";
        });

    }

    public function register() {
        //
    }

}
