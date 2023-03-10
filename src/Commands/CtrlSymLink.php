<?php

namespace Phnxdgtl\Ctrl\Commands;

use Illuminate\Console\Command;

use File;

class CtrlSymLink extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ctrl:symlink
                                {folder? : the project folder to point to, such as argos-support.co.uk }
                                {database? : the database the site will use }
                            ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the symlink that points to another app/Ctrl folder; this is how we can use dev.ctrl-c.ms to manage other sites. We also now link to Http/Controllers/Ctrl if it exists, as this is where we should be keeping a Custom Controller. We can also update the database to be used.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        if (
            config('app.url') != 'http://ctrl-c.ms.test'
            ||
            app()->environment() != 'local'
        ) {
            $this->error("This command is designed to be run from the local ctrl-c.ms site only.");
            exit();
        }

        $project_folder = $this->argument('folder');
        $database       = $this->argument('database');

        if (!$project_folder) {
            $this->error("Sample usage: ctrl:symlink [site folder] [database name]");
            exit();
        }

        $project_root = realpath(base_path().'/..'); // eg, /Users/chrisgibson/Projects
        $project_path = implode('/', [$project_root,str_replace('.test','',$project_folder)]);

        if(!File::exists($project_path)) {
            $this->error("$project_folder doesn't seem to be a valid project folder");
            $this->info("This command will look for a directory at $project_path");
            exit();
        }

        $ctrl_path              = implode('/', [$project_path,'app','Ctrl']);

        if(!File::exists($ctrl_path)) {
            $this->error("$project_folder doesn't seem to contain a valid Ctrl folder");
            $this->info("This command will look for a folder at $ctrl_path");
            exit();
        }

        // OK, we have a valid app/Ctrl folder to link to. Remove the existing one (if present) and then create a new symlink:
        $symlink = app_path('Ctrl');

        if (is_link($symlink)) {
            $this->line("Removing existing symlink at $symlink");
            unlink($symlink);
        } else if (file_exists($symlink)) {
            $this->line("Deleting existing folder at $symlink");
            File::deleteDirectory($symlink);
        }
        $this->line("Creating new symlink at ".implode('/',['app','Ctrl']));
        symlink ($ctrl_path, $symlink); // Effectively ln -s $ctrl_path $symlink

        // Also create a symlink to Http/Controllers/Ctrl if it exists; see $description above

        $custom_controller_path = implode('/', [$project_path,'app','Http','Controllers','Ctrl']);
        $symlink = app_path('Http/Controllers/Ctrl');

        if (is_link($symlink)) {
            $this->line("Removing existing symlink at $symlink");
            unlink($symlink);
        }

        if (File::exists($custom_controller_path)) {
            $this->line("Creating new symlink at ".implode('/',['app','Http','Controllers','Ctrl']));
            symlink ($custom_controller_path, $symlink); // Effectively ln -s $custom_controller_path $symlink");
        }

        if ($database) {
            $env_file = base_path('.env');
            if (!File::isWritable($env_file)) {
                $this->error('Cannot switch database as .env isn\'t writeable.');
            }
            else {
                $env_contents = File::get($env_file);

                $find       = '/\nDB_DATABASE\=.*\n/';
                $replace    = "\nDB_DATABASE=$database\n";

                if (preg_match($find, $env_contents)) {
                    $new_env_contents = preg_replace($find, $replace, $env_contents);
                    if ($env_contents == $new_env_contents) {
                        $this->comment(".env file unchanged.");
                    }
                    else {
                        File::put($env_file, $new_env_contents);
                        $this->line('Database switched to '.$database);
                    }
                    /**
                     * For some reason, this is triggering, but only generating (say) 2 files
                     * rather than 20? Why? Do we have to refresh the .env file?
                     * Try calling this? php artisan config:clear
                     * Nope, that doesn't work, unsurprisingly.
                     */
                    /*
                    $this->call('config:clear');
                    $this->call('ctrl:synch', [
                        'action' => 'files'
                    ]);
                    */
                }
                else {
                    $this->error("Unable to update .env, cannot locate DB_DATABASE key.");
                }

            }
        }
        else {
            $this->comment('Don\'t forget to switch database in .env if necessary; this can now be passed in as a second argument.');
        }

        /**
         * I've seen issues before where the previous custom Ctrl views persisted, so clear them:
         */
        $this->call('view:clear');

        $this->info('Done. You will now need to run this command:');
        $this->line('php artisan ctrl:synch files'); // See note above re. cached .env file
    }
}
