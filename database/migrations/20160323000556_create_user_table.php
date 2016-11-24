<?php

use Pecee\DB\Migration;
use Pecee\DB\Schema\Table;

class CreateUserTable extends Migration
{
	public function up()
	{
		$this->schema->create('user', function (Table $table) {
			$table->column('id')->bigint()->primary()->increment();
			$table->column('username')->string(300)->index();
			$table->column('password')->string(32)->index();
			$table->column('admin_level')->integer(1)->nullable()->index();
			$table->column('deleted')->bool()->index();
			$table->column('last_activity')->datetime()->nullable()->index();
			$table->column('updated_at')->datetime()->nullable()->index();
			$table->column('created_at')->datetime()->index();
		});
	}

	public function down()
	{
		$this->schema->drop('user');
	}
}