<?php

namespace Chaplean\Bundle\FormHandlerBundle\Form;

use Doctrine\ORM\EntityManager;
use FOS\RestBundle\Context\Context;
use FOS\RestBundle\View\View;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * Class ControllerSuccessHandler.
 *
 * @package   Chaplean\Bundle\FormHandlerBundle\Form
 * @author    Matthias - Chaplean <matthias@chaplean.coop>
 * @copyright 2014 - 2018 Chaplean (http://www.chaplean.coop)
 */
class ControllerSuccessHandler implements SuccessHandlerInterface
{
    /** @var EntityManager */
    protected $em;

    /**
     * @var SuccessHandlerInterface
     */
    protected $handler;

    /**
     * @var array
     */
    protected $groups;

    /**
     * ControllerSuccessHandler constructor.
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        $this->em = $registry->getManager();
    }

    /**
     * @param SuccessHandlerInterface $handler
     *
     * @return void
     */
    public function setHandler(SuccessHandlerInterface $handler)
    {
        $this->handler = $handler;
    }

    /**
     * @param array $groups
     *
     * @return void
     */
    public function setGroups(array $groups)
    {
        $this->groups = $groups;
    }

    /**
     * Logic to run when the form is valid
     *
     * If you need database save, this function is responsible for persist() calls
     * but not for flush(). Its called right after this function.
     *
     * @param mixed $data
     * @param array $parameters
     *
     * @return View
     */
    public function onSuccess($data, array $parameters): View
    {
        $entity = $this->handler->onSuccess($data, $parameters);
        $this->em->flush();

        $view = View::create($entity);

        if (!empty($this->groups)) {
            $context = new Context();
            $context->setGroups($this->groups);
            $view->setContext($context);
        }

        return $view;
    }
}
