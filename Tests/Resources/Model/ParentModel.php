<?php

namespace Chaplean\Bundle\FormHandlerBundle\Tests\Resources\Model;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class ParentModel
 *
 * @package   Test\Chaplean\Bundle\FormHandlerBundle\Resources\Model
 * @author    Matthias - Chaplean <matthias@chaplean.coop>
 * @copyright 2014 - 2017 Chaplean (https://www.chaplean.coop)
 * @since     1.0.0
 */
class ParentModel
{
    /**
     * @var ChildModel
     *
     * @Assert\NotNull(message="not null")
     * @Assert\Valid
     */
    protected $child;

    /**
     * @return ChildModel
     */
    public function getChild()
    {
        return $this->child;
    }

    /**
     * @param ChildModel $child
     *
     * @return ParentModel
     */
    public function setChild(ChildModel $child) : self
    {
        $this->child = $child;

        return $this;
    }
}
