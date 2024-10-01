<?php

use Pecee\DB\Migration;
use Pecee\DB\Schema\Table;

class CreateUserResetTable extends Migration
{
	public function up(): void
	{
		$this->schema->create('user_reset', function (Table $table) {
			$table->column('id')->bigint()->primary()->increment();
			$table->column('user_id')->bigint()->index()->relation('user', 'id');
			$table->column('key')->string(32)->index();
            $table->timestamps();
		});
	}

	public function down(): void
	{
		$this->schema->drop('user_reset');
	}
}