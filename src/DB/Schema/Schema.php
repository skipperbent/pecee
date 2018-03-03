<?php
namespace Pecee\DB\Schema;

class Schema
{
    /**
     * @param $name
     * @param $callback
     * @throws \PDOException
     */
    public function create($name, $callback)
    {
        $table = new Table($name);
        $callback($table);
        $table->create();
    }

    /**
     * @param string $name
     * @throws \PDOException
     */
    public function drop($name)
    {
        $table = new Table($name);
        $table->drop();
    }

    /**
     * @param string $name
     * @param string $callback
     * @throws \PDOException
     */
    public function modify($name, $callback)
    {
        $table = new Table($name);
        $callback($table);
        $table->alter();
    }

}