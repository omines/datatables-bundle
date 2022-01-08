#!/usr/bin/env php
<?php

/*
* Symfony DataTables Bundle
* (c) Omines Internetbureau B.V. - https://omines.nl/
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

declare(strict_types=1);

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Tests\Fixtures\AppKernel;

require __DIR__ . '/../vendor/autoload.php';

$kernel = new AppKernel('test', false);
$output = new ConsoleOutput();
$application = new Application($kernel);

try {
    $application->get('cache:warmup')->run(new StringInput('-vvv'), $output);
} catch (Exception $e) {
    // This is presumably okay
}