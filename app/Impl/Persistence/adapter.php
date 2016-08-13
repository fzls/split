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

use Illuminate\Support\Facades\App;
use Split\Contracts\Persistence\ArrayLike;
use Split\Impl\InvalidPersistenceAdapterError;

/**
 * Fetch adapter for user
 *
 * @return ArrayLike
 * @throws InvalidPersistenceAdapterError
 */
function adapter() {
    $adapter = \App::make('split_config')->persistence;
    if (is_null($adapter)) {
        require_once app_path('Impl/exceptions.php');/*TODO: make it autoloaded*/
        $given     = \App::make("split_config")->adapter;
        $available = implode(', ', array_keys(\App::make("split_config")->adapters));
        throw new InvalidPersistenceAdapterError("Wrong adapter name:[$given] given, only support $available");
    }

    return new $adapter();
}