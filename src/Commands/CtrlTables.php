<?php

namespace Phnxdgtl\Ctrl\Commands;

use Illuminate\Console\Command;

use DB;
//use Config;
//use View;
use File;

class CtrlTables extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ctrl:tables
                        {action? : Whether to import or export data}
                        ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command imports, or exports, the ctrl_ tables from the codebase.';

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

        $folder = app_path('Ctrl/database/');
        $file   = 'ctrl_tables.sql';

        if(!File::exists($folder)) {
            File::makeDirectory($folder,0777,true); // See http://laravel-recipes.com/recipes/147/creating-a-directory
        }

        $this->sql_file = $folder.$file;

        $action = $this->argument('action');

        if ($action == 'import') {
            $this->import();
        }
        else if ($action == 'export') {
            $this->export();
        }
        else {
            $this->line('Usage: php artisan ctrl:tables import|export');
        }

    }

    /**
     * Export the two ctrl_ tables to a dump file in app/Ctrl/data
     * @return none
     */
    public function export() {

        if (app()->environment() != 'local') {
            $this->error(sprintf("Please note that it makes little sense to export these tables from the %s environment",app()->environment()));
        }

        // From https://gist.github.com/kkiernan/bdd0954d0149b89c372a

        $database = config('database.connections.'.config('database.default').'.database');
        $user     = config('database.connections.'.config('database.default').'.username');
        $password = config('database.connections.'.config('database.default').'.password');

        if ($password) {
            // Compile the full mysql password prompt here; otherwise, we end up passing -p'' to mysqldump, which fails
            $password = sprintf('-p\'%s\'',$password);
        }

        // Is this always the correct local path?
        $mysqldump = 'mysqldump';

        /**
         * Note that we exclude LOCK TABLES from the export file (--skip-add-locks) and also
         * omit to lock tables DURING the import (--skip-lock-tables)
         * because the MySQL user won't always have permission to lock tables...
         * Also note that we have to specify a socket if we're running DBngin;
         * see https://github.com/TablePlus/DBngin/issues/38
         */

        $command = sprintf('%s --skip-add-locks --skip-lock-tables --socket /tmp/mysql_3306.sock %s -u %s %s ctrl_classes ctrl_properties > %s',
            $mysqldump,            
            $database,            
            $user,
            $password,
            $this->sql_file
        );
        if (!exec($command, $output)) {
            if (count($output) > 0) {
                $this->error($output[0]);
            }
        }

        $this->info("Data file exported, about to synch the model files");

        $this->call('ctrl:synch', [
            'action' => 'files'
        ]);



    }

     /**
     * Import the two ctrl_ tables from a dump file in app/Ctrl/data
     * @return none
     */
    public function import() {
         $response = DB::unprepared(File::get($this->sql_file));
         if (!$response) {
            $this->error("Possible error when importing SQL file");
         }
         $this->info("Data file imported, about to synch the model files");

        $this->call('ctrl:synch', [
            'action' => 'files'
        ]);
    }
}
