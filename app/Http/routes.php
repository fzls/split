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
use Split\Impl\Helper;
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
    $alts = collect([[1,2,3,'test'=>100,'blue','red'=>1000]]);
    var_dump($alts->collapse());
//    var_dump(__DIR__);
//    var_dump( app_path('Impl/exceptions.php'));
//    var_dump( app_path('Impl/Persistence/adapter.php'));
//    var_dump( __DIR__ . '/../Impl/Persistence/adapter.php');
//    var_dump($_SERVER['SERVER_ADDR']);
//    var_dump($_SERVER['DOCUMENT_ROOT']);
//    var_dump(Helper::ab_test('test_ab',['success','fail','unkown']));
//    $t = new Carbon();
//    var_dump($t->toAtomString());
//    var_dump($t->toCookieString());
//    var_dump($t->toDateString());
//    var_dump($t->toDateTimeString());
//    var_dump($t->toDayDateTimeString());
//    var_dump($t->toFormattedDateString());
//    var_dump(new Carbon());
//    $a = $b = $c = 1;
//    var_dump(compact('a', 'b', 'c'));
//    var_dump(Config::get('app.env'));
//    var_dump($request['experiment']);
//    var_dump(round(3.56782,2));
//    var_dump(url('reset.css'));
//    var_dump(url('style.css'));
//    var_dump(action('ApiController@ab_test'));
//    var_dump(asset('img/photo.jpg'));
//    var_dump(secure_asset('img/photo.jpg'));
//    var_dump(url('img/photo.jpg'));

});