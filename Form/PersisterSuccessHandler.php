<?php

namespace Chaplean\Bundle\FormHandlerBundle\Form;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;

/**
 * Class PersisterSuccessHandler.
 *
 * @package   Chaplean\Bundle\FormHandlerBundle\Form
 * @author    Matthias - Chaplean <matthias@chaplean.com>
 * @copyright 2014 - 2017 Chaplean (http://www.chaplean.com)
 * @since     1.0.0
 */
class PersisterSuccessHandler implements SuccessHandlerInterface
{
    /** @var EntityManager  */
    protected $em;

    /**
     * PerisisterSuccessHandler constructor.
     *
     * @param Registry $registry
     */
    public function __construct(Registry $registry)
    {
        $this->em = $registry->getManager();
    }

    /**
     * Just persist the given data
     *
     * @param mixed $data
     *
     * @return mixed Data used to build the response to the user
     */
    public function onSuccess($data)
    {
        $this->em->persist($data);

        return $data;
    }
}
