<?php

use \Pecee\DB\Migration;
use \Illuminate\Database\Schema\Blueprint;

class User extends Migration {
    public function up() {
        $this->schema->create('user', function(Blueprint $table){
            $table->bigIncrements('id');
            $table->string('username', 300);
            $table->string('password', 32);
            $table->dateTime('last_activity')->nullable();
            $table->integer('admin_level')->nullable();
            $table->boolean('deleted')->nullable();

            $table->index('username');
            $table->index('password');
            $table->index('last_activity');
            $table->index('admin_level');
            $table->index('deleted');
        });
    }

    public function down() {
        $this->schema->drop('user');
    }

    public function change() {

    }
}