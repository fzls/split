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
     * @param string $key
     *
     * @return void
     */
    public function delete($key);


    /**
     * @return Collection
     */
    public function keys();
}
