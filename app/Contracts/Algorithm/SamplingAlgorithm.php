<?php
/**
 * Created by PhpStorm.
 * User: 风之凌殇
 * Date: 8/7/2016
 * Time: 7:48 PM
 */

namespace Split\Contracts\Algorithm;


use Split\Impl\Alternative;
use Split\Impl\Experiment;

interface SamplingAlgorithm {

    /**
     * @param $experiment Experiment
     *
     * @return Alternative
     */
    public function choose_alternative($experiment);
}