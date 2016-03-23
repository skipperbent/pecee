<?php

use \Pecee\DB\Migration;
use \Illuminate\Database\Schema\Blueprint;

class File extends Migration {
    public function up() {
        $this->schema->create('file', function(Blueprint $table){
            $table->string('id', 40);
            $table->string('filename', 355);
            $table->string('original_filename', 32);
            $table->string('path', 355)->nullable();
            $table->string('type');
            $table->integer('bytes')->nullable();
            $table->dateTime('created_date');

            $table->primary(['id']);

            $table->index('filename');
            $table->index('original_filename');
            $table->index('path');
            $table->index('type');
            $table->index('bytes');
            $table->index('created_date');
        });
    }

    public function down() {
        $this->schema->drop('file');
    }

    public function change() {

    }
}