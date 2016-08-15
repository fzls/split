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

/**
 * Class Alternative
 * @package Split\Impl
 */
class Alternative {
    /**
     * @var string The name of the alternative
     */
    public $name;

    /**
     * @var string The name of experiment that this alternative belongs to
     */
    public $experiment_name;

    /**
     * @var int The weight of the Alternative
     */
    public $weight;

    /**
     * @var float The probability of being the winner
     */
    protected $p_winner;

    /**
     * @var string The key used to save Alternative's info to the Redis
     */
    protected $key;

    /**
     * @var \Predis\Client The Redis client
     */
    private $redis;

    /**
     * Alternative constructor.
     *
     * @param string|Collection|array $name
     * @param string                  $experiment_name
     */
    public function __construct($name, $experiment_name) {
        $this->experiment_name = $experiment_name;

        if (is_array($name)||is_object($name)) {
            $name = collect($name);
        }

        if ($name instanceof Collection) {
            /*['blue'=>'23']*/
            $this->name   = $name->keys()->first();
            $this->weight = $name->values()->first();
        } else {
            /*'blue'*/
            $this->name   = $name;
            $this->weight = 1;
        }
        $this->p_winner = 0.0;
        $this->key      = "$this->experiment_name:$this->name";
        $this->redis    = \App::make('split_redis');

    }

    /**
     * Used in : $arr[(string)$alt] , to make this a valid array offset.
     *
     * @return string
     */
    function __toString() {
        return $this->name;
    }


    /**
     * The goals of the alternative, which is also the goals of the experiment this alternative belongs to.
     *
     * @return Collection
     */
    public function goals() {
        return $this->experiment()->goals;
    }

    /**
     * Get the probability of this alternative being winner
     *
     * @param null|string $goal
     *
     * @return float
     */
    public function p_winner($goal = null) {
        $field = $this->set_prob_field($goal);

        return $this->p_winner = (float)$this->redis->hget($this->key, $field);
    }

    /**
     * Save the probability of this alternative being winner
     *
     * @param float       $prob
     * @param null|string $goal
     */
    public function set_p_winner($prob, $goal = null) {
        $field = $this->set_prob_field($goal);
        $this->redis->hset($this->key, $field, (float)$prob);
    }

    /**
     * Get the number of user assigned to in this alternative
     *
     * @return int
     */
    public function participant_count() {
        return (int)$this->redis->hget($this->key, 'participant_count');
    }

    /**
     * Save the number of user assigned to in this alternative
     *
     * @param int $count
     */
    public function set_participant_count($count) {
        $this->redis->hset($this->key, 'participant_count', (int)$count);
    }

    /**
     * Get the number of user completed certain goal
     *
     * @param null|string $goal
     *
     * @return int
     */
    public function completed_count($goal = null) {
        $field = $this->set_field($goal);

        return (int)$this->redis->hget($this->key, $field);
    }

    /**
     * Get the total number of user completed each goal
     *
     * @return int
     */
    public function all_completed_count() {
        if ($this->goals()->isEmpty()) {
            return $this->completed_count();
        } else {
            return $this->goals()->sum(function ($goal) {
                return $this->completed_count($goal);
            });
        }
    }

    /**
     * Get the number of user who didn't complete any goal
     *
     * @return int
     */
    public function unfinished_count() {
        return $this->participant_count() - $this->all_completed_count();
    }

    /**
     * Helper method for formatting completed_count field
     *
     * @param string|null $goal
     *
     * @return string
     */
    public function set_field($goal) {
        $field = "completed_count";
        if ($goal) $field .= ":$goal";

        return $field;
    }

    /**
     * Helper method for formatting p_winner field
     *
     * @param string|null $goal
     *
     * @return string
     */
    public function set_prob_field($goal) {
        $field = 'p_winner';
        if ($goal) $field .= ":$goal";

        return $field;
    }

    /**
     * Save the completed count
     *
     * @param int         $count
     * @param null|string $goal
     */
    public function set_completed_count($count, $goal = null) {
        $field = $this->set_field($goal);
        $this->redis->hset($this->key, $field, (int)$count);
    }

    /**
     * Increment the participation count
     */
    public function increment_participation() {
        $this->redis->hincrby($this->key, 'participant_count', 1);
    }

    /**
     * Increment completion count for the goal for this alternative
     *
     * @param null|string $goal
     */
    public function increment_completion($goal = null) {
        $field = $this->set_field($goal);
        $this->redis->hincrby($this->key, $field, 1);
    }

    /**
     * Check if this alternative is the belonging experiment's control alternative
     *
     * @return bool
     */
    public function is_control() {
        return $this->experiment()->control()->name === $this->name;
    }

    /**
     * Calculate the conversion rate for the goal(if any)
     *
     * @param null|string $goal
     *
     * @return float|int
     */
    public function conversion_rate($goal = null) {
        if ($this->participant_count() == 0) return 0;

        return $this->completed_count($goal) / $this->participant_count();
    }

    /**
     * Get the belonging experiment, return null if not found
     *
     * @return null|Experiment
     */
    public function experiment() {
        return \App::make('split_catalog')->find($this->experiment_name);
    }

    /**
     * Calculate the z-score of the alternative(use control for comparison).
     *
     * p_a = Pa = proportion of users who converted within the experiment split (conversion rate)
     * p_c = Pc = proportion of users who converted within the control split (conversion rate)
     * n_a = Na = the number of impressions within the experiment split
     * n_c = Nc = the number of impressions within the control split
     *
     * @param null|string $goal
     *
     * @return float|string
     */
    public function z_score($goal = null) {
        $control     = $this->experiment()->control();
        $alternative = $this;
        if ($control->name === $alternative->name) return 'N/A';

        $p_a = $alternative->conversion_rate($goal);
        $p_c = $control->conversion_rate($goal);

        $n_a = $alternative->participant_count();
        $n_c = $control->participant_count();

        return Zscore::calculate($p_a, $n_a, $p_c, $n_c);
    }

    /**
     * Save the alternative, used for initialize
     */
    public function save() {
        $this->redis->hsetnx($this->key, 'participant_count', 0);
        $this->redis->hsetnx($this->key, 'completed_count', 0);
        $this->redis->hsetnx($this->key, 'p_winner', $this->p_winner());
    }

    /**
     * check if this is alternative is valid
     *
     * @throws \InvalidArgumentException
     */
    public function validate() {
        if (!is_string($this->name))
            throw new \InvalidArgumentException('Alternative must be a string');
    }

    /**
     * Reset the alternative's data in the redis
     */
    public function reset() {
        $this->redis->hmset($this->key, ['participant_count' => 0, 'completed_count' => 0]);
        foreach ($this->goals() as $goal) {
            $field = "completed_count:$goal";
            $this->redis->hset($this->key, $field, 0);
        }
    }

    /**
     * Delete this alternative's data in the redis
     */
    public function delete() {
        $this->redis->del($this->key);
    }
}
