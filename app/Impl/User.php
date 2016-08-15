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

/**
 * Class User
 *
 * @package Split\Impl
 */
class User implements ArrayLike {
    /**
     * The handle to the adapter, used to save the user's data
     *
     * @var ArrayLike
     */
    public $user;

    /**
     * User constructor.
     *
     * @param $user
     */
    public function __construct() {
        $this->user = \App::make('split_adapter');
    }

    /**
     * Cleanup the old(unused) experiments data saved in user
     */
    public function cleanup_old_experiments() {
        /*find out the name of the experiments that user has taken*/
        $this->keys_without_finished($this->user->keys())
            /*for each of these experiments*/
             ->each(function ($key) {
                $experiment = \App::make('split_catalog')->find($this->key_without_version($key));
                /*if experiment has been deleted OR has decided the winner OR has no longer running*/
                if (is_null($experiment) || $experiment->has_winner() || is_null($experiment->start_time())) {
                    /*delete it's key*/
                    $this->user->delete($key);
                    $this->user->delete("$key:finished");
                }
            });
    }

    /**
     * Check if this user has taken too much experiments.
     *
     * @param string $experiment_key
     *
     * @return bool
     */
    public function is_max_experiments_reached($experiment_key) {
        /*if allow a user to take multiple experiments, and only allow one of them to be not control group*/
        if (\App::make('split_config')->allow_multiple_experiments === 'control') {
            /*find out the active experiments result that user has take : [result, control]*/
            $enrolled_experiment_result_and_controls = $this->active_experiments();

            foreach ($enrolled_experiment_result_and_controls as $experiment_result_and_control) {
                list($result, $control) = $experiment_result_and_control;
                /*if user has assigned not control alternative in one of the result, then return true, and only assign control alternative to him(her)*/
                if ($result !== $control) {
                    return true;
                }
            }

            return false;
        } else {
            /*otherwise, it is true of false, by default it is set to false*/
            return !\App::make('split_config')->allow_multiple_experiments && $this->keys_without_experiment($this->user->keys(), $experiment_key)->count() > 0;
        }
    }

    /**
     * Cleanup this experiment's old version's data which is saved in the user.
     *
     * @param Experiment $experiment
     */
    public function cleanup_old_versions($experiment) {
        /*find the old version's key as well as the current version*/
        $keys = $this->user->keys()->filter(function ($k) use ($experiment) {
            return preg_match("#$experiment->name#", $k);
        });

        /*then find out those old version's key in them*/
        $this->keys_without_experiment($keys, $experiment->key())
            /*for each of these old version's key*/
             ->each(function ($key) {
                /*delete it*/
                $this->user->delete($key);
            });
    }

    /**
     * Find out the experiments that this user has participated and not has winner yet.
     *
     * @return Collection [exp_name=>[user_alt_name, control_alt_name]]
     */
    public function active_experiments() {
        $experiment_pairs = collect([]);
        
        foreach ($this->user->keys() as $key) {
            foreach (Metric::possible_experiments($this->key_without_version($key)) as $experiment) {
                /* @var Experiment $experiment */
                if (!$experiment->has_winner()) {
                    $experiment_pairs[$this->key_without_version($key)] = [$this->user[$key], (string)$experiment->control()];
                }
            }
        }

        return $experiment_pairs;
    }

    /**
     * Helper method for finding out the keys that is not the current version's key.
     * 
     * @param $keys Collection The keys of this experiment, may contain old version as well as the current version
     * @param string $experiment_key The current version's experiment key
     *
     * @return Collection
     */
    private function keys_without_experiment($keys, $experiment_key) {
        /*fixme: consider change this func's name*/
        return $keys->reject(function ($k) use ($experiment_key) {
            return preg_match("/^$experiment_key(:finished)?$/", $k);
        });
    }

    /**
     * Helper method for finding out the keys of the unfinished experiment, namely not contains :finished. 
     * 
     * @param $keys Collection
     *
     * @return Collection
     */
    private function keys_without_finished($keys) {
        return $keys->reject(function ($k) {
            return str_contains($k, ":finished");
        });
    }

    /**
     * Helper method for removing the key's version, leaving only the **name** of the experiment.
     * 
     * format of key: "name:version"
     * 
     * @param string $key
     *
     * @return string
     */
    public function key_without_version($key) {
        return preg_split("/\:\d(?!\:)/", $key)[0];
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
    public function offsetExists($offset) {
        return $this->user->offsetExists($offset);
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
    public function offsetGet($offset) {
        return $this->user->offsetGet($offset);
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
        $this->user->offsetSet($offset, $value);
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
        $this->user->offsetUnset($offset);
    }
}