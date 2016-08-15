<?php
/**
 * Created by PhpStorm.
 * User: 风之凌殇
 * Date: 8/7/2016
 * Time: 12:39 PM
 */

namespace Split\Impl;


use Carbon\Carbon;
use gburtini\Distributions\Beta;
use Illuminate\Support\Collection;

use Split\Contracts\Algorithm\SamplingAlgorithm;

/**
 * Class Experiment
 *
 * @package Split\Impl
 */
class Experiment implements \ArrayAccess {
    /**
     * The name of the experiment
     *
     * @var string
     */
    public $name;

    /**
     * The Algorithm that the experiment use to choose alternative for user
     *
     * @var SamplingAlgorithm
     */
    protected $algorithm;

    /**
     * If true, when a user completes a test their session is reset so that they may start the test again in the future.
     *
     * @var bool
     */
    public $resettable;

    /**
     * The goals collection of the experiment.
     *
     * @var Collection
     */
    public $goals;

    /**
     * The alternatives of the user, and the first of which is considered as the Control group.
     *
     * @var Collection of Alternative
     */
    public $alternatives;/*fixme*/

    /**
     * Each alternative's winning probability.
     *
     * @var Collection
     */
    public $alternative_probabilities;

    /**
     * Set this when you need more than just the name of the alternative as the result.
     *
     * eg:
     * my_first_experiment: {
     *     alternatives: ["a", "b"],
     *     metadata: {
     *       "a" => {"text" => "Have a fantastic day"},
     *       "b" => {"text" => "Don't get hit by a bus"}
     *     }
     *   }
     *
     * @var Collection
     */
    public $metadata;

    const DEFAULT_OPTIONS = ['resettable' => true];

    /**
     * The version of the experiment, it will increment by one after experiment's reset.
     *
     * @var int
     */
    protected $version;

    /**
     * The key of the experiment's config data in the redis.
     *
     * @var string
     */
    protected $experiment_config_key;

    /**
     * Redis client
     */
    protected $redis;

    /**
     * The goals collection of the experiment.
     *
     * @var GoalsCollection
     */
    protected $goals_collection;

    /**
     * Experiment constructor.
     *
     * @param string           $name
     * @param array|Collection $options
     */
    public function __construct($name, $options = []) {
        $options = collect($options);
        $options = $options->merge(self::DEFAULT_OPTIONS);

        $this->name = $name;

        /*try to get alternative(array) from options*/
        $alternatives = $this->extract_alternatives_from_options($options);

        /*if not given, then try to get from configuration*/
        if ($alternatives->isEmpty() && ($exp_config = \App::make('split_config')->experiment_for($name))) {
            $options = collect(
                [
                    'alternati-ves' => $this->load_alternatives_from_configuration(),
                    'goals'         => (new GoalsCollection($name))->load_from_configuration(),
                    'metadata'      => $this->load_metadata_from_configuration(),
                    'resettable'    => Helper::value_for($exp_config, 'resettable'),
                    'algorithm'     => Helper::value_for($exp_config, 'algorithm'),
                ]
            );
            /*NOTE: check if need to load from redis when configuration is also failed*/
        } else {
            /*otherwise, update the options*/
            $options['alternatives'] = $alternatives;
        }

        $this->set_alternatives_and_options($options);

        $this->experiment_config_key = "experiment_configurations:$this->name";
        $this->redis                 = \App::make('split_redis');
    }

    /**
     * Helper for formatting experiment finished key
     *
     * @param null|string $key
     *
     * @return string
     */
    public function finished_key($key = null) {
        if (is_null($key)) $key = self::key();

        return "$key:finished";
    }

    /**
     * Set the alternatives and other settings according to the options
     *
     * @param Collection $options
     */
    public function set_alternatives_and_options($options) {
        $this->set_alternatives(Helper::value_for($options, 'alternatives'));
        $this->set_algorithm(Helper::value_for($options, 'algorithm'));
        $this->goals      = collect(Helper::value_for($options, 'goals'));
        $this->resettable = Helper::value_for($options, 'resettable');
        $this->metadata   = collect(Helper::value_for($options, 'metadata'));
    }

    /**
     * Get the alternative array from the options.
     *
     * PS: not in Alternative instance, but row data.
     * collect([[a=>1],[b=>2],c,d])
     *
     * @param Collection $options
     *
     * @return Collection of alternative's raw data
     */
    public function extract_alternatives_from_options($options) {
        $alts = collect(Helper::value_for($options, 'alternatives'));

        /*alts = [['a1'=>1,'a2'=>2,'a3',...]]*/
        if ($alts->count() == 1) {
            if (is_array($alts[0])) {
                $alts = $alts->collapse();
            }
        }

        return $this->normalize_raw_alternative($alts);
    }

    /**
     * Input :  ['blue'=>1,'red'=>2, 'green', 'blue']
     * Output:  [['blue'=>1],['red'=>2], 'green', 'blue']
     *
     * @param Collection $alts
     *
     * @return Collection
     */
    public function normalize_raw_alternative($alts) {
        /*alts = ['blue'=>1,'red'=>2] or ['blue','red']*/
        $normalized_alternatives = collect([]);
        foreach ($alts as $key => $val) {
            if (is_int($key)) {/*['blue','red']*/
                $normalized_alternatives->push($val);
            } else {/*['blue'=>1,'red'=>2]*/
                $normalized_alternatives->push([$key => $val]);
            }
        }

        return $normalized_alternatives;
    }

    /**
     * Save the experiment data to the Redis, if is new record or has changed
     *
     * @throws ExperimentNotFound
     */
    public function save() {
        $this->validate();

        if ($this->is_new_record()) {
            $this->redis->sadd('experiments', $this->name);
            if (!\App::make('split_config')->start_manually) $this->start();
            foreach ($this->alternatives as $alternative) {
                $this->redis->lpush($this->name, $alternative->name);
            }
            $this->goals_collection()->save();
            $this->save_metadata();
        } else {
            $existing_alternatives = $this->load_alternatives_from_redis();
            $existing_goals        = (new GoalsCollection($this->name))->load_from_redis();
            $existing_metadata     = $this->load_metadata_from_redis();
            /*check if the experiment's essential config has changed*/
            if (!(
                $existing_alternatives == $this->alternatives
                && $existing_goals == $this->goals
                && $existing_metadata == $this->metadata->toArray())/*note: cheeck if this always fail*/
            ) {
                /*if so, cleanup old data*/
                $this->reset();
                $this->alternatives->each(function (Alternative $a) { $a->delete(); });
                $this->goals_collection()->delete();
                $this->delete_metadata();
                $this->redis->del($this->name);
                /*and then save new data*/
                $this->alternatives->each(function (Alternative $a) { $a->save(); });
                $this->goals_collection()->save();
                $this->save_metadata();
                $this->alternatives->reverse()->each(function (Alternative $a) { $this->redis->lpush($this->name, json_encode([$a->name => $a->weight])); });
            }
        }

        $this->redis->hset($this->experiment_config_key, 'resettable', $this->resettable);
        $this->redis->hset($this->experiment_config_key, 'algorithm', collect(explode('\\', get_class($this->algorithm())))->last());
    }

    /**
     * Validate this experiment
     *
     * @throws ExperimentNotFound
     */
    public function validate() {
        if ($this->alternatives->isEmpty() && \App::make('split_config')->experiment_for($this->name) === null) {
            require_once app_path('Impl/exceptions.php');
            throw new ExperimentNotFound("Experiment $this->name not found");
        }
        $this->alternatives->each(function (Alternative $a) { $a->validate(); });
        $this->goals_collection()->validate();
    }

    /**
     * Check if this experiment is a new record
     *
     * @return bool
     */
    public function is_new_record() {
        return !$this->redis->exists($this->name);
    }

    /**
     * Check if two experiment is the same one
     *
     * @param Experiment $obj
     *
     * @return bool
     */
    public function euqals($obj) {
        return $this->name == $obj->name;
    }

    /**
     * Get experiment's sampling algorithm
     *
     * @return SamplingAlgorithm
     */
    public function algorithm() {
        if (is_null($this->algorithm)) {
            $algorithm       = \App::make('split_config')->algorithm;
            $this->algorithm = new $algorithm();
        }

        return $this->algorithm;
    }

    /**
     * Set algorithm for the experiment, should implement Interface SamplingAlgorithm
     *
     * @param SamplingAlgorithm|string $algorithm
     *
     * @throws \InvalidArgumentException
     */
    public function set_algorithm($algorithm) {
        if (is_string($algorithm)) {
            $algorithms = \App::make('split_config')->available_algorithms;
            if (array_key_exists($algorithm, $algorithms)) {
                $algorithm = new $algorithms[$algorithm]();
            } else {
                throw new \InvalidArgumentException('No such algorithm exists');
            }
        }
        $this->algorithm = $algorithm;
    }

    /**
     * Set resettable property
     *
     * @param string|bool $resettable
     */
    public function set_resettable($resettable) {
        if (is_string($resettable)) {
            $this->resettable = $resettable === 'true';
        } else {
            $this->resettable = $resettable;
        }
    }


    /**
     * Set alternatives
     *
     * @param Collection|array $alts
     */
    public function set_alternatives($alts) {
        $this->alternatives = collect($alts)->map(function ($alternative) {
            if ($alternative instanceof Alternative) {
                return $alternative;
            } else {
                /*'blue' or ['blue'=>23]*/
                return new Alternative($alternative, $this->name);
            }
        });
    }

    /**
     * Get winner from redis
     *
     * @return null|Alternative
     */
    public function winner() {
        if ($w = $this->redis->hget('experiment_winner', $this->name)) {
            return new Alternative($w, $this->name);
        } else {
            return null;
        }
    }

    /**
     * Check if there is a winner for this experiment
     *
     * @return bool
     */
    public function has_winner() {
        return $this->winner() !== null;
    }

    /**
     * Set winner name to redis
     *
     * @param string $winner_name
     */
    public function set_winner($winner_name) {
        $this->redis->hset('experiment_winner', $this->name, $winner_name);
    }

    /**
     * Get experiment's participant count
     *
     * @return int
     */
    public function participant_count() {
        return $this->alternatives->sum(function (Alternative $a) {
            return $a->participant_count();
        });
    }

    /**
     * Get experiment's control
     *
     * @return Alternative
     */
    public function control() {
        return $this->alternatives->first();
    }

    /**
     * Reset winner in the redis
     */
    public function reset_winner() {
        $this->redis->hdel('experiment_winner', $this->name);
    }

    /**
     * Start the experiment, namely set the start time key in the redis
     */
    public function start() {
        $this->redis->hset('experiment_start_times', $this->name, Carbon::now()->timestamp);
    }

    /**
     * Get start time from redis, and make into Carbon object
     *
     * @return Carbon
     */
    public function start_time() {
        $t = $this->redis->hget('experiment_start_times', $this->name);
        if ($t) {
            if (preg_match('/^[-+]?[0-9]+$/', $t)) {
                return Carbon::createFromTimestamp($t);
            } else {
                return new Carbon($t);
            }
        }
    }

    /**
     * Get a random alternative, except when this experiment already has winner the winner will be returned
     *
     * @return Alternative
     */
    public function next_alternative() {
        if ($this->has_winner()) {
            return $this->winner();
        }

        return $this->random_alternative();
    }

    /**
     * Get a random alternative from the alternatives
     *
     * @return Alternative
     */
    public function random_alternative() {
        if ($this->alternatives->count() > 1) {
            return $this->algorithm()->choose_alternative($this);
        } else {
            return $this->alternatives->first();
        }
    }

    /**
     * Get the version of the experiment
     *
     * @return int
     */
    public function version() {
        if (is_null($this->version)) {
            $this->version = (int)$this->redis->get("$this->name:version");
        }

        return $this->version;
    }

    /**
     * Increment the version of experiment
     */
    public function increment_version() {
        $this->version = $this->redis->incr("$this->name:version");
    }

    /**
     * Get the redis key of the experiment
     *
     * @return string
     */
    public function key() {
        if ($this->version() > 0) {
            return "$this->name:" . $this->version();
        } else {
            return $this->name;
        }
    }

    /**
     * Get the redis key for the goals
     *
     * @return string
     */
    public function goals_key() {
        return "$this->name:goals";
    }

    /**
     * Get the redis key for the metadata
     *
     * @return string
     */
    public function metadata_key() {
        return "$this->name:metadata";
    }

    /**
     * Check if this experiment is resettable
     *
     * @return bool
     */
    public function is_resettable() {
        return $this->resettable;
    }

    /**
     * Reset the experiment
     */
    public function reset() {
        call_user_func(\App::make('split_config')->on_before_experiment_reset, $this);

        $this->alternatives->each(function (Alternative $a) { $a->reset(); });
        $this->reset_winner();
        $this->increment_version();

        call_user_func(\App::make('split_config')->on_experiment_reset, $this);
    }

    /**
     * Delete the experiment in the redis
     */
    public function delete() {
        call_user_func(\App::make('split_config')->on_before_experiment_delete, $this);

        if (\App::make('split_config')->start_manually) {
            $this->redis->hdel('experiment_start_times', $this->name);
        }
        $this->alternatives->each(function (Alternative $a) { $a->delete(); });
        $this->reset_winner();
        $this->redis->srem('experiments', $this->name);
        $this->redis->del($this->name);
        $this->goals_collection()->delete();
        $this->delete_metadata();
        $this->increment_version();

        call_user_func(\App::make('split_config')->on_experiment_delete, $this);
    }

    /**
     * Delete the metadata
     */
    public function delete_metadata() {
        $this->redis->del($this->metadata_key());
    }

    /**
     * Load experiment data from redis
     */
    public function load_from_redis() {
        $exp_config = $this->redis->hgetall($this->experiment_config_key);

        $options = collect(
            [
                'resettable'   => $exp_config['resettable'],
                'algorithm'    => $exp_config['algorithm'],
                'alternatives' => $this->load_alternatives_from_redis(),
                'goals'        => (new GoalsCollection($this->name))->load_from_redis(),
                'metadata'     => $this->load_metadata_from_redis(),
            ]
        );

        $this->set_alternatives_and_options($options);
    }


    /**
     * Calculate the winning alternative
     */
    public function calc_winning_alternatives() {
        # Super simple cache so that we only recalculate winning alternatives once per day
        $days_since_epoch = intval(Carbon::now()->timestamp / 86400);

        if ($this->calc_time() != $days_since_epoch) {
            if ($this->goals->isEmpty()) {
                $this->estimate_winning_alternative();
            } else {
                $this->goals->each(function ($goal) {
                    $this->estimate_winning_alternative($goal);
                });
            }

            $this->set_calc_time($days_since_epoch);

            $this->save();
        }
    }

    /**
     * Do $beta_probability_simulations times simulations to find out which alternative is more likely to be the winner
     *
     * @param null|string $goal
     */
    public function estimate_winning_alternative($goal = null) {
        # TODO - refactor out functionality to work with and without goals

        # initialize a hash of beta distributions based on the alternatives' conversion rates
        $beta_params = $this->calc_beta_params($goal);

        $winning_alternatives = collect([]);

        for ($i = 0; $i < \App::make('split_config')->beta_probability_simulations; ++$i) {
            # calculate simulated conversion rates from the beta distributions
            $simulated_cr_hash = $this->calc_simulated_conversion_rates($beta_params);

            $winning_alternative = $this->find_simulated_winner($simulated_cr_hash);

            # push the winning pair to the winning_alternatives array
            $winning_alternatives->push($winning_alternative);/*name of alternative*/
        }

        $winning_counts = $this->count_simulated_wins($winning_alternatives); /*name=>counts*/

        $this->alternative_probabilities = $this->calc_alternative_probabilities($winning_counts, \App::make('split_config')->beta_probability_simulations);/*name=>prob*/

        $this->write_to_alternatives($goal);

        $this->save();
    }

    /**
     * Save each alternative's winning probabilities to the redis
     *
     * @param null|string $goal
     */
    public function write_to_alternatives($goal = null) {
        foreach ($this->alternatives as $alternative) {
            /* @var Alternative $alternative */
            $alternative->set_p_winner($this->alternative_probabilities[(string)$alternative], $goal);/*key cannot be object, use __string{name} instead*/
        }
    }

    /**
     * Use each alternative's winning counts to find out the winning probability
     *
     * @param Collection $winning_counts
     * @param int        $number_of_simulations
     *
     * @return Collection [alt name => win prob]
     */
    public function calc_alternative_probabilities(Collection $winning_counts, $number_of_simulations) {
        $alternative_probabilities = collect([]);
        foreach ($winning_counts as $alternative_name => $wins) {
            $alternative_probabilities[$alternative_name] = $wins / $number_of_simulations;
        }

        return $alternative_probabilities;
    }


    /**
     * Count each alternative's winning counts in the simulations
     *
     * @param Collection $winning_alternatives name of the winner
     *
     * @return Collection [alt_name=>win_counts]
     *
     */
    public function count_simulated_wins($winning_alternatives) {
        # initialize a hash to keep track of winning alternative in simulations
        $winning_counts = collect([]);
        foreach ($this->alternatives as $alternative) {
            $winning_counts[(string)$alternative] = 0;
        }
        foreach ($winning_alternatives as $winner_name) {
            $winning_counts[$winner_name] += 1;
        }

        return $winning_counts;
    }

    /**
     * Find out the winner in the simulations according to winning counts
     *
     * @param Collection $simulated_cr_hash
     *
     * @return string Name of the Alternative
     */
    public function find_simulated_winner($simulated_cr_hash) {
        # figure out which alternative had the highest simulated conversion rate
        $winning_pair = ['', 0.0];/*[name=>rate]*/
        foreach ($simulated_cr_hash as $alternative_name => $rate) {
            if ($rate > $winning_pair[1]) {
                $winning_pair = [$alternative_name, $rate];
            }
        }
        $winner_name = $winning_pair[0];

        return $winner_name;
    }

    /**
     * Do a simulation and guess the alternative's conversion rate
     *
     * @param Collection $beta_params [alpha, beta]
     *
     * @return Collection alt_name=>cv
     */
    public function calc_simulated_conversion_rates($beta_params) {
        $simulated_cr_hash = collect([]);

        # create a hash which has the conversion rate pulled from each alternative's beta distribution
        foreach ($beta_params as $alternative_name => $params) {
            list($alpha, $beta) = $params;
            $simulated_conversion_rate            = (new Beta($alpha, $beta))->rand();
            $simulated_cr_hash[$alternative_name] = $simulated_conversion_rate;
        }

        return $simulated_cr_hash;
    }

    /**
     * Calculate the alternative's beta params, that is, alpha and beta
     *
     * @param null|string $goal
     *
     * @return Collection [alpha, beta]
     */
    public function calc_beta_params($goal = null) {
        $beta_params = collect([]);
        foreach ($this->alternatives as $alternative) {
            /* @var Alternative $alternative */
            $conversions = $alternative->completed_count($goal);
            $alpha       = 1 + $conversions;
            $beta        = 1 + $alternative->participant_count() - $conversions;

            $params = [$alpha, $beta];

            $beta_params[(string)$alternative] = $params;
        }

        return $beta_params;
    }

    /**
     * Save the calculate winner's time(days since epoch) to the redis
     *
     * @param int $time
     */
    public function set_calc_time($time) {
        $this->redis->hset($this->experiment_config_key, 'calc_time', $time);
    }

    /**
     * Get the last calculate winner's time from the redis
     *
     * @return int
     */
    public function calc_time() {
        return (int)$this->redis->hget($this->experiment_config_key, 'calc_time');
    }

    /**
     * Convert php string(id for js) into js string
     *
     * @param null|string $goal
     *
     * @return string
     */
    public function jstring($goal = null) {
        $js_id = $this->name;
        if ($goal) $js_id .= "-$goal";

        return preg_replace('#/#', '--', $js_id);
    }

    /* protected functions*/

    /**
     * data format: [
     *      'alt_1' => [ 'text' => 'this is a test', ....],
     *      'alt 2' => [ 'text' => 'THIS IS A TEST', ....],
     *      ......
     * ]
     */
    protected function load_metadata_from_configuration() {
        $this->metadata = \App::make('split_config')->experiment_for($this->name)['metadata'];
    }

    /**
     * data format: [
     *      'alt_1' => [ 'text' => 'this is a test', ....],
     *      'alt 2' => [ 'text' => 'THIS IS A TEST', ....],
     *      ......
     * ]
     *
     * @return array
     */
    protected function load_metadata_from_redis() {
        $meta = $this->redis->get($this->metadata_key());
        if ($meta) {
            return json_decode($meta);
        }
    }

    /**
     * Get the experiment's alternatives data from configuration in raw array, not Alternative instance.
     *
     * @return Collection of alternative's raw data
     */
    protected function load_alternatives_from_configuration() {
        $alts = collect(Helper::value_for(\App::make('split_config')->experiment_for($this->name), 'alternatives'));
        if ($alts->isEmpty()) {
            throw new \InvalidArgumentException("Experiment configuration is missing alternatives array");
        }

        return $this->normalize_raw_alternative($alts);
    }

    /**
     * Used for check if need to rewrite alternatives to the redis
     *
     * @return Collection
     */
    protected function load_alternatives_from_redis() {
        $type = $this->redis->type($this->name);
        if ($type == 'set') {
            $alts = collect($this->redis->smembers($this->name));
            $this->redis->del($this->name);
            $alts->reverse()->each(function ($a) { $this->redis->lpush($this->name, $a); });
        }

        /*fixme:::::::[a=>123],a*/

        return collect($this->redis->lrange($this->name, 0, -1))->map(function ($alt_name) {
            return new Alternative(json_decode($alt_name), $this->name);
        });
    }

    /**
     * Save the metadata to the redis.
     *
     * data format: [
     *      'alt_1' => [ 'text' => 'this is a test', ....],
     *      'alt 2' => [ 'text' => 'THIS IS A TEST', ....],
     *      ......
     * ]
     */
    protected function save_metadata() {
        if ($this->metadata) {
            $this->redis->set($this->metadata_key(), json_encode($this->metadata));
        }
    }

    /* private */

    /**
     * Get the goals collection
     *
     * @return GoalsCollection
     */
    private function goals_collection() {
        if (is_null($this->goals_collection)) {
            $this->goals_collection = new GoalsCollection($this->name, $this->goals);
        }

        return $this->goals_collection;
    }

    /**
     * Whether a offset exists
     *
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
    public function offsetExists($name) {
        return $this->alternatives->first(function ($a) use ($name) {
            return $a->name == $name;
        }) != null;
    }

    /**
     * Offset to retrieve
     *
     * @link  http://php.net/manual/en/arrayaccess.offsetget.php
     *
     * @param mixed $offset <p>
     *                      The offset to retrieve.
     *                      </p>
     *
     * @return mixed Can return all value types.
     * @since 5.0.0
     */
    public function offsetGet($name) {
        return $this->alternatives->first(function ($a) use ($name) {
            return $a->name == $name;
        });
    }

    /**
     * Offset to set
     *
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
        // TODO: Implement offsetSet() method.
    }

    /**
     * Offset to unset
     *
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
        // TODO: Implement offsetUnset() method.
    }

    public function complete() {
        /*fixme: add it if necessary, maybe used in Stop/End experiment*/
    }
}