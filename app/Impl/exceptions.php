<?php
/**
 * Created by PhpStorm.
 * User: 风之凌殇
 * Date: 8/7/2016
 * Time: 5:28 PM
 */

namespace Split\Impl;

/*TODO: make this file into a director of Exception, and make these three classes into there file: eg: ExperimentNotFound.php*/
/*NOTICE: so this can be autoloaded with psr-4 */
class InvalidPersistenceAdapterError extends \Exception{};
class ExperimentNotFound extends \Exception{};
class InvalidExperimentsFormatError extends \Exception{};