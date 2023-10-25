<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\Fixtures\AppBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\ManyToOne;

#[Entity]
class Employee extends Person
{
    #[Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $employedSince;

    #[ManyToOne(targetEntity: Company::class, inversedBy: 'employees')]
    private Company $company;

    public function __construct(string $firstName, string $lastName, ?\DateTime $employedSince, Company $company)
    {
        parent::__construct($firstName, $lastName);

        $this->company = $company;
        $this->employedSince = $employedSince;
    }

    public function getEmployedSince(): \DateTime
    {
        return $this->employedSince;
    }

    public function getCompany(): Company
    {
        return $this->company;
    }
}
