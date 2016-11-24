<?php

use Pecee\DB\Migration;
use Pecee\DB\Schema\Table;

class CreateFileDataTable extends Migration
{
	public function up()
	{
		$this->schema->create('file_data', function (Table $table) {
			$table->column('id')->bigint()->primary()->increment();
			$table->column('file_id')->string(40)->index()->relation('file', 'id');
			$table->column('key')->string(255)->index();
			$table->column('value')->longtext()->nullable();
		});
	}

	public function down()
	{
		$this->schema->drop('file_data');
	}
}