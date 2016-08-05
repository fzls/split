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
        /*fixme : add context for customize*/
        $this->cookie_namespace = 'split';
        $this->expires = env('COOKIE_ADAPTER_EXPIRES',60*365);/*one year from now*/
        $this->buffer = $this->hash();
    }

    public function delete($key) {
        $this->buffer->forget($key);
        $this->set_cookie($this->buffer);
    }

    public function keys() {
        $this->buffer->keys();
    }

    public function offsetExists($offset) {
        return $this->buffer->has($offset);
    }

    public function offsetGet($offset) {
        return $this->buffer->get($offset);
    }

    public function offsetSet($offset, $value) {
        $this->buffer->put($offset, $value);
        $this->set_cookie($this->buffer);
    }

    public function offsetUnset($offset) {
        $this->delete($offset);
    }

    /**
     * deserilize data from user cookie into buffer for alter
     * @return Collection
     */
    public function hash() {
        if(isset($this->buffer) && !is_null($this->buffer)){
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