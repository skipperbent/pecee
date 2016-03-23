<?php
namespace Pecee\DB;

use Illuminate\Database\Capsule\Manager as Capsule;
use Phinx\Migration\AbstractMigration;

abstract class Migration extends AbstractMigration {

    /* @var \Illuminate\Database\Capsule\Manager $capsule */
    public $capsule;
    /* @var \Illuminate\Database\Schema\Builder $capsule */
    public $schema;

    public function init() {
        $this->capsule = new Capsule;
        $this->capsule->addConnection([
            'driver'    => env('DB_DRIVER', 'mysql'),
            'host'      => env('DB_HOST'),
            'port'      => env('DB_POST', 3306),
            'database'  => env('DB_DATABASE'),
            'username'  => env('DB_USERNAME'),
            'password'  => env('DB_PASSWORD'),
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
        ]);

        $this->capsule->bootEloquent();
        $this->capsule->setAsGlobal();
        $this->schema = $this->capsule->schema();
    }
    
}