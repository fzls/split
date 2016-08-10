<?php
/**
 * Created by PhpStorm.
 * User: thd
 * Date: 8/4/2016
 * Time: 11:58 AM
 */
namespace Split\Contracts\Persistence;

use Illuminate\Support\Collection;

interface ArrayLike extends \ArrayAccess
{

    /**
     * delete a key from the container.
     * 
     * @param string $key
     *
     * @return void
     */
    public function delete($key);


    /**
     * return the keys of the container.
     * 
     * @return Collection
     */
    public function keys();
}
