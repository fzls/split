<?php

namespace Split\Http\Controllers;

use App;
use Config;
use Illuminate\Http\Request;

use Split\Http\Requests;
use Split\Impl\Alternative;
use Split\Impl\Metric;

class DashboardController extends Controller
{
    public function index(){
        # Display experiments without a winner at the top of the dashboard
        $experiments = App::make('split_catalog')->all_active_first();

        $metrics = Metric::all();

        $current_env = App::make('split_config')->current_environment;

        return view('dashboard.index', [
            'experiments' => $experiments,
            'metrics'     => $metrics,
            'current_env' => $current_env,
        ]);
    }
    
    public function set_winner(Request $request){
        
        $experiment  = App::make('split_catalog')->find($request['experiment']);
        $alternative = new Alternative($request['alternative'], $request['experiment']);
        $experiment->set_winner($alternative->name);

        return redirect('/');
    }
    
    public function start(Request $request){
        $experiment = App::make('split_catalog')->find($request['experiment']);
        $experiment->start();

        return redirect('/');
    }
    
    public function reset(Request $request){
        $experiment = App::make('split_catalog')->find($request['experiment']);
        $experiment->reset();

        return redirect('/');
    }
    
    public function reopen(Request $request){
        $experiment = App::make('split_catalog')->find($request['experiment']);
        $experiment->reset_winner();

        return redirect('/');
    }
    
    public function delete(Request $request){
        $experiment = App::make('split_catalog')->find($request['experiment']);
        $experiment->delete();

        return redirect('/');
    }
}
