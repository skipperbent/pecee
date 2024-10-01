<?php

use Pecee\DB\Migration;
use Pecee\DB\Schema\Table;

class CreateUserDataTable extends Migration
{
	public function up(): void
	{
		$this->schema->create('user_data', function (Table $table) {
			$table->column('id')->bigint()->primary()->increment();
			$table->column('user_id')->bigint()->index()->relation('user', 'id');
			$table->column('key')->string(255)->index();
			$table->column('value')->longtext()->nullable();
		});
	}

	public function down(): void
	{
		$this->schema->drop('user_data');
	}
}