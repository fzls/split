<?php
/**
 * Created by PhpStorm.
 * User: thd
 * Date: 8/8/2016
 * Time: 3:00 PM
 */

namespace Split\Impl;


use Illuminate\Support\Collection;

/**
 * Class Trial
 *
 * @package Split\Impl
 */
class Trial {
    /**
     * The current experiment instance
     *
     * @var Experiment
     */
    public $experiment;

    /**
     * The metadata for the experiment.
     *
     * eg:
     *      my_first_experiment: {
     * alternatives: ["a", "b"],
     * metadata: {
     * "a" => {"text" => "Have a fantastic day"},
     * "b" => {"text" => "Don't get hit by a bus"}
     * }
     * }
     *
     * @var array|Collection
     */
    public $metadata;

    /**
     * The random chosen alternative instance
     *
     * @var Alternative
     */
    public $alternative;

    /**
     * The adapter used to save the user's data
     *
     * @var User
     */
    public $user;

    /**
     * The options of the trial
     *
     * @var Collection
     */
    public $options;

    /**
     * Check if this user has taken this experiment and assigned an alternative.
     *
     * @var bool
     */
    public $alternative_chosen;

    /**
     * Trial constructor.
     *
     * @param array $attrs
     */
    public function __construct($attrs = []) {
        $attrs = collect($attrs);

        $this->experiment         = $attrs->pull('experiment');
        $this->metadata           = $attrs->pull('metadata');
        $this->user               = $attrs->pull('user');
        $this->alternative_chosen = false;

        /* if has alternative, then override it*/
        if ($attrs->has('alternative')) {
            $this->set_alternative($attrs->pull('alternative'));
        }

        $this->options = $attrs;
    }

    /**
     * Get the metadata of the experiment
     *
     * @return array|Collection|null
     */
    public function metadata() {
        if ($this->experiment->metadata && is_null($this->metadata)) {
            $this->metadata = Helper::value_for($this->experiment->metadata, $this->alternative->name);
        }

        return $this->metadata;
    }

    /**
     * Get the trial's alternative, if null and has winner, then return the winner.
     *
     * @return null|Alternative
     */
    public function alternative() {
        if (is_null($this->alternative)) {
            $this->alternative = $this->experiment->has_winner() ? $this->experiment->winner() : null;
        }

        return $this->alternative;
    }

    /**
     * Set alternative, if string given, find the alternative in the experiment by name
     *
     * @param Alternative|string $alternative
     */
    public function set_alternative($alternative) {
        if ($alternative instanceof Alternative) {
            $this->alternative = $alternative;
        } else {
            $this->alternative = $this->experiment->alternatives->first(function ($key, $a) use ($alternative) {
                return $a->name == $alternative;
            });
        }
    }

    /**
     * Complete the chosen alternative, if goals given, then incr each goals given for the alternative.
     *
     * @param array $goals
     * @param null  $context
     */
    public function complete($goals = [], $context = null) {
        $goals = collect($goals);

        if ($this->alternative()) {
            /* when no goals given, increment without goal*/
            if ($goals->isEmpty()) {
                $this->alternative()->increment_completion();
            } else {
                /* otherwise, incr alternative for each goal*/
                $goals->each(function ($g) {
                    $this->alternative()->increment_completion($g);
                });
            }
            /* call the user set hook after trial complete*/
            call_user_func(\App::make('split_config')->on_trial_complete, $this);
        }
    }

    /**
     * Choose an alternative from the experiment's alternatives for the user.
     *
     * @param null $context
     *
     * @return Alternative
     */
    public function choose($context = null) {
        /* cleanup the old experiment before choose the alternative*/
        $this->user->cleanup_old_experiments();

        /* Only run the process once */
        if ($this->alternative_chosen) {
            return $this->alternative();
        }

        /* if the param override given is in the alternatives list*/
        if ($this->override_is_alternative()) {
            /*if so, find this alternative in the experiment's alts list*/
            $alt_name = $this->options['override'];
            $this->set_alternative($alt_name);

            if ($this->should_store_alternative() && is_null($this->user[$this->experiment->key()])) {
                $this->alternative->increment_participation();
            }
        } elseif ($this->options['disabled'] || \App::make('split_config')->is_disabled()) {
            /* if disabled, always return the control*/
            $this->set_alternative($this->experiment->control());
        } elseif ($this->experiment->has_winner()) {
            /* otherwise if has winner, always return it*/
            $this->set_alternative($this->experiment->winner());
        } else {/* if none the above satisfy*/
            $this->cleanup_old_versions();

            if ($this->exclude_user()) {
                /*when user is excluded, always return control*/
                $this->set_alternative($this->experiment->control());
            } else {
                $name_of_choosen_alternative = $this->user[$this->experiment->key()];

                /* check if user has take this experiment and been assigned an alternative*/
                if ($name_of_choosen_alternative) {
                    /* if so, assign it*/
                    $this->set_alternative($name_of_choosen_alternative);
                } else {
                    /* else assign a new one by running sampling algorithm*/
                    $this->set_alternative($this->experiment->next_alternative());
                    $this->alternative->increment_participation();

                    /* call user set hook on trial choose*/
                    call_user_func(\App::make('split_config')->on_trial_choose, $this);
                }
            }
        }

        if ($this->should_store_alternative()) {
            /* save the result to the user */
            $this->user[$this->experiment->key()] = $this->alternative()->name;
        }

        /* set this flag, so next time the same trial instance will not process again*/
        $this->alternative_chosen = true;

        /* if not disabled */
        if (!($this->options['disabled'] || \App::make('split_config')->is_disabled())) {
            /* call user set hook on trial end*/
            call_user_func(\App::make('split_config')->on_trial, $this);
        }

        return $this->alternative();
    }

    /**
     * Check if the override option is in the experiment's alternative list
     *
     * @return bool
     */
    private function override_is_alternative() {
        $names = $this->experiment->alternatives->pluck('name');

        /* @var Collection $names */
        return $names->contains($this->options['override']);
    }

    /**
     * Check if should save the result to the user.
     *
     * @return bool
     */
    private function should_store_alternative() {
        if ($this->options['override'] || $this->options['disabled']) {
            return \App::make("split_config")->store_override;
        } else {
            return !$this->exclude_user();
        }
    }

    /**
     * Call user to cleanup the old versions of this experiment
     */
    private function cleanup_old_versions() {
        if ($this->experiment->version() > 0) {
            $this->user->cleanup_old_versions($this->experiment);
        }
    }

    /**
     * Check if the user is excluded.
     *
     * @return bool
     */
    private function exclude_user() {
        return $this->options['exclude']
               || is_null($this->experiment->start_time())
               || $this->user->is_max_experiments_reached($this->experiment->key());
    }
}