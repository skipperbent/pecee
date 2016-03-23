<?php

use \Pecee\DB\Migration;
use \Illuminate\Database\Schema\Blueprint;

class UserBadLogin extends Migration {
    public function up() {
        $this->schema->create('user_bad_login', function(Blueprint $table){
            // Auto-increment id
            $table->bigIncrements('id');
            $table->string('username', 300);
            $table->dateTime('created_date');
            $table->string('ip', 50);
            $table->boolean('active')->nullable();

            $table->index('username');
            $table->index('created_date');
            $table->index('ip');
            $table->index('active');
        });
    }

    public function down() {
        $this->schema->drop('user_bad_login');
    }

    public function change() {

    }
}