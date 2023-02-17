<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCoordinatesFieldType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE ctrl_properties CHANGE `field_type` `field_type` ENUM('text','textarea','redactor','dropdown','checkbox','date','datetime','time','image','video','file','email','froala','colour','coordinates')");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE ctrl_properties CHANGE `field_type` `field_type` ENUM('text','textarea','redactor','dropdown','checkbox','date','datetime','time','image','video','file','email','froala','colour')");
    }
}
