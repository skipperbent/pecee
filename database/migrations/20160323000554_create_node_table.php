<?php

use Pecee\DB\Migration;
use Pecee\DB\Schema\Table;

class CreateNodeTable extends Migration
{
    public function up()
    {
        $this->schema->create('node', function (Table $table) {
            $table->column('id')->string(32)->primary();
            $table->column('parent_id')->string(32)->nullable()->index()->relation('node', 'id');
            $table->column('type')->string(50)->index();
            $table->column('title')->string()->index()->nullable();
            $table->column('content')->longtext()->nullable();
            $table->column('active_from')->datetime()->nullable()->index();
            $table->column('active_to')->datetime()->nullable()->index();
            $table->column('level')->integer()->index()->nullable();
            $table->column('order')->integer()->index()->nullable();
            $table->column('active')->bool()->index();
            $table->column('deleted')->bool()->index();
            $table->timestamps();
        });

    }

    public function down()
    {
        $this->schema->drop('node');
    }
}