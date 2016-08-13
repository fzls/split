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

/**
 * Depends on user is logged in or not, use different adapter, by default we use cookie
 * for logged out user, and redis for logged in user
 *
 * Class DualAdapter
 * @package Split\Impl\Persistence
 */
class DualAdapter implements ArrayLike {
    /**
     * User's logged in state
     *
     * @var bool
     */
    protected $logged_in;

    /**
     * The adapter used to save the user's data
     *
     * @var ArrayLike
     */
    protected $adapter;

    /**
     * DualAdapter constructor.
     */
    public function __construct() {
        $this->logged_in = Request::has('user_id');

        if ($this->logged_in) {
            $adapter = \App::make("split_config")->logged_in_adapter;
        } else {
            $adapter = \App::make("split_config")->logged_out_adapter;
        }

        $adapters      = \App::make("split_config")->adapters;
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