<?php

use Pecee\DB\Migration;
use Pecee\DB\Schema\Table;

class CreateRewriteTable extends Migration
{
	public function up()
	{
		$this->schema->create('rewrite', function (Table $table) {
			$table->column('id')->integer()->primary()->increment();
			$table->column('original_url')->string(355)->index();
			$table->column('rewrite_url')->string(355)->index();
			$table->column('host')->string(255)->index()->nullable();
			$table->column('regex')->string(255)->index()->nullable();
			$table->column('order')->integer()->index()->nullable();
            $table->timestamps();
		});
	}

	public function down()
	{
		$this->schema->drop('rewrite');
	}
}