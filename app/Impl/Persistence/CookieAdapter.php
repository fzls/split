<?php
/**
 * Created by PhpStorm.
 * User: thd
 * Date: 8/4/2016
 * Time: 11:51 AM
 */

namespace Split\Impl\Persistence;


use Carbon\Carbon;
use Illuminate\Support\Collection;
use Cookie;
use Config;

use Split\Contracts\Persistence\ArrayLike;

/**
 * Class CookieAdapter
 * @package Split\Impl\Persistence
 */
class CookieAdapter implements ArrayLike {
    /**
     * the namespace of the cookie we save in users's pc, default is split
     *
     * @var string
     */
    protected $cookie_namespace;
    /**
     * the default time of the Cookie expires time(in minute)
     *
     * @var int
     */
    protected $expires;
    /**
     * to keep synced with changed made to the user's cookie
     * when we need to change the cookie, we change the buffer first, then save that to user
     * cause we only use @cookie_namespace to save the cookie
     *
     * @var Collection
     */
    protected $buffer;

    /**
     * CookieAdapter constructor.
     */
    public function __construct() {
        $this->cookie_namespace = Config::get('split.cookie_namespace');
        $this->expires = Config::get('split.cookie_expires');
        $this->buffer = $this->hash();
    }

    public function delete($key) {
        $this->set_cookie($this->buffer->forget($key));
    }

    public function keys() {
        return $this->buffer->keys();
    }

    public function offsetExists($offset) {
        return $this->buffer->has($offset);
    }

    public function offsetGet($offset) {
        return $this->buffer->get($offset);
    }

    public function offsetSet($offset, $value) {
        $this->set_cookie($this->buffer->put($offset, $value));
    }

    public function offsetUnset($offset) {
        $this->delete($offset);
    }

    /**
     * deserialize data from user cookie into buffer for modification
     * @return Collection
     */
    public function hash() {
        if ($this->buffer){
            return $this->buffer;
        }
        if (Cookie::has($this->cookie_namespace)) {
            $res = json_decode(Cookie::get($this->cookie_namespace));
            if (json_last_error() == JSON_ERROR_NONE)
                return collect($res);
        }

        return collect([]);
    }

    public function set_cookie($value) {
        Cookie::queue(Cookie::make($this->cookie_namespace, json_encode($value), $this->expires));
    }

}