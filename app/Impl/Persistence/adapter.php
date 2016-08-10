<?php
/**
 * Created by PhpStorm.
 * User: thd
 * Date: 8/5/2016
 * Time: 5:15 PM
 */

namespace Split\Impl\Persistence;

use Illuminate\Support\Collection;
use Config;

use Split\Contracts\Persistence\ArrayLike;

/**
 * fetch adapter for user
 *
 * @return ArrayLike
 */
function adapter() {
    $adapter = \App::make('split_config')->persistence;
    return new $adapter();
}