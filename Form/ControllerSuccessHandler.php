<?php

namespace Chaplean\Bundle\FormHandlerBundle\Form;

use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Context\Context;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ControllerSuccessHandler.
 *
 * @package   Chaplean\Bundle\FormHandlerBundle\Form
 * @author    Matthias - Chaplean <matthias@chaplean.coop>
 * @copyright 2014 - 2018 Chaplean (http://www.chaplean.coop)
 */
class ControllerSuccessHandler implements SuccessHandlerInterface
{
    /**
     * @var EntityManagerInterface
     */
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
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
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
        $isCreated = false;

        try {
            $isCreated = $this->em->getUnitOfWork()->isScheduledForInsert($entity);
        } catch (\Throwable $e) {
            // The entity is not an object, so it cannot be persisted
        }

        $this->em->flush();

        $view = View::create($entity);

        if (!empty($this->groups)) {
            $context = new Context();
            $context->setGroups($this->groups);
            $view->setContext($context);
        }

        if ($isCreated) {
            $view->setStatusCode(Response::HTTP_CREATED);
        }

        return $view;
    }
}
