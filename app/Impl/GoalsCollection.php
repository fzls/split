<?php
/**
 * Created by PhpStorm.
 * User: thd
 * Date: 8/5/2016
 * Time: 6:07 PM
 */

namespace Split\Impl;

class GoalsCollection {
    protected $experiment_name;
    /**
     * @var \Illuminate\Support\Collection
     */
    protected $goals;
    protected $redis;
    protected $goals_key;

    /**
     * GoalsCollection constructor.
     *
     * @param $experiment_name
     * @param  $goals
     */
    public function __construct($experiment_name, $goals=null) {
        $this->experiment_name = $experiment_name;
        $this->goals = collect($goals);
        $this->redis=\App::make('split_redis');
        $this->goals_key=$this->experiment_name.":goals";
    }


    /**
     * Get goals from redis
     *
     * @return \Illuminate\Support\Collection
     */
    public function load_from_redis(){
        return collect($this->redis->lrange($this->goals_key,0,-1));
    }

    /**
     * Get goals from config
     *
     * @return \Illuminate\Support\Collection
     */
    public function load_from_configuration(){
        $this->goals = \App::make('split_config')->experiment_for($this->experiment_name)['goals'];
        if (is_null($this->goals)){
            $this->goals = collect([]);
        }else{
            $this->goals = $this->goals->flatten();
        }
        return $this->goals;
    }

    /**
     * Save the goals to the redis
     *
     * @return bool
     */
    public function save(){
        if($this->goals->isEmpty()) return false;
        foreach ($this->goals as $goal){
            $this->redis->lpush($this->goals_key,$goal);
        }
        return true;
    }

    /**
     * Validate the goals
     */
    public function validate(){
        if(false/*now not necessary with*/){
            throw new \InvalidArgumentException('Goals must be an array');
        }
    }

    /**
     * Delete goals from redis
     */
    public function delete(){
        $this->redis->del($this->goals_key);
    }
}