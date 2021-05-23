<?php
namespace Pecee\Model\User;

use Pecee\Guid;
use Pecee\Model\Model;
use Pecee\Model\ModelUser;

class UserReset extends Model
{
    protected string $table = 'user_reset';

    protected array $columns = [
        'id',
        'key',
    ];

    public function __construct($userId = null)
    {

        parent::__construct();

        $dataPrimaryKey = ModelUser::instance()->getDataPrimary();

        $this->columns = array_merge($this->columns, [$dataPrimaryKey]);
        $this->{$primaryKey} = $userId;
        $this->key = Guid::create();
    }

    public function clean()
    {
        $dataPrimaryKey = ModelUser::instance()->getDataPrimary();
        $this->where($dataPrimaryKey, '=', $this->{$dataPrimaryKey})->delete();
    }

    public function save(array $data = []): self
    {
        $this->clean();
        return parent::save($data);
    }

    public static function getByKey($key)
    {
        $model = new static();

        return $model->where('key', '=', $key)->first();
    }

    public static function confirm($key)
    {
        $reset = static::getByKey($key);

        if ($reset !== null) {
            $reset->clean();
            $reset->delete();

            return $reset->{ModelUser::instance()->getDataPrimary()};
        }

        return false;
    }

    public function getIdentifier()
    {
        return $this->{ModelUser::instance()->getDataPrimary()};
    }

    public function getKey()
    {
        return $this->key;
    }

}