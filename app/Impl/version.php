<?php
/**
 * Created by PhpStorm.
 * User: thd
 * Date: 8/5/2016
 * Time: 4:55 PM
 */

$MAJOR = env('VERSION_MAJOR');
$MINOR = env('VERSION_MINOR');
$PATCH = env('VERSION_PATCH');

$VERSION = collect([$MAJOR,$MINOR,$PATCH])->implode('.');