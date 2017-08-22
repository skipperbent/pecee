<?php

namespace Pecee\Model;

use Pecee\Boolean;
use Pecee\Model\Node\NodeData;
use Pecee\Pixie\QueryBuilder\QueryBuilderHandler;
use Pecee\Str;

class ModelNode extends ModelData
{
    const SORT_ID = 'id';
    const SORT_PARENT = 'parent';
    const SORT_TITLE = 'title';
    const SORT_UPDATED = 'updated';
    const SORT_CREATED = 'created';
    const SORT_ACTIVE_CREATED = 'active_created';
    const SORT_ORDER = 'order';

    public static $sortTypes = [
        self::SORT_ID,
        self::SORT_PARENT,
        self::SORT_TITLE,
        self::SORT_UPDATED,
        self::SORT_CREATED,
        self::SORT_ACTIVE_CREATED,
        self::SORT_ORDER,
    ];

    protected $parent, $next, $prev, $children, $type;
    protected $defaultType;
    protected $table = 'node';
    protected $columns = [
        'id',
        'parent_id',
        'path',
        'type',
        'title',
        'content',
        'active_from',
        'active_to',
        'level',
        'order',
        'active',
        'updated_at',
        'created_at',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->path = 0;
        $this->order = 0;
        $this->active = false;

        if ($this->defaultType !== null) {
            $this->type = $this->defaultType;
            $this->where('type', '=', $this->defaultType);
        }
    }

    public function isActive()
    {
        return ($this->active && ($this->active_from === null || time() >= strtotime($this->active_from)) && ($this->active_to === null || $this->active_to >= time()));
    }

    protected function calculatePath()
    {
        $path = ['0'];
        $fetchingPath = true;
        if ($this->parent_id) {
            /* @var $parent self */
            $parent = static::instance()->find($this->parent_id);
            $i = 0;
            while ($fetchingPath === true) {
                if ($parent->hasRows() === true) {
                    $path[] = $parent->id;
                    $p = $parent->parent_id;
                    if (!empty($p)) {
                        $parent = static::instance()->find($parent->parent_id);
                    } else {
                        $fetchingPath = false;
                    }
                    $i++;
                } else {
                    $fetchingPath = false;
                }
            }
            if ($i === 0) {
                $path[] = $this->parent_id;
            }
        }
        $this->path = join('>', $path);
        $this->level = count($path);
    }

    public function getNext()
    {
        if ($this->next === false) {
            $parentId = 0;
            if ($this->parent_id !== null) {
                /* @var $parent self */
                $parent = static::instance()->find($this->parent_id);
                if ($parent->hasRows() === true) {
                    $parentId = $parent->id;
                }
            }

            $this->next = static::instance()->filterActive(true)->filterParentId($parentId)->where('order', '>', $this->order)->first();
        }

        return $this->next;
    }

    public function getPrev()
    {
        if ($this->prev === false) {
            $parentId = 0;
            if ($this->parent_id) {
                /* @var $parent self */
                $parent = static::instance()->find($this->parent_id);
                if ($parent->hasRows() === true) {
                    $parentId = $parent->id;
                }
            }

            $this->prev = static::instance()->filterActive(true)->filterParentId($parentId)->where('order', '<', $this->order)->first();
        }

        return $this->prev;
    }

    /**
     * Get children of type
     * @param string $type
     * @param bool $recursive
     * @return static
     */
    public function getChildrenOfType($type, $recursive = true)
    {
        $out = [];
        if ($recursive === true) {
            $pages = $this->filterPath($this->id)->all();
        } else {
            $pages = $this->filterParentId($this->id)->all();
        }

        if ($pages->hasRows()) {
            foreach ($pages->getRows() as $page) {
                if ($page->type === $type) {
                    $out[] = $page;
                }
            }
        }

        /* @var $result static */
        $result = new static();
        $result->setRows($out);

        return $result;
    }

    /**
     * Get node children
     * @return static|null
     */
    public function getChildren()
    {
        if (isset($this->children[$this->id]) === false) {
            $this->children[$this->id] = $this->filterParentId($this->id)->all();
        }

        return $this->children[$this->id];
    }

    public function getParents()
    {
        $out = [];
        if ($this->parent_node_id !== null) {

            /* @var $node self */
            $node = static::instance()->find($this->parent_node_id);

            while ($node !== null) {
                $out[] = $node;
                if ($node->parent_node_id !== null) {
                    $node = static::instance()->find($node->parent_node_id);
                    continue;
                }

                $node = null;
            }
        }

        return $out;
    }

    public function getParent()
    {
        if ($this->parent === null && $this->parent_id !== null) {
            $this->parent = static::instance()->find($this->parent_id);
        }

        return $this->parent;
    }

    public function updateFields()
    {
        if ($this->data !== null) {
            $currentFields = NodeData::instance()->filerNodeId($this->id)->all();
            $cf = [];
            foreach ($currentFields as $field) {
                $cf[strtolower($field->key)] = $field;
            }
            if (count($this->data->getData())) {
                foreach ($this->data->getData() as $key => $value) {
                    if ($value === null) {
                        continue;
                    }

                    if (isset($cf[strtolower($key)]) === true) {
                        if ($cf[$key]->value === $value) {
                            unset($cf[$key]);
                            continue;
                        }

                        $cf[$key]->value = $value;
                        $cf[$key]->key = $key;
                        $cf[$key]->update();
                        unset($cf[$key]);

                    } else {
                        $field = new NodeData();
                        $field->node_id = $this->id;
                        $field->key = $key;
                        $field->value = $value;
                        $field->save();
                    }
                }
            }

            foreach ($cf as $field) {
                $field->delete();
            }
        }
    }

    public function save(array $data = null)
    {
        $this->calculatePath();
        parent::save($data);
        $this->updateFields();
    }

    public function delete()
    {
        // Delete children
        $children = $this->getChildren();
        if ($children !== null && $children->hasRows() === true) {
            /* @var $child static */
            foreach ($children->getRows() as $child) {
                $child->delete();
            }
        }
        NodeData::instance()->clear($this->id);
        parent::delete();
    }

    /**
     * Order by key
     * @param string $key
     * @param string $direction
     * @return static
     */
    public function order($key, $direction = 'DESC')
    {
        if ($this->hasRows() === true) {
            $rows = [];
            foreach ($this->getRows() as $row) {
                $k = isset($row->fields[$key]) ? $row->{$key} : $row->data->$key;
                $k = ((string)$k === 'Tjs=') ? Str::base64Decode($k) : $k;
                $rows[$k] = $row;
            }

            if (strtolower($direction) === 'asc') {
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
     * @return static|string
     */
    public function getFirstOrDefault($default = null)
    {
        if ($this->hasRows() === true) {
            return $this->getRow(0);
        }

        return $default;
    }

    /**
     * Skip number of rows
     * @param int $number
     * @return static
     */
    public function skipResult($number)
    {
        if ($number > 0 && $this->hasRows() === true) {
            $rows = $this->getRows();
            $this->setRows(array_splice($rows, $number));
        }

        return $this;
    }

    /**
     * Limit the output
     * @param int $limit
     * @return static
     */
    public function limitResult($limit)
    {
        $out = [];
        if ($this->hasRows()) {
            foreach ($this->getRows() as $i => $row) {
                if ($i < $limit) {
                    $out[] = $row;
                }
            }
        }
        $this->setRows($out);

        //$this->setNumRow($limit);

        return $this;
    }

    /**
     * Filter elements
     * @param string $key
     * @param string $value
     * @param string $delimiter
     * @return static
     */
    public function filterResult($key, $value, $delimiter = '=')
    {
        $out = [];
        if ($this->hasRows()) {
            foreach ($this->getRows() as $row) {
                $keys = (array)$key;

                if (in_array($row, $out, true) !== false) {
                    continue;
                }

                foreach ($keys as $_key) {
                    $k = array_key_exists($_key, $row->fields) ? $row->{$_key} : $row->data->$_key;
                    $k = (strpos($k, 'Tjs=') === 1) ? Str::base64Encode($k) : $k;

                    if ($delimiter === '>') {
                        if ($k > $value) {
                            $out[] = $row;
                        }
                    } elseif ($delimiter === '<') {
                        if ($k < $value) {
                            $out[] = $row;
                        }
                    } elseif ($delimiter === '>=') {
                        if ($k >= $value) {
                            $out[] = $row;
                        }
                    } elseif ($delimiter === '<=') {
                        if ($k <= $value) {
                            $out[] = $row;
                        }
                    } elseif ($delimiter === '!=') {
                        if ((string)$k !== (string)$value) {
                            $out[] = $row;
                        }
                    } elseif ($delimiter === '*') {
                        if (strtolower($k) === (string)$value || stripos($k, $value) !== false) {
                            $out[] = $row;
                        }
                    } else {
                        if ($k === $value) {
                            $out[] = $row;
                        }
                    }
                }
            }
        }

        //$this->setMaxRows(count($out));
        $this->setRows($out);

        return $this;
    }

    /**
     * Filter by node ids
     * @param array $ids
     * @return static
     */
    public function filterIds(array $ids)
    {
        return $this->whereIn('id', $ids);
    }

    public function filterActive($active)
    {
        return
            $this->where('active', '=', Boolean::parse($active))
                ->where(function (QueryBuilderHandler $q) {
                    $q->whereNull('active_from')
                        ->whereNull('active_to')
                        ->orWhere('active_from', '<=', $q->raw('NOW()'))
                        ->where(function (QueryBuilderHandler $q) {

                            $q->where('active_to', '>=', $q->raw('NOW()'))
                                ->orWhereNull('active_to');

                        });
                });
    }

    public function filterParentId($parentId = null)
    {
        if ($parentId === null) {
            return $this->where(function (QueryBuilderHandler $q) {
                $q->whereNull('parent_id')->orWhereNull('path');
            });
        }

        return $this->where('parent_id', '=', $parentId);
    }

    public function filterPath($path)
    {
        return $this->where('path', 'LIKE', '%' . $path . '%');
    }

    public function filterType($type)
    {
        return $this->where('type', '=', $type);
    }

    public function filterTypes(array $types)
    {
        return $this->whereIn('type', $types);
    }

    /**
     * Sort by custom type
     *
     * @param string $type
     * @param string $direction
     * @return static $this
     * @throws \InvalidArgumentException
     */
    public function sortBy($type, $direction = 'ASC')
    {

        if (in_array($type, static::$sortTypes, true) === false) {
            throw new \InvalidArgumentException('Invalid sort type');
        }

        switch ($type) {

            case static::SORT_ID:
                $type = 'id';
                break;
            case static::SORT_PARENT:
                $type = 'parent_id';
                break;
            case static::SORT_TITLE:
                $type = 'title';
                break;
            case static::SORT_ORDER:
                $type = 'order';
                break;
            case static::SORT_CREATED:
                $type = 'created_at';
                break;
            case static::SORT_UPDATED:
                $type = 'IFNULL(`updated_at`, IFNULL(`active_from`, `created_at`))';
                break;
            case static::SORT_ACTIVE_CREATED:
                $type = 'IFNULL(`active_from`, `created_at`)';
                break;

        }

        return $this->orderBy($type, $direction);
    }

    public function filterQuery($query)
    {
        return $this->where(function (QueryBuilderHandler $q) use ($query) {

            $q->where('title', 'LIKE', '%' . $query . '%')
                ->orWhere('content', 'LIKE', '%' . $query . '%');

        });
    }

    public function filterKey($key, $value, $operator = '=')
    {
        $subQuery = NodeData::instance()
            ->select(['node_id'])
            ->where('node_id', '=', $this->raw('node.`id`'))
            ->where('key', '=', $key)
            ->where('value', $operator, $value);

        return $this->where('id', '=', $this->subQuery($subQuery));
    }

    protected function getDataClass()
    {
        return NodeData::class;
    }

    protected function fetchData()
    {
        return NodeData::instance()->filerNodeId($this->id)->all();
    }
}