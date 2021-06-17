<?php

namespace Pecee\Model\User;

use Pecee\Guid;
use Pecee\Model\ModelMeta\ModelMetaField;

/**
 * Class UserData
 * @package Pecee\Model\User
 * @property int $id
 * @property int $user_id
 * @property string $key
 * @property string $value
 */
class UserData extends ModelMetaField
{
    protected bool $fixedIdentifier = true;
    protected bool $timestamps = false;

    protected array $columns = [
        'id',
        'user_id',
        'key',
        'value',
    ];

    protected string $table = 'user_data';

    public function __construct($userId = null, $key = null, $value = null)
    {
        parent::__construct();

        $this->id = Guid::create();
        $this->user_id = $userId;
        $this->key = $key;
        $this->value = $value;
    }

    public function getDataKeyName(): string
    {
        return 'key';
    }

    public function getDataValueName(): string
    {
        return 'value';
    }

    public function exists(): bool
    {
        if ($this->{$this->primaryKey} === null) {
            return false;
        }

        return ($this->select(['id'])->where('key', '=', $this->key)->where($this->getDataKeyName(), '=', $this->{$this->getDataKeyName()})->count() > 0);
    }

    public static function destroyByIdentifier($identifierId)
    {
        return static::instance()->where(static::instance()->getDataKeyName(), '=', $identifierId)->delete();
    }

    public static function getByIdentifier($identifierId)
    {
        return static::instance()->where(static::instance()->getDataKeyName(), '=', $identifierId)->all();
    }

    public function filterUserId(int $userId)
    {
        return $this->where('user_id', '=', $userId);
    }

    public function filterIdentifiers(array $identifiers)
    {
        return $this->whereIn($this->getDataKeyName(), $identifiers);
    }
}