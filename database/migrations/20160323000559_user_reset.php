<?php

use \Pecee\DB\Migration;
use \Illuminate\Database\Schema\Blueprint;

class UserReset extends Migration {
    public function up() {
        $this->schema->create('user_reset', function(Blueprint $table){
            // Auto-increment id
            $table->bigIncrements('id');
            $table->bigInteger('user_id');
            $table->string('key', 32);
            $table->dateTime('created_date')->nullable();

            $table->index('user_id');
            $table->index('key');
            $table->index('created_date');
        });
    }

    public function down() {
        $this->schema->drop('user_reset');
    }

    public function change() {

    }
}