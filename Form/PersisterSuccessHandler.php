<?php

namespace Chaplean\Bundle\FormHandlerBundle\Form;

use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Doctrine\RegistryInterface;

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
    /** @var EntityManager  */
    protected $em;

    /**
     * PersisterSuccessHandler constructor.
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        $this->em = $registry->getManager();
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
