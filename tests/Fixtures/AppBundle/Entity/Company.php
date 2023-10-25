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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\OneToMany;

#[Entity]
class Company
{
    #[Id, GeneratedValue(strategy: 'IDENTITY'), Column]
    private int $id;

    #[Column]
    private string $name;

    /** @var Collection<Employee> */
    #[OneToMany(mappedBy: 'company', targetEntity: Employee::class)]
    private Collection $employees;

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->employees = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return Collection<Employee>
     */
    public function getEmployees(): Collection
    {
        return $this->employees;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }
}
