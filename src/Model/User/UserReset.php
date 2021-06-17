<?php
namespace Pecee\Model\User;

use Pecee\Guid;
use Pecee\Model\Model;

class UserReset extends Model
{
    protected bool $fixedIdentifier = true;
    protected string $table = 'user_reset';

    protected array $columns = [
        'id',
        'user_id',
        'key',
    ];

    public function __construct(int $userId = null)
    {
        parent::__construct();

        $this->id = Guid::create();
        $this->{$primaryKey} = $userId;
        $this->key = Guid::create();
    }

    public function clean(): void
    {
        $dataPrimaryKey = UserData::instance()->getDataKeyName();
        $this->where($dataPrimaryKey, '=', $this->{$dataPrimaryKey})->delete();
    }

    public function save(array $data = []): self
    {
        $this->clean();
        return parent::save($data);
    }

    public static function getByKey(string $key): self
    {
        return (new static)->where('key', '=', $key)->first();
    }

    public static function confirm(string $key): bool
    {
        $reset = static::getByKey($key);

        if ($reset !== null) {
            $reset->clean();
            $reset->delete();

            return $reset->{UserData::instance()->getDataKeyName()};
        }

        return false;
    }

    public function getIdentifier(): string
    {
        return $this->{UserData::instance()->getDataKeyName()};
    }

    public function getKey(): string
    {
        return $this->key;
    }

}