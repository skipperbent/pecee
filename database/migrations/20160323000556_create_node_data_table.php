<?php

use Pecee\DB\Migration;
use Pecee\DB\Schema\Table;

class CreateNodeDataTable extends Migration
{
    public function up()
    {
        $this->schema->create('node_data', function (Table $table) {
            $table->column('id')->string(32)->primary();
            $table->column('node_id')->string(32)->relation('node', 'id');
            $table->column('key')->string(255);
            $table->column('value')->longtext()->nullable();

            $table->index()->columns([
                'node_id' => null,
                'key' => null,
                'value' => 500
            ]);
        });
    }

    public function down()
    {
        $this->schema->drop('node_data');
    }
}