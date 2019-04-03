<?php

namespace Chaplean\Bundle\FormHandlerBundle\Form;

use Doctrine\ORM\EntityManagerInterface;

/**
 * Class PersisterSuccessHandler.
 *
 * @package   Chaplean\Bundle\FormHandlerBundle\Form
 * @author    Matthias - Chaplean <matthias@chaplean.coop>
 * @copyright 2014 - 2017 Chaplean (http://www.chaplean.coop)
 * @since     1.0.0
 */
class PersisterSuccessHandler implements SuccessHandlerInterface
{
    /** @var EntityManagerInterface  */
    protected $em;

    /**
     * PersisterSuccessHandler constructor.
     *
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    /**
     * Just persist the given data
     *
     * @param mixed $data
     * @param array $parameters
     *
     * @return mixed Data used to build the response to the user
     */
    public function onSuccess($data, array $parameters)
    {
        $this->em->persist($data);

        return $data;
    }
}
