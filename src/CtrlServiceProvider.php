<?php

namespace	Phnxdgtl\Ctrl;

/**
 *
 * @author Chris Gibson <chris@phoenixdigital.agency>
 * Heavily based on https://github.com/jaiwalker/setup-laravel5-package
 */

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;

use File;
use Illuminate\Support\Str;

class CtrlServiceProvider extends ServiceProvider{

	// Dummy comment to force a commit

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	// Add the artisan command; from http://stackoverflow.com/questions/28492394/laravel-5-creating-artisan-command-for-packages. See @register()
	protected $commands = [
       \Phnxdgtl\Ctrl\Commands\CtrlSynch::class,
       \Phnxdgtl\Ctrl\Commands\CtrlTables::class,
       \Phnxdgtl\Ctrl\Commands\CtrlSymLink::class,
       \Phnxdgtl\Ctrl\Commands\CtrlStorage::class,
    ];

	public function boot()
	{

		$ctrl_folder = app_path('Ctrl');

		if (!File::exists($ctrl_folder)) {
			File::makeDirectory($ctrl_folder,0777,true); // See http://laravel-recipes.com/recipes/147/creating-a-directory
		}

		/**
		 * We always need a CtrlModules file, even before we publish anything, otherwise
		 * CtrlSynch objects to the missing file. We used to do this via publishes() but
		 * that doesn't happen soon enough in the process. So, do it here:
		 */
		$ctrlModulesFile = $ctrl_folder.'/CtrlModules.php';
		if (!File::exists($ctrlModulesFile)) {
			File::copy(__DIR__.'/Modules/CtrlModules.php', $ctrlModulesFile);
		}

		// If we run `artisan vendor publish --force`, we can overwrite config files;
		// this is user error (most likely, my user error), but it's a major cock-up so let's catch it

		if (\App::runningInConsole()) {
			$args = $_SERVER['argv'];

			if (!empty($args)) {
				// Are we attempting to run `artisan vendor publish --force`, without the public tag?
				if (

					/**
					 * Bugfix: previously 'in_array('artisan', $args)' but
					 * this trips up if you run php ../artisan from a subfolder
					 */
					Str::endsWith($args[0],'artisan')

					&& in_array('vendor:publish', $args)
					&& (
						!in_array('--tag=public', $args)
						||
						in_array('--tag=config', $args)
					)
					&& in_array('--force', $args)
				) {
					// Require a `--ctrl` flag in order to force a `vendor publish`
					if (!in_array('--ctrl=overwrite', $args)) {
						$message = [
							'Running `artisan vendor publish --force` will overwrite CTRL config files!',
							'If you really wish to do this, please add the flag `--ctrl=overwrite`.',
							'Otherwise, to publish CSS/JS files only, add the argument `--tag=public`.'
						];
						$maxlen = max(array_map('strlen', $message)); // Nice, http://stackoverflow.com/questions/1762191/how-to-get-the-length-of-longest-string-in-an-array
						$divider = str_repeat('*',$maxlen);
						array_unshift($message, $divider);
						array_push($message, $divider);
						echo "\n".implode("\n", $message)."\n\n";
						exit();
					}
				}
			}
		}

		// This folder holds the core views used by the CMS...
		$this->loadViewsFrom(realpath(__DIR__.'/../views'), 'ctrl');
		// ...and this folder can be used to store custom views if necessary
		$this->loadViewsFrom(app_path('Ctrl/views'), 'ctrl_custom');
		$this->setupRoutes($this->app->router);

		// This allows the config file to be published using artisan vendor:publish
		$this->publishes([
				__DIR__.'/config/ctrl.php' => config_path('ctrl.php'),
		], 'config'); // See https://laravel.com/docs/5.0/packages#publishing-file-groups

		// Make sure we have a CtrlModules file:
		/**
		 * As per the note at the start of this function, this isn't really used any more;
		 * might be useful to re-publish at some point though?
		 */
		$this->publishes([
	        __DIR__.'/Modules/CtrlModules.php' => $ctrl_folder.'/CtrlModules.php',
	    ],'config');

		// This copies our assets folder into the public folder for easy access, again using artisan vendor:publish
		$this->publishes([
	        realpath(__DIR__.'/../assets') => public_path('assets/vendor/ctrl'),
	        	// We could potentially just use 'vendor/ctrl'; check best practice here.
	    ], 'public');

		/**
		 * I think that loadMigrationsFrom() is a better approach in Laravel 6:
	    $this->publishes([
            realpath(__DIR__.'/../database/migrations') => database_path('/migrations')
		], 'migrations');
		**/

		$this->loadMigrationsFrom(__DIR__.'/../database/migrations');

	}

	/**
	 * Define the routes for the application.
	 *
	 * @param  \Illuminate\Routing\Router  $router
	 * @return void
	 */
	public function setupRoutes(Router $router)
	{
		$router->group(['namespace' => 'Phnxdgtl\Ctrl\Http\Controllers'], function($router)
		{
			require __DIR__.'/Http/routes.php';
		});
	}


	public function register()
	{
		$this->registerCtrl();
		config([
				'config/ctrl.php',
		]);

		// Register the DataTables service like this (saves having to add it to config/app.php)
		\App::register('Yajra\DataTables\DataTablesServiceProvider');

		// Excel module used when importing, exporting CSV data
		\App::register('Maatwebsite\Excel\ExcelServiceProvider');

		// Image module for thumbnailing images, required now that we store them all in storage
		\App::register('Intervention\Image\ImageServiceProvider');
		// Ah, this is the way to register an Alias in a ServiceProvider, from: http://stackoverflow.com/a/22749871
		$loader = \Illuminate\Foundation\AliasLoader::getInstance();
    	$loader->alias('Image', 'Intervention\Image\Facades\Image');

		// This didn't seem to work, and isn't needed if we "use Maatwebsite\Excel\Facades\Excel;" at the top of the controller
		// (See above to see how this could actually be done, but in fact it's not necessary. We only need to use the Image Facade
		// because we use it directly within the Routes file).
		// \App::alias('Excel','Maatwebsite\Excel\Facades\Excel');

		// Can we create a custom Service Provider here to drive "modules"?
		/* Don't think so
		\App::register('App\Ctrl\Providers\CtrlModuleServiceProvider');
		*/

	}

	private function registerCtrl()
	{
		$this->commands($this->commands);
		$this->app->bind('ctrl',function($app){
			return new Ctrl($app);
		});

	}
}