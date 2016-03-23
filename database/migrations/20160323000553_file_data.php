<?php

use \Pecee\DB\Migration;
use \Illuminate\Database\Schema\Blueprint;

class FileData extends Migration {
    public function up() {
        $this->schema->create('file_data', function(Blueprint $table){
            $table->bigIncrements('id');
            $table->string('file_id', 40);
            $table->string('key', 255);
            $table->longText('value')->nullable();

            $table->index('file_id');
            $table->index('key');
        });
    }

    public function down() {
        $this->schema->drop('file_data');
    }

    public function change() {

    }
}