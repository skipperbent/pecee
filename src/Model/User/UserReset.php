<?php
namespace Pecee\Model\User;

use Pecee\Model\Model;

class UserReset extends Model
{
    const IDENTIFIER_KEY = 'user_id';

    protected $table = 'user_reset';

    protected $columns = [
        'id',
        'key',
    ];

    public function __construct($userId = null)
    {

        parent::__construct();

        $this->columns = array_merge($this->columns, [static::IDENTIFIER_KEY]);

        $this->{static::IDENTIFIER_KEY} = $userId;
        $this->key = md5(uniqid());
    }

    public function clean()
    {
        $this->where(static::IDENTIFIER_KEY, '=', $this->{static::IDENTIFIER_KEY})->delete();
    }

    public function save(array $data = null)
    {
        $this->clean();
        parent::save($data);
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

            return $reset->{static::IDENTIFIER_KEY};
        }

        return false;
    }

    public function getIdentifier()
    {
        return $this->{static::IDENTIFIER_KEY};
    }

    public function getKey()
    {
        return $this->key;
    }

}