<?php

namespace Pecee\DB\Migrations;

use \Pecee\DB\Migration;
use \Illuminate\Database\Schema\Blueprint;

abstract class Node extends Migration {
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

            $table->primary(['id']);

            $table->index([
                'parent_id',
                'path',
                'type',
                'created_date',
                'changed_date',
                'active_from',
                'active_to',
                'level',
                'order',
                'active'
            ]);
        });

    }

    public function down() {
        $this->schema->drop('node');
    }
}