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

Route::get('/', function () {
    return view('welcome');
});

Route::auth();

Route::get('/home', 'HomeController@index');

//TODO: add a/b testing logic and admin view

Route::get('/test', function (Request $request) {
//    $beta = new Beta(81,219);
//    for($i=0;$i<50;++$i){
//        $draw = $beta->rand();
//        echo $draw.'<br>';
//    }
    $guesses = collect(['a' => 0.85, 'b' => 0.85, 'c' => 0.22, 'd' => 0.85, 'e' => 0.85]);
    print_r($guesses);
    $guesses=collect($guesses);
    print_r($guesses);
    $gmax=$guesses->max();
    echo $gmax,'<br>';
    $best = $guesses->filter(function ($weight,$name) use($gmax){
        echo "weight : $weight, name : $name<br>";
        return $weight == $gmax;
    })->keys();
    print_r($best);

    return $best->random();
});