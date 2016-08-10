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
class A_chen{
    public $uuid;

    /**
     * A constructor.
     *
     * @param $uuid
     */
    public function __construct($uuid) { $this->uuid = $uuid; }

}

class B_chen{
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
    public function testa(){
        return $this;
    }

}

function add_minus($a,$b){
    return collect([$a+$b,$a-$b]);
}
Route::get('/test', function (Request $request) {
    var_dump(\Split\Impl\Persistence\adapter());
    var_dump(App::make('split_config'));
//    var_dump(explode('|',null));
//    $a=1;$b=2;
//    list($a,$b) = add_minus(999,666);
//    var_dump($a);
//    var_dump($b);
//    $t = new Test();
//    var_dump($t);
//    var_dump($t->testa());
//    var_dump($t->t);
//    $redis = App::make('split_redis');
//    var_dump($redis);
//    var_dump($redis->get('chen'));
//    var_dump($redis->keys('*'));
//    var_dump($redis->time());

//    var_dump(Request::has('user_id'));
//    var_dump(App::make('test'));
////    sleep(2);
//    var_dump(App::make('test'));
//    var_dump(App::make('split_redis')->set('chen','test'));
//    var_dump(App::make('split_redis')->keys('*'));
//    var_dump(Redis::keys('*'));
//    var_dump(new B_chen());
//    var_dump(Request::get('user_id'));
//    var_dump(Request::input('user_id'));
//    var_dump(Request::all());
//    var_dump(Config::all());
//    $t = new Test();
//    var_dump(isset($t));
//    var_dump(isset($t->t));
//    var_dump(isset($t->s));
//    var_dump(is_null($t->s));
//    var_dump(is_null($t->s));
//    if ($t->t){
//        echo 'true';
//    }else{
//        echo 'false';
//    }
//    $c = collect(['name' => 1, 2, 3]);
//    var_dump($c);
//    var_dump($c->put('test',123));
//    var_dump($c);

//    $c['name']=2;
//    var_dump($c);
//    $c['test']=2;
//    var_dump($c);
//    $c['1111']=2;
//    var_dump($c);
//    $c['meow']=2;
//    var_dump($c);
//    $c->put('name',100);
//    var_dump($c);
//    $adapters = \Config::get('split.adapters');
//    $adapter = \Config::get('split.adapter');
//    var_dump($adapters);
//    var_dump($adapter);
//    var_dump(new $adapters[$adapter]());
//    $t = Collection::class;
//    var_dump($t);
//    var_dump(new $t([1,2,3]));
//    $t = ['sad/test'=>'year','?test[123]'=>999,'. \ + * ? [ ^ ] $ ( ) { } = ! < > | : -'=>100];
//    var_dump(collect($t)->map(function ($v,$k){
//            return [preg_quote($k,'/')=>$v];
//        })->collapse());
//    echo $p = "/\b(?:".implode('|',$t).")\b|\A\W*\z/i";
//    var_dump(preg_match($p,"hello green world "));
//    $t = collect(['color' => 'blue', 'name' => 'feng', 999 => 233,
//                 collect([1,2,3,collect('hello world')])]);
//    var_dump($t);
//    var_dump(collect(json_decode(json_encode($t))));
//    foreach ($t as $k => $v) {
//        echo $k, $v, '<br>';
//    }
//    echo '...........................<br>';
//    $t->each(function ($v, $k) {
//        echo $k, $v, '<br>';
//    });
////    var_dump($t['color']);
////    var_dump($t['colorss']);
//    var_dump(value_for($t,'color'));
//    var_dump(value_for($t,'colorss'));
//    var_dump(isset($t['ccccc']));
//    $k = collect(null);
//    foreach ($k as $item) {
//        echo $item,'test____';
//    }
//    echo '2222';
//    var_dump(\Request::server('HTTP_USER_AGENT'));
//    var_dump(\Request::all());
//    var_dump(Request::input('test'));
//    var_dump(Request::all());
//    var_dump(implode(',',collect([new Test(1,2),new Test(3,4),new Test(5,6),])->merge(collect([1,2,3,4=>'test']))->toArray()));
//    var_dump(preg_split("/\:\d(?!\:)/","blue:chen:1:1.3.5")[0]);
//    $test_preg = collect(['123','1a23','12a3','a123',]);
//    var_dump($test_preg);
//    var_dump($test_preg->reject(function ($k){
//        return preg_match("/123/",$k);
//    }));
//    echo intval(1==1);
//    echo intval(1==2);
//    $t = new Test();
//    $c=1;
//    echo $t instanceof Test.'1<br>';
//    echo !($t instanceof Test).'2<br>';
//    echo $c instanceof Test.'3<br>';
//    echo !$c instanceof Test.'4<br>';
//    $a = collect([1,2,3,4,5]);
//    $c = null;
//    var_dump(is_null($c)?$a:$a->prepend($c));
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