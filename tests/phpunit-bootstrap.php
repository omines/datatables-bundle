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
use Tests\Fixtures\AppBundle\Entity\Person;
use Tests\Fixtures\AppKernel;

require __DIR__ . '/../vendor/autoload.php';

// Polyfill PHPUnit 6.0 both ways
if (!class_exists('\PHPUnit\Framework\TestCase', true)) {
    class_alias('\PHPUnit_Framework_TestCase', '\PHPUnit\Framework\TestCase');
} elseif (!class_exists('\PHPUnit_Framework_TestCase', true)) {
    class_alias('\PHPUnit\Framework\TestCase', '\PHPUnit_Framework_TestCase');
}

@exec('rm -r /tmp/datatables-bundle');

$kernel = new AppKernel('test', false);
$output = new ConsoleOutput();
$application = new Application($kernel);
$application->get('doctrine:schema:update')->run(new StringInput('--force'), $output);

$em = $kernel->getContainer()->get('doctrine')->getManagerForClass(Person::class);
for ($i = 0; 125 !== $i; ++$i) {
    $em->persist(new Person('FirstName' . $i, 'LastName' . $i));
}
$em->flush();
