<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Split\Impl\Algorithms\WeightedSample;

class WeightedSampleTest extends TestCase {
    /* @var WeightedSample */
    protected $algo;

    public function setUp() {
        parent::setUp();
        $this->algo = new WeightedSample();
    }

    public function tearDown() {
        parent::tearDown();
    }


    public function test_should_always_return_a_heavily_weighted_option() {
        //TODO: replace with real experiments
        $experiment = (object)[
            'alternatives' => collect([
                                          (object)["weight" => 100, 'name' => 'blue'],
                                          (object)["weight" => 0, 'name' => 'red'],
                                      ]),
        ];
        $this->assertEquals('blue', $this->algo->choose_alternative($experiment)->name);
    }


    public function test_rand_between_0_and_1() {
        $rand = $this->algo->random_01();
        $this->assertGreaterThanOrEqual(0, $rand);
        $this->assertLessThanOrEqual(1, $rand);
    }

    public function test_should_return_an_alternative() {
        //TODO do it after the Alternative is defined
    }

    public function test_should_return_one_of_the_results() {
        //TODO: replace with real experiments
        $experiment = (object)[
            'alternatives' => collect([
                (object)["weight" => 1, 'name' => 'blue'],
                (object)["weight" => 1, 'name' => 'red'],
            ]),
        ];
        $this->assertContains($this->algo->choose_alternative($experiment)->name, ['red', 'blue']);
    }


}
