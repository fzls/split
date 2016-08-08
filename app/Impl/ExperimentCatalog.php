<?php
/**
 * Created by PhpStorm.
 * User: thd
 * Date: 8/8/2016
 * Time: 10:45 AM
 */

namespace Split\Impl;


use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Redis;

class ExperimentCatalog {
    public function all() {
        return collect(Redis::semember('experiments'))->map(function ($es) {
            return $this->find($es);
        })->reject(function ($e) {
            return $e == null;
        });
    }

    public function all_active_first() {
        $this->all()->groupBy(function ($e/* @var $e Experiment */) {
            return intval($e->winner());/*0=>not winners, 1=> winner*/
        })->map(function ($es/* @var $es Collection */) {
            return $es->sortBy(function ($e) {
                return $e->name;
            });
        })->flatten();
    }

    public function find($name) {
        if (!Redis::exists($name)) return;
        $e = new Experiment($name);/*fixme: find alternative of tap*/
        $e->load_from_redis();

        return $e;
    }

    public function find_or_initialize($metric_descriptor, $control = null, $alternatives = null) {
        if (!$alternatives instanceof Collection) {
            $alternatives = collect(func_get_args())->splice(2);
        }
        if ($control instanceof Collection && $alternatives->count() == 0) {
            extract(['control' => $control->first(), 'alternatives' => $control->splice(1)]);
            /*same as control, alternatives = control.first, control[1..-1]*/
        }

        /*experiment_name_with_version, goals*/
        extract($this->normalize_experiment($metric_descriptor));/*metric_descriptor Collection*/
        /* @var $experiment_name_with_version string */
        /* @var $goals Collection */
        $experiment_name = explode(':', $experiment_name_with_version)[0];

        return new Experiment($experiment_name, [
            'alternatives' => is_null($control) ? $alternatives : $alternatives->prepend($control),
            'goals'        => $goals,
        ]);
    }

    public function find_or_create($metric_descriptor, $control = null/*, *alternatives*/) {
        $alternatives = collect(func_get_args())->splice(2);
        $experiment = $this->find_or_initialize($metric_descriptor, $control = null, $alternatives);
        $experiment->save();
        return $experiment;
    }

    private function normalize_experiment($metric_descriptor){
        if($metric_descriptor instanceof Collection){
            $experiment_name = $metric_descriptor->keys()->first();
            $goals = [$metric_descriptor->values()->first()];
        }else{
            $experiment_name = $metric_descriptor;
            $goals = [];
        }
        return ['experiment_name_with_version'=>$experiment_name,'goals'=>$goals];
    }
    
    
}