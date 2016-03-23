<?php

use \Pecee\DB\Migration;
use \Illuminate\Database\Schema\Blueprint;

class Node extends Migration {
    public function up() {

        $this->schema->create('node', function(Blueprint $table){
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->nullable();
            $table->string('path', 255)->nullable();
            $table->string('type', 255)->nullable();
            $table->string('title', 255);
            $table->longText('content')->nullable();
            $table->dateTime('created_date');
            $table->dateTime('changed_date')->nullable();
            $table->dateTime('active_from')->nullable();
            $table->dateTime('active_to')->nullable();
            $table->integer('level')->nullable();
            $table->integer('order')->nullable();
            $table->boolean('active')->nullable();

            $table->index('parent_id');
            $table->index('path');
            $table->index('type');
            $table->index('created_date');
            $table->index('changed_date');
            $table->index('active_from');
            $table->index('active_to');
            $table->index('level');
            $table->index('order');
            $table->index('active');
        });

    }

    public function down() {
        $this->schema->drop('node');
    }

    public function change() {

    }
}