<?php
/**
 * Created by PhpStorm.
 * User: thd
 * Date: 8/8/2016
 * Time: 1:59 PM
 */

namespace Split\Impl;


use Illuminate\Support\Collection;
use Split\Contracts\Persistence\ArrayLike;

class User implements ArrayLike{
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

    /**
     *
     */
    public function cleanup_old_experiments(){
        $this->keys_without_finished($this->user->keys())->each(function ($key){
            $experiment = \App::make('split_catalog')->find($this->key_without_version($key));
            if (is_null($experiment)||$experiment->has_winner()||is_null($experiment->start_time())){
                $this->user->delete($key);
                $this->user->delete("$key:finished");
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
            return !\App::make('split_config')->allow_multiple_experiments && $this->keys_without_experiment($this->user->keys(),$experiment_key)->count()>0;
        }
    }

    /**
     * @param Experiment $experiment
     */
    public function cleanup_old_versions($experiment){
        $keys = $this->user->keys()->filter(function ($k)use($experiment){
            return preg_match("#$experiment->name#",$k);
        });
        $this->keys_without_experiment($keys,$experiment->key())->each(function ($key){
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
     *
     * @return Collection
     */
    private function keys_without_finished($keys){
        return $keys->reject(function ($k){
            return str_contains($k,":finished");
        });
    }

    public function key_without_version($key){
        return preg_split("/\:\d(?!\:)/",$key)[0];
    }

    /**
     * delete a key from the container.
     *
     * @param string $key
     *
     * @return void
     */
    public function delete($key) {
        $this->user->delete($key);
    }

    /**
     * return the keys of the container.
     *
     * @return Collection
     */
    public function keys() {
        return $this->user->keys();
    }

    /**
     * Whether a offset exists
     * @link  http://php.net/manual/en/arrayaccess.offsetexists.php
     *
     * @param mixed $offset <p>
     *                      An offset to check for.
     *                      </p>
     *
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     * @since 5.0.0
     */
    public function offsetExists($offset) {
        return $this->user->offsetExists($offset);
    }

    /**
     * Offset to retrieve
     * @link  http://php.net/manual/en/arrayaccess.offsetget.php
     *
     * @param mixed $offset <p>
     *                      The offset to retrieve.
     *                      </p>
     *
     * @return mixed Can return all value types.
     * @since 5.0.0
     */
    public function offsetGet($offset) {
        return $this->user->offsetGet($offset);
    }

    /**
     * Offset to set
     * @link  http://php.net/manual/en/arrayaccess.offsetset.php
     *
     * @param mixed $offset <p>
     *                      The offset to assign the value to.
     *                      </p>
     * @param mixed $value  <p>
     *                      The value to set.
     *                      </p>
     *
     * @return void
     * @since 5.0.0
     */
    public function offsetSet($offset, $value) {
        $this->user->offsetSet($offset,$value);
    }

    /**
     * Offset to unset
     * @link  http://php.net/manual/en/arrayaccess.offsetunset.php
     *
     * @param mixed $offset <p>
     *                      The offset to unset.
     *                      </p>
     *
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset($offset) {
        $this->user->offsetUnset($offset);
}}