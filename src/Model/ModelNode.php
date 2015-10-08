<?php
namespace Pecee\Model;
use Pecee\Bool;
use Pecee\Date;
use Pecee\DB\DB;
use Pecee\DB\DBTable;
use Pecee\Collection;
use Pecee\Model\Node\NodeData;
use Pecee\PhpString;

class ModelNode extends Model {
	const ORDER_ID_DESC = 'n.`nodeId` DESC';
	const ORDER_CHANGED_DESC = 'IFnull(n.`changedDate`, IFnull(n.`activeFrom`, n.`pubDate`)) DESC';
	const ORDER_CHANGED_ASC = 'IFnull(n.`changedDate`, IFnull(n.`activeFrom`, n.`pubDate`)) ASC';
	const ORDER_DATE_DESC = 'IFnull(n.`activeFrom`, n.`pubDate`) DESC';
	const ORDER_DATE_ASC = 'n.`pubDate` ASC';
	const ORDER_TITLE_DESC = 'n.`title` DESC';
	const ORDER_TITLE_ASC = 'n.`title` ASC';
	const ORDER_PARENT_DESC = 'n.`parentNodeId` DESC';
	const ORDER_PARENT_ASC = 'n.`parentNodeId` ASC';
	const ORDER_ORDER_DESC = 'n.`order` DESC';
	const ORDER_ORDER_ASC = 'n.`order` ASC';

	public static $orders=array(self::ORDER_ID_DESC,self::ORDER_DATE_ASC,
								self::ORDER_DATE_DESC,self::ORDER_TITLE_ASC,
								self::ORDER_TITLE_DESC, self::ORDER_PARENT_DESC,
								self::ORDER_PARENT_ASC, self::ORDER_ORDER_ASC, self::ORDER_ORDER_DESC);

	public $data;
	protected $parent, $next, $prev;
	protected $childs;
	public function __construct() {

        $table = new DBTable();
        $table->column('nodeId')->bigint()->primary()->increment();
        $table->column('parentNodeId')->bigint()->index();
        $table->column('path')->string(255)->index();
        $table->column('type')->string(255)->index();
        $table->column('title')->string(255);
        $table->column('content')->longtext();
        $table->column('pubDate')->datetime()->index();
        $table->column('changedDate')->datetime()->index();
        $table->column('activeFrom')->datetime()->index();
        $table->column('activeTo')->datetime()->index();
        $table->column('level')->integer()->index();
        $table->column('order')->integer()->index();

		parent::__construct($table);
		$this->data = new Collection();
	}

	protected function calculatePath() {
		$path=array('0');
		$fetchingPath=true;
		if($this->parentNodeId) {
			$parent=self::GetByNodeID($this->parentNodeId);
			$i=0;
			while($fetchingPath) {
				if($parent->hasRow()) {
					$path[]=$parent->getNodeId();
					$p=$parent->getParentNodeId();
					if(!empty($p)) {
						$parent=self::GetByNodeID($parent->getParentNodeId());
					} else {
						$fetchingPath=false;
					}
					$i++;
				} else {
					$fetchingPath=false;
				}
			}

			if($i==0) {
				$path[]=$this->parentNodeId;
			}
		}
		$this->Path=join('>', $path);
		$this->Level=count($path);
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
			$parentNodeId = 0;
			if($this->parentNodeId) {
				$parentNodeId = self::GetByNodeID($this->parentNodeId);
				if($parentNodeId->hasRow()) {
					$parentNodeId = $parentNodeId->getNodeId();
				}
			}

			$where=array('n.`active` = 1');
			$where[] = DB::FormatQuery('(ISnull(n.`activeFrom`) && ISnull(n.`activeTo`) || n.`activeFrom` <= NOW() && (n.`activeTo` >= NOW() || ISnull(n.`activeTo`)))');
			$where[] = "n.`parentNodeID` = '".DB::Escape($parentNodeId)."'";
			$where[] = "n.`path` LIKE '%>".DB::Escape($parentNodeId).">%'";
			$where[] = 'n.`order` > ' . $this->order;

			$this->next = self::FetchOne('SELECT n.* FROM {table} n WHERE ' . join(' && ', $where));
		}
		return $this->next;
	}

	public function getPrev() {
		if(!$this->prev) {
			$parentNodeId = 0;
			if($this->parentNodeId) {
				$parentNodeId = self::GetByNodeID($this->parentNodeId);
				if($parentNodeId->hasRow()) {
					$parentNodeId = $parentNodeId->getNodeId();
				}
			}

			$where=array('n.`active` = 1');
			$where[] = DB::FormatQuery('(ISnull(n.`activeFrom`) && ISnull(n.`activeTo`) || n.`activeFrom` <= NOW() && (n.`activeTo` >= NOW() || ISnull(n.`activeTo`)))');
			$where[] = "n.`parentNodeId` = '".DB::Escape($parentNodeId)."'";
			$where[] = "n.`path` LIKE '%>".DB::Escape($parentNodeId).">%'";
			$where[] = 'n.`order` < ' . $this->Order;

			$this->prev = self::FetchOne('SELECT n.* FROM {table} n WHERE ' . join(' && ', $where));
		}
		return $this->prev;
	}

	/**
	 * Get childs
	 * @param string $alias
	 * @param string $recursive
	 * @param string $order
	 * @return self
	 */
	public function getChildsOfType($alias, $recursive=true, $order = null) {
		$out = array();
		if($recursive) {
			$pages = self::Get(null, null, null, null, $this->getNodeId(), $order);
		} else {
			$pages =  self::Get(null, null, null, $this->getNodeId(), null, $order, null, null);
		}
		if($pages->hasRows()) {
			foreach($pages->getRows() as $page) {
				if($page->getProperty()->hasRow() && $page->getProperty()->getAlias() == $alias) {
					$out[] = $page;
				}
			}
		}
		$result = get_called_class();
		$result = new $result();
		$result->setRows($out);
		return $result;
	}

	public function setChilds($childs) {
		$this->childs = $childs;
	}

	public function getChilds() {
		if(!$this->parent) {
			$this->parent = self::Get(null, null, null, $this->getNodeId(), null, null, null, null);
		}
		return $this->parent;
	}

	public function updateFields() {
		if($this->data) {
			/* Remove all fields */
			NodeData::Clear($this->nodeId);
			if(count($this->data->getData()) > 0) {
				foreach($this->data->getData() as $key=>$value) {
					$field=new NodeData();
					$field->setNodeId($this->nodeId);
					$field->setKey($key);
					$field->setValue($value);
					$field->save();
				}
			}
		}
	}

	public function save() {
		$this->calculatePath();
		$this->nodeId = parent::save()->getInsertId();
		$this->updateFields();
	}

	public function update() {
		$this->changedDate = Date::ToDateTime();
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

		NodeData::Clear($this->nodeId);
		parent::delete();
	}

	public function exists() {
		return self::Scalar('SELECT `NodeID` FROM {table} WHERE `NodeID` = %s', $this->nodeId);
	}

	protected function fetchField($row) {
		$data = NodeData::GetByNodeID($row->nodeId);
		if($data->hasRows()) {
			foreach($data->getRows() as $field) {
				$key=$field->getKey();
				$row->data->$key = $field->getValue();
			}
		}
	}

	protected function setEntityFields($single=false) {
		if($single && $this->hasRow()) {
			$this->fetchField($this);
		} else {
			if($this->hasRows()) {
				$nodeIds = array();
				foreach($this->getRows() as $row) {
					$nodeIds[] = $row->getNodeId();
				}

				if(count($nodeIds) > 0) {
					$nodeData = array();
					$data = NodeData::GetByNodeIDs($nodeIds);
					if($data->hasRows()) {
						foreach($data->getRows() as $data) {
							$nodeData[$data->getNodeId()][] = $data;
						}
					}

					foreach($this->getRows() as $row) {
						if(isset($nodeData[$row->getNodeId()])) {
							foreach($nodeData[$row->getNodeId()] as $field) {
								$key=$field->getKey();
								$row->data->$key = $field->getValue();
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Order by key
	 * @param string $key
	 * @param string $direction
	 * @return self
	 */
	public function order($key, $direction = 'DESC') {
		if($this->hasRows()) {
			$rows = array();
			foreach($this->getRows() as $row) {
				$k = (isset($row->fields[$key])) ? $row->__get($key) : $row->data->$key;
				$k = ($k == 'Tjs=') ? PhpString::base64Decode($k) : $k;
				$rows[$k] = $row;
			}
			if(strtolower($direction) == 'asc') {
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
	 * @return self
	 */
	public function getFirstOrDefault($default=null) {
		if($this->hasRows()) {
			return $this->getRow(0);
		}
		return $default;
	}

	/**
	 * Skip number of rows
	 * @param int $number
	 * @return self
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
	 * @return self
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
	 * @return self
	 */
	public function where($key, $value, $delimiter = '=') {
		$out = array();
		if($this->hasRows()) {
			foreach($this->getRows() as $row) {
				$keys = (is_array($key)) ? $key : array($key);
				foreach($keys as $_key) {
					$k = (array_key_exists($_key, $row->fields)) ? $row->__get($_key) : $row->data->$_key;
					$k = (strpos($k, 'Tjs=') == '1') ? PhpString::base64Decode($k) : $k;

					if($delimiter == '>') {
						if($k > $value) {
							if(!in_array($row, $out)) {
								$out[] = $row;
							}
						}
					} elseif($delimiter == '<') {
						if($k < $value) {
							if(!in_array($row, $out)) {
								$out[] = $row;
							}
						}
					} elseif($delimiter == '>=') {
						if($k >= $value) {
							if(!in_array($row, $out)) {
								$out[] = $row;
							}
						}
					} elseif($delimiter == '<=') {
						if($k <= $value) {
							if(!in_array($row, $out)) {
								$out[] = $row;
							}
						}
					} elseif($delimiter == '!=') {
						if($k != $value) {
							if(!in_array($row, $out)) {
								$out[] = $row;
							}
						}
					} elseif($delimiter == '*') {
						if(strtolower($k) == $value || strstr(strtolower($k), strtolower($value)) !== false) {
							if(!in_array($row, $out)) {
								$out[] = $row;
							}
						}
					} else {
						if($k == $value) {
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
	 * @param array $nodeIds
     * @param bool|null $active
     * @param int|null $rows
     * @param int|null $page
	 * @return self
	 */
	public static function GetByNodeIDs(array $nodeIds, $active=null, $rows=null,$page=null) {
		$where='n.`nodeId` IN('.DB::JoinArray($nodeIds).')';
		if(!is_null($active)) {
			$where.=' AND n.`active` = ' . PhpBoolParse($active,0);
		}
		return self::FetchPage('SELECT n.* FROM {table} n WHERE ' . $where . ' ORDER BY n.`order` ASC', $rows, $page);
	}

	/**
	 * Get node by node id.
	 * @param int $nodeId
     * @param bool|null $active
	 * @return self
	 */
	public static function GetByNodeID($nodeId, $active=null) {
		$where='n.`nodeId` = %s';
		if(!is_null($active)) {
			$where.=' AND n.`active` = ' . PhpBoolParse($active,0);
		}
		return self::FetchOne('SELECT n.* FROM {table} n WHERE ' . $where, array($nodeId));
	}

	/**
	 * Get nodes.
	 * @param string|null $type
	 * @param string|null $query
	 * @param bool|null $active
	 * @param int|null $parentNodeId
     * @param string|null $order
     * @param int|null $rows
     * @param int|null $page
	 * @return self
	 */
	public static function GetByPath($type=null, $query=null, $active=null, $parentNodeId=null, $order=null, $rows=null, $page=null) {
		$where=array('1=1');
		if(!is_null($active)) {
			$where[] = DB::FormatQuery('n.`active` = %s', array(PhpBoolParse($active)));
			$where[] = DB::FormatQuery('(ISnull(n.`activeFrom`) && ISnull(n.`activeTo`) || n.`activeFrom` <= NOW() && (n.`activeTo` >= NOW() || ISnull(n.`activeTo`)))');
		}
		if(!is_null($parentNodeId)) {
			if(empty($parentNodeId)) {
				$where[] = "(n.`path` IS null OR n.`parentNodeId` IS null)";
			} else {
				$where[] = "(n.`path` LIKE '%".DB::Escape($parentNodeId)."%'') ";
			}
		}
		if(!is_null($type)) {
			$where[] =  'n.`type` = \''.$type.'\'';
		}
		if(!is_null($query)) {
			$where[] = sprintf('(n.`title` LIKE \'%s\' OR n.`content` LIKE \'%s\')', '%'.DB::Escape($query).'%', '%'.DB::Escape($query).'%');
		}
		$order=(!is_null($order) && in_array($order, self::$orders)) ? $order : self::ORDER_DATE_DESC;
		return self::FetchPage('SELECT n.* FROM {table} n WHERE ' . join(' && ', $where) . ' ORDER BY ' . $order, $rows, $page);
	}

	/**
	 * Get entities.
	 * @param string|null $type
	 * @param string|null $query
	 * @param bool|null $active
	 * @param int|null $parentNodeId
     * @param string|null $path
     * @param string|null $order
     * @param int|null $rows
     * @param int|null $page
	 * @return self
	 */
	public static function Get($type=null, $query=null, $active=null, $parentNodeId=null, $path=null, $order=null, $rows=null, $page=null) {
		$where=array('1=1');
		if(!is_null($active)) {
			$where[] = DB::FormatQuery('n.`active` = %s', array(PhpBoolParse($active)));
			$where[] = DB::FormatQuery('(ISnull(n.`activeFrom`) && ISnull(n.`activeTo`) || n.`activeFrom` <= NOW() && (n.`activeTo` >= NOW() || ISnull(n.`activeTo`)))');
		}
		if(!is_null($parentNodeId)) {
			$where[] = "n.`parentNodeId` = '".DB::Escape($parentNodeId)."'";
		}

		if(!is_null($path)) {
			$where[] = "n.`path` LIKE '>%".DB::Escape($path)."'";
		}

		if(!is_null($type)) {
			if(is_array($type)) {
				$where[] =  'n.`type` IN ('.DB::JoinArray($type).')';
			} else {
				$where[] =  'n.`type` = \''.$type.'\'';
			}

		}
		if(!is_null($query)) {
			$where[] = sprintf('(n.`title` LIKE \'%s\' OR n.`content` LIKE \'%s\')', '%'.DB::Escape($query).'%', '%'.DB::Escape($query).'%');
		}
		$order=(!is_null($order) && in_array($order, self::$orders)) ? $order : self::ORDER_ORDER_ASC;
		return self::FetchPage('SELECT n.* FROM {table} n WHERE ' . join(' && ', $where) . ' ORDER BY ' . $order, $rows, $page);
	}

	public static function FetchPage($query, $rows = 10, $page = 0, $args=null) {
		$args = (!$args || is_array($args) ? $args : DB::ParseArgs(func_get_args(), 3));
		$model = parent::FetchPage($query, $rows, $page, $args);
		$model->setEntityFields();
		return $model;
	}

	public static function FetchRows($query, $startIndex=0, $rows = 10, $args = null) {
		$args = (!$args || is_array($args) ? $args : DB::ParseArgs(func_get_args(), 3));
		$model = parent::FetchAllPage($query, $startIndex, $rows, $args);
		$model->setEntityFields();
		return $model;
	}

	public static function FetchOne($query, $args=null) {
		$args = (!$args || is_array($args) ? $args : DB::ParseArgs(func_get_args(), 1));
		$model = parent::FetchOne($query, $args);
		$model->setEntityFields(true);
		return $model;
	}

}
