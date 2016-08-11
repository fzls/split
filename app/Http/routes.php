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
use Illuminate\Support\Collection;
use Split\Impl\Alternative;
use Split\Impl\Experiment;
use Split\Impl\Helper;
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


function isAssoc($arr) {
    /* @var $arr Collection */
    return $arr->keys()->toArray() !== range(0, count($arr) - 1);
}

function test_args($a, $b) {
    echo $a, $b;
    var_dump(func_num_args());
    var_dump(collect(func_get_args())->splice(2));
}

//
//function value_for($hash, $key) {
//    if ($hash->has($key)) return $hash[$key];
//
//    return null;
//}
class A_chen {
    public $uuid;

    /**
     * A constructor.
     *
     * @param $uuid
     */
    public function __construct($uuid) { $this->uuid = $uuid; }

}

class B_chen {
    public $a;

    /**
     * B constructor.
     *
     * @param $a
     */
    public function __construct(A_chen $a) { $this->a = $a; }

}


class Test {
    public $t;
    public $s;

    /**
     * Test constructor.
     *
     * @param $t
     * @param $s
     */
//    public function __construct($t, $s) {
//        $this->t = $t;
//        $this->s = $s;
//    }
    public function testa() {
        return $this;
    }

}

function add_minus($a, $b) {
    return collect([$a + $b, $a - $b]);
}

function test_vari($a, $b, Collection...$o) {
    var_dump($a);
    var_dump($b);
    var_dump($o);
}

Route::get('/test', function (Request $request) {
    var_dump(Cookie::get('split'));
//    var_dump(implode('|', [1,2,3,4,5,6]));
    Helper::ab_test(['link_color'=>['purchase','refund']],['blue','red']);
//    Helper::ab_finished('link_color');
//    $experiment = new Experiment('basket_text',['alternatives'=>[['Basket' => 0.6],["Cart" => 0.4]]]);
//    var_dump($experiment->alternatives[0]);
//    var_dump($experiment->alternatives[1]);
//
//    $alt1 = new Alternative('Basket','basket_text');
//    $alt2 = new Alternative('Cart','basket_text');
//    $exp = App::make('split_catalog')->find_or_create(
//        collect(['basket_text'=>['purchase','refund']]),
//        'Basket',
//        'Cart'
//    );
//    $goal1 = 'purchase';
//    $goal2 = 'refund';
//    var_dump($alt1->goals());
//    var_dump($alt2->name);

});