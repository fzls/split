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
use Split\Impl\Zscore;

require_once __DIR__ . '/../Impl/Persistence/adapter.php';

Route::get('/', function () {
    return view('welcome');
});

Route::auth();

Route::get('/home', 'HomeController@index');

//TODO: add a/b testing logic and admin view

Route::get('/test', function (Request $request) {
//    echo Zscore::calculate(0.5,222,0.6,200);
    echo 1.0 * 3 / 4, '<br>';
    echo (float)3 / 4, '<br>';
    echo ((float)3) / 4, '<br>';
    echo 3 / (float)4, '<br>';
    echo 3 /4, '<br>';
    Class A{
        function test(){
            var_dump($this);
            $test = $this;
            var_dump($test);
        }
    }
    (new A())->test();
});