<?php
/**
 * Created by PhpStorm.
 * User: thd
 * Date: 8/5/2016
 * Time: 2:20 PM
 */

namespace Split\Impl\Persistence;


use Illuminate\Support\Collection;
use Auth;
use Redis;
use Split\Contracts\Persistence\ArrayLike;

class RedisAdapter implements ArrayLike {
    /*TODO test it*/
    protected $expire_seconds;
    /**
     *  scheme: namespace:{user_id}
     *
     * @var string
     */
    protected $redis_key;

    /**
     * @var \Redis $redis
     */
    protected $redis;

    /**
     * RedisAdapter constructor.
     */
    public function __construct() {
        $key = Auth::user()->id;
        $namespace = env('REDIS_ADAPTER_USER_NAMESPACE', 'persistence');

        $this->expire_seconds=env('REDIS_ADAPTER_EXPIRE_SECONDS');
        $this->redis_key = $namespace . ':' . $key;
        $this->redis = Redis::connection();
    }


    public function delete($field) {
        $this->redis->hDel($this->redis_key, $field);
    }

    public function keys() {
        $this->redis->hKeys($this->redis_key);
    }

    public function offsetExists($field) {
        return $this->redis->hGet($this->redis_key, $field) != False;
    }

    public function offsetGet($field) {
        return $this->redis->hGet($this->redis_key, $field);
    }

    public function offsetSet($field, $value) {
        $this->redis->hSet($this->redis_key, $field, $value);
        if ($this->expire_seconds) {
            $this->redis->expire($this->redis_key, $this->expire_seconds);
        }
    }

    public function offsetUnset($offset) {
        $this->delete($offset);
    }
}