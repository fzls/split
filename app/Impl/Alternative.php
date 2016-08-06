<?php
/**
 * Created by PhpStorm.
 * User: 风之凌殇
 * Date: 8/6/2016
 * Time: 12:33 PM
 */

namespace Split\Impl;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Redis;
use Split\Impl\Zscore;

class Alternative {
    public $name;
    public $experiment_name;
    public $weight;
    protected $p_winner;
    protected $key;

    /**
     * Alternative constructor.
     *
     * @param mixed  $name
     * @param string $experiment_name
     */
    public function __construct($name, $experiment_name) {
        $this->experiment_name = $experiment_name;

        if ($name instanceof Collection) {
            $this->name = $name->keys()->first();
            $this->weight = $name->values()->first();
        } else {
            $this->name = $name;
            $this->weight = 1;
        }
        $p_winner = 0.0;
        $this->key = "$this->experiment_name:$this->name";

    }

    function __toString() {
        return $this->name;
    }


    /**
     * @return Collection
     */
    public function goals() {
        return $this->experiment()->goals;
    }

    public function p_winner($goal = null) {
        $field = $this->set_prob_field($goal);
        $this->p_winner = (float)Redis::hget($this->key, $field);
    }

    public function set_p_winner($prob, $goal = null) {
        $field = $this->set_prob_field($goal);
        Redis::hset($this->key, $field, (float)$prob);
    }

    public function participant_count() {
        return (int)Redis::hget($this->key, 'participant_count');
    }

    public function set_participant_count($count) {
        Redis::hset($this->key, 'participant_count', (int)$count);
    }

    public function completed_count($goal = null) {
        $field = $this->set_field($goal);

        return (int)Redis::hget($this->key, $field);
    }

    public function all_completed_count() {
        if ($this->goals()->isEmpty()) {
            return $this->completed_count();
        } else {
            return $this->goals()->sum(function ($goal) {
                return $this->completed_count($goal);
            });
        }
    }

    public function unfinished_count() {
        return $this->participant_count() - $this->all_completed_count();
    }

    public function set_field($goal) {
        $field = "completed_count";
        if ($goal) $field .= ":$goal";

        return $field;
    }

    public function set_prob_field($goal) {
        $field = 'p_winner';
        if ($goal) $field .= ":$goal";

        return $field;
    }

    public function set_completed_count($count, $goal = null) {
        $field = $this->set_field($goal);
        Redis::hset($this->key, $field, (int)$count);
    }

    public function increment_participation() {
        Redis::hincrby($this->key, 'participant_count', 1);
    }

    public function increment_completion($goal = null) {
        $field = $this->set_field($goal);
        Redis::hincrby($this->key, $field, 1);
    }

    public function is_control() {
        return $this->experiment()->control->name == $this->name;
    }

    public function conversion_rate($goal = null) {
        if ($this->participant_count() == 0) return 0;

        return $this->completed_count($goal) / $this->participant_count();
    }

    public function experiment() {
        /*fixme : add after @ExperimentCatalog*/
        return \Split\Impl\ExperimentCatalog::find($this->experiment_name);
    }

    public function z_score($goal = null) {
        # p_a = Pa = proportion of users who converted within the experiment split (conversion rate)
        # p_c = Pc = proportion of users who converted within the control split (conversion rate)
        # n_a = Na = the number of impressions within the experiment split
        # n_c = Nc = the number of impressions within the control split
        $control = $this->experiment()->control;
        /* @var $control Alternative */
        $alternative = $this;
        if ($control->name == $alternative->name) return 'N/A';

        $p_a = $alternative->conversion_rate($goal);
        $p_c = $control->conversion_rate($goal);

        $n_a = $alternative->participant_count();
        $n_c = $control->participant_count();

        return Zscore::calculate($p_a, $n_a, $p_c, $n_c);
    }

    public function save() {
        Redis::hsetnx($this->key, 'participant_count', 0);
        Redis::hsetnx($this->key, 'completed_count', 0);
        Redis::hsetnx($this->key, 'p_winner', $this->p_winner);
    }

    public function validate() {
        if (!is_string($this->name) && !$this->hash_with_correct_values($this->name))
            throw new \InvalidArgumentException('Alternative must be a string');
    }

    public function reset() {
        Redis::hmset($this->key, 'participant_count', 0, 'completed_count', 0);
        if (!$this->goals()->isEmpty()) {
            foreach ($this->goals() as $goal) {
                $field = "completed_count:$goal";
                Redis::hset($this->key, $field, 0);
            }
        }
    }

    public function delete() {
        Redis::del($this->key);
    }

    private function hash_with_correct_values($name) {
        /*fixme : the original seems to be wrong it is not necessary to do this*/
        return true;

    }
}

?>