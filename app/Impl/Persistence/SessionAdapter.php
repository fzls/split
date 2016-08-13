<?php
/**
 * Created by PhpStorm.
 * User: thd
 * Date: 8/5/2016
 * Time: 3:47 PM
 */

namespace Split\Impl\Persistence;


use Illuminate\Support\Collection;
use Session;
use Config;

use Split\Contracts\Persistence\ArrayLike;

/**
 * Class SessionAdapter
 * @package Split\Impl\Persistence
 */
class SessionAdapter implements ArrayLike {
    /**
     * Namespce of the session for saving user data
     *
     * @var string
     */
    protected $session_namespace;

    /**
     * The collection resides on the Session to store the user data during the session
     *
     * @var Collection
     */
    protected $session;

    /**
     * SessionAdapter constructor.
     */
    public function __construct() {
        $this->session_namespace = \App::make("split_config")->session_namespace;

        /*if Collection not exists in Session, init it*/
        if (!Session::has($this->session_namespace)) {
            Session::put($this->session_namespace, collect([]));
        }
        $this->session = Session::get($this->session_namespace);
    }


    public function delete($key) {
        $this->session->forget($key);
    }

    public function keys() {
        return $this->session->keys();
    }

    public function offsetExists($offset) {
        return $this->session->has($offset);
    }

    public function offsetGet($offset) {
        return $this->session->get($offset);
    }

    public function offsetSet($offset, $value) {
        $this->session->put($offset, $value);
    }

    public function offsetUnset($offset) {
        $this->delete($offset);
    }
}