<?php

use \Pecee\DB\Migration;
use \Illuminate\Database\Schema\Blueprint;

class Rewrite extends Migration {
    public function up() {
        $this->schema->create('rewrite', function(Blueprint $table){
            $table->increments('id');
            $table->string('original_url', 355);
            $table->string('rewrite_url', 355);
            $table->string('host', 255)->nullable();
            $table->string('regex', 255)->nullable();
            $table->integer('order')->nullable();

            $table->index('original_url');
            $table->index('rewrite_url');
            $table->index('host');
            $table->index('order');
        });
    }

    public function down() {
        $this->schema->drop('rewrite');
    }

    public function change() {

    }
}