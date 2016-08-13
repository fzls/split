<?php
/**
 * Created by PhpStorm.
 * User: thd
 * Date: 8/8/2016
 * Time: 10:45 AM
 */

namespace Split\Impl;


use Illuminate\Support\Collection;

/**
 * Class ExperimentCatalog
 * @package Split\Impl
 */
class ExperimentCatalog {
    protected $redis;

    /**
     * ExperimentCatalog constructor.
     *
     * @param $redis
     */
    public function __construct() { $this->redis = \App::make('split_redis'); }


    /**
     * Return all the experiments
     *
     * @return Collection
     */
    public function all() {
        return collect($this->redis->smembers('experiments'))->map(function ($es) {
            return $this->find($es);
        })->reject(function ($e) {
            return $e === null;
        });
    }


    /**
     * Get the experiments, and make those has no winner to be the first
     * 
     * @return Collection Of Experiment
     */
    public function all_active_first() {
        return self::all()->groupBy(function (Experiment $e) {
            return intval($e->has_winner());/*0=>not winners, 1=> winner*/
        })->map(function (Collection $es) {
            return $es->sortBy(function (Experiment $e) {
                return $e->name;
            });
        })->flatten();
    }

    /**
     * Find a experiment by name
     *
     * @param string $name
     *
     * @return null|Experiment
     */
    public function find($name) {
        if (!$this->redis->exists($name)) return null;
        $e = new Experiment($name);
        $e->load_from_redis();

        return $e;
    }

    /**
     * Find a experiment and initialize it
     *
     * @param Collection|string             $metric_descriptor
     * @param null|string|Collection $control
     * @param null|Collection        $alternatives
     *
     * @return Experiment
     */
    public function find_or_initialize($metric_descriptor, $control = null, $alternatives = null) {
        if (!$alternatives instanceof Collection) {
            $alternatives = collect(func_get_args())->splice(2);
        }
        if ($control instanceof Collection && $alternatives->count() == 0) {
            list($control, $alternatives) = [$control->shift(), $control];
        }

        list($experiment_name_with_version, $goals) = $this->normalize_experiment($metric_descriptor);
        $experiment_name = explode(':', $experiment_name_with_version)[0];

        return new Experiment($experiment_name, [
            'alternatives' => is_null($control) ? $alternatives : $alternatives->prepend($control),
            'goals'        => $goals,
        ]);
    }

    /**
     * Find a experiment and save it to redis
     *
     * @param Collection             $metric_descriptor
     * @param null|string|Collection $control
     * @param null|Collection        $alternatives
     *
     * @return Experiment
     */
    public function find_or_create($metric_descriptor, $control = null, $alternatives = null) {
        $alternatives = collect(func_get_args())->splice(2);
        $experiment = self::find_or_initialize($metric_descriptor, $control, $alternatives);
        $experiment->save();

        return $experiment;
    }

    /**
     * @param $metric_descriptor
     *
     * @return array
     */
    private function normalize_experiment($metric_descriptor) {
        if ($metric_descriptor instanceof Collection) {
            $experiment_name = $metric_descriptor->keys()->first();
            $goals = collect($metric_descriptor->values()->first());
        } else {
            $experiment_name = $metric_descriptor;
            $goals = collect([]);
        }

        return [$experiment_name, $goals];
    }
}