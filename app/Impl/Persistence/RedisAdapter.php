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

/**
 * Class RedisAdapter
 * @package Split\Impl\Persistence
 */
class RedisAdapter implements ArrayLike {
    /**
     * The redis expire time(in seconds), if not set, will not be auto deleted by default.
     *
     * @var int
     */
    protected $expire_seconds;

    /**
     * The key used to save the user data.
     * scheme: namespace:{user_id}
     * by default, namespace is persistence
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
        $key                  = Request::get('user_id');/*get from url or request(post)*/
        $namespace            = \App::make("split_config")->redis_namespace;
        $this->redis_key      = $namespace . ':' . $key;
        $this->expire_seconds = \App::make("split_config")->redis_expires;
        $this->redis          = \App::make('split_redis');
    }


    public function delete($field) {
        $this->redis->hDel($this->redis_key, $field);
    }

    public function keys() {
        return collect($this->redis->hKeys($this->redis_key));
    }

    public function offsetExists($field) {
        return $this->redis->hGet($this->redis_key, $field) != false;
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