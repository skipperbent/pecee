<?php

namespace Pecee\Model;

use Carbon\Carbon;
use InvalidArgumentException;
use Pecee\Boolean;
use Pecee\Guid;
use Pecee\Model\Collections\ModelCollection;
use Pecee\Model\Exceptions\ModelException;
use Pecee\Model\Node\NodeData;
use Pecee\Model\Relation\HasOne;
use Pecee\Pixie\QueryBuilder\QueryBuilderHandler;

/**
 * @property string $id
 * @property string $parent_id
 * @property int|null $user_id
 * @property string $path
 * @property string $type
 * @property string $title
 * @property string $content
 * @property Carbon|null $active_from
 * @property Carbon|null $active_to
 * @property int $level
 * @property int $order
 * @property bool $active
 * @property bool $deleted
 * @property Carbon|null $updated_at
 * @property Carbon|null $created_at
 */
class ModelNode extends ModelData
{
    public static array $operators = ['=', '!=', '<', '>', '<=', '>=', 'between', 'is not', 'is', 'like', 'find'];

    public const SORT_ID = 'id';
    public const SORT_PARENT = 'parent';
    public const SORT_TITLE = 'title';
    public const SORT_UPDATED = 'updated';
    public const SORT_CREATED = 'created';
    public const SORT_ACTIVE_CREATED = 'active_created';
    public const SORT_ORDER = 'order';

    public static array $sortTypes = [
        self::SORT_ID,
        self::SORT_PARENT,
        self::SORT_TITLE,
        self::SORT_UPDATED,
        self::SORT_CREATED,
        self::SORT_ACTIVE_CREATED,
        self::SORT_ORDER,
    ];

    protected ModelCollection $children;
    protected string $defaultType = '';
    protected string $dataPrimary = 'node_id';
    protected string $table = 'node';
    protected array $columns = [
        'id',
        'parent_id',
        'user_id',
        'path',
        'type',
        'title',
        'content',
        'active_from',
        'active_to',
        'level',
        'order',
        'active',
        'deleted',
        'updated_at',
        'created_at',
    ];
    protected bool $mergeData = false;
    protected bool $fixedIdentifier = true;

    public function __construct()
    {
        parent::__construct();
        $this->id = Guid::create();
        $this->path = '0';
        $this->active = false;
        $this->deleted = false;

        if ($this->defaultType !== '') {
            $this->type = $this->defaultType;
            $this->where('type', '=', $this->defaultType);
        }
    }

    public function delete()
    {
        // Delete children

        /* @var $child static */
        foreach ($this->getChildren()->all() as $child) {
            $child->delete();
        }

        return parent::delete();
    }

    public function getUserId(): ?string
    {
        return $this->user_id;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getParentId(): ?string
    {
        return $this->parent_id;
    }

    public function setParentId(?string $parentId): self
    {
        $this->parent_id = $parentId;

        return $this;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getActiveFrom(): ?Carbon
    {
        if ($this->active_from !== null) {
            return Carbon::parse($this->active_from);
        }

        return null;
    }

    public function setActiveFrom(Carbon $date): self
    {
        $this->active_from = $date->toDateTimeString();

        return $this;
    }

    public function getActiveTo(): ?Carbon
    {
        if ($this->active_to !== null) {
            return Carbon::parse($this->active_to);
        }

        return null;
    }

    public function setActiveTo(Carbon $date): self
    {
        $this->active_to = $date->toDateTimeString();

        return $this;
    }

    public function getActive(): bool
    {
        return ((int)$this->active === 1);
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function setLevel(int $level): self
    {
        $this->level = $level;

        return $this;
    }

    public function getOrder(): int
    {
        return (int)$this->order;
    }

    public function setOrder($order): self
    {
        $this->order = $order;

        return $this;
    }

    public function getUpdatedAt(): ?Carbon
    {
        if ($this->updated_at !== null) {
            return Carbon::parse($this->updated_at);
        }

        return null;
    }

    /**
     * @param Carbon $date
     * @return static
     */
    public function setUpdatedAt(Carbon $date): self
    {
        $this->updated_at = $date->toDateTimeString();

        return $this;
    }

    public function getCreatedAt(): Carbon
    {
        return Carbon::parse($this->created_at, app()->getTimezone());
    }

    /**
     * @param Carbon $date
     * @return static
     */
    public function setCreatedAt(Carbon $date): self
    {
        $this->created_at = $date->toDateTimeString();

        return $this;
    }

    public function isActive(): bool
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

    public function calculatePath(): void
    {
        $parent = ModelNode::instance()->select(['id', 'path'])->find($this->parent_id);
        $this->path = ($parent !== null) ? $parent->getPath() . '>' . $parent->id : '0';
        $this->level = count(explode('>', $this->path));
    }

    /**
     * Get children of type
     * @param string $type
     * @param bool $recursive
     * @return static
     * @throws \Pecee\Pixie\Exception
     */
    public function getChildrenOfType(string $type, bool $recursive = true): self
    {
        $out = [];
        if ($recursive === true) {
            $pages = $this->filterPath($this->id . '%')->all();
        } else {
            $pages = $this->filterParentId($this->id)->all();
        }

        foreach ($pages as $page) {
            if ($page->type === $type) {
                $out[] = $page;
            }
        }

        $result = new static();
        $result->setRows($out);

        return $result;
    }

    /**
     * Get node children
     */
    public function getChildren(): self
    {
        return static::instance()->filterParentId($this->id);
    }

    public function getParents(): self
    {
        $parentIds = explode('>', $this->path);

        return static::instance()->filterIds($parentIds)->orderBy(['path', 'order']);
    }

    public function parent(): HasOne
    {
        return $this->hasOne(static::class, 'id', 'parent_id');
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    /**
     * @param array|null $data
     * @return static
     * @throws ModelException
     * @throws \Pecee\Pixie\Exception
     */
    public function save(array $data = []): self
    {
        $this->mergeData($data);
        $this->updateOrder();

        if ($this->isNew() === true) {
            $this->calculatePath();
        }

        return parent::save($data);
    }

    /**
     * Filter by node ids
     * @param array $ids
     * @return static
     */
    public function filterIds(array $ids): self
    {
        if (count($ids) === 0) {
            return $this;
        }

        return $this->whereIn('id', $ids);
    }

    /**
     * @param bool $active
     * @return static
     */
    public function filterActive(bool $active = true): self
    {
        return
            $this->where('active', '=', Boolean::parse($active))
                ->where(static function (QueryBuilderHandler $q) {
                    $q->whereNull('active_from')
                        ->whereNull('active_to')
                        ->orWhere('active_from', '<=', $q->raw('NOW()'))
                        ->where(static function (QueryBuilderHandler $q) {

                            $q->where('active_to', '>=', $q->raw('NOW()'))
                                ->orWhereNull('active_to');

                        });
                });
    }

    /**
     * @param string|null $parentId
     * @return static
     */
    public function filterParentId(?string $parentId = null): self
    {
        if ($parentId === null || (string)$parentId === '0') {
            return $this->where(static function (QueryBuilderHandler $q) {
                $q->whereNull('parent_id')->orWhereNull('path');
            });
        }

        return $this->where('parent_id', '=', $parentId);
    }

    public function filterParentIds(array $parentIds): self
    {
        return $this->whereIn('parent_id', $parentIds);
    }

    public function filterPath($path): self
    {
        return $this->where('path', 'LIKE', $path);
    }

    /**
     * @param string $type
     * @return static
     */
    public function filterType(string $type): self
    {
        return $this->where('type', '=', $type);
    }

    /**
     * @param array $types
     * @return static
     */
    public function filterTypes(array $types): self
    {
        return $this->whereIn('type', $types);
    }

    /**
     * Sort by custom type
     *
     * @param string $type
     * @param string $direction
     * @return static $this
     * @throws InvalidArgumentException
     */
    public function order(string $type, string $direction = 'ASC'): self
    {

        if (in_array($type, static::$sortTypes, true) === false) {
            throw new InvalidArgumentException('Invalid sort type');
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

    public function filterQuery($query): self
    {
        return $this->where(function (QueryBuilderHandler $q) use ($query) {

            $q->where('title', 'LIKE', '%' . $query . '%')
                ->orWhere('content', 'LIKE', '%' . $query . '%');

        });
    }

    /**
     * Filter by key
     *
     * @param string $key
     * @param string|null $value
     * @param string $operator
     * @return static
     * @throws \Pecee\Pixie\Exception
     */
    public function filterKey(string $key, ?string $value = null, string $operator = '='): self
    {
        if (in_array(strtolower($operator), static::$operators, true) === false) {
            throw new ModelException(sprintf('Invalid operator "%s". Must be one of the following type: %s.', $operator, implode(', ', static::$operators)));
        }

        if (strtolower($operator) === 'find') {
            $value = "%$value%";
            $operator = 'LIKE';
        }

        $keyOperator = '=';
        if (strpos($key, '[') > -1) {

            // Search all keys on key[]
            if ($key[strlen($key) - 1] === '[') {
                $key .= '%';
                $keyOperator = 'LIKE';
            } else {
                $key .= ']';
            }
        }

        $table = $this->getQuery()->getAlias() ?? $this->getQuery()->getTable();
        $subQuery = $this->subQuery(NodeData::instance()
            ->select(['data.node_id'])
            ->alias('data')
            ->where('data.node_id', '=', $this->raw("`$table`.`id`"))
            ->where('data.key', $keyOperator, $key)
            ->where('data.value', $operator, $value)
            ->limit(1));

        return $this->where('id', '=', $subQuery);
    }

    /**
     * Filter by multiple key values
     *
     * @param string $key
     * @param array $values
     * @return static
     * @throws \Pecee\Pixie\Exception
     */
    public function filterKeys(string $key, array $values): self
    {
        $table = $this->getQuery()->getAlias() ?? $this->getQuery()->getTable();
        $subQuery = $this->subQuery(NodeData::instance()
            ->select(['data.node_id'])
            ->alias('data')
            ->where('data.node_id', '=', $this->raw("`$table`.`id`"))
            ->where('data.key', '=', $key)
            ->whereIn('data.value', $values));

        return $this->whereIn('id', $subQuery);
    }

    public function updateOrder(): void
    {
        // Ignore if order is already set
        if ($this->order !== null) {
            return;
        }

        $order = 0;
        if ($this->isNew() === true && $this->parent_id !== null) {
            // Starts with 0 so will be automaticially incremented
            $order = static::instance()
                ->select(['id'])
                ->filterParentId($this->parent_id)
                ->count('id');
        }

        $this->setOrder($order);
    }

    protected function getDataClass(): string
    {
        return NodeData::class;
    }

    protected function fetchData(): \IteratorAggregate
    {
        return NodeData::instance()->filterNodeId($this->id)->all();
    }

}