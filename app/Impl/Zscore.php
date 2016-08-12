<?php
/**
 * Created by PhpStorm.
 * User: thd
 * Date: 8/5/2016
 * Time: 6:32 PM
 */

namespace Split\Impl;


class Zscore {
    static public function calculate($p1, $n1, $p2, $n2) {
        # p_1 = Pa = proportion of users who converted within the experiment split (conversion rate)
        # p_2 = Pc = proportion of users who converted within the control split (conversion rate)
        # n_1 = Na = the number of impressions within the experiment split
        # n_2 = Nc = the number of impressions within the control split
        # s_1 = SEa = standard error of p_1, the estiamte of the mean
        # s_2 = SEc = standard error of p_2, the estimate of the control
        # s_p = SEp = standard error of p_1 - p_2, assuming a pooled variance
        # s_unp = SEunp = standard error of p_1 - p_2, assuming unpooled variance
        $p_1 = floatval($p1);
        $p_2 = floatval($p2);

        $n_1 = floatval($n1);
        $n_2 = floatval($n2);

        if ($n_1 < 30 || $n_2 < 30) {
            return "needs 30+ participants.";
        } elseif ($p_1 * $n_1 < 5 || $p_2 * $n_2 < 5) {
            return "Needs 5+ conversions.";
        }

        # Formula for standard error: root(pq/n) = root(p(1-p)/n)
        $s_1 = sqrt(($p_1) * (1 - $p_1) / ($n_1));
        $s_2 = sqrt(($p_2) * (1 - $p_2) / ($n_2));

        # Formula for pooled error of the difference of the means: root(π*(1-π)*(1/na+1/nc)
        # π = (xa + xc) / (na + nc)
        $pi  = ($p_1 * $n_1 + $p_2 * $n_2) / ($n_1 + $n_2);
        $s_p = sqrt($pi * (1 - $pi) * (1 / $n_1 + 1 / $n_2));

        # Formula for unpooled error of the difference of the means: root(sa**2/na + sc**2/nc)
        $s_unp = sqrt($s_1 ** 2 + $s_2 ** 2);

        # Boolean variable decides whether we can pool our variances
        $pooled = $s_1 / $s_2 < 2 && $s_2 / $s_1 < 2;

        # Assign standard error either the pooled or unpooled variance
        $se = $pooled ? $s_p : $s_unp;

        # Calculate z-score
        $z_score = ($p_1 - $p_2) / ($se);;

        return $z_score;

    }

    static public function confidence_level($z_score) {
        if (is_string($z_score)) {
            return $z_score;
        }

        $z = abs(round($z_score, 3));

        if ($z >= 2.58) {
            return '99% confidence';
        } elseif ($z >= 1.96) {
            return '95% confidence';
        } elseif ($z >= 1.65) {
            return '90% confidence';
        } else {
            return 'Insufficient confidence';
        }
    }
}