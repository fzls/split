<?php
/**
 * Created by PhpStorm.
 * User: thd
 * Date: 8/8/2016
 * Time: 3:00 PM
 */

namespace Split\Impl;


class Trial {
    /**
     * @var Experiment
     */
    public $experiment;
    public $metadata;
    /**
     * @var Alternative
     */
    public $alternative;
    /**
     * @var User
     */
    public $user;
    public $options;
    public $alternative_choosen;

    /**
     * Trial constructor.
     */
    public function __construct($attrs = []) {
        $attrs = collect($attrs);
        $this->experiment = $attrs->pull('experiment');
        $this->alternative = $attrs->pull('alternative');
        $this->metadata = $attrs->pull('metadata');

        $this->user = new User($attrs->pull('user'));
        $this->options = $attrs;

        $this->alternative_choosen = false;
    }

    public function metadata() {
        if ($this->experiment->metadata && !$this->metadata) {
            $this->metadata = $this->experiment->metadata[$this->alternative->name];
        }

        return $this->metadata;
    }

    public function alternative() {
        if (!$this->alternative) {
            $this->alternative = $this->experiment->has_winner() ? $this->experiment->winner() : null;
        }

        return $this->alternative;
    }

    public function set_alternative($alternative) {
        if ($alternative instanceof Alternative) {
            $this->alternative = $alternative;
        } else {
            $this->alternative = $this->experiment->alternatives->first(function ($a) use ($alternative) {
                return $a->name == $alternative;
            });
        }
    }

    public function complete($goals = [], $context = null) {
        $goals = collect($goals);
        if ($this->alternative()) {
            if ($goals->isEmpty()) {
                $this->alternative()->increment_completion();
            } else {
                $goals->each(function ($g) {
                    $this->alternative()->increment_completion($g);
                });
            }

            /*fixme: run_callback context, Split.configuration.on_trial_complete*/
        }
    }

    public function choose($context = null) {
        $this->user->cleanup_old_experiments();
        if ($this->alternative_choosen) return $this->alternative();

        if ($this->override_is_alternative()) {
            $this->alternative = $this->options['override'];
            if ($this->should_store_alternative() && !$this->user[$this->experiment->key()]) {
                $this->alternative->increment_participation();
            } elseif ($this->options['disabled'] || env('DISABLED')) {
                $this->alternative = $this->experiment->control();
            } elseif ($this->experiment->has_winner()) {
                $this->alternative = $this->experiment->winner();
            } else {
                $this->cleanup_old_versions();

                if ($this->exclude_user()) {
                    $this->alternative = $this->experiment->control();
                } else {
                    $value = $this->user[$this->experiment->key()];
                    if ($value) {
                        $this->alternative = $value;
                    } else {
                        $this->alternative = $this->experiment->next_alternative();

                        $this->alternative->increment_participation();

                        /*fixme : run_callback context, Split.configuration.on_trial_choose*/
                    }
                }
            }
        }

        if ($this->should_store_alternative()) {
            $this->user[$this->experiment->key()] = $this->alternative()->name;
        }
        $this->alternative_choosen = true;/*fixme: save to user?*/

        /*fixme: run_callback context, Split.configuration.on_trial unless @options[:disabled] || Split.configuration.disabled?*/

        return $this->alternative();
    }

    /*fixme
    def run_callback(context, callback_name)
      context.send(callback_name, self) if callback_name && context.respond_to?(callback_name, true)
    end
    */

    private function override_is_alternative() {
        return $this->experiment->alternatives->map(function ($a) {
            return $a->name;
        })->contains($this->options['override']);
    }

    private function should_store_alternative() {
        if ($this->options['override'] || $this->options['disabled']) {
            return env('STORE_OVERRIDE');
        } else {
            return !$this->exclude_user();
        }
    }

    private function cleanup_old_versions() {
        if ($this->experiment->version() > 0) {
            $this->user->cleanup_old_versions($this->experiment);
        }
    }

    private function exclude_user() {
        return $this->options['exclude'] ||
        is_null($this->experiment->start_time()) ||
        $this->user->is_max_experiments_reached($this->experiment->key());
    }
}