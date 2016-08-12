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
use Split\Impl\InvalidPersistenceAdapterError;

/**
 * fetch adapter for user
 *
 * @return ArrayLike
 * @throws InvalidPersistenceAdapterError
 */
function adapter() {
    $adapter = \App::make('split_config')->persistence;
    if (is_null($adapter)) {
        require_once __DIR__ . '/../exceptions.php';
        $given     = Config::get('split.adapter');
        $available = implode(', ', array_keys(Config::get('split.adapters')));
        throw new InvalidPersistenceAdapterError("Wrong adapter name:[$given] given, only support $available");
    }

    return new $adapter();
}