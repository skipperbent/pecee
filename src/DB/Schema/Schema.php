<?php

namespace Pecee\DB\Schema;

class Schema
{
    /**
     * Creates new table
     *
     * @param string $name
     * @param callable $callback
     */
    public function create(string $name, callable $callback): void
    {
        $table = new Table($name);
        $callback($table);
        $table->create();
    }

    /**
     * Modify table by given name
     *
     * @param string $name
     * @param callable $callback
     */
    public function modify(string $name, callable $callback): void
    {
        $table = new Table($name);
        $callback($table);
        $table->alter();
    }

    /**
     * Drops table by given name
     *
     * @param string $name
     */
    public function drop(string $name): void
    {
        $table = new Table($name);
        $table->drop();
    }

}