<?php
/**
 * Created by PhpStorm.
 * User: thd
 * Date: 8/3/2016
 * Time: 3:56 PM
 */

namespace Split\Algorithms;


use gburtini\Distributions\Beta;

class Whiplash {
    const FAIRNESS_CONSTANT = 7;

    function choose_alternative($experiment) {
        return $experiment[best_guess($experiment->alternatives)];
    }

    private function arm_guess($participants, $completions) {
        $a = max($participants, 0);
        $b = max($completions, 0);
        $beta = new Beta($a+self::FAIRNESS_CONSTANT, $b+self::FAIRNESS_CONSTANT);
        return $beta->rand();
    }

    private function best_guess($alternatives) {
        $guesses = [];
        foreach ($alternatives as $alternative) {
            $guesses[$alternative->name] = $this->arm_guess($alternative->participant_count, $alternative->completed_count);
        }
        $gmax = max(array_values($guesses));
        $best = array_where(array_keys($guesses), function ($name) use ($gmax, $guesses) {
            return $guesses[$name] == $gmax;
        });
        return $best[array_rand($best)];

    }
}