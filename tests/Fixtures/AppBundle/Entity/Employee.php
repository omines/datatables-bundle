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

use Doctrine\ORM\Mapping as ORM;

/**
 * Employee.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 *
 * @ORM\Entity
 */
class Employee extends Person
{
    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $employedSince;

    /**
     * @var Company
     *
     * @ORM\ManyToOne(targetEntity="Company", inversedBy="employees")
     */
    private $company;

    /**
     * Person constructor.
     */
    public function __construct(string $firstName, string $lastName, \DateTime $employedSince = null, Company $company)
    {
        parent::__construct($firstName, $lastName);

        $this->company = $company;
        $this->employedSince = $employedSince;
    }

    /**
     * @return mixed
     */
    public function getEmployedSince()
    {
        return $this->employedSince;
    }

    public function getCompany(): Company
    {
        return $this->company;
    }
}
