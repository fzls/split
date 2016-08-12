<?php
/**
 * Created by PhpStorm.
 * User: thd
 * Date: 8/9/2016
 * Time: 3:32 PM
 */

namespace Split\Impl;


use Illuminate\Support\Collection;
use Config;

use Split\Impl\Algorithms\WeightedSample;
use Split\Impl\Persistence\SessionAdapter;

/**
 * Class Configuration
 * @package Split\Impl
 */
class Configuration {
    /**
     * @var
     */
    public $bots;
    public $robot_regex;
    public $ignore_ip_addresses;
    public $ignore_filter;
    public $db_failover;
    public $db_failover_on_db_error;
    public $db_failover_allow_parameter_override;
    public $allow_multiple_experiments;
    public $enabled;
    public $persistence;
    public $persistence_cookie_length;
    public $algorithm;
    public $store_override;
    public $start_manually;
    public $on_trial;
    public $on_trial_choose;
    public $on_trial_complete;
    public $on_experiment_reset;
    public $on_experiment_delete;
    public $on_before_experiment_reset;
    public $on_before_experiment_delete;
    public $include_rails_helper;
    public $beta_probability_simulations;
    public $redis_url;

    /**
     * @var Collection When init from outside, wrap it in collect()
     */
    public $experiments;

    public $metrics;
    public $experiment_config;

    public $version;

    /**
     * return the collection of the robots
     *
     * @return Collection
     */
    public function bots() {
        if (is_null($this->bots)) {
            $this->bots = collect(
                [
                    # Indexers
                    'AdsBot-Google'                               => 'Google Adwords',
                    'Baidu'                                       => 'Chinese search engine',
                    'Baiduspider'                                 => 'Chinese search engine',
                    'bingbot'                                     => 'Microsoft bing bot',
                    'Butterfly'                                   => 'Topsy Labs',
                    'Gigabot'                                     => 'Gigabot spider',
                    'Googlebot'                                   => 'Google spider',
                    'MJ12bot'                                     => 'Majestic-12 spider',
                    'msnbot'                                      => 'Microsoft bot',
                    'rogerbot'                                    => 'SeoMoz spider',
                    'PaperLiBot'                                  => 'PaperLi is another content curation service',
                    'Slurp'                                       => 'Yahoo spider',
                    'Sogou'                                       => 'Chinese search engine',
                    'spider'                                      => 'generic web spider',
                    'UnwindFetchor'                               => 'Gnip crawler',
                    'WordPress'                                   => 'WordPress spider',
                    'YandexBot'                                   => 'Yandex spider',
                    'ZIBB'                                        => 'ZIBB spider',

                    # HTTP libraries
                    'Apache-HttpClient'                           => 'Java http library',
                    'AppEngine-Google'                            => 'Google App Engine',
                    'curl'                                        => 'curl unix CLI http client',
                    'ColdFusion'                                  => 'ColdFusion http library',
                    'EventMachine HttpClient'                     => 'Ruby http library',
                    'Go http package'                             => 'Go http library',
                    'Java'                                        => 'Generic Java http library',
                    'libwww-perl'                                 => 'Perl client-server library loved by script kids',
                    'lwp-trivial'                                 => 'Another Perl library loved by script kids',
                    'Python-urllib'                               => 'Python http library',
                    'PycURL'                                      => 'Python http library',
                    'Test Certificate Info'                       => 'C http library?',
                    'Wget'                                        => 'wget unix CLI http client',

                    # URL expanders / previewers
                    'awe.sm'                                      => 'Awe.sm URL expander',
                    'bitlybot'                                    => 'bit.ly bot',
                    'bot@linkfluence.net'                         => 'Linkfluence bot',
                    'facebookexternalhit'                         => 'facebook bot',
                    'Feedfetcher-Google'                          => 'Google Feedfetcher',
                    'https://developers.google.com/+/web/snippet' => 'Google+ Snippet Fetcher',
                    'LongURL'                                     => 'URL expander service',
                    'NING'                                        => 'NING - Yet Another Twitter Swarmer',
                    'redditbot'                                   => 'Reddit Bot',
                    'ShortLinkTranslate'                          => 'Link shortener',
                    'TweetmemeBot'                                => 'TweetMeMe Crawler',
                    'Twitterbot'                                  => 'Twitter URL expander',
                    'UnwindFetch'                                 => 'Gnip URL expander',
                    'vkShare'                                     => 'VKontake Sharer',

                    # Uptime monitoring
                    'check_http'                                  => 'Nagios monitor',
                    'NewRelicPinger'                              => 'NewRelic monitor',
                    'Panopta'                                     => 'Monitoring service',
                    'Pingdom'                                     => 'Pingdom monitoring',
                    'SiteUptime'                                  => 'Site monitoring services',

                    # ???
                    'DigitalPersona Fingerprint Software'         => 'HP Fingerprint scanner',
                    'ShowyouBot'                                  => 'Showyou iOS app spider',
                    'ZyBorg'                                      => 'Zyborg? Hmmm....',
                    'ELB-HealthChecker'                           => 'ELB Health Check',
                ]);
        }

        return $this->bots;
    }

    /**
     * Set the experiments, should be a collection, if not, exception will be threw
     *
     * @param Collection $experiments
     *
     * @throws InvalidExperimentsFormatError
     */
    public function set_experiments($experiments) {
        if (!$this->experiments instanceof Collection){
            require_once __DIR__.'/exceptions.php';
            throw new InvalidExperimentsFormatError("Experiments must be a Hash");
        }
        $this->experiments = $experiments;
    }

    /**
     * Check if the split is enabled
     *
     * @return bool
     */
    public function is_disabled() {
        return !$this->enabled;
    }

    /**
     * Fetch experiment from the configuration's defined experiments
     *
     * @param string $name
     *
     * @return array
     */
    public function experiment_for($name) {
        if ($this->normalized_experiments()) {
            return Helper::value_for($this->normalized_experiments(), $name);
        }
    }

    /**
     * Get metrics from the experiment arrays
     *
     * @return Collection
     */
    public function metrics() {
        if (is_null($this->metrics)) {
            $this->metrics = collect([]);
            if ($this->experiments) {
                foreach ($this->experiments as $experiment_name => $settings) {
                    $_metrics = Helper::value_for($settings, 'metric');
                    foreach (collect($_metrics) as $metric_name) {
                        if ($metric_name) {
                            if (!isset($this->metrics[$metric_name])) $this->metrics[$metric_name] = collect([]);
                            $this->metrics[$metric_name]->push(new Experiment($experiment_name));
                        }
                    }

                }
            }
        }

        return $this->metrics;
    }

    /**
     * Normalized the experiments get from the outside like the one below with yaml or json or set at runtime, and return
     * the result for extracting
     * note:all the settings is [array].
     *
     * Examples:
     * Ex1: YAML
     *   my_experiment:
     *     alternatives:
     *       - name: Control Opt
     *         percent: 67
     *       - name: Alt One
     *         percent: 10
     *       - name: Alt Two
     *         percent: 23
     *     metadata:
     *       Control Opt:
     *         text: 'Control Option'
     *       Alt One:
     *         text: 'Alternative One'
     *       Alt Two:
     *         text: 'Alternative Two'
     *     resettable: false
     *
     * EX2: Runtime
     *      ["my_experiment"=>[
     *                          "alternatives"=>["control_opt", "other_opt"],
     *                          "metric"=>"my_metric",
     *                          ]
     *      ]
     *
     * EX3: JSON
     *      {"my_experiment":{"alternatives":["control_opt", "other_opt"],"metric":"my_metric"}}
     *
     * @return Collection|null
     */
    public function normalized_experiments() {
        if (is_null($this->experiments)) return null;

        if (is_null($this->experiment_config)) {
            $this->experiment_config = collect([]);
            foreach ($this->experiments->keys() as $name) {
                $this->experiment_config[$name] = collect([]);
            }

            foreach ($this->experiments as $experiment_name => $settings) {
                $alternatives = null;
                if ($alts = Helper::value_for($settings, 'alternatives')) {
                    $alternatives = $this->normalize_alternatives($alts);
                }

                $experiment_data = [
                    'alternatives' => $alternatives, /*array*/
                    'goals'        => Helper::value_for($settings, 'goals'),
                    'metadata'     => Helper::value_for($settings, 'metadata'),/*contain the details of the alternative*/
                    'algorithm'    => Helper::value_for($settings, 'algorithm'),/*TODO: set algorithms in the @experiment*/
                    'resettable'   => Helper::value_for($settings, 'resettable'),
                ];

                foreach ($experiment_data as $name => $value) {
                    if (!is_null($value))
                        $this->experiment_config[$experiment_name][$name] = $value;
                }
            }
        }

        return $this->experiment_config;
    }

    /**
     * Normalize alternatives from the outside
     *
     * Normalized format: [control, [experiment_group]]
     * Ex1:
     *  before: [['name'=>'Control Opt','percent'=>67],['name'=>'Alt One','percent'=>10],['name'=>'Alt Two','percent'=>23]]
     *
     *  after : [['Control Opt'=>0.67], [['Alt One'=>0.1], ['Alt Two'=>0.23]]]
     *
     * Ex2:
     *  before: ['Control Opt', 'Alt One', 'Alt Two']
     *
     *  after : ['Control Opt', ['Alt One', 'Alt Two']]
     *
     * @param array $alternatives
     *
     * @return array
     */
    public function normalize_alternatives($alternatives) {
        list($given_probability, $num_with_probability) = collect($alternatives)->reduce(function ($a, $alternative) {
            list($p, $n) = $a;
            if ($percent = Helper::value_for($alternative, 'percent')) {
                return [$p + $percent, $n + 1];
            } else {
                return $a;
            }
        }, [0, 0]);
        $num_without_probability = count($alternatives) - $num_with_probability;
        $unassigned_probability = ((100.0 - $given_probability) / $num_without_probability / 100.0);

        if ($num_with_probability) {
            $alternatives = collect($alternatives)->map(function ($v) use ($unassigned_probability) {
                if (($name = Helper::value_for($v, 'name')) && ($percent = Helper::value_for($v, 'percent'))) {
                    return [$name => $percent / 100.0];
                } elseif ($name = Helper::value_for($v, 'name')) {
                    return [$name => $unassigned_probability];
                } else {
                    return [$v => $unassigned_probability];
                }
            });

            return [$alternatives->shift(), $alternatives->toArray()];
        } else {
            return [array_shift($alternatives), $alternatives];
        }
    }

    /**
     * Generate the robot regex by implode the bots array
     *
     * @return string
     */
    public function robot_regex() {
        if (is_null($this->robot_regex)) {
            $this->robot_regex = "/\b(?:" . implode('|', $this->escaped_bots()->keys()->toArray()) . ")\b|\A\W*\z/i";
        }

        return $this->robot_regex;
    }


    /**
     * Configuration constructor.
     */
    public function __construct() {
        $ips = Config::get('split.ignore_ip_addresses');
        if (!is_null($ips)) {
            $this->ignore_ip_addresses = explode('|', $ips);
        }
        $this->ignore_filter = function () { return Helper::is_robot() || Helper::is_ignored_ip_address(); };
        $this->db_failover = Config::get('split.db_failover');
        $this->db_failover_on_db_error = function ($error) { };
        $this->on_experiment_reset = function ($experiment) { };
        $this->on_experiment_delete = function ($experiment) { };
        $this->on_before_experiment_reset = function ($experiment) { };
        $this->on_before_experiment_delete = function ($experiment) { };
        $this->on_trial_complete = function ($trial) { };
        $this->on_trial_choose = function ($trial) { };
        $this->on_trial = function ($trial) { };
        $this->db_failover_allow_parameter_override = Config::get('split.db_failover_allow_parameter_override');
        $this->allow_multiple_experiments = Config::get('split.allow_multiple_experiments');
        $this->enabled = Config::get('split.enabled');
        $this->experiments = collect([]);/*notice: load from json or yaml*/

        $adapters = Config::get('split.adapters');
        $adapter = Config::get('split.adapter');
        if (array_key_exists($adapter, $adapters)){
            $this->persistence = $adapters[$adapter];
        }

        $this->persistence_cookie_length = Config::get('split.cookie_expires');

        $algorithms = Config::get('split.algorithms');
        $algorithm = Config::get('split.algorithm');
        $this->algorithm = $algorithms[$algorithm];

        $this->beta_probability_simulations = Config::get('split.beta_probability_simulations');

        $this->version = Config::get('split.version');
    }


    public function escaped_bots() {
        return $this->bots()->map(function ($v, $k) {
            return [preg_quote($k, '/') => $v];
        })->collapse();
    }
}

























