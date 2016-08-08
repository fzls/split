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

function test_constant_scope() {
    define('BIG', '5<br>');
    var_dump(BIG);
}

class Test {
    public $t;
}

function isAssoc($arr) {
    /* @var $arr Collection */
    return $arr->keys()->toArray() !== range(0, count($arr) - 1);
}

function test_args($a, $b) {
    echo $a, $b;
    var_dump(func_num_args());
    var_dump(collect(func_get_args())->splice(2));
}

Route::get('/test', function (Request $request) {
    $t = new Test();
    $c=1;
    echo $t instanceof Test.'1<br>';
    echo !($t instanceof Test).'2<br>';
    echo $c instanceof Test.'3<br>';
    echo !$c instanceof Test.'4<br>';
    $a = collect([1,2,3,4,5]);
    $c = null;
    var_dump(is_null($c)?$a:$a->prepend($c));
//    var_dump(explode(':','chen:1.3:test')[0]);
//    var_dump(explode(':','chen:1.3:test'));
//    $control = collect(['Alt 1', 'Alt 2', 'Alt 3',4]);
//    var_dump($control);
//    extract(['control'=>$control->first(),'alternatives'=>$control->splice(1)]);
////    $tmp = $control;
////    $control = $tmp->first();
////    $alternatives = $tmp->splice(1);
//    var_dump($control);
//    var_dump($alternatives);
//    test_args(1,2);
//    test_args(1,2,'test','meow');
//    test_args(1,2,3,4);
//    test_args(1,2,3,4,5,6);
//    $t=new Test();
//    $t->t='hello ';
//    echo $t->t;
//    $t['t']='test ';
//    echo $t['t'];
//    echo $t->t;
//    $nums = collect([1,2,3,4,5,6]);
//    print_r($nums->groupBy(function ($val,$key){return intval($val%2!=0);}));
//    $test = collect(['blue','red','green']);
//    var_dump(isAssoc($test));
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