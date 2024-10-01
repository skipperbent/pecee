<?php

use Pecee\DB\Migration;
use Pecee\DB\Schema\Table;

class MigrationDummy extends Migration
{

	public function up(): void
	{
		$this->schema->create('dummy_table', function (Table $table) {

		});
	}

	public function down(): void
	{
		$this->schema->drop('dummy_table');
	}
}