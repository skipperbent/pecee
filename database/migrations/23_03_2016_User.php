<?php

namespace Pecee\DB\Migrations;

use \Pecee\DB\Migration;
use \Illuminate\Database\Schema\Blueprint;

abstract class User extends Migration {
    public function up() {
        $this->schema->create('user', function(Blueprint $table){
            $table->bigIncrements('id');
            $table->string('username', 300);
            $table->string('password', 32);
            $table->dateTime('last_activity')->nullable();
            $table->integer('admin_level')->nullable();
            $table->boolean('deleted')->nullable();

            $table->primary(['id']);

            $table->index([
                'username',
                'password',
                'last_activity',
                'admin_level',
                'deleted'
            ]);
        });
    }

    public function down() {
        $this->schema->drop('user');
    }
}