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
use gburtini\Distributions\Beta;
use Illuminate\Database\Eloquent\Collection;
use Split\Impl\Alternative;
use Split\Impl\Persistence\CookieAdapter;
use Split\Impl\Zscore;

require_once __DIR__ . '/../Impl/Persistence/adapter.php';

Route::get('/', function () {
    return view('welcome');
});

Route::auth();

Route::get('/home', 'HomeController@index');

//TODO: add a/b testing logic and admin view

function finished_key($key = null) {
    if ($key) return "$key:finished"; // finished_key($key)
    return finished_key("Test");
}
function test_constant_scope(){
    define('BIG','5<br>');
    var_dump(BIG);
}

function isAssoc($arr) {/* @var $arr Collection*/
        return $arr->keys()->toArray() !== range(0, count($arr) - 1);
    }

Route::get('/test', function (Request $request) {
    $test = collect(['blue','red','green']);
    var_dump(isAssoc($test));
//    $js_id = '/Split/test/Hello';
//    var_dump($js_id);
//     $js_id=preg_replace('#/#','--',$js_id);
//    var_dump($js_id);
//    $a = new Alternative('blue','color');
//    $t = [];
//    var_dump($t);
//    var_dump($a);
//    $t[(string)$a]='test_to_s';
//    var_dump($t);
//    $winning_counts = collect(['a' => 1, 'b' => 2, 'c' => 3]);
//    $number_of_simulations = 6;
//    $alternative_probabilities = collect([]);
//    var_dump($alternative_probabilities);
//    $winning_counts->each(function ($wins, $alternative_name/*string*/) use ($alternative_probabilities, $number_of_simulations) {
//        $alternative_probabilities[$alternative_name] = $wins / $number_of_simulations;
//    });
//    var_dump($alternative_probabilities);
////    echo Zscore::calculate(0.5,222,0.6,200);
//    echo 1.0 * 3 / 4, '<br>';
//    echo (float)3 / 4, '<br>';
//    echo ((float)3) / 4, '<br>';
//    echo 3 / (float)4, '<br>';
//    echo 3 / 4, '<br>';
//
//    Class A {
//        function test() {
//            var_dump($this);
//            $test = $this;
//            var_dump($test);
//        }
//    }
//
//    (new A())->test();
////    var_dump(Carbon::now()->timestamp);
////    print_r(Carbon::now()->timestamp);
//    $t='3 days ago';
//    if ($t) {
//        if (preg_match('/^[-+]?[0-9]+$/', $t)) {
//            echo Carbon::createFromTimestamp($t);
//        } else {
//            echo new Carbon($t);
//        }
//    }
//    var_dump(null||123);
});