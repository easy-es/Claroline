<?php

namespace Claroline\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="claro_license")
 */
class License
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column()
     */
    protected $name;

    /**
     * @ORM\Column(nullable=true)
     */
    protected $acronym;

    /**
     * @ORM\OneToMany(
     *     targetEntity="Claroline\CoreBundle\Entity\Resource\ResourceNode",
     *     mappedBy="license",
     *     cascade={"persist"}
     * )
     */
    protected $abstractResources;

    public function getId()
    {
        return $this->id;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setAcronym($acronym)
    {
        $this->acronym = $acronym;
    }

    public function getAcronym()
    {
        return $this->acronym;
    }

    public function getResources()
    {
        return $this->abstractResources;
    }
}
