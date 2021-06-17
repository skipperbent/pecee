<?php

use Pecee\DB\Migration;
use Pecee\DB\Schema\Table;

class CreateNodeDataTable extends Migration
{

    public function up()
    {
        $this->schema->create('node_data', function (Table $table) {
            $table->column('id')->string(32)->primary();
            $table->column('node_id')->string(32)->index()->relation('node', 'id');
            $table->column('key')->string(255)->index();
            $table->column('value')->longtext()->nullable()->index(1000);
            $table->column('value_hash')->string(32)->nullable()->index();
        });
    }

    public function down()
    {
        $this->schema->drop('node_data');
    }
}