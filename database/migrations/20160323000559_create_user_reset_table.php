<?php

use Pecee\DB\Migration;
use Pecee\DB\Schema\Table;

class CreateUserResetTable extends Migration
{
	public function up()
	{
		$this->schema->create('user_reset', function (Table $table) {
			$table->column('id')->bigint()->primary()->increment();
			$table->column('user_id')->bigint()->index()->relation('user', 'id');
			$table->column('key')->string(32)->index();
			$table->column('updated_at')->datetime()->nullable()->index();
			$table->column('created_at')->datetime()->index();
		});
	}

	public function down()
	{
		$this->schema->drop('user_reset');
	}
}