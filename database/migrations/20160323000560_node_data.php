<?php

use \Pecee\DB\Migration;
use \Illuminate\Database\Schema\Blueprint;

class NodeData extends Migration {

    public function up() {
        $this->schema->create('node_data', function(Blueprint $table){
            $table->bigIncrements('id');
            $table->bigInteger('node_id');
            $table->string('key', 255);
            $table->longText('value')->nullable();

            $table->index('node_id');
            $table->index('key');
        });
    }

    public function down() {
        $this->schema->drop('node_data');
    }

    public function change() {

    }
}