<?php

namespace Split\Http\Controllers;

use Illuminate\Http\Request;

use Split\Http\Requests;
use Split\Impl\Helper;

/**
 * Class ApiController
 *
 * @package Split\Http\Controllers
 */
class ApiController extends Controller {
    /**
     * Api for user to start an ab test.
     * 
     * Currently the data is transferred by params, or we can use json instead if necessary.
     * 
     * @param Request $request
     *
     * @throws \Exception
     */
    public function ab_test(Request $request) {
        $metric_descriptor = $request['metric_descriptor'];
        $control           = $request['control'];
        $alternatives      = $request['alternatives'];

        /*FIXME: when in production, add try catch to return error code if error happend*/
        Helper::ab_test($metric_descriptor, $control, $alternatives);
    }

    /**
     * Api for user to complete an ab test that (s)he has took in.
     * 
     * @param Request $request
     *
     * @throws \Exception
     */
    public function ab_finished(Request $request) {
        $metric_descriptor = $request['metric_descriptor'];
        $options           = $request['options'];
        
        /*FIXME: when in production, add try catch to return error code if error happend*/
        if ($options) {
            Helper::ab_finished($metric_descriptor, $options);
        } else {
            Helper::ab_finished($metric_descriptor);
        }
    }
}
