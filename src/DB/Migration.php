<?php
namespace Pecee\DB;

use Pecee\DB\Schema\Schema;
use Phinx\Migration\AbstractMigration;

abstract class Migration extends AbstractMigration {

    /**
     * @var Schema
     */
    public $schema;

    public function init() {
        $this->schema = new Schema();
    }

}