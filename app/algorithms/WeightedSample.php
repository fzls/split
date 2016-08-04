<?php
/**
 * Created by PhpStorm.
 * User: thd
 * Date: 8/3/2016
 * Time: 10:18 AM
 */

namespace Split\Algorithms;

class WeightedSample {
    function random_01() {
        return (float)mt_rand() / (float)mt_getrandmax();
    }

    function choose_alternative($experiment) {
        $weights = array_pluck($experiment->alternatives, 'weight');

        $total = array_reduce($weights, function ($carry, $weight) { return $carry + $weight; });
        $point = $this->random_01() * $total;

        foreach ($experiment->alternatives as $alternative) {
            if ($alternative->weight >= $point) return $alternative;
            $point -= $alternative->weight;
        }
    }
}