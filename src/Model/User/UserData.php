<?php

namespace Pecee\Model\User;

use Pecee\Model\Model;
use Pecee\Model\ModelUser;

class UserData extends Model
{
    protected bool $timestamps = false;

    protected array $columns = [
        'id',
        'key',
        'value',
    ];

    protected string $table = 'user_data';

    public function __construct($userId = null, $key = null, $value = null)
    {

        parent::__construct();

        $this->columns = array_merge($this->columns, [
            ModelUser::instance()->getDataPrimary(),
        ]);

        $this->user_id = $userId;
        $this->key = $key;
        $this->value = $value;
    }

    public function exists(): bool
    {
        if ($this->{$this->primaryKey} === null) {
            return false;
        }

        $dataPrimaryKey = ModelUser::instance()->getDataPrimary();

        return ($this->where('key', '=', $this->key)->where($dataPrimaryKey, '=', $this->{$dataPrimaryKey})->first() !== null);
    }

    public static function destroyByIdentifier($identifierId)
    {
        return static::instance()->where(ModelUser::instance()->getDataPrimary(), '=', $identifierId)->delete();
    }

    public static function getByIdentifier($identifierId)
    {
        return static::instance()->where(ModelUser::instance()->getDataPrimary(), '=', $identifierId)->all();
    }

    public function filterIdentifier($identifier)
    {
        return $this->where(ModelUser::instance()->getDataPrimary(), '=', $identifier);
    }

    public function filterIdentifiers(array $identifiers)
    {
        return $this->whereIn(ModelUser::instance()->getDataPrimary(), $identifiers);
    }
}