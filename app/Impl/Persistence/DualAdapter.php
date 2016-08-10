<?php
/**
 * Created by PhpStorm.
 * User: thd
 * Date: 8/5/2016
 * Time: 4:13 PM
 */

namespace Split\Impl\Persistence;


use Illuminate\Support\Collection;
use Request;
use Config;

use Split\Contracts\Persistence\ArrayLike;

class DualAdapter implements ArrayLike {
    protected $logged_in;

    /**
     * @var ArrayLike
     */
    protected $adapter;

    /**
     * DualAdapter constructor.
     */
    public function __construct() {
        $this->logged_in = Request::has('user_id');
        if ($this->logged_in) {
            $adapter = Config::get('split.logged_in_adapter');
        } else {
            $adapter = Config::get('split.logged_out_adapter');
        }
        $adapters = Config::get('split.adapters');
        $this->adapter = new $adapters[$adapter]();
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
        $this->delete($offset);
    }


}