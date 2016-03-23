<?php
namespace Pecee\DB\Schema;

class Schema {

    public function create($name, $callback) {
        $callback(new Table($name));
    }

    public function drop($name) {
        $table = new Table($name);
        $table->drop();
    }

}