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

class SessionAdapter implements ArrayLike {
    protected $session_namespace;

    /**
     * @var Collection
     */
    protected $session;

    /**
     * SessionAdapter constructor.
     */
    public function __construct() {
        $this->session_namespace = Config::get('split.session_namespace');
        
        if (!Session::has($this->session_namespace)) {
            Session::put($this->session_namespace, collect([]));
        }
        $this->session = Session::get($this->session_namespace);
    }


    public function delete($key) {
        $this->session->forget($key);
    }

    public function keys() {
        $this->session->keys();
    }

    public function offsetExists($offset) {
        $this->session->has($offset);
    }

    public function offsetGet($offset) {
        $this->session->get($offset);
    }

    public function offsetSet($offset, $value) {
        $this->session->put($offset, $value);
    }

    public function offsetUnset($offset) {
        $this->delete($offset);
    }
}