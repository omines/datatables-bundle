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
use Tests\Fixtures\AppBundle\Entity\Company;
use Tests\Fixtures\AppBundle\Entity\Employee;
use Tests\Fixtures\AppKernel;

require __DIR__ . '/../vendor/autoload.php';

// Clean up from previous runs
@exec('rm -rf ' . escapeshellarg(__DIR__ . '/../tmp'));
@exec('mkdir ' . escapeshellarg(__DIR__ . '/../tmp'));

// Create basic DB schema
$kernel = new AppKernel('test', false);
$output = new ConsoleOutput();
$application = new Application($kernel);
$application->get('doctrine:schema:drop')->run(new StringInput('--force --quiet'), $output);
$application->get('doctrine:schema:create')->run(new StringInput('--quiet'), $output);

// Fill some basic fixtures
$em = $kernel->getContainer()->get('doctrine')->getManager();
$companies = [];
for ($i = 0; 5 !== $i; ++$i) {
    $companies[] = $company = new Company('Company ' . $i);
    $em->persist($company);
}
$date = new \DateTime('2017-05-03 12:34:56');
for ($i = 0; 125 !== $i; ++$i) {
    $date->sub(new \DateInterval('P3DT5H27M'));
    $em->persist(new Employee('FirstName' . $i, 'LastName' . $i, $i % 2 ? clone $date : null, $companies[$i % count($companies)]));
}
$em->flush();
