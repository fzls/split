<?php
/**
 * Created by PhpStorm.
 * User: thd
 * Date: 8/4/2016
 * Time: 11:51 AM
 */

namespace Split\Impl\Persistence;


use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cookie;
use Split\Contracts\Persistence\ArrayLike;

class CookieAdapter implements ArrayLike{


    /**
     * CookieAdapter constructor.
     */
    public function __construct() {
        
    }

    public function delete($key) {
        // TODO: Implement delete() method.
    }

    public function keys() {
        // TODO: Implement keys() method.
    }

    public function offsetExists($offset) {
        // TODO: Implement offsetExists() method.
    }

    public function offsetGet($offset) {
        // TODO: Implement offsetGet() method.
    }

    public function offsetSet($offset, $value) {
        // TODO: Implement offsetSet() method.
    }

    public function offsetUnset($offset) {
        // TODO: Implement offsetUnset() method.
    }

}