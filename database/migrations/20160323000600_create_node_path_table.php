<?php

use Pecee\DB\Migration;
use Pecee\DB\Schema\Table;

class CreateNodePathTable extends Migration
{
    public function up()
    {
        $this->schema->create('node_path', function (Table $table) {
            $table->column('node_id')->string(32)->index()->relation('node', 'id');
            $table->column('parent_node_id')->string(32)->index()->relation('node', 'id');
            $table->column('order')->integer()->index();
        });

    }

    public function down()
    {
        $this->schema->drop('node_path');
    }
}