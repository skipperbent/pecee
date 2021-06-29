<?php

use Pecee\DB\Migration;
use Pecee\DB\Schema\Table;

class CreateUserDataTable extends Migration
{
    public function up()
    {
        $this->schema->create('user_data', function (Table $table) {
            $table->column('id')->string(32)->primary();
            $table->column('user_id')->bigint()->relation('user', 'id');
            $table->column('key')->string(255);
            $table->column('value')->longtext()->nullable();

            $table->index()->columns([
                'user_id' => null,
                'key' => null,
                'value' => 500
            ]);
        });
    }

	public function down()
	{
		$this->schema->drop('user_data');
	}
}