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

use Carbon\Carbon;
use Illuminate\Http\Request;
use Split\Impl\Alternative;
use Split\Impl\Metric;

Route::auth();
Route::get('/home', 'HomeController@index');

Route::group(['prefix' => 'api', 'as' => 'api.'], function () {
    Route::post('/ab_test', 'ApiController@ab_test');
    Route::post('/ab_finished', 'ApiController@ab_finished');
});

Route::group(['as' => 'dashboard.'], function () {
    Route::get('/', 'DashboardController@index');

    Route::post('/experiment', 'DashboardController@set_winner');
    Route::post('/start', 'DashboardController@start');
    Route::post('/reset', 'DashboardController@reset');
    Route::post('/reopen', 'DashboardController@reopen');

    Route::delete('/experiment', 'DashboardController@delete');

});

Route::get('/test', function (Request $request) {
    $t = new Carbon();
    var_dump($t->toAtomString());
    var_dump($t->toCookieString());
    var_dump($t->toDateString());
    var_dump($t->toDateTimeString());
    var_dump($t->toDayDateTimeString());
    var_dump($t->toFormattedDateString());
    var_dump(new Carbon());
    $a = $b = $c = 1;
    var_dump(compact('a', 'b', 'c'));
    var_dump(Config::get('app.env'));
    var_dump($request['experiment']);
    var_dump(round(3.56782,2));
    var_dump(url('reset.css'));
    var_dump(url('style.css'));
    var_dump(action('ApiController@ab_test'));
    var_dump(asset('img/photo.jpg'));
    var_dump(secure_asset('img/photo.jpg'));
    var_dump(url('img/photo.jpg'));

});