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
     * choose an alternative from the experiment.
     * 
     * @param Experiment $experiment
     *
     * @return Alternative
     */
    public function choose_alternative($experiment);
}