<?php

use Pecee\DB\Migration;
use Pecee\DB\Schema\Table;

class CreateFileTable extends Migration
{
	public function up(): void
	{
		$this->schema->create('file', function (Table $table) {
			$table->column('id')->string(40)->primary();
			$table->column('filename')->string(355)->index();
			$table->column('original_filename')->string(355)->index();
			$table->column('path')->string(355)->index();
			$table->column('type')->string(255)->index();
			$table->column('bytes')->integer()->index();
            $table->timestamps();
		});
	}

	public function down(): void
	{
		$this->schema->drop('file');
	}
}