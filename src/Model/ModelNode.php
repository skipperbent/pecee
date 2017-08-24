<?php

namespace Pecee\Model;

use Carbon\Carbon;
use Pecee\Boolean;
use Pecee\Model\Node\NodeData;
use Pecee\Pixie\QueryBuilder\QueryBuilderHandler;

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

    protected $dataPrimary = 'node_id';
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

    public function getId()
    {
        return $this->id;
    }

    public function getParentId()
    {
        return $this->parent_id;
    }

    public function setParentId($id)
    {
        $this->parentId = $id;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function setPath($path)
    {
        $this->path = $path;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setContent($content)
    {
        $this->content = $content;
    }

    public function getActiveFrom()
    {
        if ($this->active_from !== null) {
            return Carbon::parse($this->active_from);
        }

        return null;
    }

    public function setActiveFrom(Carbon $date)
    {
        $this->active_from = $date->toDateTimeString();
    }

    public function getActiveTo()
    {
        if ($this->active_to !== null) {
            return Carbon::parse($this->active_to);
        }

        return null;
    }

    public function setActiveTo(Carbon $date)
    {
        $this->active_to = $date->toDateTimeString();
    }

    public function getActive()
    {
        return ((int)$this->active === 1);
    }

    public function setActive($active)
    {
        $this->active = $active;
    }

    public function getLevel()
    {
        return $this->level;
    }

    public function setLevel($level)
    {
        $this->level = $level;
    }

    public function getOrder()
    {
        return (int)$this->order;
    }

    public function setOrder($order)
    {
        $this->order = $order;
    }

    public function getUpdatedAt()
    {
        if ($this->updated_at !== null) {
            return Carbon::parse($this->updated_at);
        }

        return null;
    }

    public function setUpdatedAt(Carbon $date)
    {
        $this->updated_at = $date->toDateTimeString();
    }

    public function getCreatedAt()
    {
        return Carbon::parse($this->created_at);
    }

    public function setCreatedAt(Carbon $date)
    {
        $this->created_at = $date->toDateTimeString();
    }

    public function isActive()
    {
        if ($this->getActive() === false) {
            return false;
        }

        if ($this->getActiveFrom() !== null && $this->getActiveFrom()->isFuture() === true) {
            return false;
        }

        if ($this->getActiveTo() !== null && $this->getActiveTo()->isPast() === true) {
            return false;
        }

        return true;
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
                if ($parent !== null) {
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
            $pages = $this->filterPath($this->id . '%')->all();
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
        if ($this->parent_id !== null) {
            return static::instance()->filterPath($this->parent_id . '>%')->orderBy('path');
        }

        return null;
    }

    public function getParent()
    {
        if ($this->parent === null && $this->parent_id !== null) {
            $this->parent = static::instance()->find($this->parent_id);
        }

        return $this->parent;
    }

    public function save(array $data = null)
    {
        $this->calculatePath();
        parent::save($data);

        return $this;
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
        if ($parentId === null || $parentId === 0) {
            return $this->where(function (QueryBuilderHandler $q) {
                $q->whereNull('parent_id')->orWhereNull('path');
            });
        }

        return $this->where('parent_id', '=', $parentId);
    }

    public function filterPath($path)
    {
        return $this->where('path', 'LIKE', $path);
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
    public function order($type, $direction = 'ASC')
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