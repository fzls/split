<?php
/**
 * Created by PhpStorm.
 * User: thd
 * Date: 8/8/2016
 * Time: 1:59 PM
 */

namespace Split\Impl;


use Illuminate\Support\Collection;

class User {
    public $user;

    /**
     * User constructor.
     *
     * @param $user
     */
    public function __construct() {
        /*fixme: make the same*/
        $this->user = \Split\Impl\Persistence\adapter();
    }

    public function cleanup_old_experiments(){
        $this->keys_without_finished($this->user->keys())->each(function ($key){
            $experiment = ExperimentCatalog::find($this->key_without_version($key));
            if (is_null($experiment)||$experiment->has_winner()||is_null($experiment->start_time())){
                $this->user->delete($key);
                $this->user->delete(Experiment::finished_key($key));
            }
        });
    }

    public function is_max_experiments_reached($experiment_key){
        if (env('ALLOW_MULTIPLE_EXPERIMENTS') == 'control'){
            /*fixme*/
            $experiments = $this->active_experiments();/* @var $experiments Collection*/
            $count_control = $experiments->values()->sum(function ($v){
                return intval($v == 'control');
            });
            return $experiments->count() > $count_control;
        }else{
            return !env('ALLOW_MULTIPLE_EXPERIMENTS') && $this->keys_without_experiment($this->user->keys(),$experiment_key)->count>0;
        }
    }

    public function cleanup_old_versions($experiment){
        $keys = $this->user->keys()->filter(function ($k)use($experiment){
            return preg_match("#$experiment->name#",$k);
        });
        $this->keys_without_experiment($keys,$experiment->key)->each(function ($key){
            $this->user->delete($key);
        });
    }

    public function active_experiments(){
        $experiment_pairs = collect([]);
        $this->user->keys()->each(function ($key)use($experiment_pairs){
            Metric::possible_experiments($this->key_without_version($key))->each(function ($experiment)use($experiment_pairs,$key){/* @var $experiment Experiment*/
                if (!$experiment->has_winner()){
                    $experiment_pairs[$this->key_without_version($key)]=$this->user[$key];
                }
            });
        });
        return $experiment_pairs;
    }

    /**
     * @param $keys Collection
     * @param $experiment_key
     *
     * @return Collection
     */
    private function keys_without_experiment($keys, $experiment_key){
        return $keys->reject(function ($k)use($experiment_key){
            return preg_match("/^$experiment_key(:finished)?$/",$k);
        });
    }

    /**
     * @param $keys Collection
     */
    private function keys_without_finished($keys){
        $keys->reject(function ($k){
            return str_contains($k,":finished");
        });
    }

    public function key_without_version($key){
        return preg_split("/\:\d(?!\:)/",$key)[0];
    }

}