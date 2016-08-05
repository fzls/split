<?php
/**
 * Created by PhpStorm.
 * User: thd
 * Date: 8/5/2016
 * Time: 6:07 PM
 */

namespace Split\Impl;


use Redis;

class GoalsCollection {
    protected $experiment_name;
    /**
     * @var \Illuminate\Support\Collection
     */
    protected $goals;
    protected $goals_for_validate;
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
        $this->goals_for_validate=$goals;
        $this->redis=Redis::connection();
        $this->goals_key=$this->experiment_name.":goals";
    }


    /**
     * @return \Illuminate\Support\Collection
     */
    public function load_from_redis(){
        return collect($this->redis->lrange($this->goals_key,0,-1));
    }

    public function load_from_configuration(){
        /*TODO add after configuration is defined*/
    }

    /**
     * @return bool
     */
    public function save(){
        if($this->goals->isEmpty()) return false;
        foreach ($this->goals as $goal){
            $this->redis->lpush($this->goals_key,$goal);
        }
        return true;
    }
    
    public function validate(){
        if(!is_null($this->goals_for_validate)&&!is_array($this->goals_for_validate)){
            throw new \InvalidArgumentException('Goals must be an array');
        }
    }

    public function delete(){
        $this->redis->del($this->goals_key);
    }
}