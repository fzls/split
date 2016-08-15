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

/**
 * Class Metric
 *
 * @package Split\Impl
 */
class Metric {
    /**
     * Name of the metric
     *
     * @var string
     */
    public $name;

    /**
     * The experiments that has this metric
     *
     * @var Collection of Experiment
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

    /**
     * Load metric from redis
     *
     * @param $name
     *
     * @return null|Metric
     */
    public static function load_from_redis($name){
        $metric = \App::make('split_redis')->hget('metrics',$name);

        if ($metric){
            /* metric format: m1,m2,m3
               or try to use json instead*/
            $experiment_names = collect(explode(',',$metric));

            $experiments = $experiment_names->map(function ($experiment_name){
                \App::make('split_catalog')->find($experiment_name);
            });

            return new Metric(['name'=>$name, 'experiments'=>$experiments]);
        }else{
            return null;
        }
    }

    /**
     * Load metric from configuration
     *
     * @param string $name The name of the metric that we want
     *
     * @return null|Metric
     */
    public static function load_from_configuration($name){
        $metrics = \App::make('split_config')->metrics();

        if ($metrics&& $metrics->contains($name)){
            return new Metric(['experiments'=>$metrics[$name],'name'=>$name]);
        }else{
            return null;
        }
    }

    /**
     * Find a metric.
     *
     * By default, we will try to find it from the configuration, if not found, we will then try to find from redis.
     *
     * @param string $name
     *
     * @return null|Metric
     */
    public static function find($name){
        $metric = self::load_from_configuration($name);

        if (is_null($metric)){
            $metric = self::load_from_redis($name);
        }

        return $metric;
    }

    /**
     * Find a metric, otherwise create it.
     *
     * @param array|Collection $attrs
     *
     * @return null|Metric
     */
    public static function find_or_create($attrs){
        $metric = self::find(Helper::value_for($attrs,'name'));

        if (!$metric){
            $metric = new Metric($attrs);
            $metric->save();
        }

        return $metric;
    }

    /**
     * Return all the metric from redis and configuration
     *
     * @return Collection of Metric
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
     * Return all the possible experiments.
     *
     * @param string $metric_name The name of metric or the name of the experiment.
     *
     * @return Collection Of Experiment
     */
    public static function possible_experiments($metric_name){
        $experiments = collect([]);
        /* case when name of metric*/
        $metric = self::find($metric_name);
        if ($metric){
            $experiments->push($metric->experiments);
        }

        /*case when name of experiment*/
        $experiment = \App::make('split_catalog')->find($metric_name);
        if ($experiment){
            $experiments->push($experiment);
        }

        return $experiments->flatten();
    }

    /**
     * Save the metric to the redis.
     */
    public function save(){
        \App::make('split_redis')->hset('metrics',$this->name,implode(',',$this->experiments->map(function ($e){return $e->name;})->toArray()));
    }

    /**
     * Complete each experiment.
     */
    public function complete(){
        $this->experiments->each(function ($experiment/* @var $experiment Experiment*/){
            $experiment->complete();/*fixme: seems to not implement in Split*/
        });
    }
}
