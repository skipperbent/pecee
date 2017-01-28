<?php

use Pecee\DB\Migration;
use Pecee\DB\Schema\Table;

class CreateUserBadLoginTable extends Migration
{
	public function up()
	{
		$this->schema->create('user_bad_login', function (Table $table) {
			$table->column('id')->bigint()->primary()->increment();
			$table->column('username')->string(300)->index();
			$table->column('ip')->string(50)->index();
			$table->column('active')->bool()->nullable()->index();
            $table->timestamps();
		});
	}

	public function down()
	{
		$this->schema->drop('user_bad_login');
	}
}