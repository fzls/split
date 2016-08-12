<?php
/**
 * Created by PhpStorm.
 * User: thd
 * Date: 8/10/2016
 * Time: 10:53 AM
 */

return [
    /*TODO: move all the config into Configuration*/
    'algorithms' => [
        'weighted' => Split\Impl\Algorithms\WeightedSample::class,
        'whiplash' => Split\Impl\Algorithms\Whiplash::class,
    ],
    'algorithm'  => env('SPLIT_ALGORITHM', 'weighted'),
    /*
    |--------------------------------------------------------------------------
    | Available User Store
    |--------------------------------------------------------------------------
    */
    'adapters'   => [
        'cookie'  => Split\Impl\Persistence\CookieAdapter::class,
        'session' => Split\Impl\Persistence\SessionAdapter::class,
        'redis'   => Split\Impl\Persistence\RedisAdapter::class,
        'dual'    => Split\Impl\Persistence\DualAdapter::class,
    ],
    /*
    |--------------------------------------------------------------------------
    | Default User Store
    |--------------------------------------------------------------------------
    |
    | use that to store the user's info
    |
    | Supported: "cookie", "session", "redis", "dual"
    |
    */
    'adapter'    => env('SPLIT_ADAPTER', 'dual'),

    'cookie_namespace' => env('SPLIT_NAMESPACE', 'split'),
    'cookie_expires'   => env('SPLIT_COOKIE_ADAPTER_EXPIRES', 60 * 24 * 365/*mins*/),

    'session_namespace' => env('SPLIT_SESSION_NAMESPACE', 'split'),

    'redis_namespace' => env('SPLIT_REDIS_NAMESPACE', 'persistence'),
    'redis_expires'   => env('SPLIT_REDIS_EXPIRES_SECONDS', 86400 * 30/*secs*/),

    'logged_in_adapter'                    => env('SPLIT_DUAL_LOGGED_IN_ADAPTER', 'redis'),
    'logged_out_adapter'                   => env('SPLIT_DUAL_LOGGED_OUT_ADAPTER', 'cookie'),


    /*
    |--------------------------------------------------------------------------
    | Configuration related settings
    |--------------------------------------------------------------------------
    |
    | use that to store the user's info
    |
    | Supported: "cookie", "session", "redis", "dual"
    |
    */
    'ignore_ip_addresses'                  => env('SPLIT_IGNORE_IP_ADDRESSES'),
    'db_failover'                          => env('SPLIT_DB_FAILOVER', false),
    'db_failover_allow_parameter_override' => env('SPLIT_DB_FAILOVER_ALLOW_PARAMETER_OVERRIDE', false),
    'allow_multiple_experiments'           => env('SPLIT_ALLOW_MULTIPLE_EXPERIMENTS', false),
    'enabled'                              => env('SPLIT_ENABLED', true),
    'beta_probability_simulations'         => env('SPLIT_BETA_PROBABILITY_SIMULATIONS', 10000),
    'version'                              => collect([
                                                          env('SPLIT_VERSION_MAJOR'),
                                                          env('SPLIT_VERSION_MINOR'),
                                                          env('SPLIT_VERSION_PATCH'),
                                                      ])->implode('.'),
];