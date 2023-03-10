<?php

namespace Phnxdgtl\Ctrl\Commands;

use Illuminate\Console\Command;

use \App\Ctrl\CtrlModules;

use Illuminate\Support\Str;
use Illuminate\Support\Arr;

use DB;
use Schema;
use Config;
use View;
use File;

class CtrlSynch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    //protected $signature = 'ctrl:synch {action?}';
    protected $signature = 'ctrl:synch
                        {action? : Whether to update files, data or everything}
                        {--wipe : Whether the database should be wiped first}
                        {--force : Force through some errors identified by the "tidy" action}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command updates the ctrl_ tables to reflect the current database, and/or generates model files';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    // Lifted from CtrlController, and based on http://stackoverflow.com/a/30373386/1463965
    protected $ctrlModules;

    public function __construct(CtrlModules $ctrlModules) {

        $this->module = $ctrlModules;
        /* We can now run modules:
            dd($this->modules->run('test',[
                'string' => 'Hello world!'
            ]));
        }
        */
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        $action = $this->argument('action');
        $wipe   = $this->option('wipe');
        $force  = $this->option('force');

        if ($action == 'files') {
            $this->tidy_up($force);
            $this->generate_model_files();
        }
        else if ($action == 'data') {
            $this->populate_ctrl_tables($wipe);
            $this->tidy_up($force);
        }
        else if ($action == 'tidy') {
            $this->tidy_up($force);
        }
        else if ($action == 'all') {
            $this->populate_ctrl_tables($wipe);
            $this->tidy_up($force);
            $this->generate_model_files();
        }
        else {
            $this->line('Usage: php artisan ctrl:synch files|data|tidy|all --wipe');
        }

    }

    /**
     * Loop through all database tables, and create the necessary records in ctrl_classes and ctrl_properties
     * @return Response
     */
    protected function populate_ctrl_tables($wipe_all_existing_tables = false) {

        // While testing, it's easier to start from scratch each time
        if ($wipe_all_existing_tables) {
            DB::table('ctrl_classes')->truncate();
            DB::table('ctrl_properties')->truncate();
        }

        // Loop through all tables in the database

        // We'll store the tables in two arrays; $standard_tables and $pivot_tables
        $standard_tables = [];
        $pivot_tables    = [];

        // Get the current database name (from https://octobercms.com/forum/post/howto-get-the-default-database-name-in-eloquentlaravel-config)
        $database_name = Config::get('database.connections.'.Config::get('database.default').'.database');
        $tables = DB::select('SHOW TABLES');
        $ignore_tables = [
            'ctrl_classes','ctrl_properties','migrations','password_resets','revisions','audits','jobs','failed_jobs',
            'telescope_monitoring', 'telescope_entries_tags', 'telescope_entries',
            'oauth_access_tokens', 'oauth_auth_codes', 'oauth_personal_access_clients', 'oauth_refresh_tokens',
            'youtube_access_tokens', 'personal_access_tokens'
        ];
        foreach ($tables as $table) {
            $table_name = $table->{'Tables_in_'.$database_name};

            
                

            /*
                Ignore the following tables:
                - The ctrl_ tables
                - The migrations table
                - The password_resets table
                - The revisions table (assuming we're using this)
                - Any tables prefixed with '_'
            */

            if (in_array($table_name, $ignore_tables) || Str::startsWith($table_name,'_')) continue;
            
            // We now need to identify whether the table we're looking at is a pivot table or not
            // We assume a table is a pivot if it has two or three columns, with two "_id" columns
            // -- EXCLUDING id, created_at and updated_at
            // This allows for a pivot between two tables, with a maximum of one pivot value
            // This is pretty flaky TBH, there must be a better way to do this
            $table_columns = DB::select("SHOW COLUMNS FROM {$table_name} WHERE Field != 'id' AND Field != 'updated_at' AND Field != 'created_at'"); // Bindings fail here for some reason
            $pivot_table   = false;
            $id_count  = 0;
            if (count($table_columns) == 2 || count($table_columns) == 3) {
                foreach ($table_columns as $table_column) {
                    $column_name = $table_column->Field;
                    if (Str::endsWith($column_name,'_id')) {
                        /**
                         * Have we got 2 or more 'id' colums?
                         */
                        if (++$id_count >= 2) {
                            $pivot_table = true;
                            break;
                        }
                    }
                }
            }

            /**
             * Also, exclude "product_texts"; this is a quick hack specific to MT,
             * which has an odd table called "product_texts" that looks like a join
             * but which does in fact contain objects of a "ProductText" class
             */
            if ($pivot_table && $table_name != 'product_texts') {
                // $table_name is a pivot table
                $pivot_tables[] = $table_name;
            }
            else {
                // $table_name is a standard table
                $standard_tables[] = $table_name;
            }
        }

        // We now have an array of standard tables, and an array of pivot tables
        // We can loop through these in order and generate all the classes and properties we'll need

        $tables_processed  = 0;
        $columns_processed = 0; // Could track added/updated counts here, and possibly even 'deleted'
        $ignore_columns = ['id','remember_token', 'stripe_id']; // Don't ever handle these fields

        $column_ordering = []; // Keep all properties of a class in the correct order; previously we reset relationships to 1, which put the form fields in a strange order
        // UNTESTED so might need fixing the next time we sync!

        for ($pass = 1; $pass <= 2; $pass++) { // Properties on pass 1, relationships on pass 2
            foreach ($standard_tables as $standard_table) {

                $model_name = Str::studly(Str::singular($standard_table));

                /**
                 * This handles an edge case in VV/MT, which had a "String" class :-(
                 * In theory we could rename all classes to [name]Class, but let's
                 * see if this one-off works first...
                 */
                if ($model_name == 'String') {
                    $model_name = 'StringClass';
                }

                $ctrl_class = \Phnxdgtl\Ctrl\Models\CtrlClass::firstOrNew(['name' => $model_name]);

                if (!$ctrl_class->exists) {
                    // This is a new model, so set some default values:
                    $ctrl_class->table_name = $standard_table;
                    $ctrl_class->singular = '';
                    $ctrl_class->plural = '';
                    $ctrl_class->description = '';
                    // Set some default permissions, icons and menu items (?) here
                    $ctrl_class->permissions = implode(',',array('list','add','edit','delete'));
                    $ctrl_class->icon        = 'fa-toggle-right';
                    // Let's leave menu_title for now
                    $ctrl_class->order = 0;
                }

                $ctrl_class->save();

                 // Has the table been deleted?!
                if (!Schema::hasTable($standard_table)) {
                    $this->error("Table $standard_table doesn't exist");
                }

                $columns = DB::select("SHOW COLUMNS FROM {$standard_table}");

                /**
                 * New: get the previous highest order value here, to keep things in a sensible order:
                 */
                if ($pass == 1) {
                    $highestOrder = DB::table('ctrl_properties')
                                        ->where('ctrl_class_id',$ctrl_class->id)
                                        ->max('order');
                    $column_ordering[$model_name] = $highestOrder + 1;
                }

                foreach ($columns as $column) {
                    
                    $column_name = $column->Field;

                    if (in_array($column_name, $ignore_columns) || Str::startsWith($column_name,'_')) continue;
                        // Not sure we ever prefix columns with _, but I suppose it's possible

                    /*
                        Is this column a straight property, or a relationship?
                        We'll handle relationships on the second pass, so that it's easy enough
                        to identify the corresponding table we're linking to.
                        For example, if we have tables A and B, with A.B_id, we need to know
                        that table B exists before we can create *both* relationships.
                     */

                    if (!Str::endsWith($column_name,'_id') && $pass == 1) { // A straight property
                        $ctrl_property = \Phnxdgtl\Ctrl\Models\CtrlProperty::firstOrNew([
                            'ctrl_class_id' => $ctrl_class->id,
                            'name'          => $column_name
                        ]);

                        // $ctrl_property->ctrl_class()->save($ctrl_class);
                        // I think we can omit this, as we've already set ctrl_class_id when calling firstOrNew():

                        if (!$ctrl_property->exists) {
                            // This is a new model, so set some default values:

                            if ($ctrl_property->name == 'order') {
                                // Set this as a header, so that we can reorder the table, then skip the rest
                                $ctrl_property->add_to_set('flags','header');
                                $ctrl_property->order = -1; // To force this to be the first column on the left of the table
                                $ctrl_property->save();
                                continue; // Is this correct? Or break?
                            }

                             // Set some default flags, labels, field_types and so on:
                            switch ($ctrl_property->name) {
                                case 'title':
                                case 'name':
                                    $ctrl_property->add_to_set('flags','header');
                                    $ctrl_property->add_to_set('flags','string');
                                    $ctrl_property->add_to_set('flags','required');
                                    $ctrl_property->add_to_set('flags','search');
                                    break;
                                case 'image':
                                case 'photo':
                                    $ctrl_property->field_type = 'image';
                                    break;
                                case 'file':
                                    $ctrl_property->field_type = 'file';
                                    break;
                                case 'email':
                                case 'email_address':
                                    $ctrl_property->field_type = 'email';
                                    break;
                                case 'content':
                                    $ctrl_property->field_type = 'froala';
                                    break;
                            }

                            if (!$ctrl_property->field_type) {
                                $ctrl_property->field_type    = $ctrl_property->get_field_type_from_column($column->Type);
                            }

                            // $ctrl_property->order = $column_order++;
                            $ctrl_property->order = $column_ordering[$model_name]++;

                            $ctrl_property->label    = ucfirst(str_replace('_',' ',$ctrl_property->name));

                            $ctrl_property->foreign_key = '';
                            $ctrl_property->local_key = '';
                            $ctrl_property->pivot_table = '';

                            /*
                            * There are some columns we rarely want to display as editable fields
                            * and some we want to add to specific fieldsets, such as Meta data:
                            **/
                            $exclude_fields_from_form = ['created_at','updated_at','deleted_at','url','uri'];
                            if (strpos($ctrl_property->name,'meta_') === 0) {
                                $ctrl_property->fieldset = 'SEO';
                                $ctrl_property->label    = ucfirst(str_replace('Meta ','',$ctrl_property->label));
                            }
                            else if (!in_array($ctrl_property->name, $exclude_fields_from_form)) {
                                $ctrl_property->fieldset = 'Details';
                            }
                        }

                        $ctrl_property->save();
                    }
                    else if (Str::endsWith($column_name,'_id') && $pass == 2) { // A relationship

                        $ctrl_property = \Phnxdgtl\Ctrl\Models\CtrlProperty::firstOrNew([
                            'ctrl_class_id' => $ctrl_class->id,
                            'name'          => str_replace('_id', '', $column_name)
                        ]);
                        if ($ctrl_property->exists) {
                            // Property $column_name already exists, so we're not re-synching the relationship
                            // We may need to introduce a --force option if this prevents us from synching new classes
                            // $this->line("CtrlProperty for CtrlClass {$ctrl_class->id} exists");
                        } else {
                            // $this->line("CtrlProperty for CtrlClass {$ctrl_class->id} (".str_replace('_id', '', $column_name).") does not exist");
                            // Identify the table (and hence ctrl class) that this is a relationship to
                            $inverse_table_name = Str::plural(str_replace('_id', '', $column_name));
                            $inverse_ctrl_class = \Phnxdgtl\Ctrl\Models\CtrlClass::where([
                                ['table_name',$inverse_table_name]
                            ])->first();

                            if (is_null($inverse_ctrl_class)) {
                                $this->error("Cannot load ctrl_class for inverse property $column_name of $standard_table");

                                /**
                                 * Have we already created this as a parent or child property?
                                 */
                                $parent_child_ctrl_property = \Phnxdgtl\Ctrl\Models\CtrlProperty::where('ctrl_class_id', $ctrl_class->id)->where(function($q) {
                                    $q->where('name', 'parent')
                                        ->orWhere('name', 'children');
                                })->where('foreign_key', $column_name)->first();
                                if (!is_null($parent_child_ctrl_property)) {
                                    $this->line("Property already exists as a parent/child relationship");
                                    continue;
                                }

                                /**
                                 * NEW! Let's ask what class it should be...
                                 */
                                $inverse_table_name = $this->ask('What table does this property point to?');
                                $inverse_ctrl_class = \Phnxdgtl\Ctrl\Models\CtrlClass::where([
                                    ['table_name',$inverse_table_name]
                                ])->first();
                                if (is_null($inverse_ctrl_class)) {
                                    $this->error("Still can't identify table, so create as a standard property");
                                    $ctrl_property->field_type = $ctrl_property->get_field_type_from_column($column->Type);
                                    $ctrl_property->order      = $column_ordering[$model_name]++;
                                    $ctrl_property->label      = ucfirst(str_replace('_',' ',$ctrl_property->name));
                                    $ctrl_property->save();
                                    continue;
                                }
                            }

                            // Now -- if the inverse table name matches the current table name, this is a parent/child property (ie, page_id within the pages table);l
                            if ($inverse_table_name == $standard_table) {
                                $ctrl_property_name         = 'parent'; //str_replace('_id', '', $column_name);
                                $inverse_ctrl_property_name = 'children';
                            }
                            else {
                                $ctrl_property_name         = str_replace('_id', '', $column_name);
                                $inverse_ctrl_property_name = strtolower($ctrl_class->name);
                            }

                            $ctrl_property = \Phnxdgtl\Ctrl\Models\CtrlProperty::firstOrNew([
                                'ctrl_class_id'     => $ctrl_class->id,
                                'related_to_id'     => $inverse_ctrl_class->id,
                                'name'              => $ctrl_property_name,
                                'relationship_type' => 'belongsTo',
                                'foreign_key'       => $column_name,
                                'local_key'         => 'id'
                            ]);

                            // Only set these if they're not already set, otherwise we'll overwrite custom settings:
                            if (!$ctrl_property->exists) {
                                // $ctrl_property->order      = $column_order++;
                                $ctrl_property->order      = $column_ordering[$model_name]++;
                                $ctrl_property->field_type = 'dropdown';
                                $ctrl_property->label      = ucfirst(str_replace(['_id', '_'], ['', ' '], $column_name));
                                $ctrl_property->fieldset   = 'Details'; // Assume we always want to include simple "belongsTo" relationships on the form
                            }

                            $ctrl_property->save(); // As above, no need to explicitly save relationship

                            // We do need to create the inverse property though:

                            $inverse_ctrl_property = \Phnxdgtl\Ctrl\Models\CtrlProperty::firstOrNew([
                                'name'              => $inverse_ctrl_property_name,
                                    // 'name' here could possibly be ascertained in other ways; TBC
                                'ctrl_class_id'     => $inverse_ctrl_class->id,
                                'related_to_id'     => $ctrl_class->id,
                                'relationship_type' => 'hasMany',
                                    // This could in theory be haveOne, but in practice hasOne is very rarely used;
                                    // any hasOne relationship could actually be a hasMany in most cases.
                                'foreign_key'       => $column_name,
                                'local_key'         => 'id'
                            ]);

                            // $inverse_ctrl_property->order      = $column_order++; // Useful not to create these with NULL orders
                            $inverse_ctrl_property->order = $column_ordering[$model_name]++;
                            $inverse_ctrl_property->save();  // As above, no need to explicitly save relationship
                        }
                    }
                    if ($pass == 1) $columns_processed++;
                }

                if ($pass == 1) $tables_processed++;
            }
        }

        // Now loop through the pivot tables and create the hasMany relationships
        foreach ($pivot_tables as $pivot_table) {

             // Has the table been deleted?!
            if (!Schema::hasTable($pivot_table)) {
                $this->error("Pivot table $pivot_table doesn't exist");
            }

            $columns = DB::select("SHOW COLUMNS FROM {$pivot_table}");

            // Filter out anything that isn't an _id
            $columns = Arr::where($columns, function ($key, $value) {
                // Jesus, $key and $value are transposed in Laravel 5.4!
                if (!is_object($value)) {
                    $value = $key;
                }
                return Str::endsWith($value->Field,'_id');
            });
            // Make sure we have the columns in alphabetical order; is this necessary?
            // I think it's just the NAME of the pivot table that matters, and that's beyond our control
            $columns = Arr::sort($columns, function ($value) {
                return $value->Field;
            });

            for ($pass = 1; $pass <= 2; $pass++) { // Allows us to create (invert) both relationships without duplicating code

                if ($pass == 1) {
                    $pivot_one = head($columns)->Field;
                    $pivot_two = last($columns)->Field;
                }
                else if ($pass == 2) {
                    $pivot_one = last($columns)->Field;
                    $pivot_two = head($columns)->Field;
                }

                // Identify the tables (and hence ctrl classes) that we're relating
                $related_table_one = Str::plural(str_replace('_id', '', $pivot_one));
                $related_ctrl_class_one = \Phnxdgtl\Ctrl\Models\CtrlClass::where([
                    ['table_name',$related_table_one]
                ])->first();

                $related_table_two = Str::plural(str_replace('_id', '', $pivot_two));
                $related_ctrl_class_two = \Phnxdgtl\Ctrl\Models\CtrlClass::where([
                    ['table_name',$related_table_two]
                ])->first();

                if (is_null($related_ctrl_class_one) || is_null($related_ctrl_class_two)) {
                    $this->error("Cannot load both ctrl_classes for tables $related_table_one and $related_table_two, based on pivot columns $pivot_one and $pivot_two; this may not be a problem");
                    continue;
                }

                /*
                    Now. In some circumstances -- for example, where we have two instances of a relationship serving two different purposes, such as the products/profile relationship in Argos (a profile contains products, but a product is also linked to a profile using a product_profile_cache pivot table) -- we need to create two similar relationships with different names. Previously, both of these relationships (in the Argos example) would have been called profiles(), which obviously borks the model as we have two indentically-named methods.
                    Ideally, we'd have one method called profile(), and one called profile_cache(). So: look at the name of the pivot table, not the related object.
                    Will this break things elsewhere? It shouldn't do; everything should just use the property name, and it will just work. Maybe.
                 */

                // Previous approach:
                // $ctrl_property_name = str_replace('_id', '', $pivot_two);
                // New approach:

                // This will break product_profile_cache into an array, remove "product", and then rejoin it as "profile_cache"

                /**
                 * Ah -- this falls over on pivot tables where one or both classes already contain an underscore...
                 */
                /**
                 * Let's patch in a quick fix that will handle up to two classes with one underscore each
                 * In fact, let's rewrite this altogether
                 */

                /*
                $pivot_table_parts = explode('_', $pivot_table);
                $pivot_key = array_search(str_replace('_id', '', $pivot_one),$pivot_table_parts);

                // Handle plurals in pivot tables (old sites or ropey databases only)
                if ($pivot_key === false) {
                    $pivot_key = array_search(Str::plural(str_replace('_id', '', $pivot_one)),$pivot_table_parts);
                    if ($pivot_key === false) {
                        $this->error("Cannot identify property name for pivot key $pivot_one (pivot_two is $pivot_two");
                        $this->error(print_r($pivot_table_parts, true));
                    }
                }

                unset($pivot_table_parts[$pivot_key]);
                $ctrl_property_name = implode('_', $pivot_table_parts);
                */
                $pivot_table_with_pivot_one = str_replace(str_replace('_id', '', $pivot_one), '', $pivot_table);
                $ctrl_property_name         = trim($pivot_table_with_pivot_one, '_');
                // This no longer handles plurals in pivot tables! see code above for reference if we ever need this

                // $ctrl_property = \Phnxdgtl\Ctrl\Models\CtrlProperty::firstOrCreate([ // Should this be first or New?
                $ctrl_property = \Phnxdgtl\Ctrl\Models\CtrlProperty::firstOrNew([ // Try it...
                    'ctrl_class_id'     => $related_ctrl_class_one->id,
                    'related_to_id'     => $related_ctrl_class_two->id,
                    'name'              => $ctrl_property_name,
                    'relationship_type' => 'belongsToMany',
                    'foreign_key'       => $pivot_two,
                    'local_key'         => $pivot_one,
                    'pivot_table'       => $pivot_table
                ]);

                // Only set these if they're not already set, otherwise we'll overwrite custom settings:
                if (!$ctrl_property->exists) {
                    // $ctrl_property->order      = $column_order++;
                    $ctrl_property->order      = $column_ordering[$model_name]++;
                    $ctrl_property->field_type = 'dropdown';
                    $ctrl_property->label      = ucfirst(str_replace('_',' ',$ctrl_property_name));
                    $ctrl_property->fieldset   = ''; // We don't always want to include these...
                }

                $ctrl_property->save(); // As above, no need to explicitly save relationship

                if ($pass == 1) $columns_processed++;
            }

            if ($pass == 1) $tables_processed++;
        }
        $this->info("$tables_processed tables and $columns_processed columns processed");
    }

    /**
     * Generate model files based on the ctrl_tables
     *
     * @return Response
     */
    public function generate_model_files()
    {
        $model_folder = 'Ctrl/Models/';
        if(!File::exists(app_path($model_folder))) {
            File::makeDirectory(app_path($model_folder),0777,true); // See http://laravel-recipes.com/recipes/147/creating-a-directory
        }
        else {
            // Otherwise, empty the folder:
            File::cleanDirectory(app_path($model_folder));
        }
        $ctrl_classes = \Phnxdgtl\Ctrl\Models\CtrlClass::get();

        foreach ($ctrl_classes as $ctrl_class) {

            $view_data = [
                'model_name'    => $ctrl_class->name,
                'soft_deletes'  => false, // Let's leave soft deletes for now
                'audit_trail'   => true, // Use Auditable. Could use an env() variable for this
                'table_name'    => $ctrl_class->table_name,
                'fillable'      => [],
                'belongsTo'     => [],
                'hasMany'       => [],
                'belongsToMany' => [],
                'timestamps'    => true, // Assume we can have timestamps by default; could also set CREATED_AT and UPDATED_AT if these need to be customised
                'globalScope'   => false
            ];

            // NOTE: this may need to include properties that we set using a filter in the URL
            // ie, if we want to add a course to a client, but "client" isn't directly visible in the form;
            // instead, we get to the list of courses by clicking the filtered_list "courses" when listing clients.
            $fillable_properties = $ctrl_class->ctrl_properties()
                                              ->where('fieldset','!=','')
                                              ->where(function ($query) {
                                                    $query->whereNull('relationship_type')
                                                          ->orWhere('relationship_type','belongsTo');
                                                })
                                              ->get();

            // We can only fill relationships if they're belongsTo (ie, have a specific local key, such as one_id)
            // OR if they're belongsToMany, in which case we have a pivot table (I think?)
            foreach ($fillable_properties as $fillable_property) {
                $view_data['fillable'][] = $fillable_property->get_field_name();
                    // Does Laravel/Eloquent give us a quick way of extracting all ->name properties into an array?
                    // I think it does.
            }

            // Which properties can be automatically filled via a filtered list? ie, clicking to add a related page to a pagesection, should set the pagesection variable.
            // This is a bit complex as we have to look at properties of other classes, linking to this class...
            $filtered_list_properties = \Phnxdgtl\Ctrl\Models\CtrlProperty::whereRaw(
                                           '(find_in_set(?, flags))',
                                           ['filtered_list']
                                        )->where('related_to_id',$ctrl_class->id)->get();
            if (!$filtered_list_properties->isEmpty()) {

                foreach ($filtered_list_properties as $filtered_list_property) {
                    $default_properties = $ctrl_class->ctrl_properties()->
                                            where('relationship_type','belongsTo')->
                                            where('related_to_id',$filtered_list_property->ctrl_class_id)->get();
                    if (!$default_properties->isEmpty()) {
                        foreach ($default_properties as $default_property) {
                            $view_data['fillable'][] = $default_property->get_field_name();
                        }
                    }
                }
            }

            $relationship_properties = $ctrl_class->ctrl_properties()->whereNotNull('related_to_id')->get();

            foreach ($relationship_properties as $relationship_property) {
                $related_ctrl_class = \Phnxdgtl\Ctrl\Models\CtrlClass::find($relationship_property->related_to_id);

                /**
                 * Now. If we've set a custom property name (eg, "exclude_supplier_id")
                 * then we need to generate a new model name here, otherwise we end up
                 * with (eg) two properties called "profile" in the inverse relationship.
                 * This assumes that the second matching property is always the "custom" one...
                 */
                if (isset($view_data[$relationship_property->relationship_type][$relationship_property->name])) {
                    //$this->line($relationship_property->name." appears to be a custom class name?");
                    /**
                     * We could try and set a sensible name here based on the inverse property
                     * but I'm actually not sure it's important, as long as the names don't clash
                     */
                    $relationship_property->name = 'secondary_' . $relationship_property->name;
                }

                $relationship_data = [
                    'name'        => $relationship_property->name,
                    'model'       => $related_ctrl_class->name,
                    'foreign_key' => $relationship_property->foreign_key,
                    'local_key'   => $relationship_property->local_key,
                ];

                if ($relationship_property->relationship_type == 'belongsToMany') {
                    $relationship_data['pivot_table'] = $relationship_property->pivot_table;
                }
                $view_data[$relationship_property->relationship_type][$relationship_property->name] = $relationship_data;
            }

            // Do we have timestamps?
            $timestamps = DB::select("SHOW COLUMNS FROM {$ctrl_class->table_name} WHERE `field` = 'created_at' OR `field` = 'updated_at'"); // Bindings fail here for some reason
            if (count($timestamps) != 2) $view_data['timestamps'] = false; // Don't set timestamps, as we don't have the default Laravel timestamp fields

            // Do we have any custom global scopes...?
            if ($this->module->enabled('globalScope')) {
                $view_data['globalScope'] = $this->module->run('globalScope',[
                    $ctrl_class
                ]);
            }

            $model_code = View::make('ctrl::model_template',$view_data)->render();
            $model_path = app_path($model_folder.$ctrl_class->name.'.php');

            File::put($model_path, $model_code);

        }

        $this->info($ctrl_classes->count() . ' files generated');

    }

    /**
     * Tidy up some known issues that can occur with the database; floating records etc
     * @param  $force Forcibly delete (eg) columns that appear to be redundant. Without this, we just identify them
     *
     * @return Response
     */
    public function tidy_up($force = null)
    {
        // Remove any CTRL Properties that no longer have a parent class (ie, where the parent class has since been deleted)
        DB::delete('DELETE FROM ctrl_properties WHERE ctrl_class_id NOT IN (SELECT id FROM ctrl_classes);');

        // Remove any CTRL Properties that no longer have a related class (ie, where the related class has since been deleted)
        DB::delete('DELETE FROM ctrl_properties WHERE related_to_id NOT IN (SELECT id FROM ctrl_classes);');

        // Now check for redundant (deleted?) classes and properties; classes without a table, properties without a column
        $missing_tables = false;
        $ctrl_classes = \Phnxdgtl\Ctrl\Models\CtrlClass::get();
        foreach ($ctrl_classes as $ctrl_class) {
            $table = DB::select("SHOW TABLES like '{$ctrl_class->table_name}'");
            if (!$table) {
                $this->error("The table for the class {$ctrl_class->name} ('{$ctrl_class->table_name}') appears not to exist");
                if ($force) {
                    $this->info("Deleting it...");
                    $ctrl_class->ctrl_properties()->delete();
                    $ctrl_class->related_ctrl_properties()->delete();
                    $ctrl_class->delete();
                }
                $missing_tables = true;
            }
        }
        if (!$missing_tables) $this->info("All tables present and correct");

        $missing_columns = false;
        $ctrl_properties = \Phnxdgtl\Ctrl\Models\CtrlProperty::get();
        foreach ($ctrl_properties as $ctrl_property) {
            if (in_array($ctrl_property->relationship_type,['belongsToMany'])) { // belongsToMany, has $ctrl_property->pivot_table
                $table_name = $ctrl_property->pivot_table;
                // Check foreign key and local key
                foreach (['foreign_key','local_key'] as $key) {

                    // Has the table been deleted?!
                    if (!Schema::hasTable($table_name)) {
                        $this->error("Pivot table $table_name doesn't exist; deleting this property");
                        $ctrl_property->delete();
                        // $missing_columns = true; // Technically shouldn't flag this as an issue if we've resolved it
                    }
                    else {
                        $table_column = DB::select("SHOW COLUMNS FROM {$table_name} LIKE '{$ctrl_property->$key}'");
                        if (!$table_column) {
                            $this->error("The {$ctrl_property->relationship_type} column for {$ctrl_property->ctrl_class->name}::{$ctrl_property->$key} (from the pivot table '{$table_name}') appears not to exist");
                            $missing_columns = true;
                        }
                    }
                }
            }
            else if (in_array($ctrl_property->relationship_type,['hasMany'])) { // hasMany, has a key in a related table, as per $ctrl_property->related_to_id
                if (!empty($ctrl_property->related_ctrl_class)) {
                    $table_name = $ctrl_property->related_ctrl_class->table_name;
                }
                else {
                    $this->info("Cannot load related ctrl class for {$ctrl_property->name}");
                    break;
                }

                // Has the table been deleted?!
                if (!Schema::hasTable($table_name)) {
                    // I believe that this table should now have already been deleted, in the first foreach loop above
                    $this->error("Table $table_name doesn't exist");
                    $missing_columns = true;
                }
                else {
                    $table_column = DB::select("SHOW COLUMNS FROM {$table_name} LIKE '{$ctrl_property->foreign_key}'");
                    if (!$table_column) {
                        $this->error("The {$ctrl_property->relationship_type} column for {$ctrl_property->ctrl_class->name}::{$ctrl_property->foreign_key} (from the table '{$table_name}') appears not to exist");
                        $missing_columns = true;
                        // WIP: this might delete columns that we actually need, keep an eye on it!
                        if ($force) {
                            $ctrl_property->delete();
                            $this->info("Deleting it...");
                        }
                    }
                }
            }
            else if (in_array($ctrl_property->relationship_type,['belongsTo'])) { // belongsTo, has a join column (eg _id)
                $table_name = $ctrl_property->ctrl_class->table_name;

                // Has the table been deleted?!
                if (!Schema::hasTable($table_name)) {
                    // I believe that this table should now have already been deleted, in the first foreach loop above
                    $this->error("Table $table_name doesn't exist");
                    $missing_columns = true;
                }
                else {

                    $table_column = DB::select("SHOW COLUMNS FROM {$table_name} LIKE '{$ctrl_property->foreign_key}'");
                    if (!$table_column) {
                        $this->error("The {$ctrl_property->relationship_type} column for {$ctrl_property->ctrl_class->name}::{$ctrl_property->foreign_key} (from the table '{$table_name}') appears not to exist");
                        $missing_columns = true;
                        // WIP: this might delete columns that we actually need, keep an eye on it!
                        if ($force) {
                            $ctrl_property->delete();
                            $this->info("Deleting it...");
                        }
                    }
                }
            }
            else {
                if (is_object($ctrl_property->ctrl_class)) {
                    $table_name = $ctrl_property->ctrl_class->table_name;

                    // Has the table been deleted?!
                    if (!Schema::hasTable($table_name)) {
                        // I believe that this table should now have already been deleted, in the first foreach loop above
                        $this->error("Table $table_name doesn't exist");
                        $missing_columns = true;
                    }
                    else {

                        $table_column = DB::select("SHOW COLUMNS FROM {$table_name} LIKE '{$ctrl_property->name}'");
                        if (!$table_column) {
                            $this->error("The standard column for {$ctrl_property->ctrl_class->name}::{$ctrl_property->name} (from the table '{$table_name}') appears not to exist");
                            $missing_columns = true;
                            if ($force) {
                                $ctrl_property->delete();
                                $this->info("Deleting it...");
                            }
                        }
                    }

                } else {
                    $this->error("Cannot load CTRL Class for CTRL Property ".$ctrl_property->id);
                }
            }
        }
        if (!$missing_columns) {
            $this->info("All columns present and correct");
        }
        else{
            if ($force) {
                $this->error("Found some potential problems with tables that may have been automatically fixed.");
            }
            else {
                $this->error("Found some potential problems with tables that need to be reviewed.");
                $this->comment("Use the --force flag to attempt to fix these problems automatically.");
            }
        }

    }
}
