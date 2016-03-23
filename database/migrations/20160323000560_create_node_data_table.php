<?php

use \Pecee\DB\Migration;
use \Pecee\DB\Schema\Table;

class CreateNodeDataTable extends Migration {

    public function up() {
        $this->schema->create('node_data', function(Table $table){
            $table->column('id')->bigint()->primary()->increment();
            $table->column('node_id')->bigint()->index();
            $table->column('key')->string(255)->index();
            $table->column('value')->longtext()->nullable();
        });
    }

    public function down() {
        $this->schema->drop('node_data');
    }
}