<?php

namespace Chaplean\Bundle\FormHandlerBundle\Tests\Resources\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class DummyEntity
 *
 * @package   Test\Chaplean\Bundle\FormHandlerBundle\Resources\Entity
 * @author    Matthias - Chaplean <matthias@chaplean.com>
 * @copyright 2014 - 2017 Chaplean (http://www.chaplean.com)
 * @since     1.0.0
 *
 * @ORM\Entity
 */
class DummyEntity
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer", name="id")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     *
     * @JMS\Groups({"dummy_entity_id"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=false, name="name")
     * @Assert\NotBlank(message="not blank")
     *
     * @JMS\Groups({"dummy_entity_name"})
     */
    private $name;

    /**
     * @return integer
     */
    public function getId() : int
    {
        return $this->id;
    }

    /**
     * @param integer $id
     *
     * @return DummyEntity
     */
    public function setId(int $id) : self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return DummyEntity
     */
    public function setName(string $name) : self
    {
        $this->name = $name;

        return $this;
    }
}
