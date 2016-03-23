<?php

use \Pecee\DB\Migration;
use \Illuminate\Database\Schema\Blueprint;

class UserData extends Migration {
    public function up() {
        $this->schema->create('user_data', function(Blueprint $table){
            // Auto-increment id
            $table->bigIncrements('id');
            $table->bigInteger('user_id');
            $table->string('key', 255);
            $table->longText('value')->nullable();

            $table->index('user_id');
            $table->index('key');
        });
    }

    public function down() {
        $this->schema->drop('user_data');
    }

    public function change() {

    }
}