<?php
/**
 * Created by PhpStorm.
 * User: thd
 * Date: 8/8/2016
 * Time: 4:09 PM
 */

namespace Split\Impl;


use Illuminate\Support\Facades\Redis;

class Metric {
    public $name;
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
        $metric = Redis::hget('metrics',$name);
        if ($metric){
            $experiment_names = collect(explode(',',$metric));

            $experiments = $experiment_names->map(function ($experiment_name){
                ExperimentCatalog::find($experiment_name);
            });

            return new Metric(['name'=>$name, 'experiments'=>$experiments]);
        }else{
            return null;
        }
    }

    public static function load_from_configuration($name){
        $metrics = Configuration::metrics();

        if ($metrics&& $metrics[$name]){
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

    public static function all(){
        $redis_metrics = collect(Redis::hgetall('metrics'));/*TODO*/
    }


}