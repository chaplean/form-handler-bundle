<?php

namespace Chaplean\Bundle\FormHandlerBundle\Tests\Resources\Model;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class ChildModel
 *
 * @package   Test\Chaplean\Bundle\FormHandlerBundle\Resources\Model
 * @author    Matthias - Chaplean <matthias@chaplean.com>
 * @copyright 2014 - 2017 Chaplean (http://www.chaplean.com)
 * @since     1.0.0
 */
class ChildModel
{
    /**
     * @var string
     *
     * @Assert\NotBlank(message="not blank")
     */
    protected $name;

    /**
     * @return string
     */
    public function getName() : string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return ChildModel
     */
    public function setName(string $name) : self
    {
        $this->name = $name;

        return $this;
    }
}
