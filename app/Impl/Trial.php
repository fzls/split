<?php
/**
 * Created by PhpStorm.
 * User: thd
 * Date: 8/8/2016
 * Time: 3:00 PM
 */

namespace Split\Impl;


use Illuminate\Support\Collection;

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
        $attrs            = collect($attrs);
        $this->experiment = $attrs->pull('experiment');
        $this->set_alternative($attrs->pull('alternative'));
        $this->metadata = $attrs->pull('metadata');

        $this->user    = $attrs->pull('user');
        $this->options = $attrs;

        $this->alternative_choosen = false; /* change to get from redis?*/
    }

    public function metadata() {
        if ($this->experiment->metadata && is_null($this->metadata)) {
            $this->metadata = Helper::value_for($this->experiment->metadata, $this->alternative->name);
        }

        return $this->metadata;
    }

    public function alternative() {
        if (is_null($this->alternative)) {
            $this->alternative = $this->experiment->has_winner() ? $this->experiment->winner() : null;
        }

        return $this->alternative;
    }

    public function set_alternative($alternative) {
        if ($alternative instanceof Alternative) {
            $this->alternative = $alternative;
        } else {
            $this->alternative = $this->experiment->alternatives->first(function ($key,$a) use ($alternative) {
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

            call_user_func(\App::make('split_config')->on_trial_complete, $this);
        }
    }

    public function choose($context = null) {
        $this->user->cleanup_old_experiments();
        if ($this->alternative_choosen) return $this->alternative();

        if ($this->override_is_alternative()) {
            $alt_name = $this->options['override'];
            $this->set_alternative(new Alternative($alt_name, $this->experiment->name));
            if ($this->should_store_alternative() && is_null($this->user[$this->experiment->key()])) {
                $this->alternative->increment_participation();
            }
        } elseif ($this->options['disabled'] || \App::make('split_config')->is_disabled()) {
            $this->set_alternative($this->experiment->control());
        } elseif ($this->experiment->has_winner()) {
            $this->set_alternative($this->experiment->winner());
        } else {
            $this->cleanup_old_versions();

            if ($this->exclude_user()) {
                $this->set_alternative($this->experiment->control());
            } else {
                $value = $this->user[$this->experiment->key()];
                if ($value) {
                    $this->set_alternative($value);
                } else {
                    $this->set_alternative($this->experiment->next_alternative());

                    $this->alternative->increment_participation();

                    call_user_func(\App::make('split_config')->on_trial_choose, $this);
                }
            }
        }

        if ($this->should_store_alternative()) {
            $this->user[$this->experiment->key()] = $this->alternative()->name;
        }
        $this->alternative_choosen = true;

        if (!($this->options['disabled'] || \App::make('split_config')->is_disabled())) {
            call_user_func(\App::make('split_config')->on_trial, $this);
        }
        
        return $this->alternative();
    }

    private function override_is_alternative() {
        $names = $this->experiment->alternatives->pluck('name');

        /* @var Collection $names */
        return $names->contains($this->options['override']);
    }

    private function should_store_alternative() {
        if ($this->options['override'] || $this->options['disabled']) {
            return \App::make("split_config")->store_override;
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
        return $this->options['exclude']
               || is_null($this->experiment->start_time())
               || $this->user->is_max_experiments_reached($this->experiment->key());
    }
}