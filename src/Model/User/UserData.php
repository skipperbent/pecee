<?php
namespace Pecee\Model\User;

use Pecee\Model\Model;
use Pecee\Model\ModelUser;

class UserData extends Model
{
    protected $timestamps = false;

    protected $columns = [
        'id',
        'key',
        'value',
    ];

    protected $table = 'user_data';

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

    public function exists()
    {
        if ($this->{$this->primary} === null) {
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
}