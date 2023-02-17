<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCtrlGroupToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('users', 'ctrl_group')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('ctrl_group',50)->nullable();
            });
        }
        /**
         * Very rare, but we need a password field, so check we have one...
         */
        if (!Schema::hasColumn('users', 'password')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('password')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
           $table->dropColumn('ctrl_group');
           $table->dropColumn('password');
        });
    }
}
