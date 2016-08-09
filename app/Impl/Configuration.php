<?php
/**
 * Created by PhpStorm.
 * User: thd
 * Date: 8/9/2016
 * Time: 3:32 PM
 */

namespace Split\Impl;


use Illuminate\Support\Collection;
use Split\Impl\Algorithms\WeightedSample;
use Split\Impl\Persistence\SessionAdapter;

class Configuration {
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
     * @var Collection
     */
    public $experiments;

    public $metrics;

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

    public function set_experiments($experiments) {
        if (!$this->experiments instanceof Collection || !is_array($experiments))
            throw new InvalidExperimentsFormatError("Experiments must be a Hash");
        $this->experiments = $experiments;
    }

    public function is_disabled() {
        return !$this->enabled;
    }

    public function experiment_for($name) {
        if ($this->normalized_experiments()) {
            return $this->normalized_experiments()[$name];
        }
    }

    public function metrics() {
        if (is_null($this->metrics)) {
            $this->metrics = collect([]);
            if ($this->experiments) {
                foreach ($this->experiments as $key => $value) {
                    $_metrics = $this->value_for($value, 'metric');
                    foreach (collect($_metrics) as $metric_name) {
                        if ($metric_name) {
                            if (!isset($this->metrics[$metric_name])) $this->metrics[$metric_name] = collect([]);
                            $this->metrics[$metric_name]->push(new Experiment($key));
                        }
                    }

                }
            }
        }

        return $this->metrics;
    }

    public function normalized_experiments() {
        if (is_null($this->experiments)) return null;

        $experiment_config = collect([]);
        foreach ($this->experiments->keys() as $name) {
            $experiment_config[$name] = collect([]);
        }

        foreach ($this->experiments as $experiment_name => $settings) {
            $alternatives = null;
            if ($alts = $this->value_for($settings, 'alternatives')) {
                $alternatives = $this->normalize_alternatives($alts);
            }

            $experiment_data = [
                'alternatives' => $alternatives,
                'goals'        => value_for($settings, 'goals'),
                'metadata'     => value_for($settings, 'metadata'),
                'algorithm'    => value_for($settings, 'algorithm'),
                'resettable'   => value_for($settings, 'resettable'),
            ];

            foreach ($experiment_data as $name => $value) {
                if (!is_null($value))
                    $experiment_config[$experiment_name][$name] = $value;
            }
        }
        return $experiment_config;
    }

    public function normalize_alternatives($alternatives){
        $_gn = collect($alternatives)->reduce(function ($a, $v){
            $p = $a[0];
            $n = $a[1];
            if ($percent = value($v,'percent')){
                return [$p+$percent,$n+1];
            }else{
                return $a;
            }
        },[0,0]);
        $given_probability = $_gn[0];
        $num_with_probability=$_gn[1];

        $num_without_probability = count($alternatives) - $num_with_probability;
        $unassigned_probability = ((100.0-$given_probability)/$num_without_probability/100.0);

        if ($num_with_probability){
            $alternatives = collect($alternatives)->map(function ($v)use($unassigned_probability){
                if (($name = $this->value_for($v,'name'))&&($percent = $this->value_for($v,'percent'))){
                    return [$name=>$percent/100.0];
                }elseif ($name = $this->value_for($v,'name')){
                    return [$name=>$unassigned_probability];
                }else{
                    return [$v=>$unassigned_probability];
                }
            });

            return [$alternatives->shift(),$alternatives->toArray()];
        }else{
            return [array_shift($alternatives),$alternatives];
        }
    }

    public function robot_regex(){
        if (is_null($this->robot_regex)){
            $this->robot_regex = "/\b(?:".implode('|',$this->escaped_bots()).")\b|\A\W*\z/i";
        }
        return $this->robot_regex;
    }


    /**
     * Configuration constructor.
     */
    public function __construct() {
        $this->ignore_ip_addresses=[];
        $this->ignore_filter=function (){return Helper::is_robot() || Helper::is_ignored_ip_address();};
        $this->db_failover = false;
        $this->db_failover_on_db_error = function ($error){};
        $this->on_experiment_reset = function ($experiment){};
        $this->on_experiment_delete = function ($experiment){};
        $this->on_before_experiment_reset = function ($experiment){};
        $this->on_before_experiment_delete = function ($experiment){};
        $this->db_failover_allow_parameter_override=false;
        $this->allow_multiple_experiments = false;
        $this->enabled = true;
        $this->experiments = [];
        $this->persistence=SessionAdapter::class;
        $this->persistence_cookie_length=31536000;
        $this->algorithm=WeightedSample::class;
        $this->beta_probability_simulations=10000;
    }

    /**
     * @param $hash Collection
     * @param $key  string
     * @return mixed
     */
    private function value_for($hash, $key) {
        if ($hash->has($key)) return $hash[$key];

        return null;
    }

    public function escaped_bots(){
        return $this->bots()->map(function ($v,$k){
            return [preg_quote($k,'/')=>$v];
        })->collapse();
    }
}

























