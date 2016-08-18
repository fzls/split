<?php
/**
 * Created by PhpStorm.
 * User: thd
 * Date: 8/9/2016
 * Time: 10:24 AM
 */

namespace Split\Impl;


use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Class Helper
 *
 * @package Split\Impl
 */
class Helper {
    /**
     * Use this param in url to override the config
     */
    const OVERRIDE_PARAM_NAME = "ab_test";

    /**
     * Do an abtest
     *
     * @param array|Collection             $metric_descriptor
     * @param null|string|array|Collection $control
     * @param null|array|Collection        $alternatives
     *
     * @return array Name of the chosen alternative and the metadata( if any)
     * @throws \Exception
     */
    public static function ab_test($metric_descriptor, $control = null, $alternatives = null) {
        /* normalize param*/
        if (is_array($metric_descriptor)) {
            $metric_descriptor = collect($metric_descriptor);
        }
        $control      = collect($control);
        $alternatives = collect(func_get_args())->splice(2);

        try {
            /* get the experiment*/
            $experiment = \App::make('split_catalog')->find_or_initialize($metric_descriptor, $control, $alternatives);

            if (\App::make('split_config')->enabled) {
                /* if enabled, save the experiment*/
                $experiment->save();

                /* init a trial*/
                $trial = new Trial([
                                       'user'       => self::ab_user(),
                                       'experiment' => $experiment,
                                       'override'   => self::override_alternative($experiment->name),
                                       'exclude'    => self::exclude_visitor(),
                                       'disabled'   => self::split_generically_disabled(),
                                   ]);
                /* choose an alternative*/
                $alt         = $trial->choose(/*self*/);
                $alternative = $alt ? $alt->name : null;
            } else {
                /* otherwise, use control instead*/
                $alternative = self::control_variable($experiment->control());
            }
        } catch (\Exception $e) {
            /* if not configure db failover, rethrow exception*/
            if (!\App::make('split_config')->db_failover) throw $e;

            /* otherwise call the configured hook*/
            call_user_func(\App::make('split_config')->db_failover_on_db_error, $e);

            if (\App::make('split_config')->db_failover_allow_parameter_override) {
                if (self::override_present($experiment->name)) $alternative = self::override_alternative($experiment->name);
                if (self::split_generically_disabled()) $alternative = self::control_variable($experiment->control());
            }
        } finally {
            if (!$alternative) $alternative = self::control_variable($experiment->control());
        }

        /* get metadata if any*/
        $metadata = $trial ? $trial->metadata() : collect([]);

        return ['alternative' => $alternative, 'metadata' => $metadata];
    }

    /**
     * Reset the experiment in the user.
     *
     * @param $experiment Experiment
     */
    public static function reset($experiment) {
        self::ab_user()->delete($experiment->key());
    }

    /**
     * Finish the experiment.
     *
     * @param  Experiment      $experiment
     * @param array|Collection $options
     *
     * @return bool
     */
    public static function finish_experiment($experiment, $options = ['reset' => true]) {
        /* normalize data*/
        if (is_array($options))
            $options = collect($options);

        /* if experiment has winner, stopped here*/
        if ($experiment->has_winner()) return true;

        $should_reset = $experiment->is_resettable() && $options['reset'];
        if (self::ab_user()[$experiment->finished_key()] && !$should_reset) {
            /* if already finished, just return*/
            return true;
        } else {
            /* else fetch the assigned alternative name from the user */
            $alternative_name = self::ab_user()[$experiment->key()];
            /* and increment the completion for this alternative*/
            $trial = new Trial([
                                   'user'        => self::ab_user(),
                                   'experiment'  => $experiment,
                                   'alternative' => $alternative_name,
                               ]);
            $trial->complete(Helper::value_for($options, 'goals')/*, self*/);

            if ($should_reset) {
                /* delete the experiment from the user*/
                self::reset($experiment);
            } else {
                /* else, tag this experiment as finished in the user*/
                self::ab_user()[$experiment->finished_key()] = true;
            }
        }
    }

    /**
     * Finish an experiment or metric for the user.
     *
     * @param Collection|string $metric_descriptor
     * @param array             $options
     *
     * @return null
     * @throws \Exception
     */
    public static function ab_finished($metric_descriptor, $options = ['reset' => true]) {
        try {
            /* normalize data*/
            $options = collect($options);

            /* if this user is excluded or the experiment is disabled, do nothing*/
            if (self::exclude_visitor() || env('DISABLED')) return null;

            /* extract metric descriptor and goals*/
            list($metric_descriptor, $goals) = self::normalize_metric($metric_descriptor);
            /* get the experiments by experiment name or metric name*/
            $experiments = Metric::possible_experiments($metric_descriptor);

            /* finish each experiment*/
            $experiments->each(function ($experiment) use ($options, $goals) {
                self::finish_experiment($experiment, $options->put('goals', $goals));
            });
        } catch (\Exception $e) {
            /* if not configure db failover, rethrow exception*/
            if (!\App::make('split_config')->db_failover) throw $e;
            /* call user defined hook*/
            call_user_func(\App::make('split_config')->db_failover_on_db_error, $e);
        }
    }

    /**
     * return override if override given.
     *
     * Eg: ?ab_test[color]=red
     * ps: here the OVERRIDE_PARAM_NAME is ab_test
     *
     * @param string $experiment_name
     *
     * @return null|string
     */
    public static function override_present($experiment_name) {
        if (self::override_alternative($experiment_name)) {
            return \Request::input('OVERRIDE_PARAM_NAME')[$experiment_name];
        }

        return null;
    }

    /**
     * Check if override given.
     *
     * @param string $experiment_name
     *
     * @return bool
     */
    public static function override_alternative($experiment_name) {
        /*can use via browser, or in console via api*/
        return /*!\App::runningInConsole()
        &&*/
            \Request::has('OVERRIDE_PARAM_NAME')
            && collect(\Request::input('OVERRIDE_PARAM_NAME'))->has($experiment_name);
    }

    /**
     * Check if disabled.
     *
     * @return bool
     */
    public static function split_generically_disabled() {
        return /*!\App::runningInConsole() && */
            \Request::has('SPLIT_DISABLE');
    }

    /**
     * Get the user instance.
     *
     * @return User
     */
    public static function ab_user() {
        return \App::make('split_user');
    }

    /**
     * Check if this user is excluded.
     *
     * When user is in ignore_filter or ip is ignored or is a robot.
     *
     * @return bool
     */
    public static function exclude_visitor() {
        return call_user_func(\App::make('split_config')->ignore_filter) || self::is_ignored_ip_address() || self::is_robot();
    }

    /**
     * Check if the current user is a robot.
     *
     * @return int
     */
    public static function is_robot() {
        return /*!\App::runningInConsole() && */
            preg_match(\App::make('split_config')->robot_regex(), \Request::server('HTTP_USER_AGENT'));
    }

    /**
     * Check if the current ip address is ignored.
     *
     * Match precisely or by regex
     *
     * @return bool
     */
    public static function is_ignored_ip_address() {
        if (is_null(\App::make('split_config')->ignore_ip_addresses)) return false;

        foreach (\App::make('split_config')->ignore_ip_addresses as $ip) {
            if (/*!\App::runningInConsole() && */
            (\Request::ip() == $ip || preg_match($ip, \Request::ip()))
            )
                return true;
        }

        return false;
    }

    /**
     * Get all of the current active experiments that user has participated.
     *
     * @return Collection
     */
    public static function active_experiments() {
        return self::ab_user()->active_experiments();
    }

    /**
     * Normalize the metric descriptor.
     *
     * @param string $metric_descriptor
     *
     * @return array
     */
    public static function normalize_metric($metric_descriptor) {
        if ($metric_descriptor instanceof Collection) {
            $metric_name = $metric_descriptor->keys()->first();
            $goals       = collect($metric_descriptor->values()->first());
        } else {
            $metric_name = $metric_descriptor;
            $goals       = collect([]);
        }

        return [$metric_name, $goals];
    }

    /**
     * Get the control's name.
     *
     * @param Alternative|Collection $control
     *
     * @return string
     */
    public static function control_variable($control) {
        if ($control instanceof Collection) {
            return (string)$control->keys()->first();
        } else {
            return (string)$control;
        }
    }

    /**
     * A helper to extract the value from the hash by key, null will be returned if has no such key of not a hash.
     *
     * @param Collection|array|mixed $hash
     * @param string|int             $key
     *
     * @return mixed|null
     */
    public static function value_for($hash, $key) {
        if ($hash instanceof Collection && $hash->has($key) || is_array($hash) && array_key_exists($key, $hash)) {
            return $hash[$key];
        }

        return null;
    }
}




















