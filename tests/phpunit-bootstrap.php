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
use Tests\Fixtures\AppBundle\Entity\Person;
use Tests\Fixtures\AppKernel;

require __DIR__ . '/../vendor/autoload.php';

// Clean up from previous runs
@exec('rm -rf /tmp/datatables-bundle');

// Create basic DB schema
$kernel = new AppKernel('test', false);
$output = new ConsoleOutput();
$application = new Application($kernel);
$application->get('doctrine:schema:update')->run(new StringInput('--force'), $output);

// Fill some basic fixtures
$em = $kernel->getContainer()->get('doctrine')->getManager();
$companies = [];
for ($i = 0; 5 !== $i; ++$i) {
    $companies[] = $company = new Company('Company ' . $i);
    $em->persist($company);
}
for ($i = 0; 125 !== $i; ++$i) {
    $em->persist(new Person('FirstName' . $i, 'LastName' . $i, $companies[$i % count($companies)]));
}
$em->flush();
