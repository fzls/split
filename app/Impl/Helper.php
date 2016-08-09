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

class Helper {
    const OVERRIDE_PARAM_NAME = "ab_test";

    public static function ab_test($metric_descriptor, $control = null, $alternatives) {
        $alternatives = collect(func_get_args())->splice(2);

        try {
            $experiment = ExperimentCatalog::find_or_initialize($metric_descriptor, $control, $alternatives);
            if (env('ENABLED')) {
                $experiment->save();
                $trial = new Trial([
                                       'user'       => self::ab_user(),
                                       'experiment' => $experiment,
                                       'override'   => self::override_alternative($experiment->name),
                                       'exclude'    => self::exclude_visitor(),
                                       'disabled'   => self::split_generically_disabled(),
                                   ]);
                $alt = $trial->choose(/*self*/);
                $alternative = $alt ? $alt->name : null;
            } else {
                $alternative = self::control_variable($experiment->control());
            }
        } catch (\Exception $e) {
            if (!env('DB_FAILOVER')) throw $e;
            Configuration::db_failover_on_db_error($e);

            if (env('DB_FAILOVER_ALLOW_PARAMETER_OVERRIDE')) {
                if (self::override_present($experiment->name)) $alternative = self::override_alternative($experiment->name);
                if (self::split_generically_disabled()) $alternative = self::control_variable($experiment->control());
            }
        } finally {
            if (!$alternative) $alternative = self::control_variable($experiment->control());
        }

        /*if (self::block_given()){
            $metadata = $trial?$trial->metadata():collect([]);
        }*/

        return $alternative;
    }

    /**
     * @param $experiment Experiment
     */
    public static function reset($experiment) {
        self::ab_user()->delete($experiment->key());
    }

    /**
     * @param       $experiment Experiment
     * @param array $options
     */
    public static function finish_experiment($experiment, $options = ['reset' => true]) {
        $options = collect($options);
        if ($experiment->has_winner()) return true;
        $should_reset = $experiment->is_resettable() && $options['reset'];
        if (self::ab_user()[$experiment->finished_key()] && !$should_reset) {
            return true;
        } else {
            $alternative_name = self::ab_user()[$experiment->key()];
            $trial = new Trial([
                                   'user'        => self::ab_user(),
                                   'experiment'  => $experiment,
                                   'alternative' => $alternative_name,
                               ]);
            $trial->complete([$options['goals']]/*, self*/);

            if ($should_reset) {
                self::reset($experiment);
            } else {
                self::ab_user()[$experiment->finished_key()] = true;
            }
        }
    }

    public static function ab_finished($metric_descriptor, $options = ['reset' => true]) {
        try {
            $options = collect($options);
            if (self::exclude_visitor() || env('DISABLED')) return null;
            extract(self::normalize_metric($metric_descriptor));
            /* @var $goals Collection
             * @var $metric_descriptor string
             * */
            $experiments = Metric::possible_experiments($metric_descriptor);

            if ($experiments->count() > 0) {
                $experiments->each(function ($experiment) use ($options, $goals) {
                    self::finish_experiment($experiment, $options->put('goals', $goals));
                });
            }
        }catch (\Exception $e){
            if (!env('DB_FAILOVER')) throw $e;
            Configuration::db_failover_on_db_error($e);
        }
    }

    public static function override_present($experiment_name){
        return self::override_alternative($experiment_name);
    }

    public static function override_alternative($experiment_name){
        return !\App::runningInConsole() &&
            \Request::has('OVERRIDE_PARAM_NAME') &&
        collect(\Request::input('OVERRIDE_PARAM_NAME'))->has($experiment_name);
    }

    public static function split_generically_disabled(){
        return !\App::runningInConsole() && \Request::has('SPLIT_DISABLE');
    }

    public static function ab_user(){
        /*fixme : @ab_user ||= User.new(self)*/
        return new User();
    }

    public static function exclude_visitor(){
        return Configuration::ignore_filter() || self::is_ignored_ip_address()||self::is_robot();
    }

    public static function is_robot(){
        return !\App::runningInConsole() && preg_match(Configuration::robot_regex(),\Request::server('HTTP_USER_AGENT'));
    }

    public static function is_ignored_ip_address(){
        if (Configuration::ignore_ip_addresses(/**/)->isEmpty()) return false;


        foreach (Configuration::ignore_ip_addresses as $ip) {
            if (!\App::runningInConsole() && (\Request::ip()==$ip||preg_match($ip,\Request::ip())))
                return true;
        }
        return false;
    }

    public static function active_experiments(){
        return self::ab_user()->active_experiments();
    }

    public static function normalize_metric($metric_descriptor){
        if ($metric_descriptor instanceof Collection){
            $experiment_name = $metric_descriptor->keys()->first();
            $goals = collect($metric_descriptor->values()->first());
        }else{
            $experiment_name = $metric_descriptor;
            $goals = collect([]);
        }

        return ['$metric_descriptor'=>$experiment_name,'goals'=>$goals];
        /*fixme*/
    }

    public static function control_variable($control){
        if ($control instanceof Collection){
            return (string)$control->keys()->first();
        }else{
            return (string)$control;
        }
    }
}




















