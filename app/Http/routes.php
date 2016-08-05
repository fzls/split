<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

use gburtini\Distributions\Beta;
use Split\Impl\Persistence\CookieAdapter;

Route::get('/', function () {
    return view('welcome');
});

Route::auth();

Route::get('/home', 'HomeController@index');

//TODO: add a/b testing logic and admin view

Route::get('/test', function (Request $request) {
    $test = 1;
    var_dump($test);
    $test = collect([1,2,3]);
    var_dump($test);
    echo $test->implode('.');
});