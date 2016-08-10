<?php
/**
 * Created by PhpStorm.
 * User: thd
 * Date: 8/5/2016
 * Time: 2:20 PM
 */

namespace Split\Impl\Persistence;


use Illuminate\Support\Collection;
use Request;
use Redis;
use Config;

use Split\Contracts\Persistence\ArrayLike;

class RedisAdapter implements ArrayLike {
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
        $key = Request::get('user_id');/*get from url or request(post)*/
        $namespace = Config::get('split.redis_namespace');

        $this->expire_seconds=Config::get('split.redis_expires');
        $this->redis_key = $namespace . ':' . $key;
        $this->redis = \App::make('split_redis');
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