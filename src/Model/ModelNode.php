<?php
namespace Pecee\Model;

use Pecee\Boolean;
use Pecee\Date;
use Pecee\DB\DBTable;
use Pecee\Collection\CollectionItem;
use Pecee\DB\PdoHelper;
use Pecee\Model\Node\NodeData;
use Pecee\Str;

class ModelNode extends Model {

    const ORDER_ID_DESC = 'n.`id` DESC';
    const ORDER_CHANGED_DESC = 'IFNULL(n.`changed_date`, IFNULL(n.`active_from`, n.`created_date`)) DESC';
    const ORDER_CHANGED_ASC = 'IFNULL(n.`changed_date`, IFNULL(n.`active_from`, n.`created_date`)) ASC';
    const ORDER_CREATED_DESC = 'n.`created_date` DESC';
    const ORDER_CREATED_ASC = 'n.`created_date` ASC';
    const ORDER_ACTIVE_CREATED_DESC = 'IFNULL(n.`active_from`, n.`created_date`) DESC, n.`created_date` DESC';
    const ORDER_ACTIVE_CREATED_ASC = 'IFNULL(n.`active_from`, n.`created_date`) ASC, n.`created_date` ASC';
    const ORDER_TITLE_DESC = 'n.`title` DESC';
    const ORDER_TITLE_ASC = 'n.`title` ASC';
    const ORDER_PARENT_DESC = 'n.`parent_id` DESC';
    const ORDER_PARENT_ASC = 'n.`parent_id` ASC';
    const ORDER_ORDER_DESC = 'n.`order` DESC';
    const ORDER_ORDER_ASC = 'n.`order` ASC';

    public static $orders = [
        self::ORDER_ID_DESC,
        self::ORDER_CREATED_ASC,
        self::ORDER_CREATED_DESC,
        self::ORDER_TITLE_ASC,
        self::ORDER_TITLE_DESC,
        self::ORDER_PARENT_DESC,
        self::ORDER_PARENT_ASC,
        self::ORDER_ORDER_ASC,
        self::ORDER_ORDER_DESC,
        self::ORDER_CHANGED_DESC,
        self::ORDER_CHANGED_ASC,
        self::ORDER_ACTIVE_CREATED_DESC,
        self::ORDER_ACTIVE_CREATED_ASC
    ];

    public $data;
    protected $parent, $next, $prev, $childs, $type;

    public function __construct() {

        $table = new DBTable('node');
        $table->column('id')->bigint()->primary()->increment();
        $table->column('parent_id')->bigint()->nullable()->index();
        $table->column('path')->string(255)->index();
        $table->column('type')->string(255)->index();
        $table->column('title')->string(255);
        $table->column('content')->longtext()->nullable();
        $table->column('created_date')->datetime()->index();
        $table->column('changed_date')->datetime()->nullable()->index();
        $table->column('active_from')->datetime()->nullable()->index();
        $table->column('active_to')->datetime()->nullable()->index();
        $table->column('level')->integer()->index();
        $table->column('order')->integer()->index();
        $table->column('active')->bool()->index()->nullable();

        parent::__construct($table);

        $this->data = new CollectionItem();
        $this->path = 0;
        $this->created_date = Date::toDateTime();
    }

    public function isActive() {
        return ($this->active && ($this->active_from === null || time() >= strtotime($this->active_from)) && ($this->active_to === null || $this->active_to >= time()));
    }

    protected function calculatePath() {
        $path = array('0');
        $fetchingPath = true;
        if($this->parent_id) {
            $parent = self::getById($this->parent_id);
            $i = 0;
            while($fetchingPath) {
                if($parent->hasRow()) {
                    $path[] = $parent->id;
                    $p = $parent->parent_id;
                    if(!empty($p)) {
                        $parent = self::getById($parent->parent_id);
                    } else {
                        $fetchingPath = false;
                    }
                    $i++;
                } else {
                    $fetchingPath = false;
                }
            }

            if($i === 0) {
                $path[]=$this->parent_id;
            }
        }
        $this->Path = join('>', $path);
        $this->Level = count($path);
    }

    public function removeData($name) {
        unset($this->data->$name);
    }

    public function setData($name,$value) {
        $this->data->$name = $value;
    }

    public function getData($name) {
        return $this->data->$name;
    }

    public function getNext() {
        if(!$this->next) {
            $parentId = 0;
            if($this->parent_id) {
                $parentId = self::getById($this->parent_id);
                if($parentId->hasRow()) {
                    $parentId = $parentId->id;
                }
            }

            $where = array('n.`active` = 1');
            $where[] = PdoHelper::formatQuery('(ISNULL(n.`active_from`) && ISNULL(n.`active_to`) || n.`active_from` <= NOW() && (n.`active_to` >= NOW() || ISNULL(n.`active_to`)))');
            $where[] = "n.`parent_id` = '".PdoHelper::escape($parentId)."'";
            $where[] = "n.`path` LIKE '%>".PdoHelper::escape($parentId).">%'";
            $where[] = 'n.`order` > ' . $this->order;

            $this->next = self::fetchOne('SELECT n.* FROM {table} n WHERE ' . join(' && ', $where));
        }
        return $this->next;
    }

    public function getPrev() {
        if(!$this->prev) {
            $parentId = 0;
            if($this->parent_id) {
                $parentId = self::getById($this->parent_id);
                if($parentId->hasRow()) {
                    $parentId = $parentId->id;
                }
            }

            $where = array('n.`active` = 1');
            $where[] = PdoHelper::formatQuery('(ISNULL(n.`active_from`) && ISNULL(n.`active_to`) || n.`active_from` <= NOW() && (n.`active_to` >= NOW() || ISNULL(n.`active_to`)))');
            $where[] = "n.`parent_id` = '".PdoHelper::escape($parentId)."'";
            $where[] = "n.`path` LIKE '%>".PdoHelper::escape($parentId).">%'";
            $where[] = 'n.`order` < ' . $this->Order;

            $this->prev = self::fetchOne('SELECT n.* FROM {table} n WHERE ' . join(' && ', $where));
        }
        return $this->prev;
    }

    /**
     * Get childs
     * @param string $alias
     * @param bool $recursive
     * @param string $order
     * @return static
     */
    public function getChildsOfType($alias, $recursive = true, $order = null) {
        $out = array();
        if($recursive) {
            $pages = self::get(null, null, null, null, $this->id, $order);
        } else {
            $pages =  self::get(null, null, null, $this->id, null, $order, null, null);
        }
        if($pages->hasRows()) {
            foreach($pages->getRows() as $page) {
                if($page->getProperty()->hasRow() && $page->getProperty()->alias == $alias) {
                    $out[] = $page;
                }
            }
        }
        $result = get_called_class();
        $result = new $result();
        $result->setRows($out);
        return $result;
    }

    public function getChilds($query = null, $active = null, $order = null) {
        $key = md5($query . $active . $order);
        if(!isset($this->childs[$key])) {
            $this->childs[$key] = static::get($this->type, $query, $active, $this->id, null, $order, null, null);
        }
        return $this->childs[$key];
    }

    public function getParent() {
        if($this->parent === null && $this->parent_id !== null) {
            $this->parent = static::getById($this->parent_id);
        }

        return $this->parent;
    }

    public function updateFields() {
        if($this->data !== null) {

            $currentFields = NodeData::getByNodeId($this->id);

            $cf = array();
            foreach($currentFields as $field) {
                $cf[strtolower($field->key)] = $field;
            }

            if(count($this->data->getData())) {
                foreach($this->data->getData() as $key=>$value) {

                    if($value === null) {
                        continue;
                    }

                    if(isset($cf[strtolower($key)])) {
                        if($cf[$key]->value === $value) {
                            unset($cf[$key]);
                            continue;
                        } else {
                            $cf[$key]->value = $value;
                            $cf[$key]->key = $key;
                            $cf[$key]->update();
                            unset($cf[$key]);
                        }
                    } else {
                        $field = new NodeData();
                        $field->node_id = $this->id;
                        $field->key = $key;
                        $field->value = $value;
                        $field->save();
                    }
                }
            }

            foreach($cf as $field) {
                $field->delete();
            }
        }
    }

    public function save() {
        $this->calculatePath();
        $this->id = parent::save()->id;
        $this->updateFields();
    }

    public function update() {
        $this->changed_date = Date::toDateTime();
        $this->calculatePath();
        $this->updateFields();
        parent::update();
    }

    public function delete() {
        // Delete childs
        $childs = $this->getChilds();
        if($childs->hasRows()) {
            foreach($childs->getRows() as $child) {
                $child->delete(false);
            }
        }

        NodeData::clear($this->id);
        parent::delete();
    }

    public function exists() {
        return self::scalar('SELECT `id` FROM {table} WHERE `id` = %s', $this->id);
    }

    protected function fetchField() {
        $data = NodeData::getByNodeId($this->id);
        if($data->hasRows()) {
            foreach($data->getRows() as $field) {
                $key=$field->key;
                $this->data->$key = $field->value;
            }
        }
    }

    /**
     * Order by key
     * @param string $key
     * @param string $direction
     * @return static
     */
    public function order($key, $direction = 'DESC') {
        if($this->hasRows()) {
            $rows = array();
            foreach($this->getRows() as $row) {
                $k = (isset($row->fields[$key])) ? $row->__get($key) : $row->data->$key;
                $k = ($k == 'Tjs=') ? Str::base64Decode($k) : $k;
                $rows[$k] = $row;
            }
            if(strtolower($direction) === 'asc') {
                ksort($rows);
            } else {
                krsort($rows);
            }

            $this->setRows(array_values($rows));
        }

        return $this;
    }

    /**
     * Get first or default value
     * @param string $default
     * @return static
     */
    public function getFirstOrDefault($default = null) {
        if($this->hasRows()) {
            return $this->getRow(0);
        }
        return $default;
    }

    /**
     * Skip number of rows
     * @param int $number
     * @return static
     */
    public function skip($number) {
        if($this->hasRows() && $number > 0) {
            $out = array_splice($this->getRows(), $number);
            $this->setRows($out);
        }
        return $this;
    }

    /**
     * Limit the output
     * @param int $limit
     * @return static
     */
    public function limit($limit) {
        $out = array();
        if($this->hasRows()) {
            foreach($this->getRows() as $i=>$row) {
                if($i < $limit) {
                    $out[] = $row;
                }
            }
        }
        $this->setRows($out);
        $this->setNumRow($limit);
        return $this;
    }

    /**
     * Filter elements
     * @param string $key
     * @param string $value
     * @param string $delimiter
     * @return static
     */
    public function where($key, $value, $delimiter = '=') {
        $out = array();
        if($this->hasRows()) {
            foreach($this->getRows() as $row) {
                $keys = (is_array($key)) ? $key : array($key);
                foreach($keys as $_key) {
                    $k = (array_key_exists($_key, $row->fields)) ? $row->__get($_key) : $row->data->$_key;
                    $k = (strpos($k, 'Tjs=') == '1') ? Str::base64Encode($k) : $k;

                    if($delimiter === '>') {
                        if($k > $value) {
                            if(!in_array($row, $out)) {
                                $out[] = $row;
                            }
                        }
                    } elseif($delimiter === '<') {
                        if($k < $value) {
                            if(!in_array($row, $out)) {
                                $out[] = $row;
                            }
                        }
                    } elseif($delimiter === '>=') {
                        if($k >= $value) {
                            if(!in_array($row, $out)) {
                                $out[] = $row;
                            }
                        }
                    } elseif($delimiter === '<=') {
                        if($k <= $value) {
                            if(!in_array($row, $out)) {
                                $out[] = $row;
                            }
                        }
                    } elseif($delimiter === '!=') {
                        if($k != $value) {
                            if(!in_array($row, $out)) {
                                $out[] = $row;
                            }
                        }
                    } elseif($delimiter === '*') {
                        if(strtolower($k) == $value || strstr(strtolower($k), strtolower($value)) !== false) {
                            if(!in_array($row, $out)) {
                                $out[] = $row;
                            }
                        }
                    } else {
                        if($k === $value) {
                            if(!in_array($row, $out)) {
                                $out[] = $row;
                            }
                        }
                    }
                }
            }
        }
        $this->setMaxRows(count($out));
        $this->setRows($out);
        return $this;
    }

    /**
     * Get node by node ids
     * @param array $ids
     * @param bool|null $active
     * @param int|null $rows
     * @param int|null $page
     * @return static
     */
    public static function getByIds(array $ids, $active = null, $rows = null,$page = null) {
        $where='n.`id` IN('.PdoHelper::joinArray($ids).')';
        if(!is_null($active)) {
            $where .= ' AND n.`active` = ' . Boolean::parse($active,0);
        }
        return self::fetchPage('SELECT n.* FROM {table} n WHERE ' . $where . ' ORDER BY n.`order` ASC', $rows, $page);
    }

    /**
     * Get node by node id.
     * @param int $id
     * @param bool|null $active
     * @return static
     */
    public static function getById($id, $active = null) {
        $where = 'n.`id` = %s';
        if(!is_null($active)) {
            $where.=' AND n.`active` = ' . Boolean::parse($active,0);
        }
        return self::fetchOne('SELECT n.* FROM {table} n WHERE ' . $where, array($id));
    }

    /**
     * Get nodes.
     * @param string|null $type
     * @param string|null $query
     * @param bool|null $active
     * @param int|null $parentId
     * @param string|null $order
     * @param int|null $rows
     * @param int|null $page
     * @return static
     */
    public static function getByPath($type = null, $query = null, $active = null, $parentId = null, $order = null, $rows = null, $page = null) {
        $where=array('1=1');

        if(!is_null($active)) {
            $where[] = PdoHelper::formatQuery('n.`active` = %s', array(Boolean::parse($active)));
            $where[] = PdoHelper::formatQuery('(ISNULL(n.`active_from`) && ISNULL(n.`active_to`) || n.`active_from` <= NOW() && (n.`active_to` >= NOW() || ISNULL(n.`active_to`)))');
        }

        if(!is_null($parentId)) {
            if(empty($parentId)) {
                $where[] = "(n.`path` IS NULL OR n.`parent_id` IS NULL)";
            } else {
                $where[] = "(n.`path` LIKE '%".PdoHelper::escape($parentId)."%') ";
            }
        }

        if(!is_null($type)) {
            $where[] =  'n.`type` = \''.$type.'\'';
        }

        if(!is_null($query)) {
            $where[] = sprintf('(n.`title` LIKE \'%s\' OR n.`content` LIKE \'%s\')', '%'.PdoHelper::escape($query).'%', '%'.PdoHelper::escape($query).'%');
        }

        $order = (!is_null($order) && in_array($order, self::$orders)) ? $order : static::ORDER_CREATED_DESC;

        return self::fetchPage('SELECT n.* FROM {table} n WHERE ' . join(' && ', $where) . ' ORDER BY ' . $order, $rows, $page);
    }

    /**
     * Get entities.
     * @param string|null $type
     * @param string|null $query
     * @param bool|null $active
     * @param int|null $parentId
     * @param string|null $path
     * @param string|null $order
     * @param int|null $rows
     * @param int|null $page
     * @return static
     */
    public static function get($type = null, $query = null, $active = null, $parentId = null, $path = null, $order = null, $rows = null, $page = null) {
        $where = array('1=1');

        if(!is_null($active)) {
            $where[] = PdoHelper::formatQuery('n.`active` = %s', array(Boolean::parse($active)));
            $where[] = PdoHelper::formatQuery('(ISNULL(n.`active_from`) && ISNULL(n.`active_to`) || n.`active_from` <= NOW() && (n.`active_to` >= NOW() || ISNULL(n.`active_to`)))');
        }

        if(!is_null($parentId)) {
            $where[] = "n.`parent_id` = '".PdoHelper::escape($parentId)."'";
        }

        if(!is_null($path)) {
            $where[] = "(n.`path` = '".PdoHelper::escape($path)."' || n.`path` LIKE '>%".PdoHelper::escape($path)."')";
        }

        if(!is_null($type)) {
            if(is_array($type)) {
                $where[] =  'n.`type` IN ('.PdoHelper::joinArray($type).')';
            } else {
                $where[] =  'n.`type` = \''.$type.'\'';
            }

        }

        if(!is_null($query)) {
            $where[] = sprintf('(n.`title` LIKE \'%s\' OR n.`content` LIKE \'%s\')', '%'.PdoHelper::escape($query).'%', '%'.PdoHelper::escape($query).'%');
        }

        $order = (!is_null($order) && in_array($order, self::$orders)) ? $order : static::ORDER_CREATED_DESC;

        return self::fetchPage('SELECT n.* FROM {table} n WHERE ' . join(' && ', $where) . ' ORDER BY ' . $order, $rows, $page);
    }

    public function setRows(array $rows)  {
        parent::setRows($rows);
        $this->fetchField();
    }

}