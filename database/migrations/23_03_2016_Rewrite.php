<?php

namespace Pecee\DB\Migrations;

use \Pecee\DB\Migration;
use \Illuminate\Database\Schema\Blueprint;

abstract class Rewrite extends Migration {
    public function up() {
        $this->schema->create('rewrite', function(Blueprint $table){
            // Auto-increment id
            $table->increments('id');
            $table->string('original_url', 355);
            $table->string('rewrite_url', 355);
            $table->string('host', 255)->nullable();
            $table->string('regex', 255)->nullable();
            $table->integer('order')->nullable();

            $table->primary(['id']);

            $table->index([
                'original_url',
                'rewrite_url',
                'host',
                'order'
            ]);
        });
    }

    public function down() {
        $this->schema->drop('rewrite');
    }
}