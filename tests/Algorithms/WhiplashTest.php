<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

use Split\Impl\Algorithms\Whiplash;

require realpath(dirname(__FILE__) . '/../UtilForTest/helper.php');

/*TODO write these test which has dependency not implemented LATER*/

class WhiplashTest extends TestCase {
    /* @var Whiplash */
    protected $algo;

    public function setUp() {
        parent::setUp();
        $this->algo = new Whiplash();
    }

    public function test_should_return_an_alternative() {
        //TODO do it after the Alternative is defined 
    }

    public function test_should_return_one_of_the_results() {
        /* TODO replace with real exp*/
    }

    public function test_should_guess_floats() {
        $this->assertInternalType('float',invokeMethod($this->algo, 'arm_guess', [0, 0]));
        $this->assertInternalType('float',invokeMethod($this->algo, 'arm_guess', [1, 0]));
        $this->assertInternalType('float',invokeMethod($this->algo, 'arm_guess', [2, 1]));
        $this->assertInternalType('float',invokeMethod($this->algo, 'arm_guess', [1000, 5]));
        $this->assertInternalType('float',invokeMethod($this->algo, 'arm_guess', [10, -2]));
    }


}
