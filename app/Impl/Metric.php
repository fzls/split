<?php
/**
 * Created by PhpStorm.
 * User: thd
 * Date: 8/8/2016
 * Time: 4:09 PM
 */

namespace Split\Impl;


use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Redis;

class Metric {
    public $name;

    /**
     * @var Collection
     */
    public $experiments;

    /**
     * Metric constructor.
     */
    public function __construct($attrs=[]) {
        $attrs=collect($attrs);
        $this->name = $attrs->pull('name');
        $this->experiments = $attrs->pull('experiments');
    }

    public static function load_from_redis($name){
        $metric = \App::make('split_redis')->hget('metrics',$name);
        if ($metric){
            $experiment_names = collect(explode(',',$metric));

            $experiments = $experiment_names->map(function ($experiment_name){
                \App::make('split_catalog')->find($experiment_name);
            });

            return new Metric(['name'=>$name, 'experiments'=>$experiments]);
        }else{
            return null;
        }
    }

    public static function load_from_configuration($name){
        $metrics = \App::make('split_config')->metrics();

        if ($metrics&& $metrics->contains($name)){
            return new Metric(['experiments'=>$metrics[$name],'name'=>$name]);
        }else{
            return null;
        }
    }

    public static function find($name){
        $metric = self::load_from_configuration($name);
        if (is_null($metric)){
            $metric = self::load_from_redis($name);
        }
        return $metric;
    }

    public static function find_or_create($attrs){
        $metric = self::find($attrs['name']);
        if (!$metric){
            $metric = new Metric($attrs);
            $metric->save();
        }
        return $metric;
    }

    /**
     * @return Collection
     */
    public static function all(){
        $redis_metrics = collect(\App::make('split_redis')->hgetall('metrics'))->map(function ($value,$key){
            return self::find($key);
        });

        $configuration_metrics = \App::make('split_config')->metrics()->map(function ($value,$key){
            return new Metric(['name'=>$key,'experiments'=>$value]);
        });
        return $redis_metrics->merge($configuration_metrics);
    }

    /**
     * @param $metric_name string
     *
     * @return Collection Of Experiment
     */
    public static function possible_experiments($metric_name){
        $experiments = collect([]);
        $metric = self::find($metric_name);
        if ($metric){
            $experiments->push($metric->experiments);
        }
        $experiment = \App::make('split_catalog')->find($metric_name);
        if ($experiment){
            $experiments->push($experiment);
        }
        return $experiments->flatten();
    }

    public function save(){
        \App::make('split_redis')->hset('metrics',$this->name,implode(',',$this->experiments->map(function ($e){return $e->name;})->toArray()));
    }

    public function complete(){
        $this->experiments->each(function ($experiment/* @var $experiment Experiment*/){
            $experiment->complete();/*fixme: seems to not implement in Split*/
        });
    }

    private static function normalize_metric($label){
        if ($label instanceof Collection){
            $metric_name = $label->keys()->first();
            $goals = $label->values()->first();
        }else{
            $metric_name = $label;
            $goals = [];
        }
        return [$metric_name,$goals];
    }


}