<?php
/**
 * Created by PhpStorm.
 * User: thd
 * Date: 8/3/2016
 * Time: 10:18 AM
 */

namespace Split\Impl\Algorithms;

use Illuminate\Support\Collection;

class WeightedSample {
    function random_01() {
        return (float)mt_rand() / (float)mt_getrandmax();
    }

    /**
     * @param $experiment
     *
     * @return mixed
     */
    function choose_alternative($experiment) {
        /**
         * @var $weights Collection
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