<?php
/**
 * Created by PhpStorm.
 * User: thd
 * Date: 8/5/2016
 * Time: 5:15 PM
 */

namespace Split\Impl\Persistence;

use Illuminate\Support\Collection;
use Split\Contracts\Persistence\ArrayLike;

/**
 * @return ArrayLike
 */
function adapter() {
    static $ADAPTERS = [
        'cookie'  => 'Split\Impl\Persistence\CookieAdapter',
        'session' => 'Split\Impl\Persistence\SessionAdapter',
        'redis'   => 'Split\Impl\Persistence\RedisAdapter',
        'dual'    => 'Split\Impl\Persistence\DualAdapter',
    ];
    $adapter = env('ADAPTER');
    if (array_key_exists($adapter, $ADAPTERS)) {
        return new $ADAPTERS[$adapter]();
    }

    return new $ADAPTERS['dual']();
}