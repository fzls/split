<?php
/**
 * Created by PhpStorm.
 * User: thd
 * Date: 8/3/2016
 * Time: 10:18 AM
 */

namespace Split\Impl\Algorithms;

use Illuminate\Support\Collection;
use Split\Contracts\Algorithm\SamplingAlgorithm;
use Split\Impl\Alternative;

class WeightedSample implements SamplingAlgorithm {

    /**
     * generate a random number between 0 and 1 (inclusive).
     *
     * @return float
     */
    function random_01() {
        return (float)mt_rand() / (float)mt_getrandmax();
    }

    function choose_alternative($experiment) {
        /**
         * @var Collection $weights 
         */
        $weights = $experiment->alternatives->pluck('weight');

        $total = $weights->sum();
        $point = $this->random_01() * $total;

        foreach ($experiment->alternatives as $alternative) {
            if ($alternative->weight >= $point) return $alternative;
            $point -= $alternative->weight;
        }
    }
}