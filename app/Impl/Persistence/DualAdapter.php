<?php
/**
 * Created by PhpStorm.
 * User: thd
 * Date: 8/5/2016
 * Time: 4:13 PM
 */

namespace Split\Impl\Persistence;


use Illuminate\Support\Collection;
use Split\Contracts\Persistence\ArrayLike;
use Auth;

class DualAdapter implements ArrayLike {
    protected $logged_in;

    /**
     * @var ArrayLike
     */
    protected $adapter;

    /**
     * DualAdapter constructor.
     *
     * @param $logged_in_adapter
     * @param $logged_out_adapter
     */
    public function __construct() {
        $this->logged_in = Auth::check();
        if ($this->logged_in) {
            $this->adapter = env('DUAL_ADAPTER_LOGGED_IN_ADAPTER');
        } else {
            $this->adapter = env('DUAL_ADAPTER_LOGGED_OUT_ADAPTER');
        }
        switch ($this->adapter) {
            case 'Redis':
                $this->adapter = new RedisAdapter();
                break;
            case 'Cookie':
                $this->adapter = new CookieAdapter();
                break;
            case 'session':
                $this->adapter = new SessionAdapter();
                break;
            default:
                if ($this->logged_in)
                    $this->adapter = new RedisAdapter();
                else
                    $this->adapter = new CookieAdapter();
        }
    }

    public function delete($key) {
        $this->adapter->delete($key);
    }

    public function keys() {
        return $this->adapter->keys();
    }

    public function offsetExists($offset) {
        return $this->adapter->offsetExists($offset);
    }

    public function offsetGet($offset) {
        return $this->adapter->offsetGet($offset);
    }

    public function offsetSet($offset, $value) {
        $this->adapter->offsetSet($offset, $value);
    }

    public function offsetUnset($offset) {
        $this->offsetUnset($offset);
    }


}