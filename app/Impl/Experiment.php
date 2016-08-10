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
use Illuminate\Support\Facades\Redis;
use Split\Contracts\Algorithm\SamplingAlgorithm;

class Experiment implements \ArrayAccess {
    public $name;
    protected $algorithm;
    public $resettable;
    /**
     * @var Collection
     */
    public $goals;
    /**
     * @var Collection
     */
    public $alternatives;
    /**
     * @var Collection
     */
    public $alternative_probabilities;
    public $metadata;

    const DEFAULT_OPTIONS = ['resettable' => true];
    protected $version;
    protected $experiment_config_key;

    /**
     * Experiment constructor.
     *
     * @param $name
     */
    public function __construct($name, $options = []) {
        $options = collect($options);
        $options = $options->merge(self::DEFAULT_OPTIONS);

        $this->name = $name;

        $this->alternatives = $this->extract_alternatives_from_options($options);

        if ($this->alternatives->isEmpty() && ($exp_config = Configuration::experiment_for($name))) {
            $options = collect(
                [
                    'alternatives' => $this->load_alternatives_from_configuration(),
                    'goals'        => (new GoalsCollection($name))->load_from_configuration(),
                    'metadata'     => $this->load_metadata_from_configuration(),
                    'resettable'   => $exp_config['resettable'],
                    'algorithm'    => $exp_config['algorithm'],
                ]
            );
        } else {
            $options['alternatives'] = $this->alternatives;
        }

        $this->set_alternatives_and_options($options);
        $this->experiment_config_key = "experiment_configurations/$this->name";
    }

    public static function finished_key($key = null) {
        if ($key) return "$key:finished"; // finished_key($key)
        return self::finished_key(self::key());//when called finished(), set key to key()
    }

    public function set_alternatives_and_options($options) {
        $this->alternatives = $options['alternatives'];
        $this->goals = $options['goals'];
        $this->resettable = $options['resettable'];
        $this->algorithm = $options['algorithm'];
        $this->metadata = $options['metadata'];
    }

    public function extract_alternatives_from_options($options) {
        $alts = $options['alternatives'];
        if (!$alts) $alts = collect([]);
        /* @var $alts Collection */

        if ($alts->count() == 1) {
            if ($alts[0] instanceof Collection) {
                $alts = $alts[0]->map(function ($item, $key) {
                    return [$key => $item];
                });
            }
        }

        if ($alts->isEmpty()) {
            $exp_config = Configuration::experiment_for($this->name);
            if ($exp_config) {
                $alts = $this->load_alternatives_from_configuration();
                $options['goals'] = (new GoalsCollection($this->name))->load_from_configuration();
                $options['metadata'] = $this->load_metadata_from_configuration();
                $options['resettable'] = $exp_config['resettable'];
                $options['algorithm'] = $exp_config['algorithm'];
            }
        }

        $options['alternatives'] = $alts;
        $this->set_alternatives_and_options($options);

        # calculate probability that each alternative is the winner
        $this->alternative_probabilities = collect([]);

        return $alts;
    }

    public function save() {
        $this->validate();

        if ($this->is_new_record()) {
            Redis::sadd('experiments', $this->name);
            if (!Configuration::start_manually) $this->start();
            $this->alternatives->each(function ($a) { Redis::lpush($this->name, $a->name); });
            $this->goals_collection()->save();
            $this->save_metadata();
        } else {
            $existing_alternatives = $this->load_alternatives_from_redis();
            $existing_goals = (new GoalsCollection($this->name))->load_from_redis();
            $existing_metadata = $this->load_metadata_from_redis();
            /*fixme: altertives.map(&:name)*/
            if (!($existing_alternatives == $this->alternatives->map(function (Alternative $a) { return $a->name; }) && $existing_goals == $this->goals && $existing_metadata == $this->metadata)) {
                $this->reset();
                $this->alternatives->each(function (Alternative $a) { $a->delete(); });
                $this->goals_collection()->delete();
                $this->delete_metadata();
                Redis::del($this->name);
                $this->alternatives->reverse()->each(function (Alternative $a) { Redis::lpush($this->name, $a->name); });
                $this->goals_collection()->save();
                $this->save_metadata();
            }
        }

        Redis::hset($this->experiment_config_key, 'resettable', $this->resettable);
        Redis::hset($this->experiment_config_key, 'algorithm', $this->algorithm);
    }

    /*TODO : validate*/
    public function validate() {
        if ($this->alternatives->isEmpty() && Configuration::experiment_for($this->name) == null)
            throw new ExperimentNotFound("Experiment $this->name not found");
        $this->alternatives->each(function ($a/* @var $a Alternative*/) { $a->validate(); });
        $this->goals_collection()->validate();
    }

    public function is_new_record() {
        return !Redis::exists($this->name);
    }

    public function euqals($obj) {
        return $this->name == $obj->name;
    }

    /**
     * @return SamplingAlgorithm
     */
    public function algorithm() {
        if ($this->algorithm == null) {
            $this->algorithm = Configuration::algorithm;
        }

        return $this->algorithm;
    }

    public function set_algorithm($algorithm) {
        /*fixme: constantize*/
        if (is_string($algorithm)) {
            //when $algorithn is "WeightedSample" or "Whiplash"
            $algorithm = "Algorithm/$algorithm";
            $this->algorithm = new $algorithm();
        } else {
            $this->algorithm = $algorithm;
        }
    }

    public function set_resettable($resettable) {
        if (is_string($resettable)) {
            $this->resettable = $resettable == 'true';
        } else {
            $this->resettable = $resettable;
        }
    }


    /**
     * @param $alts \Illuminate\Support\Collection
     */
    public function set_alternatives($alts) {
        $this->alternatives = $alts->map(function ($alternative) {
            if ($alternative instanceof Alternative) {
                return $alternative;
            } else {
                return new Alternative($alternative, $this->name);
            }
        });
    }

    public function winner() {
        if ($w = Redis::hget('experiment_winner', $this->name)) {
            return new Alternative($w, $this->name);
        } else {
            return null;
        }
    }

    public function has_winner() {
        return $this->winner() != null;
    }

    public function set_winner($winner_name) {
        Redis::hset('experiment_winner', $this->name, $winner_name);
    }

    public function participant_count() {
        return $this->alternatives->sum(function ($a/* @var $a Alternative */) {
            return $a->participant_count();
        });
    }

    /**
     * @return Alternative
     */
    public function control() {
        return $this->alternatives->first();
    }

    public function reset_winner() {
        Redis::hdel('experiment_winner', $this->name);
    }

    public function start() {
        Redis::hset('experiment_start_times', $this->name, Carbon::now()->timestamp);
    }

    public function start_time() {
        $t = Redis::hget('experiment_start_times', $this->name);
        if ($t) {
            if (preg_match('^[-+]?[0-9]+$', $t)) {
                return Carbon::createFromTimestamp($t);
            } else {
                return new Carbon($t);
            }
        }
    }

    public function next_alternative() {
        if ($this->has_winner()) {
            return $this->winner();
        }

        return $this->random_alternative();
    }

    public function random_alternative() {
        if ($this->alternatives->count() > 1) {
            return $this->algorithm()->choose_alternative($this);
        } else {
            return $this->alternatives->first();
        }
    }

    public function version() {
        if (!$this->version) {
            $this->version = (int)Redis::get("$this->name:version");
        }

        return $this->version;
    }

    public function increment_version() {
        $this->version = Redis::incr("$this->name:version");
    }

    public function key() {
        if ($this->version() > 0) {
            return "$this->name:$this->version()";
        } else {
            return $this->name;
        }
    }

    public function goals_key() {
        return "$this->name:goals";
    }

    public function metadata_key() {
        return "$this->name:metadata";
    }

    public function is_resettable() {
        return $this->resettable;
    }

    public function reset() {
        Configuration::on_before_experiment_reset($this);
        $this->alternatives->each(function ($a/* @var $a Alternative */) { $a->reset(); });
        $this->reset_winner();
        Configuration::on_experiment_reset($this);
        $this->increment_version();
    }

    public function delete() {
        Configuration::on_before_experiment_delete($this);
        if (Configuration::start_manually) {
            Redis::hdel('experiment_start_times', $this->name);
        }
        $this->alternatives->each(function ($a/* @var $a Alternative */) { $a->delete(); });
        $this->reset_winner();
        Redis::srem('experiments', $this->name);
        Redis::del($this->name);
        $this->goals_collection()->delete();
        $this->delete_metadata();
        Configuration::on_experiment_delete($this);
        $this->increment_version();
    }

    public function delete_metadata() {
        Redis::del($this->metadata_key());
    }

    public function load_from_redis() {
        $exp_config = Redis::hgetall($this->experiment_config_key);

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

    public function calc_winning_alternatives() {
        # Super simple cache so that we only recalculate winning alternatives once per day
        $days_since_epoch = Carbon::now()->timestamp / 86400;

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

    public function estimate_winning_alternative($goal = null) {
        # TODO - refactor out functionality to work with and without goals

        # initialize a hash of beta distributions based on the alternatives' conversion rates
        $beta_params = $this->calc_beta_params($goal);

        $winning_alternatives = collect([]);

        for ($i = 0; $i < Configuration::beta_probability_simulations; ++$i) {
            # calculate simulated conversion rates from the beta distributions
            $simulated_cr_hash = $this->calc_simulated_conversion_rates($beta_params);

            $winning_alternative = $this->find_simulated_winner($simulated_cr_hash);

            # push the winning pair to the winning_alternatives array
            $winning_alternatives->push($winning_alternative);/*Alternative*/
        }

        $winning_counts = $this->count_simulated_wins($winning_alternatives); /*name=>counts*/

        $this->alternative_probabilities = $this->calc_alternative_probabilities($winning_counts, Configuration::beta_probability_simulations);/*name=>prob*/

        $this->write_to_alternatives($goal);

        $this->save();
    }

    public function write_to_alternatives($goal = null) {
        $this->alternatives->each(function ($alternative/* @var $alternative Alternative */) use ($goal) {
            $alternative->set_p_winner($this->alternative_probabilities[(string)$alternative], $goal);/*key cannot be object, use __string{name} instead*/
        });
    }

    public function calc_alternative_probabilities(Collection $winning_counts, $number_of_simulations) {
        $alternative_probabilities = collect([]);
        $winning_counts->each(function ($wins, $alternative_name/*string*/) use ($alternative_probabilities, $number_of_simulations) {
            $alternative_probabilities[$alternative_name] = $wins / $number_of_simulations;
        });

        return $alternative_probabilities;
    }


    /**
     * @param $winning_alternatives Collection
     *
     * @return Collection
     * [alt_name=>win_counts]
     */
    public function count_simulated_wins($winning_alternatives) {
        # initialize a hash to keep track of winning alternative in simulations
        $winning_counts = collect([]);
        $this->alternatives->each(function ($alternative) use ($winning_counts) {
            $winning_counts[(string)$alternative] = 0;
        });
        # count number of times each alternative won, calculate probabilities, place in hash
        $winning_alternatives->each(function ($alternative) use ($winning_counts) {
            $winning_counts[(string)$alternative] += 1;
        });

        return $winning_counts;
    }

    /**
     * @param $simulated_cr_hash Collection
     */
    public function find_simulated_winner($simulated_cr_hash) {
        # figure out which alternative had the highest simulated conversion rate
        $winning_pair = ['', 0.0];/*[name=>rate]*/
        $simulated_cr_hash->each(function ($rate, $alternative_name) use ($winning_pair) {
            if ($rate > $winning_pair[1]) {
                $winning_pair = [$alternative_name, $rate];
            }
        });
        $this->set_winner($winning_pair[0]);

        return $this->winner();
    }

    /**
     * @param $beta_params Collection
     */
    public function calc_simulated_conversion_rates($beta_params) {
        $simulated_cr_hash = collect([]);

        # create a hash which has the conversion rate pulled from each alternative's beta distribution
        $beta_params->each(function ($params, $alternative_name) use ($simulated_cr_hash) {
            $alpha = $params[0];
            $beta = $params[1];
            $simulated_conversion_rate = (new Beta($alpha, $beta))->rand();
            $simulated_cr_hash[$alternative_name] = $simulated_conversion_rate;
        });

        return $simulated_cr_hash;
    }

    public function calc_beta_params($goal = null) {
        $beta_params = collect([]);
        $this->alternatives->each(function ($alternative/* @var Alternative $alternative */) use ($beta_params, $goal) {
            $conversions = $alternative->completed_count($goal);
            $alpha = 1 + $conversions;
            $beta = 1 + $alternative->participant_count() - $conversions;

            $params = [$alpha, $beta];

            $beta_params[(string)$alternative] = $params;
        });

        return $beta_params;
    }

    public function set_calc_time($time) {
        Redis::hset($this->experiment_config_key, 'calc_time', $time);
    }

    public function calc_time() {
        return (int)Redis::hget($this->experiment_config_key, 'calc_time');
    }

    public function jstring($goal = null) {
        $js_id = $this->name;
        if ($goal) $js_id .= "-$goal";

        return preg_replace('#/#', '--', $js_id);
    }

    /* protected functions*/

    protected function load_metadata_from_configuration() {
        $this->metadata = Configuration::experiment_for($this->name)['metadata'];
    }

    protected function load_metadata_from_redis() {
        $meta = Redis::get($this->metadata_key());
        if ($meta) {
            return json_decode($meta);
        }
    }

    /**
     * from http://stackoverflow.com/a/173479
     *
     * @param $collection Collection
     *
     * @return bool
     */
    protected function isAssoc($collection) {
        return $collection->keys()->toArray() !== range(0, $collection->count() - 1);
    }

    protected function load_alternatives_from_configuration() {
        $alts = Configuration::experiment_for($this->name)['alternatives'];
        /* @var $alts Collection */
        if (!$alts) {
            throw new \InvalidArgumentException("Experiment configuration is missing alternatives array");
        }
        /*TODO: check diff betwwen assoc and seque*/
        if ($this->isAssoc($alts)) {/* case when [blue=>xxxx, red=> yyyy, green=>zzzz]]*/
            return $alts->keys();
        } else {/* case when [blue, red, green]*/
            return $alts->flatten();
        }
    }

    protected function load_alternatives_from_redis() {
        $type = Redis::type($this->name);
        if ($type == 'set') {
            $alts = collect(Redis::smember($this->name));
            Redis::del($this->name);
            $alts->reverse()->each(function ($a) { Redis::lpush($this->name, $a); });
        }

        return Redis::lrange($this->name, 0, -1);
    }

    protected function save_metadata() {
        if ($this->metadata) {
            Redis::set($this->metadata_key(), json_encode($this->metadata));
        }
    }

    /* private */

    private function goals_collection(){
        return new GoalsCollection($this->name,$this->goals);
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
    public function offsetExists($name) {
        return $this->alternatives->first(function ($a) use ($name) {
            return $a->name == $name;
        }) != null;
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
    public function offsetGet($name) {
        return $this->alternatives->first(function ($a) use ($name) {
            return $a->name == $name;
        });
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
        // TODO: Implement offsetSet() method.
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
        // TODO: Implement offsetUnset() method.
    }
}